<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\StaffPayment;
use App\Models\CashierSession;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminPayrollController extends Controller
{
    /**
     * Liste du personnel et état des paiements
     */
    public function index(Request $request)
    {
        $query = User::whereNotIn('role', ['client']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                  ->orWhere('last_name', 'like', "%$search%")
                  ->orWhere('username', 'like', "%$search%");
            });
        }

        $staffMembers = $query->with(['agency'])->paginate(15);

        // Statistiques du mois en cours
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        $stats = [
            'total_staff' => User::whereNotIn('role', ['client'])->count(),
            'total_paid_this_month' => StaffPayment::whereBetween('payment_date', [$monthStart, $monthEnd])
                ->where('status', 'paid')
                ->sum('amount'),
            'pending_salaries' => User::whereNotIn('role', ['client'])
                ->whereNotNull('base_salary')
                ->count(), // Simplification
        ];

        return view('admin.payroll.index', compact('staffMembers', 'stats'));
    }

    /**
     * Formulaire de paiement pour un membre du personnel
     */
    public function createPayment(User $user)
    {
        $activeSession = CashierSession::where('user_id', Auth::id())
            ->where('status', 'open')
            ->first();

        return view('admin.payroll.create_payment', compact('user', 'activeSession'));
    }

    /**
     * Enregistrer un paiement
     */
    public function storePayment(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'payment_type' => 'required|string',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $staff = User::findOrFail($request->user_id);
        $user = Auth::user();

        try {
            DB::beginTransaction();

            $cashierSessionId = null;
            $transactionRef = 'PAY-' . strtoupper(uniqid());

            // Si paiement en espèces, vérifier la session de caisse
            if ($request->payment_method === 'cash') {
                $activeSession = CashierSession::where('user_id', $user->id)
                    ->where('status', 'open')
                    ->first();

                if (!$activeSession) {
                    throw new \Exception("Une session de caisse ouverte est requise pour les paiements en espèces.");
                }

                // Vérifier le solde disponible (Optionnel selon les règles métiers)
                // $expectedBalance = $activeSession->opening_balance + $activeSession->total_deposits - $activeSession->total_withdrawals;
                // if ($expectedBalance < $request->amount) {
                //     throw new \Exception("Solde de caisse insuffisant.");
                // }

                $cashierSessionId = $activeSession->id;

                // Créer une transaction de sortie de caisse
                Transaction::create([
                    'agency_id' => $user->agency_id,
                    'cashier_session_id' => $cashierSessionId,
                    'transaction_type' => 'withdrawal', // On utilise withdrawal comme type générique de sortie
                    'amount' => $request->amount,
                    'transaction_date' => now(),
                    'status' => 'completed',
                    'description' => "Paiement Personnel : " . $staff->full_name . " (" . $request->payment_type . ")",
                    'reference' => $transactionRef,
                ]);
            }

            // Créer l'enregistrement de paiement
            StaffPayment::create([
                'user_id' => $staff->id,
                'amount' => $request->amount,
                'payment_date' => now(),
                'payment_type' => $request->payment_type,
                'payment_method' => $request->payment_method,
                'cashier_session_id' => $cashierSessionId,
                'processed_by' => $user->id,
                'status' => 'paid',
                'transaction_reference' => $transactionRef,
                'notes' => $request->notes,
            ]);

            DB::commit();

            return redirect()->route('admin.payroll.index')
                ->with('success', "Paiement de " . number_format($request->amount, 0, ',', ' ') . " XOF enregistré pour " . $staff->full_name);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', "Erreur lors du paiement : " . $e->getMessage())->withInput();
        }
    }

    /**
     * Rapport des paiements
     */
    public function report(Request $request)
    {
        $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        $payments = StaffPayment::with(['staff', 'processor', 'cashierSession'])
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->latest()
            ->paginate(30);

        $totals = StaffPayment::whereBetween('payment_date', [$startDate, $endDate])
            ->select('payment_type', DB::raw('SUM(amount) as total'))
            ->groupBy('payment_type')
            ->get();

        return view('admin.payroll.report', compact('payments', 'totals', 'startDate', 'endDate'));
    }
}
