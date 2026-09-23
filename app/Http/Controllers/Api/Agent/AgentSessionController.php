<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Models\CashierSession;
use App\Models\Transaction;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AgentSessionController extends Controller
{
    /**
     * Obtenir la session actuelle du caissier.
     */
    public function current(Request $request): JsonResponse
    {
        $user = $request->user();

        $session = CashierSession::where('user_id', $user->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        $today = Carbon::today();
        $todayDeposits = (float) Transaction::where('processed_by', $user->id)
            ->where('status', 'completed')
            ->whereDate('created_at', $today)
            ->where('transaction_type', 'like', '%deposit%')
            ->sum('amount');

        $todayWithdrawals = (float) Transaction::where('processed_by', $user->id)
            ->where('status', 'completed')
            ->whereDate('created_at', $today)
            ->where('transaction_type', 'withdrawal')
            ->sum('amount');

        $txCount = Transaction::where('processed_by', $user->id)
            ->where('status', 'completed')
            ->whereDate('created_at', $today)
            ->count();

        if (!$session) {
            // Chercher la dernière session fermée pour référence
            $lastClosed = CashierSession::where('user_id', $user->id)
                ->where('status', 'closed')
                ->latest('closed_at')
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'has_open_session' => false,
                    'status' => 'closed',
                    'session' => null,
                    'opening_balance' => 0.0,
                    'total_deposits' => $todayDeposits,
                    'total_withdrawals' => $todayWithdrawals,
                    'expected_closing_balance' => $todayDeposits - $todayWithdrawals,
                    'transactions_count' => $txCount,
                    'last_closed_session' => $lastClosed,
                ],
            ]);
        }

        $opening = (float) $session->opening_balance;
        $deposits = $session->total_deposits > 0 ? (float) $session->total_deposits : $todayDeposits;
        $withdrawals = $session->total_withdrawals > 0 ? (float) $session->total_withdrawals : $todayWithdrawals;
        $expected = $opening + $deposits - $withdrawals;

        return response()->json([
            'success' => true,
            'data' => [
                'has_open_session' => true,
                'status' => 'open',
                'session' => [
                    'id' => $session->id,
                    'status' => 'open',
                    'opened_at' => $session->opened_at,
                    'opening_balance' => $opening,
                    'total_deposits' => $deposits,
                    'total_withdrawals' => $withdrawals,
                    'expected_closing_balance' => $expected,
                    'notes' => $session->notes,
                ],
                'opening_balance' => $opening,
                'total_deposits' => $deposits,
                'total_withdrawals' => $withdrawals,
                'expected_closing_balance' => $expected,
                'transactions_count' => $txCount,
            ],
        ]);
    }

    /**
     * Ouvrir une session de caisse.
     */
    public function open(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'opening_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        // Vérifier si une session est déjà ouverte
        $existing = CashierSession::where('user_id', $user->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'message' => 'Une session de caisse est déjà active pour votre compte.',
                'data' => [
                    'id' => $existing->id,
                    'status' => 'open',
                    'has_open_session' => true,
                    'opening_balance' => (float) $existing->opening_balance,
                    'expected_closing_balance' => (float) $existing->expected_closing_balance,
                    'total_deposits' => (float) $existing->total_deposits,
                    'total_withdrawals' => (float) $existing->total_withdrawals,
                    'opened_at' => $existing->opened_at,
                    'notes' => $existing->notes,
                ],
            ], 200);
        }

        try {
            $session = CashierSession::create([
                'user_id' => $user->id,
                'agency_id' => $user->agency_id ?? 1,
                'opened_at' => now(),
                'opening_balance' => (float) $validated['opening_balance'],
                'expected_closing_balance' => (float) $validated['opening_balance'],
                'total_deposits' => 0,
                'total_withdrawals' => 0,
                'status' => 'open',
                'notes' => $validated['notes'] ?? 'Ouverture de caisse mobile POS',
            ]);

            Log::info('Session caisse ouverte', [
                'user_id' => $user->id,
                'session_id' => $session->id,
                'opening_balance' => $session->opening_balance,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Session de caisse ouverte avec succès.',
                'data' => $session,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur ouverture session caisse', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Impossible d\'ouvrir la session : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clôturer la session de caisse active.
     */
    public function close(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'closing_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        $session = CashierSession::where('user_id', $user->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune session de caisse active à clôturer.',
            ], 404);
        }

        try {
            DB::beginTransaction();

            // Calculer les totaux réels des transactions associées
            $today = Carbon::today();
            $totals = Transaction::where('processed_by', $user->id)
                ->where('status', 'completed')
                ->whereDate('created_at', $today)
                ->select(
                    DB::raw('SUM(CASE WHEN transaction_type LIKE "%deposit%" THEN amount ELSE 0 END) as total_deposits'),
                    DB::raw('SUM(CASE WHEN transaction_type = "withdrawal" THEN amount ELSE 0 END) as total_withdrawals')
                )->first();

            $totalDeposits = (float) ($totals->total_deposits ?? 0);
            $totalWithdrawals = (float) ($totals->total_withdrawals ?? 0);
            $expected = (float) $session->opening_balance + $totalDeposits - $totalWithdrawals;
            $physicalClosing = (float) $validated['closing_balance'];
            $difference = $physicalClosing - $expected;

            $session->update([
                'closed_at' => now(),
                'closing_balance' => $physicalClosing,
                'expected_closing_balance' => $expected,
                'total_deposits' => $totalDeposits,
                'total_withdrawals' => $totalWithdrawals,
                'status' => 'closed',
                'notes' => $validated['notes'] ?? 'Clôture manuelle effectuée par le caissier',
            ]);

            // Rattacher les transactions du jour à cette session si pas encore fait
            Transaction::where('processed_by', $user->id)
                ->whereDate('created_at', $today)
                ->whereNull('cashier_session_id')
                ->update(['cashier_session_id' => $session->id]);

            // Notification d'audit si écart
            if (abs($difference) > 0.01) {
                $statusType = $difference > 0 ? 'EXCÉDENT' : 'DÉFICIT';
                Log::warning("Écart de caisse détecté ($statusType)", [
                    'session_id' => $session->id,
                    'user_id' => $user->id,
                    'difference' => $difference,
                    'expected' => $expected,
                    'physical' => $physicalClosing,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Session de caisse clôturée avec succès.',
                'data' => [
                    'session' => $session,
                    'summary' => [
                        'opening_balance' => $session->opening_balance,
                        'total_deposits' => $totalDeposits,
                        'total_withdrawals' => $totalWithdrawals,
                        'expected_closing_balance' => $expected,
                        'closing_balance' => $physicalClosing,
                        'difference' => $difference,
                        'difference_type' => $difference == 0 ? 'exact' : ($difference > 0 ? 'surplus' : 'deficit'),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur clôture session caisse', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Impossible de clôturer la session : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Historique des sessions passées.
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessions = CashierSession::where('user_id', $user->id)
            ->latest('opened_at')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $sessions,
        ]);
    }
}
