<?php

namespace App\Http\Controllers\Web\Manager;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Http\Resources\TransactionResource;
use Illuminate\Http\Request;

class ManagerTransactionController extends Controller
{

    /**
     * Transactions en attente de validation
     */
    public function pending(Request $request)
    {
        $user = auth()->user();

        $pending = Transaction::whereHas('account.client', function($query) use ($user) {
                $query->where('agency_id', $user->agency_id);
            })
            ->where('validation_required', true)
            ->where('status', 'pending')
            ->with(['account.client', 'processedBy'])
            ->when($request->get('transaction_type'), function($query, $type) {
                $query->where('transaction_type', $type);
            })
            ->when($request->get('amount_min'), function($query, $min) {
                $query->where('amount', '>=', $min);
            })
            ->when($request->get('amount_max'), function($query, $max) {
                $query->where('amount', '<=', $max);
            })
            ->orderBy('created_at')
            ->paginate($request->get('per_page', 15));

        return TransactionResource::collection($pending);
    }

    /**
     * Valider une transaction
     */
    public function validatetransaction(Request $request, $transactionId)
    {
        $request->validate([
            'comment' => 'nullable|string|max:500'
        ]);

        try {
            $user = auth()->user();

            $transaction = Transaction::whereHas('account.client', function($query) use ($user) {
                $query->where('agency_id', $user->agency_id);
            })->findOrFail($transactionId);

            $result = $this->transactionService->validateTransaction(
                $transactionId,
                $user->id,
                $request->get('comment')
            );

            return response()->json([
                'success' => true,
                'message' => 'Transaction validée avec succès',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rejeter une transaction
     */
    public function reject(Request $request, $transactionId)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        try {
            $user = auth()->user();

            $transaction = Transaction::whereHas('account.client', function($query) use ($user) {
                $query->where('agency_id', $user->agency_id);
            })->findOrFail($transactionId);

            $result = $this->transactionService->rejectTransaction(
                $transactionId,
                $user->id,
                $request->get('reason')
            );

            return response()->json([
                'success' => true,
                'message' => 'Transaction rejetée avec succès',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rejet',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Historique des validations
     */
    public function validationHistory(Request $request)
    {
        $user = auth()->user();

        $history = Transaction::whereHas('account.client', function($query) use ($user) {
                $query->where('agency_id', $user->agency_id);
            })
            ->where('validated_by', $user->id)
            ->with(['account.client'])
            ->when($request->get('status'), function($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->get('date_from'), function($query, $dateFrom) {
                $query->whereDate('updated_at', '>=', $dateFrom);
            })
            ->when($request->get('date_to'), function($query, $dateTo) {
                $query->whereDate('updated_at', '<=', $dateTo);
            })
            ->orderBy('updated_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return TransactionResource::collection($history);
    }

    /**
     * Statistiques de validation
     */
    public function validationStats(Request $request)
    {
        $user = auth()->user();
        $period = $request->get('period', '30d');

        $stats = $this->transactionService->getValidationStats($user->agency_id, $period);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Transactions suspectes
     */
    public function suspicious(Request $request)
    {
        $user = auth()->user();

        $suspicious = $this->transactionService->getSuspiciousTransactions($user->agency_id);

        return response()->json([
            'success' => true,
            'data' => $suspicious
        ]);
    }
}
