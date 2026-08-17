<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

// Modèles
use App\Models\Client;
use App\Models\Account;
use App\Models\TontineAccount;
use App\Models\TontineCycle;
use App\Models\Transaction;

class AgentSyncController extends Controller
{
    /**
     * Variable temporaire pour mapper les IDs mobiles (UUID) aux IDs serveurs
     */
    private $idMapping = [
        'clients'      => [],
        'accounts'     => [],
        'transactions' => []
    ];

    // =========================================================================
    // PULL — Serveur → Mobile (Récupération des données)
    // =========================================================================

    public function pull(Request $request)
    {
        try {
            $lastPulledAt = $request->get('last_pulled_at');
            $timestamp    = now()->timestamp * 1000;
            $agentId      = auth()->id();

            if (!$agentId) {
                return response()->json(['success' => false, 'message' => 'Agent non authentifié'], 401);
            }

            // Conversion timestamp mobile (ms) en objet Carbon
            $lastSync = $lastPulledAt ? Carbon::createFromTimestamp($lastPulledAt / 1000) : null;

            $changes = [
                'clients'      => ['created' => [], 'updated' => [], 'deleted' => []],
                'accounts'     => ['created' => [], 'updated' => [], 'deleted' => []],
                'transactions' => ['created' => [], 'updated' => [], 'deleted' => []],
            ];

            // 1. Synchronisation des Clients
            $clientsQuery = Client::where('registered_by', $agentId);
            if ($lastSync) $clientsQuery->where('updated_at', '>', $lastSync);

            foreach ($clientsQuery->get() as $client) {
                $status = ($lastSync && $client->created_at <= $lastSync) ? 'updated' : 'created';
                $changes['clients'][$status][] = [
                    'id'                  => (string) $client->id,
                    'server_id'           => (string) $client->id,
                    'first_name'          => $client->first_name,
                    'last_name'           => $client->last_name,
                    'phone'               => $client->phone,
                    'email'               => $client->email ?? '',
                    'address'             => $client->address ?? '',
                    'registration_status' => $client->registration_status,
                    'kyc_status'          => $client->kyc_status,
                    'created_at'          => $client->created_at->timestamp * 1000,
                    'updated_at'          => $client->updated_at->timestamp * 1000,
                ];
            }

            // 2. Synchronisation des Comptes (Solde serveur fait foi)
            $clientIds = Client::where('registered_by', $agentId)->pluck('id');
            $accountsQuery = Account::with('tontineAccount')->whereIn('client_id', $clientIds);
            if ($lastSync) $accountsQuery->where('updated_at', '>', $lastSync);

            foreach ($accountsQuery->get() as $account) {
                $status = ($lastSync && $account->created_at <= $lastSync) ? 'updated' : 'created';
                $changes['accounts'][$status][] = [
                    'id'                   => (string) $account->id,
                    'server_id'            => (string) $account->id,
                    'client_id'            => (string) $account->client_id,
                    'account_number'       => $account->account_number,
                    'account_type'         => $account->account_type,
                    'target_amount'        => (float) (optional($account->tontineAccount)->tontine_amount ?? 0),
                    'cycle_duration_months'=> optional($account->tontineAccount)->cycle_duration_months ?? 12,
                    'payment_frequency'    => optional($account->tontineAccount)->payment_frequency ?? 'daily',
                    'balance'              => (float) $account->balance, 
                    'status'               => $account->status,
                    'created_at'           => $account->created_at->timestamp * 1000,
                    'updated_at'           => $account->updated_at->timestamp * 1000,
                ];
            }

            // 3. Synchronisation des Transactions
            $accountIds = Account::whereIn('client_id', $clientIds)->pluck('id');
            $txQuery    = Transaction::whereIn('account_id', $accountIds);
            
            if ($lastSync) {
                $txQuery->where('updated_at', '>', $lastSync);
            } else {
                $txQuery->latest()->limit(200); 
            }

            foreach ($txQuery->get() as $tx) {
                $changes['transactions']['created'][] = [
                    'id'               => (string) $tx->id,
                    'server_id'        => (string) $tx->id,
                    'account_id'       => (string) $tx->account_id,
                    'amount'           => (float) $tx->amount,
                    'transaction_type' => $tx->transaction_type,
                    'payment_method'   => $tx->payment_method ?? 'cash',
                    'description'      => $tx->description ?? ($tx->transaction_type == 'deposit' ? 'Dépôt' : 'Retrait'),
                    'status'           => $tx->status,
                    'collected_at'     => ($tx->transaction_date ? Carbon::parse($tx->transaction_date) : $tx->created_at)->timestamp * 1000,
                ];
            }

            return response()->json([
                'success' => true,
                'data'    => ['changes' => $changes, 'timestamp' => $timestamp],
            ]);

        } catch (\Exception $e) {
            Log::error('PULL Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // PUSH — Mobile → Serveur (Envoi des données locales)
    // =========================================================================

    public function push(Request $request)
    {
        $changes = $request->get('changes', []);
        $agent   = auth()->user();

        if (!$agent) {
            return response()->json(['success' => false, 'message' => 'Agent non authentifié'], 401);
        }

        try {
            DB::beginTransaction();

            // 1. Traitement des Clients créés localement
            if (!empty($changes['clients']['created'])) {
                $this->processPushClients($changes['clients']['created'], $agent);
            }

            // 2. Traitement des Comptes créés localement
            if (!empty($changes['accounts']['created'])) {
                $this->processPushAccounts($changes['accounts']['created'], $agent);
            }

            // 3. Traitement des Transactions (Dépôts et Retraits)
            if (!empty($changes['transactions']['created'])) {
                $this->processPushTransactions($changes['transactions']['created'], $agent);
            }

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => 'Synchronisation réussie',
                'results' => $this->idMapping
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PUSH Error: ' . $e->getMessage() . ' at line ' . $e->getLine());
            return response()->json(['success' => false, 'message' => 'Erreur Serveur: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // LOGIQUE DE TRAITEMENT (HELPERS)
    // =========================================================================

    private function processPushClients(array $created, $agent): void
    {
        foreach ($created as $item) {
            $mobileUuid = $item['id'];
            $client = Client::where('phone', $item['phone'])->first();

            if (!$client) {
                $client = Client::create([
                    'client_number' => $this->generateClientNumber(),
                    'first_name'    => $item['first_name'] ?? 'Inconnu',
                    'last_name'     => $item['last_name']  ?? 'Inconnu',
                    'phone'         => $item['phone'],
                    'email'         => $item['email']      ?? null,
                    'address'       => $item['address']    ?? null,
                    'password'      => Hash::make('1234'), 
                    'registered_by' => $agent->id,
                    'agency_id'     => $agent->agency_id   ?? 1,
                    'registration_status' => 'completed',
                    'kyc_status'          => 'pending',
                ]);
            }

            $this->idMapping['clients'][$mobileUuid] = $client->id;
        }
    }

    private function processPushAccounts(array $accounts, $agent): void
    {
        foreach ($accounts as $item) {
            $mobileAccountId = $item['id'];
            $mobileClientId  = $item['client_id'];

            $realClientId = $this->idMapping['clients'][$mobileClientId] 
                ?? (is_numeric($mobileClientId) ? (int) $mobileClientId : null);

            if (!$realClientId) continue;

            $existing = Account::where('account_number', $item['account_number'] ?? 'IGNORE')->first();
            if ($existing) {
                $this->idMapping['accounts'][$mobileAccountId] = $existing->id;
                continue;
            }

            $account = Account::create([
                'client_id'      => $realClientId,
                'account_number' => $item['account_number'] ?? 'ACC-' . strtoupper(Str::random(8)),
                'account_type'   => $item['account_type']   ?? 'tontine',
                'balance'        => 0, 
                'status'         => 'active',
                'created_by'     => $agent->id,
                'activated_at'   => now(),
            ]);

            $this->idMapping['accounts'][$mobileAccountId] = $account->id;

            if ($account->account_type === 'tontine') {
                $this->createTontineAccount($account, $item);
            }
        }
    }

    private function processPushTransactions(array $transactions, $agent): void
    {
        foreach ($transactions as $item) {
            $mobileTxId      = $item['id'];
            $mobileAccountId = $item['account_id'];
            $realAccountId   = $this->idMapping['accounts'][$mobileAccountId] 
                ?? (is_numeric($mobileAccountId) ? (int) $mobileAccountId : null);
    
            if (!$realAccountId) continue;
    
            $account = Account::with('tontineAccount.activeCycle')->find($realAccountId);
            if (!$account) continue;
    
            $amount = (float) $item['amount'];
            $type = $item['transaction_type'] ?? 'deposit';

            // Anti-doublon allégé (10 secondes)
            $exists = Transaction::where('account_id', $realAccountId)
                ->where('amount', $amount)
                ->where('transaction_type', $type)
                ->where('created_at', '>=', now()->subSeconds(10))
                ->exists();
    
            if ($exists) continue;
    
            if ($type === 'deposit' && $account->account_type === 'tontine' && $account->tontineAccount) {
                $tontine = $account->tontineAccount;
                $cycle   = $tontine->activeCycle ?? $this->createTontineCycle($tontine);
                if ($cycle) {
                    $this->distributeTontineAmount($tontine, $cycle, $amount);
                }
                $tontine->increment('total_paid', $amount);
            }
    
            $tx = Transaction::create([
                'transaction_reference' => 'SYNC-' . strtoupper(Str::random(6)) . '-' . now()->format('YmdHis'),
                'account_id'            => $realAccountId,
                'amount'                => $amount,
                'transaction_type'      => $type,
                'payment_method'        => $item['payment_method'] ?? 'cash',
                'description'           => $item['description']    ?? ($type == 'deposit' ? 'Dépôt mobile' : 'Retrait mobile'),
                'status'                => 'completed',
                'processed_by'          => $agent->id,
                'agency_id'             => $agent->agency_id ?? 1,
                'transaction_date'      => now(),
                'balance_before'        => $account->balance,
                'balance_after'         => ($type === 'deposit') ? ($account->balance + $amount) : ($account->balance - $amount),
            ]);

            $this->idMapping['transactions'][$mobileTxId] = $tx->id;
    
            if ($type === 'deposit') {
                $account->increment('balance', $amount);
            } else {
                $account->decrement('balance', $amount);
            }
            
            $account->update(['last_transaction_at' => now()]);
        }
    }

    private function createTontineAccount(Account $account, array $item): ?TontineAccount
    {
        $tontineAmount       = (float) ($item['target_amount']         ?? 1000);
        $cycleDurationMonths = (int)   ($item['cycle_duration_months'] ?? 12);
        $paymentFrequency    = $item['payment_frequency']              ?? 'daily';

        $totalPeriods = match ($paymentFrequency) {
            'daily'   => $cycleDurationMonths * 31,
            'weekly'  => (int) round(($cycleDurationMonths * 52) / 12),
            'monthly' => $cycleDurationMonths,
            default   => $cycleDurationMonths,
        };

        $tontineAccount = TontineAccount::create([
            'account_id'               => $account->id,
            'tontine_amount'           => $tontineAmount,
            'cycle_duration_months'    => $cycleDurationMonths,
            'payment_frequency'        => $paymentFrequency,
            'expected_monthly_payment' => $tontineAmount,
            'total_expected'           => $tontineAmount * $totalPeriods,
            'total_paid'               => 0,
            'penalty_rate'             => 0.05,
            'total_penalties'          => 0,
            'cycle_start_date'         => now(),
            'cycle_end_date'           => now()->addMonths($cycleDurationMonths),
        ]);

        $this->createTontineCycle($tontineAccount);
        return $tontineAccount;
    }

    private function createTontineCycle(TontineAccount $tontine): ?TontineCycle
    {
        $cycleNumber = $tontine->cycles()->count() + 1;
        $startDate = $cycleNumber === 1 ? Carbon::parse($tontine->cycle_start_date) : now();

        $frequency = $tontine->payment_frequency;
        $amount    = (float) $tontine->tontine_amount;

        switch ($frequency) {
            case 'daily':
                $endDate = (clone $startDate)->addDays(31);
                $target  = $amount * 31;
                break;
            case 'weekly':
                $endDate = (clone $startDate)->addMonth();
                $target  = round($amount * (52 / 12), 2);
                break;
            default:
                $endDate = (clone $startDate)->addMonth();
                $target  = $amount;
        }

        return TontineCycle::create([
            'tontine_account_id' => $tontine->id,
            'cycle_number'       => $cycleNumber,
            'start_date'         => $startDate,
            'end_date'           => $endDate,
            'target_amount'      => $target,
            'collected_amount'   => 0,
            'status'             => 'active',
        ]);
    }

    private function distributeTontineAmount(TontineAccount $tontine, TontineCycle $startCycle, float $amount): void
    {
        $remaining = $amount;
        $current   = $startCycle;
        $safety    = 0;

        while ($remaining > 0 && $safety < 100) {
            $safety++;
            $gap = $current->target_amount - $current->collected_amount;

            if ($gap <= 0) {
                $current = $this->getOrCreateNextCycle($tontine, $current);
                continue;
            }

            $fill = min($remaining, $gap);
            $current->increment('collected_amount', $fill);
            $remaining -= $fill;

            if ($current->collected_amount >= $current->target_amount) {
                $current->update(['status' => 'completed', 'payout_date' => now()]);
                if ($remaining > 0) $current = $this->getOrCreateNextCycle($tontine, $current);
            }
        }
    }

    private function getOrCreateNextCycle($tontine, $current)
    {
        $next = TontineCycle::where('tontine_account_id', $tontine->id)
            ->where('cycle_number', $current->cycle_number + 1)
            ->first();
        return $next ?? $this->createTontineCycle($tontine);
    }

    private function generateClientNumber(): string
    {
        return 'CLT-' . strtoupper(Str::random(3)) . '-' . date('ym') . rand(1000, 9999);
    }
}
