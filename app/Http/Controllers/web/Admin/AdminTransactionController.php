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

        $user = auth()->user();

        // Filtrage par Agence (Isolation Multi-Agence)
        if (
            $user->role !== 'administrateur_systeme' &&
            $user->role !== 'administrateur_reglementaire'
        ) {
            $query->whereHas('account.client', function ($q) use ($user) {
                $q->where('agency_id', $user->agency_id);
            });
        }

        // Filtres
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_reference', 'like', "%{$search}%")
                  ->orWhere('payment_reference', 'like', "%{$search}%")
                  ->orWhereHas('account', function ($q2) use ($search) {
                      $q2->where('account_number', 'like', "%{$search}%")
                         ->orWhereHas('client', function ($q3) use ($search) {
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
        $sortBy    = $request->get('sort_by', 'transaction_date');
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
     * Détails d'une transaction
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
                'success'     => true,
                'transaction' => $transaction,
                'html'        => view('admin.transactions.partials.detail', compact('transaction'))->render()
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
                $startDate = $request->filled('start_date')
                    ? Carbon::parse($request->start_date)
                    : now()->subDays(30);
                break;
            default:
                $startDate = now()->subDays(30);
        }

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : now();

        $analytics = [
            'volume'             => $this->getVolumeStats($startDate, $endDate),
            'by_type'            => $this->getTransactionsByType($startDate, $endDate),
            'by_payment_method'  => $this->getTransactionsByPaymentMethod($startDate, $endDate),
            'timeline'           => $this->getTimelineData($startDate, $endDate),
            'top_clients'        => $this->getTopClients($startDate, $endDate),
            'success_rate'       => $this->getSuccessRate($startDate, $endDate),
            'by_hour'            => $this->getTransactionsByHour($startDate, $endDate),
            'comparison'         => $this->getPeriodComparison($startDate, $endDate),
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
                'status'       => 'completed',
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
                'status'           => 'rejected',
                'rejection_reason' => $request->reason,
                'validated_by'     => auth()->id(),
                'validated_at'     => now(),
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

    /**
     * Génère un reçu numérique sécurisé avec QR Code
     *
     * CORRECTIONS :
     * - Vérification que account et client ne sont pas null avant d'y accéder
     * - transaction_date casté en Carbon via Carbon::parse() pour éviter
     *   l'erreur "Call to undefined method on null" si le champ n'est pas
     *   automatiquement casté dans le modèle
     * - Suppression de l'import inutilisé SimpleSoftwareIO\QrCode (le QR est
     *   généré côté vue via la variable $qrData)
     */
    public function generateReceipt($id)
    {
        $transaction = Transaction::with([
            'account.client',
            'processedBy',
            'validatedBy'
        ])->findOrFail($id);

        // Sécurité : vérifier que les relations sont bien chargées
        if (! $transaction->account || ! $transaction->account->client) {
            abort(404, 'Données du compte ou du client introuvables pour cette transaction.');
        }

        // CORRECTION : cast explicite en Carbon pour éviter l'erreur si le champ
        // n'est pas déclaré dans $casts du modèle Transaction
        $transactionDate = $transaction->transaction_date
            ? Carbon::parse($transaction->transaction_date)
            : now();

        // Données pour le QR Code (vérification d'authenticité)
        $qrData = json_encode([
            'ref'    => $transaction->transaction_reference,
            'amount' => $transaction->amount,
            'client' => $transaction->account->client->full_name,
            'date'   => $transactionDate->toIso8601String(),
            'status' => 'CERTIFIÉ PAR LE PROTOCOLE SYSTÈME',
        ]);

        return view('admin.transactions.receipt', compact('transaction', 'qrData'));
    }

    // =============== MÉTHODES PRIVÉES ===============

    /**
     * Statistiques globales
     *
     * CORRECTION : L'ancienne version réutilisait la même variable $query
     * après des where() successifs, ce qui cumulait les conditions et faussait
     * les totaux (ex: total_deposits comptait aussi le filtre status=completed
     * appliqué juste avant). Chaque stat a maintenant sa propre query isolée.
     */
    private function getGlobalStats($request)
    {
        // Closure pour créer une query de base avec les filtres de date
        $base = function () use ($request) {
            $q = Transaction::query();
            if ($request->filled('date_from')) {
                $q->whereDate('transaction_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $q->whereDate('transaction_date', '<=', $request->date_to);
            }
            return $q;
        };

        return [
            'total_transactions' => $base()->count(),
            'total_amount'       => $base()->where('status', 'completed')->sum('amount'),
            'total_deposits'     => $base()->where('transaction_type', 'deposit')
                                           ->where('status', 'completed')
                                           ->sum('amount'),
            'total_withdrawals'  => $base()->where('transaction_type', 'withdrawal')
                                           ->where('status', 'completed')
                                           ->sum('amount'),
            'pending_count'      => $base()->where('status', 'pending')->count(),
            'pending_amount'     => $base()->where('status', 'pending')->sum('amount'),
            'completed_count'    => $base()->where('status', 'completed')->count(),
            'failed_count'       => $base()->where('status', 'failed')->count(),
            'average_transaction'=> $base()->where('status', 'completed')->avg('amount'),
            'today_transactions' => Transaction::whereDate('transaction_date', today())->count(),
            'today_amount'       => Transaction::whereDate('transaction_date', today())
                                               ->where('status', 'completed')
                                               ->sum('amount'),
        ];
    }

    /**
     * Données pour les graphiques
     */
    private function getChartData($request)
    {
        $startDate = now()->subDays(30);

        $dailyTransactions = Transaction::where('transaction_date', '>=', $startDate)
            ->where('status', 'completed')
            ->selectRaw('DATE(transaction_date) as date, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $byType = Transaction::where('status', 'completed')
            ->selectRaw('transaction_type, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('transaction_type')
            ->get();

        $byMethod = Transaction::where('status', 'completed')
            ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('payment_method')
            ->get();

        return [
            'daily'     => $dailyTransactions,
            'by_type'   => $byType,
            'by_method' => $byMethod,
        ];
    }

    /**
     * Volume des transactions
     */
    private function getVolumeStats($startDate, $endDate)
    {
        return [
            'total'  => Transaction::whereBetween('transaction_date', [$startDate, $endDate])
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
        $days          = $startDate->diffInDays($endDate);
        $previousStart = $startDate->copy()->subDays($days);
        $previousEnd   = $startDate->copy();

        $current = [
            'count'  => Transaction::whereBetween('transaction_date', [$startDate, $endDate])
                ->where('status', 'completed')->count(),
            'amount' => Transaction::whereBetween('transaction_date', [$startDate, $endDate])
                ->where('status', 'completed')->sum('amount'),
        ];

        $previous = [
            'count'  => Transaction::whereBetween('transaction_date', [$previousStart, $previousEnd])
                ->where('status', 'completed')->count(),
            'amount' => Transaction::whereBetween('transaction_date', [$previousStart, $previousEnd])
                ->where('status', 'completed')->sum('amount'),
        ];

        return [
            'current'       => $current,
            'previous'      => $previous,
            'count_change'  => $previous['count'] > 0
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
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Référence',
                'Date',
                'Client',
                'Type',
                'Montant',
                'Méthode',
                'Statut',
                'Traité par',
            ]);

            foreach ($transactions as $transaction) {
                // CORRECTION : null-safe sur account et client pour éviter
                // une erreur fatale si une transaction orpheline existe en base
                $clientName = $transaction->account && $transaction->account->client
                    ? $transaction->account->client->first_name . ' ' . $transaction->account->client->last_name
                    : 'N/A';

                $transactionDate = $transaction->transaction_date
                    ? Carbon::parse($transaction->transaction_date)->format('d/m/Y H:i')
                    : 'N/A';

                fputcsv($file, [
                    $transaction->transaction_reference,
                    $transactionDate,
                    $clientName,
                    $transaction->transaction_type,
                    $transaction->amount,
                    $transaction->payment_method,
                    $transaction->status,
                    $transaction->processedBy->first_name ?? 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export Excel
     */
    private function exportToExcel($transactions)
    {
        return \Excel::download(
            new \App\Exports\TransactionExport($transactions),
            'transactions_' . date('Y-m-d_His') . '.xlsx'
        );
    }

    /**
     * Export PDF
     */
    private function exportToPdf($transactions)
    {
        return back()->with('info', 'Export PDF en développement');
    }
}