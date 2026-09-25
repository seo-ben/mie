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
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\CashierSession;

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
                $rawGender = strtoupper((string)($item['gender'] ?? 'M'));
                $gender = in_array($rawGender, ['M', 'F', 'OTHER']) ? ($rawGender === 'OTHER' ? 'Other' : $rawGender) : 'M';
                $rawIdType = strtolower((string)($item['id_type'] ?? 'cni'));
                $idType = in_array($rawIdType, ['cni', 'passport', 'driving_license', 'other']) ? $rawIdType : 'cni';

                $client = Client::create([
                    'client_number'       => $this->generateClientNumber(),
                    'first_name'          => $item['first_name'] ?? 'Inconnu',
                    'last_name'           => $item['last_name']  ?? 'Inconnu',
                    'phone'               => $item['phone'],
                    'email'               => $item['email']      ?? null,
                    'address'             => $item['address']    ?? null,
                    'gender'              => $gender,
                    'id_type'             => $idType,
                    'id_number'           => $item['id_number']  ?? null,
                    'password'            => Hash::make('1234'), 
                    'registered_by'       => $agent->id,
                    'agency_id'           => $agent->agency_id   ?? 1,
                    'registration_channel'=> 'agent_assisted',
                    'registration_type'   => 'agency',
                    'registration_status' => 'approved',
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

    private function getOrCreateNextCycle(TontineAccount $tontine, TontineCycle $current): TontineCycle
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

    private function generateTransactionReference(): string
    {
        return 'TX-' . date('Ymd') . '-' . strtoupper(Str::random(8));
    }

    /**
     * Synchronisation en lot des opérations de caisse hors-ligne
     * POST /api/v1/agent/transactions/sync
     */
    public function syncTransactions(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Utilisateur non authentifié.'], 401);
        }

        $rawTransactions = $request->input('transactions', []);
        if (empty($rawTransactions)) {
            return response()->json([
                'success' => true,
                'message' => 'Aucune transaction à synchroniser.',
                'synced_count' => 0,
                'synced_ids' => [],
                'errors' => [],
            ]);
        }

        // Trouver ou associer la session de caisse ouverte
        $activeSession = CashierSession::where('user_id', $user->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        // Si aucune session n'est ouverte sur le serveur, chercher la dernière session
        if (!$activeSession) {
            $activeSession = CashierSession::where('user_id', $user->id)
                ->latest('opened_at')
                ->first();
        }

        // Si toujours aucune session et que l'utilisateur est caissier/agent, en ouvrir une automatiquement
        if (!$activeSession) {
            try {
                $activeSession = CashierSession::create([
                    'session_code'      => 'SESS-' . date('Ymd') . '-' . strtoupper(Str::random(4)),
                    'user_id'           => $user->id,
                    'agency_id'         => $user->agency_id ?? 1,
                    'opening_balance'   => 0,
                    'expected_balance'  => 0,
                    'actual_balance'    => 0,
                    'total_deposits'    => 0,
                    'total_withdrawals' => 0,
                    'status'            => 'open',
                    'opened_at'         => now(),
                    'notes'             => 'Session automatique synchronisation hors-ligne',
                ]);
            } catch (\Exception $se) {
                Log::warning('Impossible de créer une session automatique pour sync: ' . $se->getMessage());
            }
        }

        // Tri par ordre de dépendance : 1) Clients, 2) Comptes, 3) Prêts, 4) Dépôts / Retraits / Remboursements
        $sortedTransactions = $rawTransactions;
        usort($sortedTransactions, function ($a, $b) {
            $getWeight = function ($item) {
                $act = $item['action'] ?? $item['transaction_type'] ?? '';
                $offId = (string)($item['offline_id'] ?? $item['id'] ?? '');
                if ($act === 'create_client' || str_starts_with($offId, 'CLIENT-')) return 1;
                if ($act === 'create_account' || str_starts_with($offId, 'ACC-')) return 2;
                if ($act === 'apply_loan' || str_starts_with($offId, 'OFF-LOAN-')) return 3;
                return 4;
            };
            return $getWeight($a) <=> $getWeight($b);
        });

        $syncedIds = [];
        $errors = [];

        foreach ($sortedTransactions as $item) {
            $offlineId = $item['offline_id'] ?? $item['id'] ?? null;
            if (!$offlineId) {
                continue;
            }

            $offlineIdStr = (string) $offlineId;

            // 1. Clé d'idempotence : vérification anti-doublon absolue
            $alreadyExists = Transaction::where('payment_reference', $offlineIdStr)
                ->orWhere('transaction_reference', 'like', "%{$offlineIdStr}%")
                ->first();

            if ($alreadyExists) {
                $syncedIds[] = $offlineIdStr;
                continue;
            }

            $action = $item['action'] ?? $item['transaction_type'] ?? 'deposit';
            $offlineCreatedAt = !empty($item['offline_created_at']) 
                ? Carbon::parse($item['offline_created_at']) 
                : now();

            // -------------------------------------------------------------
            // CAS 1 : CRÉATION DE CLIENT HORS-LIGNE
            // -------------------------------------------------------------
            if ($action === 'create_client' || str_starts_with($offlineIdStr, 'CLIENT-')) {
                DB::beginTransaction();
                try {
                    $phone = $item['phone'] ?? null;
                    $client = $phone ? Client::where('phone', $phone)->first() : null;

                    if (!$client) {
                        $rawGender = strtoupper((string)($item['gender'] ?? 'M'));
                        $gender = in_array($rawGender, ['M', 'F', 'OTHER']) ? ($rawGender === 'OTHER' ? 'Other' : $rawGender) : 'M';
                        $rawIdType = strtolower((string)($item['id_type'] ?? 'cni'));
                        $idType = in_array($rawIdType, ['cni', 'passport', 'driving_license', 'other']) ? $rawIdType : 'cni';

                        $client = Client::create([
                            'client_number'       => $this->generateClientNumber(),
                            'first_name'          => $item['first_name'] ?? 'Client',
                            'last_name'           => $item['last_name'] ?? 'Hors-Ligne',
                            'phone'               => $phone ?? ('+2289' . rand(1000000, 9999999)),
                            'email'               => $item['email'] ?? null,
                            'address'             => $item['address'] ?? null,
                            'gender'              => $gender,
                            'id_type'             => $idType,
                            'id_number'           => $item['id_number'] ?? null,
                            'password'            => Hash::make('1234'),
                            'registered_by'       => $user->id,
                            'agency_id'           => $user->agency_id ?? 1,
                            'registration_channel'=> 'agent_assisted',
                            'registration_type'   => 'agency',
                            'registration_status' => 'approved',
                            'kyc_status'          => 'pending',
                        ]);
                    }

                    // Création automatique compte épargne et compte tontine
                    $savAcc = Account::firstOrCreate([
                        'client_id'    => $client->id,
                        'account_type' => 'savings',
                    ], [
                        'account_number' => 'SAV-' . strtoupper(Str::random(8)),
                        'balance'        => 0,
                        'status'         => 'active',
                        'created_by'     => $user->id,
                        'activated_at'   => now(),
                    ]);

                    $tonAcc = Account::where('client_id', $client->id)->where('account_type', 'tontine')->first();
                    if (!$tonAcc) {
                        $tonAcc = Account::create([
                            'client_id'      => $client->id,
                            'account_number' => 'TON-' . strtoupper(Str::random(8)),
                            'account_type'   => 'tontine',
                            'balance'        => 0,
                            'status'         => 'active',
                            'created_by'     => $user->id,
                            'activated_at'   => now(),
                        ]);
                        $this->createTontineAccount($tonAcc, ['target_amount' => $item['initial_tontine_amount'] ?? 1000]);
                    }

                    // Mapping étendu de tous les identifiants possibles
                    $this->idMapping['clients'][$offlineIdStr] = $client->id;
                    $this->idMapping['accounts'][$offlineIdStr] = $savAcc->id;
                    if (!empty($item['id'])) {
                        $this->idMapping['clients'][(string)$item['id']] = $client->id;
                    }
                    if (!empty($item['temp_client_id'])) {
                        $this->idMapping['clients'][(string)$item['temp_client_id']] = $client->id;
                    }
                    preg_match_all('!\d+!', $offlineIdStr, $matches);
                    if (!empty($matches[0])) {
                        foreach ($matches[0] as $numStr) {
                            $this->idMapping['clients'][$numStr] = $client->id;
                            $this->idMapping['clients']['-' . $numStr] = $client->id;
                        }
                    }

                    DB::commit();
                    $syncedIds[] = $offlineIdStr;
                } catch (\Exception $ce) {
                    DB::rollBack();
                    $errors[] = ['id' => $offlineIdStr, 'error' => 'Client: ' . $ce->getMessage()];
                }
                continue;
            }

            // -------------------------------------------------------------
            // CAS 2 : CRÉATION DE COMPTE HORS-LIGNE
            // -------------------------------------------------------------
            if ($action === 'create_account' || str_starts_with($offlineIdStr, 'ACC-')) {
                DB::beginTransaction();
                try {
                    $rawClientId = $item['client_id'] ?? null;
                    $clientId = $this->idMapping['clients'][$rawClientId] 
                        ?? $this->idMapping['clients'][(string)$rawClientId]
                        ?? (is_numeric($rawClientId) && $rawClientId > 0 ? (int)$rawClientId : null);

                    if (!$clientId && !empty($this->idMapping['clients'])) {
                        $clientId = end($this->idMapping['clients']);
                    }

                    if ($clientId) {
                        $accNum = $item['account_number'] ?? ('ACC-' . strtoupper(Str::random(8)));
                        $account = Account::where('account_number', $accNum)->first();

                        if (!$account) {
                            $account = Account::create([
                                'client_id'      => $clientId,
                                'account_number' => $accNum,
                                'account_type'   => $item['account_type'] ?? 'tontine',
                                'balance'        => 0,
                                'status'         => 'active',
                                'created_by'     => $user->id,
                                'activated_at'   => now(),
                            ]);

                            if ($account->account_type === 'tontine') {
                                $this->createTontineAccount($account, $item);
                            }
                        }

                        $tempAccId = $item['temp_account_id'] ?? $item['account_id'] ?? $offlineIdStr;
                        $this->idMapping['accounts'][(string)$tempAccId] = $account->id;
                        $this->idMapping['accounts'][$offlineIdStr] = $account->id;
                        if (!empty($item['id'])) {
                            $this->idMapping['accounts'][(string)$item['id']] = $account->id;
                        }

                        DB::commit();
                        $syncedIds[] = $offlineIdStr;
                    } else {
                        DB::rollBack();
                        $errors[] = ['id' => $offlineIdStr, 'error' => 'Client introuvable pour création compte'];
                    }
                } catch (\Exception $ae) {
                    DB::rollBack();
                    $errors[] = ['id' => $offlineIdStr, 'error' => 'Compte: ' . $ae->getMessage()];
                }
                continue;
            }

            // -------------------------------------------------------------
            // CAS 3 : DEMANDE DE PRÊT HORS-LIGNE
            // -------------------------------------------------------------
            if ($action === 'apply_loan' || str_starts_with($offlineIdStr, 'OFF-LOAN-')) {
                DB::beginTransaction();
                try {
                    $rawClientId = $item['client_id'] ?? null;
                    $clientId = $this->idMapping['clients'][$rawClientId] 
                        ?? $this->idMapping['clients'][(string)$rawClientId]
                        ?? (is_numeric($rawClientId) && $rawClientId > 0 ? (int)$rawClientId : null);

                    if (!$clientId && !empty($this->idMapping['clients'])) {
                        $clientId = end($this->idMapping['clients']);
                    }

                    if ($clientId) {
                        $reqAmount = (float)($item['requested_amount'] ?? 0);
                        $duration = (int)($item['duration_months'] ?? 6);
                        $defaultRate = (float)(DB::table('system_parameters')->where('parameter_key', 'loan_interest_rate_default')->value('parameter_value') ?? 17.0);
                        $rate = (float)($item['interest_rate'] ?? $defaultRate);
                        $totalDue = $reqAmount + ($reqAmount * ($rate / 100));

                        Loan::create([
                            'loan_number'            => 'PRT-' . strtoupper(Str::random(6)) . '-' . date('ymd'),
                            'client_id'              => $clientId,
                            'requested_amount'       => $reqAmount,
                            'approved_amount'        => 0,
                            'interest_rate'          => $rate,
                            'duration_months'        => $duration,
                            'total_amount_due'       => $totalDue,
                            'outstanding_principal'  => $totalDue,
                            'total_paid'             => 0,
                            'purpose'                => $item['purpose'] ?? null,
                            'collateral_description' => $item['collateral_description'] ?? null,
                            'status'                 => 'pending',
                            'registered_by'          => $user->id,
                            'agency_id'              => $user->agency_id ?? 1,
                            'application_date'       => $offlineCreatedAt,
                        ]);

                        DB::commit();
                        $syncedIds[] = $offlineIdStr;
                    } else {
                        DB::rollBack();
                        $errors[] = ['id' => $offlineIdStr, 'error' => 'Client introuvable pour demande de prêt'];
                    }
                } catch (\Exception $le) {
                    DB::rollBack();
                    $errors[] = ['id' => $offlineIdStr, 'error' => 'Prêt: ' . $le->getMessage()];
                }
                continue;
            }

            // -------------------------------------------------------------
            // CAS 4 : REMBOURSEMENT DE PRÊT
            // -------------------------------------------------------------
            $type = $item['transaction_type'] ?? 'deposit';
            $amount = (float) ($item['amount'] ?? 0);

            if ($amount <= 0) {
                $syncedIds[] = $offlineIdStr;
                continue;
            }

            if ($type === 'loan_repayment' && !empty($item['loan_id'])) {
                DB::beginTransaction();
                try {
                    $loan = Loan::with('client')->find($item['loan_id']);
                    if (!$loan) {
                        DB::rollBack();
                        $errors[] = ['id' => $offlineIdStr, 'error' => 'Prêt #' . $item['loan_id'] . ' non trouvé'];
                        continue;
                    }

                    $clientAccount = Account::where('client_id', $loan->client_id)->first();
                    $txRef = 'SYNC-REM-' . strtoupper(Str::random(5)) . '-' . date('ymd');

                    Transaction::create([
                        'transaction_reference' => $txRef,
                        'account_id'            => $clientAccount->id ?? 1,
                        'loan_id'               => $loan->id,
                        'cashier_session_id'    => $activeSession?->id,
                        'transaction_type'      => 'loan_repayment',
                        'amount'                => $amount,
                        'payment_method'        => $item['payment_method'] ?? 'cash',
                        'payment_reference'     => $offlineIdStr,
                        'fee_amount'            => 0,
                        'description'           => $item['description'] ?? ('Remboursement hors-ligne Prêt N° ' . $loan->loan_number),
                        'status'                => 'completed',
                        'balance_before'        => 0,
                        'balance_after'         => 0,
                        'processed_by'          => $user->id,
                        'agency_id'             => $user->agency_id ?? 1,
                        'processed_at'          => now(),
                        'transaction_date'      => $offlineCreatedAt,
                        'created_at'            => $offlineCreatedAt,
                    ]);

                    // Répartir sur l'échéancier
                    $remainingPayment = $amount;
                    $pendingPayments = LoanPayment::where('loan_id', $loan->id)
                        ->whereIn('status', ['pending', 'overdue', 'partial'])
                        ->orderBy('payment_number')
                        ->get();

                    foreach ($pendingPayments as $p) {
                        if ($remainingPayment <= 0) break;
                        $expected = (float) $p->expected_amount;
                        $currentPaid = (float) $p->paid_amount;
                        $due = max(0, $expected - $currentPaid);
                        if ($due <= 0) continue;

                        if ($remainingPayment >= ($due - 1.0)) {
                            $p->update([
                                'paid_amount' => $expected,
                                'status' => 'paid',
                                'paid_date' => $offlineCreatedAt,
                                'processed_by' => $user->id,
                                'processed_at' => now(),
                            ]);
                            $remainingPayment = max(0, $remainingPayment - $due);
                        } else {
                            $newPaid = $currentPaid + $remainingPayment;
                            $p->update([
                                'paid_amount' => round($newPaid, 2),
                                'status' => $newPaid >= ($expected - 1.0) ? 'paid' : 'partial',
                                'paid_date' => $offlineCreatedAt,
                                'processed_by' => $user->id,
                                'processed_at' => now(),
                            ]);
                            $remainingPayment = 0;
                        }
                    }

                    $totalPaid = LoanPayment::where('loan_id', $loan->id)->sum('paid_amount');
                    $totalExpected = (float) ($loan->total_amount_due ?? $loan->requested_amount);
                    $isFullyPaid = $totalPaid >= ($totalExpected - 1.0);
                    $loan->update([
                        'total_paid' => round($totalPaid, 2),
                        'outstanding_principal' => max(0, round($totalExpected - $totalPaid, 2)),
                        'status' => $isFullyPaid ? 'completed' : $loan->status,
                    ]);

                    if ($activeSession) {
                        $activeSession->increment('total_deposits', $amount);
                    }

                    DB::commit();
                    $syncedIds[] = $offlineIdStr;
                } catch (\Exception $rpe) {
                    DB::rollBack();
                    $errors[] = ['id' => $offlineIdStr, 'error' => 'Remboursement: ' . $rpe->getMessage()];
                }
                continue;
            }

            // -------------------------------------------------------------
            // CAS 5 : DÉPÔTS / RETRAITS / COTISATIONS TONTINE
            // -------------------------------------------------------------
            DB::beginTransaction();
            try {
                $rawAccountId = $item['account_id'] ?? null;
                $realAccountId = $this->idMapping['accounts'][$rawAccountId] 
                    ?? $this->idMapping['accounts'][(string)$rawAccountId]
                    ?? (is_numeric($rawAccountId) && $rawAccountId > 0 ? (int)$rawAccountId : null);

                $account = null;
                if ($realAccountId) {
                    $account = Account::with('tontineAccount.activeCycle')->find($realAccountId);
                }

                if (!$account && !empty($item['account_number'])) {
                    $account = Account::with('tontineAccount.activeCycle')
                        ->where('account_number', $item['account_number'])
                        ->first();
                }

                // Recherche avancée et liaison de compte hors-ligne (ex: OFF-TON-..., compte d'un nouveau client)
                if (!$account) {
                    $clientId = null;
                    $rawCliId = $item['client_id'] ?? $item['temp_client_id'] ?? null;
                    if ($rawCliId && isset($this->idMapping['clients'][$rawCliId])) {
                        $clientId = $this->idMapping['clients'][$rawCliId];
                    }
                    if (!$clientId && $rawAccountId && isset($this->idMapping['clients'][$rawAccountId])) {
                        $clientId = $this->idMapping['clients'][$rawAccountId];
                    }

                    // Chercher par le numéro de téléphone si présent
                    if (!$clientId && !empty($item['phone'])) {
                        $cl = Client::where('phone', $item['phone'])->first();
                        if ($cl) $clientId = $cl->id;
                    }

                    // Si aucun clientId mais un client créé dans le même lot de synchro
                    if (!$clientId && !empty($this->idMapping['clients'])) {
                        $clientId = end($this->idMapping['clients']);
                    }

                    if ($clientId) {
                        $isTontine = str_contains((string)($item['account_number'] ?? ''), 'TON') 
                            || ($item['account_type'] ?? '') === 'tontine'
                            || ($item['transaction_type'] ?? '') === 'tontine';

                        $account = Account::with('tontineAccount.activeCycle')
                            ->where('client_id', $clientId)
                            ->where('account_type', $isTontine ? 'tontine' : 'savings')
                            ->first();

                        if (!$account) {
                            $account = Account::with('tontineAccount.activeCycle')
                                ->where('client_id', $clientId)
                                ->first();
                        }

                        if (!$account) {
                            $account = Account::create([
                                'client_id'      => $clientId,
                                'account_number' => ($isTontine ? 'TON-' : 'SAV-') . strtoupper(Str::random(8)),
                                'account_type'   => $isTontine ? 'tontine' : 'savings',
                                'balance'        => 0,
                                'status'         => 'active',
                                'created_by'     => $user->id,
                                'activated_at'   => now(),
                            ]);
                            if ($isTontine) {
                                $this->createTontineAccount($account, ['target_amount' => 1000]);
                            }
                        }
                    }
                }

                if (!$account) {
                    DB::rollBack();
                    $errors[] = ['id' => $offlineIdStr, 'error' => "Compte introuvable (ID: {$rawAccountId}, N°: " . ($item['account_number'] ?? 'N/A') . ")"];
                    continue;
                }

                $before = (float) $account->balance;
                $after = ($type === 'withdrawal') ? max(0, $before - $amount) : ($before + $amount);

                if ($type === 'deposit' && $account->account_type === 'tontine' && $account->tontineAccount) {
                    $tontine = $account->tontineAccount;
                    $cycle = $tontine->activeCycle ?? $this->createTontineCycle($tontine);
                    if ($cycle) {
                        $this->distributeTontineAmount($tontine, $cycle, $amount);
                    }
                    $tontine->increment('total_paid', $amount);
                }

                $txPrefix = ($type === 'withdrawal') ? 'SYNC-RET-' : 'SYNC-DEP-';
                $txRef = $txPrefix . strtoupper(Str::random(5)) . '-' . date('ymd');

                $validTxTypes = ['deposit', 'withdrawal', 'transfer', 'fee', 'interest', 'penalty', 'payout', 'tontine_contribution', 'tontine_payout', 'savings_deposit', 'tontine_deposit', 'loan_repayment', 'loan_disbursement', 'transfer_in', 'transfer_out'];
                $dbTxType = in_array($type, $validTxTypes) ? $type : 'deposit';

                $validPayMethods = ['cash', 'mobile_money', 'bank_transfer', 'system'];
                $rawPayMethod = strtolower((string)($item['payment_method'] ?? 'cash'));
                $dbPayMethod = in_array($rawPayMethod, $validPayMethods) ? $rawPayMethod : 'cash';

                Transaction::create([
                    'transaction_reference' => $txRef,
                    'account_id'            => $account->id,
                    'cashier_session_id'    => $activeSession?->id,
                    'transaction_type'      => $dbTxType,
                    'amount'                => $amount,
                    'payment_method'        => $dbPayMethod,
                    'payment_reference'     => $offlineIdStr,
                    'fee_amount'            => 0,
                    'description'           => $item['description'] ?? ($type === 'withdrawal' ? 'Retrait guichet hors-ligne' : 'Dépôt guichet hors-ligne'),
                    'status'                => 'completed',
                    'balance_before'        => $before,
                    'balance_after'         => $after,
                    'processed_by'          => $user->id,
                    'agency_id'             => $user->agency_id ?? 1,
                    'processed_at'          => now(),
                    'transaction_date'      => $offlineCreatedAt,
                    'created_at'            => $offlineCreatedAt,
                ]);

                $account->update(['balance' => $after, 'last_transaction_at' => now()]);

                if ($activeSession) {
                    if ($type === 'withdrawal') {
                        $activeSession->increment('total_withdrawals', $amount);
                    } else {
                        $activeSession->increment('total_deposits', $amount);
                    }
                }

                DB::commit();
                $syncedIds[] = $offlineIdStr;
            } catch (\Exception $txe) {
                DB::rollBack();
                $errors[] = ['id' => $offlineIdStr, 'error' => 'Transaction: ' . $txe->getMessage()];
            }
        }

        return response()->json([
            'success'             => true,
            'message'             => count($syncedIds) . ' opération(s) synchronisée(s) avec succès' . (!empty($errors) ? ', ' . count($errors) . ' erreur(s).' : '.'),
            'synced_count'        => count($syncedIds),
            'synced_ids'          => $syncedIds,
            'errors'              => $errors,
            'active_session_open' => $activeSession !== null,
        ]);
    }
}

