<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminTransactionController extends Controller
{
    /**
     * Page principale des transactions avec analytics
     */
    public function index(Request $request)
    {
        $query = Transaction::with([
            'account.client',
            'processedBy',
            'validatedBy',
            'receipt'
        ]);

        // Filtres
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('transaction_reference', 'like', "%{$search}%")
                  ->orWhere('payment_reference', 'like', "%{$search}%")
                  ->orWhereHas('account', function($q2) use ($search) {
                      $q2->where('account_number', 'like', "%{$search}%")
                         ->orWhereHas('client', function($q3) use ($search) {
                             $q3->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('client_number', 'like', "%{$search}%");
                         });
                  });
            });
        }

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        // Tri
        $sortBy = $request->get('sort_by', 'transaction_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $transactions = $query->paginate(15);

        // Statistiques globales
        $stats = $this->getGlobalStats($request);

        // Données pour les graphiques
        $chartData = $this->getChartData($request);

        return view('admin.transactions.index', compact('transactions', 'stats', 'chartData'));
    }

    /**
     * Détails d'une transaction (AJAX)
     */
    public function show($id)
    {
        $transaction = Transaction::with([
            'account.client',
            'account.savingsAccount',
            'account.tontineAccount',
            'processedBy',
            'validatedBy',
            'receipt'
        ])->findOrFail($id);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'transaction' => $transaction,
                'html' => view('admin.transactions.partials.detail', compact('transaction'))->render()
            ]);
        }

        return view('admin.transactions.show', compact('transaction'));
    }

    /**
     * Analytics détaillées
     */
    public function analytics(Request $request)
    {
        $period = $request->get('period', '30days');

        // Définir la période
        switch ($period) {
            case '7days':
                $startDate = now()->subDays(7);
                break;
            case '30days':
                $startDate = now()->subDays(30);
                break;
            case '90days':
                $startDate = now()->subDays(90);
                break;
            case 'year':
                $startDate = now()->subYear();
                break;
            case 'custom':
                $startDate = $request->get('start_date') ? Carbon::parse($request->get('start_date')) : now()->subDays(30);
                break;
            default:
                $startDate = now()->subDays(30);
        }

        $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date')) : now();

        // Statistiques avancées
        $analytics = [
            // Volume des transactions
            'volume' => $this->getVolumeStats($startDate, $endDate),

            // Répartition par type
            'by_type' => $this->getTransactionsByType($startDate, $endDate),

            // Répartition par méthode de paiement
            'by_payment_method' => $this->getTransactionsByPaymentMethod($startDate, $endDate),

            // Évolution temporelle
            'timeline' => $this->getTimelineData($startDate, $endDate),

            // Top clients
            'top_clients' => $this->getTopClients($startDate, $endDate),

            // Taux de réussite
            'success_rate' => $this->getSuccessRate($startDate, $endDate),

            // Transactions par heure
            'by_hour' => $this->getTransactionsByHour($startDate, $endDate),

            // Comparaison avec période précédente
            'comparison' => $this->getPeriodComparison($startDate, $endDate),
        ];

        return view('admin.transactions.analytics', compact('analytics', 'period', 'startDate', 'endDate'));
    }

    /**
     * Export des transactions
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'csv');

        $query = Transaction::with(['account.client', 'processedBy']);

        // Appliquer les mêmes filtres que l'index
        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        $transactions = $query->get();

        switch ($format) {
            case 'csv':
                return $this->exportToCsv($transactions);
            case 'excel':
                return $this->exportToExcel($transactions);
            case 'pdf':
                return $this->exportToPdf($transactions);
            default:
                return back()->with('error', 'Format non supporté');
        }
    }

    /**
     * Valider une transaction en attente
     */
    public function validatestran(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $transaction = Transaction::findOrFail($id);

            if ($transaction->status !== 'pending') {
                throw new \Exception('Seules les transactions en attente peuvent être validées.');
            }

            $transaction->update([
                'status' => 'completed',
                'validated_by' => auth()->id(),
                'validated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction validée avec succès.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rejeter une transaction en attente
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $transaction = Transaction::findOrFail($id);

            if ($transaction->status !== 'pending') {
                throw new \Exception('Seules les transactions en attente peuvent être rejetées.');
            }

            $transaction->update([
                'status' => 'rejected',
                'rejection_reason' => $request->reason,
                'validated_by' => auth()->id(),
                'validated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction rejetée avec succès.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rejet: ' . $e->getMessage()
            ], 500);
        }
    }

    // =============== MÉTHODES PRIVÉES ===============

    /**
     * Statistiques globales
     */
    private function getGlobalStats($request)
    {
        $query = Transaction::query();

        // Appliquer les filtres de date si présents
        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        return [
            'total_transactions' => $query->count(),
            'total_amount' => $query->where('status', 'completed')->sum('amount'),
            'total_deposits' => $query->where('transaction_type', 'deposit')->where('status', 'completed')->sum('amount'),
            'total_withdrawals' => $query->where('transaction_type', 'withdrawal')->where('status', 'completed')->sum('amount'),
            'pending_count' => Transaction::where('status', 'pending')->count(),
            'pending_amount' => Transaction::where('status', 'pending')->sum('amount'),
            'completed_count' => Transaction::where('status', 'completed')->count(),
            'failed_count' => Transaction::where('status', 'failed')->count(),
            'average_transaction' => $query->where('status', 'completed')->avg('amount'),
            'today_transactions' => Transaction::whereDate('transaction_date', today())->count(),
            'today_amount' => Transaction::whereDate('transaction_date', today())->where('status', 'completed')->sum('amount'),
        ];
    }

    /**
     * Données pour les graphiques
     */
    private function getChartData($request)
    {
        $days = 30;
        $startDate = now()->subDays($days);

        // Transactions par jour
        $dailyTransactions = Transaction::where('transaction_date', '>=', $startDate)
            ->where('status', 'completed')
            ->selectRaw('DATE(transaction_date) as date, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Répartition par type
        $byType = Transaction::where('status', 'completed')
            ->selectRaw('transaction_type, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('transaction_type')
            ->get();

        // Répartition par méthode
        $byMethod = Transaction::where('status', 'completed')
            ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('payment_method')
            ->get();

        return [
            'daily' => $dailyTransactions,
            'by_type' => $byType,
            'by_method' => $byMethod,
        ];
    }

    /**
     * Volume des transactions
     */
    private function getVolumeStats($startDate, $endDate)
    {
        return [
            'total' => Transaction::whereBetween('transaction_date', [$startDate, $endDate])
                ->where('status', 'completed')
                ->count(),
            'amount' => Transaction::whereBetween('transaction_date', [$startDate, $endDate])
                ->where('status', 'completed')
                ->sum('amount'),
        ];
    }

    /**
     * Transactions par type
     */
    private function getTransactionsByType($startDate, $endDate)
    {
        return Transaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->selectRaw('transaction_type, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('transaction_type')
            ->get();
    }

    /**
     * Transactions par méthode de paiement
     */
    private function getTransactionsByPaymentMethod($startDate, $endDate)
    {
        return Transaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('payment_method')
            ->get();
    }

    /**
     * Évolution temporelle
     */
    private function getTimelineData($startDate, $endDate)
    {
        return Transaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->selectRaw('DATE(transaction_date) as date, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Top clients
     */
    private function getTopClients($startDate, $endDate, $limit = 10)
    {
        return Transaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->with('account.client')
            ->selectRaw('account_id, COUNT(*) as transaction_count, SUM(amount) as total_amount')
            ->groupBy('account_id')
            ->orderBy('total_amount', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Taux de réussite
     */
    private function getSuccessRate($startDate, $endDate)
    {
        $total = Transaction::whereBetween('transaction_date', [$startDate, $endDate])->count();
        $completed = Transaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->count();

        return $total > 0 ? round(($completed / $total) * 100, 2) : 0;
    }

    /**
     * Transactions par heure
     */
    private function getTransactionsByHour($startDate, $endDate)
    {
        return Transaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->selectRaw('HOUR(transaction_date) as hour, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();
    }

    /**
     * Comparaison avec période précédente
     */
    private function getPeriodComparison($startDate, $endDate)
    {
        $days = $startDate->diffInDays($endDate);
        $previousStart = (clone $startDate)->subDays($days);
        $previousEnd = clone $startDate;

        $current = [
            'count' => Transaction::whereBetween('transaction_date', [$startDate, $endDate])
                ->where('status', 'completed')
                ->count(),
            'amount' => Transaction::whereBetween('transaction_date', [$startDate, $endDate])
                ->where('status', 'completed')
                ->sum('amount'),
        ];

        $previous = [
            'count' => Transaction::whereBetween('transaction_date', [$previousStart, $previousEnd])
                ->where('status', 'completed')
                ->count(),
            'amount' => Transaction::whereBetween('transaction_date', [$previousStart, $previousEnd])
                ->where('status', 'completed')
                ->sum('amount'),
        ];

        return [
            'current' => $current,
            'previous' => $previous,
            'count_change' => $previous['count'] > 0
                ? round((($current['count'] - $previous['count']) / $previous['count']) * 100, 2)
                : 0,
            'amount_change' => $previous['amount'] > 0
                ? round((($current['amount'] - $previous['amount']) / $previous['amount']) * 100, 2)
                : 0,
        ];
    }

    /**
     * Export CSV
     */
    private function exportToCsv($transactions)
    {
        $filename = 'transactions_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($transactions) {
            $file = fopen('php://output', 'w');

            // En-têtes
            fputcsv($file, [
                'Référence',
                'Date',
                'Client',
                'Type',
                'Montant',
                'Méthode',
                'Statut',
                'Traité par'
            ]);

            // Données
            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->transaction_reference,
                    $transaction->transaction_date->format('d/m/Y H:i'),
                    $transaction->account->client->first_name . ' ' . $transaction->account->client->last_name,
                    $transaction->transaction_type,
                    $transaction->amount,
                    $transaction->payment_method,
                    $transaction->status,
                    $transaction->processedBy->first_name ?? 'N/A'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export Excel (placeholder)
     */
    private function exportToExcel($transactions)
    {
        // Nécessite une bibliothèque comme PhpSpreadsheet
        return back()->with('info', 'Export Excel en développement');
    }

    /**
     * Export PDF (placeholder)
     */
    private function exportToPdf($transactions)
    {
        // Nécessite une bibliothèque comme DomPDF
        return back()->with('info', 'Export PDF en développement');
    }
}
