<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Client;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgentFieldTeamController extends Controller
{
    /**
     * Liste des agents de terrain de l'agence avec résumé de collecte par jour
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $dateStr = $request->get('date', Carbon::today()->toDateString());
        $search = $request->get('search');

        try {
            $date = Carbon::parse($dateStr);
        } catch (\Exception $e) {
            $date = Carbon::today();
            $dateStr = $date->toDateString();
        }

        $agencyId = $user->agency_id;

        // Requête des agents de terrain rattachés à l'agence
        $agentsQuery = User::query()
            ->where(function ($q) use ($agencyId) {
                if ($agencyId) {
                    $q->where('agency_id', $agencyId);
                }
            })
            ->where(function ($q) {
                $q->where('role', 'agent_terrain')
                  ->orWhere('role', 'LIKE', 'agent%');
            })
            ->where('role', '!=', 'caissier');

        if ($search) {
            $agentsQuery->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $agents = $agentsQuery->orderBy('first_name')->get();

        $inflowTypes = ['deposit', 'depot', 'credit', 'tontine_deposit', 'loan_repayment'];

        $globalTotalCollected = 0.0;
        $globalTotalOperations = 0;
        $activeAgentsCount = 0;

        $agentsList = $agents->map(function ($agent) use ($date, $dateStr, $inflowTypes, &$globalTotalCollected, &$globalTotalOperations, &$activeAgentsCount) {
            // Nombre de clients enregistrés par l'agent
            $clientsCount = Client::where('registered_by', $agent->id)
                ->whereIn('registration_status', ['approved', 'pending'])
                ->count();

            // Transactions réalisées par cet agent à la date sélectionnée
            $dayTransactions = Transaction::query()
                ->where('processed_by', $agent->id)
                ->where(function ($q) use ($date) {
                    $q->whereDate('transaction_date', $date)
                      ->orWhere(function ($sub) use ($date) {
                          $sub->whereNull('transaction_date')
                              ->whereDate('created_at', $date);
                      });
                })
                ->where('status', 'completed')
                ->with('account')
                ->get();

            $totalCollected = 0.0;
            $totalWithdrawals = 0.0;
            $savingsAmount = 0.0;
            $tontineAmount = 0.0;
            $loanRepaymentsAmount = 0.0;
            $operationsCount = $dayTransactions->count();

            foreach ($dayTransactions as $tx) {
                $type = strtolower($tx->transaction_type ?? '');
                $amount = (float) $tx->amount;

                if (in_array($type, $inflowTypes) || str_contains($type, 'depot') || str_contains($type, 'deposit') || str_contains($type, 'repayment')) {
                    $totalCollected += $amount;

                    $accountType = strtolower($tx->account->account_type ?? '');
                    if ($accountType === 'tontine' || str_contains($type, 'tontine')) {
                        $tontineAmount += $amount;
                    } elseif ($accountType === 'savings' || $accountType === 'epargne') {
                        $savingsAmount += $amount;
                    } elseif ($type === 'loan_repayment' || $tx->loan_id) {
                        $loanRepaymentsAmount += $amount;
                    } else {
                        $savingsAmount += $amount;
                    }
                } elseif (str_contains($type, 'retrait') || str_contains($type, 'withdraw') || $type === 'debit') {
                    $totalWithdrawals += $amount;
                }
            }

            if ($operationsCount > 0 || $totalCollected > 0) {
                $activeAgentsCount++;
            }

            $globalTotalCollected += $totalCollected;
            $globalTotalOperations += $operationsCount;

            return [
                'id' => $agent->id,
                'first_name' => $agent->first_name,
                'last_name' => $agent->last_name,
                'full_name' => trim($agent->first_name . ' ' . $agent->last_name),
                'username' => $agent->username,
                'phone' => $agent->phone,
                'email' => $agent->email,
                'role' => $agent->role,
                'is_active' => (bool) $agent->is_active,
                'total_clients' => $clientsCount,
                'collections' => [
                    'date' => $dateStr,
                    'total_collected' => round($totalCollected, 2),
                    'total_withdrawals' => round($totalWithdrawals, 2),
                    'net_collected' => round($totalCollected - $totalWithdrawals, 2),
                    'operations_count' => $operationsCount,
                    'savings_amount' => round($savingsAmount, 2),
                    'tontine_amount' => round($tontineAmount, 2),
                    'loan_repayments_amount' => round($loanRepaymentsAmount, 2),
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'date' => $dateStr,
            'summary' => [
                'total_agents' => $agents->count(),
                'active_agents' => $activeAgentsCount,
                'total_collected' => round($globalTotalCollected, 2),
                'total_operations' => $globalTotalOperations,
            ],
            'data' => $agentsList,
        ]);
    }

    /**
     * Fiche détaillée d'un agent avec ses clients et le détail de ses collectes
     */
    public function show($agentId, Request $request)
    {
        $user = auth()->user();
        $dateStr = $request->get('date', Carbon::today()->toDateString());

        try {
            $date = Carbon::parse($dateStr);
        } catch (\Exception $e) {
            $date = Carbon::today();
            $dateStr = $date->toDateString();
        }

        $agencyId = $user->agency_id;

        $agent = User::where('id', $agentId)
            ->when($agencyId, function ($q) use ($agencyId) {
                $q->where('agency_id', $agencyId);
            })
            ->first();

        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Agent introuvable ou non autorisé.',
            ], 404);
        }

        // 1. Clients de l'agent
        $clients = Client::where('registered_by', $agentId)
            ->with(['accounts' => function ($q) {
                $q->where('status', 'active');
            }])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($client) {
                $totalBalance = $client->accounts->sum('balance');
                return [
                    'id' => $client->id,
                    'client_number' => $client->client_number,
                    'first_name' => $client->first_name,
                    'last_name' => $client->last_name,
                    'full_name' => trim($client->first_name . ' ' . $client->last_name),
                    'phone' => $client->phone,
                    'email' => $client->email,
                    'address' => $client->address,
                    'kyc_status' => $client->kyc_status,
                    'registration_status' => $client->registration_status,
                    'total_balance' => (float) $totalBalance,
                    'accounts_count' => $client->accounts->count(),
                    'created_at' => $client->created_at ? $client->created_at->format('d/m/Y') : null,
                ];
            });

        // 2. Collectes / Transactions de la date sélectionnée
        $inflowTypes = ['deposit', 'depot', 'credit', 'tontine_deposit', 'loan_repayment'];

        $transactions = Transaction::where('processed_by', $agentId)
            ->where(function ($q) use ($date) {
                $q->whereDate('transaction_date', $date)
                  ->orWhere(function ($sub) use ($date) {
                      $sub->whereNull('transaction_date')
                          ->whereDate('created_at', $date);
                  });
            })
            ->where('status', 'completed')
            ->with(['account.client'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($tx) {
                $client = $tx->account ? $tx->account->client : null;
                return [
                    'id' => $tx->id,
                    'reference' => $tx->transaction_reference ?? ('TX-' . $tx->id),
                    'type' => $tx->transaction_type,
                    'amount' => (float) $tx->amount,
                    'account_number' => $tx->account ? $tx->account->account_number : 'N/A',
                    'account_type' => $tx->account ? $tx->account->account_type : 'N/A',
                    'client_id' => $client ? $client->id : null,
                    'client_name' => $client ? trim($client->first_name . ' ' . $client->last_name) : 'Client Inconnu',
                    'client_phone' => $client ? $client->phone : 'N/A',
                    'time' => $tx->created_at ? $tx->created_at->format('H:i') : '--:--',
                    'created_at' => $tx->created_at ? $tx->created_at->toISOString() : null,
                    'payment_method' => $tx->payment_method ?? 'cash',
                    'description' => $tx->description,
                ];
            });

        $totalCollected = 0.0;
        $totalWithdrawals = 0.0;

        foreach ($transactions as $tx) {
            $type = strtolower($tx['type'] ?? '');
            $amount = (float) $tx['amount'];
            if (in_array($type, $inflowTypes) || str_contains($type, 'depot') || str_contains($type, 'deposit')) {
                $totalCollected += $amount;
            } elseif (str_contains($type, 'retrait') || str_contains($type, 'withdraw')) {
                $totalWithdrawals += $amount;
            }
        }

        return response()->json([
            'success' => true,
            'date' => $dateStr,
            'agent' => [
                'id' => $agent->id,
                'full_name' => trim($agent->first_name . ' ' . $agent->last_name),
                'username' => $agent->username,
                'phone' => $agent->phone,
                'email' => $agent->email,
                'role' => $agent->role,
                'is_active' => (bool) $agent->is_active,
                'total_clients' => $clients->count(),
            ],
            'summary' => [
                'total_collected' => round($totalCollected, 2),
                'total_withdrawals' => round($totalWithdrawals, 2),
                'net_collected' => round($totalCollected - $totalWithdrawals, 2),
                'operations_count' => $transactions->count(),
            ],
            'collections' => $transactions,
            'clients' => $clients,
        ]);
    }
}
