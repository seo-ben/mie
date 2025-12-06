<?php

namespace App\Http\Controllers\web\Client;

use App\Http\Controllers\Controller;
use App\Models\TontineAccount;
use App\Models\TontineCycle;
use App\Models\Transaction;
use App\Services\TontineService;
use App\Http\Resources\TontineResource;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ClientTontineController extends Controller
{
    public function __construct(
        private TontineService $tontineService
    ) {}

    /**
     * Liste des tontines du client
     */
    public function index()
    {
        $client = auth()->user();

        $tontines = TontineAccount::whereHas('account', function($query) use ($client) {
                $query->where('client_id', $client->id);
            })
            ->with(['account', 'cycles' => function($query) {
                $query->where('status', 'active')->orWhere('status', 'completed')->latest();
            }])
            ->get()
            ->map(function($tontine) {
                return [
                    'id' => $tontine->id,
                    'account_number' => $tontine->account->account_number,
                    'tontine_amount' => $tontine->tontine_amount,
                    'balance' => $tontine->account->balance,
                    'cycle_duration_months' => $tontine->cycle_duration_months,
                    'current_cycle' => $tontine->current_cycle,
                    'payments_made' => $tontine->payments_made,
                    'total_expected' => $tontine->total_expected,
                    'progress_percentage' => ($tontine->payments_made / ($tontine->total_expected / $tontine->expected_monthly_payment)) * 100,
                    'next_payment_date' => $this->calculateNextPaymentDate($tontine),
                    'status' => $tontine->account->status,
                    'created_at' => $tontine->created_at
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $tontines,
            'summary' => [
                'total_tontines' => $tontines->count(),
                'total_balance' => $tontines->sum('balance'),
                'active_tontines' => $tontines->where('status', 'active')->count()
            ]
        ]);
    }

    /**
     * Détails d'une tontine spécifique
     */
    public function show($tontineId)
    {
        $client = auth()->user();

        $tontine = TontineAccount::whereHas('account', function($query) use ($client) {
                $query->where('client_id', $client->id);
            })
            ->with(['account.transactions' => function($query) {
                $query->where('transaction_type', 'deposit')
                      ->orWhere('transaction_type', 'payout')
                      ->latest()
                      ->limit(20);
            }, 'cycles'])
            ->findOrFail($tontineId);

        $currentCycle = $tontine->cycles->where('status', 'active')->first();
        $completedCycles = $tontine->cycles->where('status', 'completed');

        $details = [
            'tontine' => [
                'id' => $tontine->id,
                'account_number' => $tontine->account->account_number,
                'tontine_amount' => $tontine->tontine_amount,
                'balance' => $tontine->account->balance,
                'cycle_duration_months' => $tontine->cycle_duration_months,
                'payment_frequency' => $tontine->payment_frequency,
                'expected_monthly_payment' => $tontine->expected_monthly_payment,
                'total_expected' => $tontine->total_expected,
                'total_paid' => $tontine->total_paid,
                'payments_made' => $tontine->payments_made,
                'penalty_amount' => $tontine->total_penalties
            ],
            'current_cycle' => $currentCycle ? [
                'cycle_number' => $currentCycle->cycle_number,
                'start_date' => $currentCycle->start_date,
                'end_date' => $currentCycle->end_date,
                'target_amount' => $currentCycle->target_amount,
                'collected_amount' => $currentCycle->collected_amount,
                'progress_percentage' => ($currentCycle->collected_amount / $currentCycle->target_amount) * 100,
                'days_remaining' => Carbon::parse($currentCycle->end_date)->diffInDays(now()),
                'status' => $currentCycle->status
            ] : null,
            'payment_schedule' => $this->generatePaymentSchedule($tontine, $currentCycle),
            'recent_transactions' => $tontine->account->transactions,
            'statistics' => [
                'total_cycles_completed' => $completedCycles->count(),
                'total_payouts_received' => $completedCycles->sum('payout_amount'),
                'average_monthly_contribution' => $tontine->total_paid / max($tontine->payments_made, 1),
                'on_time_payment_rate' => $this->calculateOnTimeRate($tontine)
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $details
        ]);
    }

    /**
     * Cycles d'une tontine
     */
    public function cycles($tontineId)
    {
        $client = auth()->user();

        $tontine = TontineAccount::whereHas('account', function($query) use ($client) {
                $query->where('client_id', $client->id);
            })->findOrFail($tontineId);

        $cycles = TontineCycle::where('tontine_account_id', $tontineId)
            ->orderBy('cycle_number', 'desc')
            ->get()
            ->map(function($cycle) {
                return [
                    'id' => $cycle->id,
                    'cycle_number' => $cycle->cycle_number,
                    'start_date' => $cycle->start_date->format('d/m/Y'),
                    'end_date' => $cycle->end_date->format('d/m/Y'),
                    'target_amount' => $cycle->target_amount,
                    'collected_amount' => $cycle->collected_amount,
                    'payout_amount' => $cycle->payout_amount,
                    'payout_date' => $cycle->payout_date ? $cycle->payout_date->format('d/m/Y') : null,
                    'status' => $cycle->status,
                    'status_label' => $this->getCycleStatusLabel($cycle->status),
                    'completion_rate' => ($cycle->collected_amount / $cycle->target_amount) * 100
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'tontine' => [
                    'id' => $tontine->id,
                    'tontine_amount' => $tontine->tontine_amount,
                    'current_cycle' => $tontine->current_cycle
                ],
                'cycles' => $cycles,
                'current_cycle' => $cycles->where('status', 'active')->first(),
                'completed_cycles_count' => $cycles->where('status', 'completed')->count(),
                'total_payouts' => $cycles->where('status', 'completed')->sum('payout_amount')
            ]
        ]);
    }

    /**
     * Effectuer un paiement de cotisation tontine
     */
    public function payment(Request $request, $tontineId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:mobile_money,bank_transfer,cash',
            'payment_reference' => 'nullable|string',
            'mobile_money_operator' => 'required_if:payment_method,mobile_money'
        ]);

        try {
            $client = auth()->user();
            $tontine = TontineAccount::whereHas('account', function($query) use ($client) {
                    $query->where('client_id', $client->id);
                })->findOrFail($tontineId);

            // Vérifier que le montant correspond au montant attendu
            if ($request->get('amount') < $tontine->expected_monthly_payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le montant minimum de cotisation est de ' . number_format($tontine->expected_monthly_payment, 0) . ' FCFA'
                ], 422);
            }

            $result = $this->tontineService->processTontinePayment(
                $tontineId,
                $request->get('amount'),
                $request->get('payment_method'),
                $request->only(['payment_reference', 'mobile_money_operator'])
            );

            return response()->json([
                'success' => true,
                'message' => 'Paiement de cotisation initié avec succès',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du paiement de cotisation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculateur de projection tontine
     */
    public function projection(Request $request, $tontineId)
    {
        $client = auth()->user();
        $tontine = TontineAccount::whereHas('account', function($query) use ($client) {
                $query->where('client_id', $client->id);
            })->findOrFail($tontineId);

        $projection = $this->calculateProjection($tontine);

        return response()->json([
            'success' => true,
            'data' => $projection
        ]);
    }

    /**
     * Historique des redistributions
     */
    public function payouts($tontineId)
    {
        $client = auth()->user();
        $tontine = TontineAccount::whereHas('account', function($query) use ($client) {
                $query->where('client_id', $client->id);
            })->findOrFail($tontineId);

        $payouts = TontineCycle::where('tontine_account_id', $tontineId)
            ->where('status', 'completed')
            ->whereNotNull('payout_date')
            ->orderBy('payout_date', 'desc')
            ->get()
            ->map(function($cycle) {
                return [
                    'cycle_number' => $cycle->cycle_number,
                    'payout_date' => $cycle->payout_date->format('d/m/Y'),
                    'amount_contributed' => $cycle->collected_amount,
                    'payout_received' => $cycle->payout_amount,
                    'benefit' => $cycle->payout_amount - $cycle->collected_amount,
                    'benefit_percentage' => (($cycle->payout_amount - $cycle->collected_amount) / $cycle->collected_amount) * 100
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'payouts' => $payouts,
                'total_payouts_received' => $payouts->sum('payout_received'),
                'total_benefits' => $payouts->sum('benefit'),
                'average_benefit_rate' => $payouts->avg('benefit_percentage')
            ]
        ]);
    }

    /**
     * Rappel de cotisation
     */
    public function paymentReminder($tontineId)
    {
        try {
            $client = auth()->user();
            $tontine = TontineAccount::whereHas('account', function($query) use ($client) {
                    $query->where('client_id', $client->id);
                })->findOrFail($tontineId);
            $this->tontineService->sendPaymentReminder($tontineId);
            return response()->json([
                'success' => true,
                'message' => 'Rappel de cotisation envoyé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du rappel de cotisation',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Calcul de la date du prochain paiement
     */
    private function calculateNextPaymentDate($tontine)
    {
        if ($tontine->payments_made >= ($tontine->cycle_duration_months * (30 / $tontine->payment_frequency))) {
            return null; // Tous les paiements ont été effectués
        }
        $lastPaymentDate = Transaction::where('tontine_account_id', $tontine->id)
            ->where('transaction_type', 'deposit')
            ->latest()
            ->value('created_at');
        if (!$lastPaymentDate) {
            return Carbon::parse($tontine->created_at)->addDays($tontine->payment_frequency);
        }
        return Carbon::parse($lastPaymentDate)->addDays($tontine->payment_frequency);
    }
    /**
     * Génération du calendrier de paiement
     */
    private function generatePaymentSchedule($tontine, $currentCycle)
    {
        $schedule = [];
        if (!$currentCycle) {
            return $schedule;
        }
        $totalPayments = $tontine->cycle_duration_months * (30 / $tontine->payment_frequency);
        $startDate = Carbon::parse($currentCycle->start_date);
        for ($i = 0; $i < $totalPayments; $i++) {
            $dueDate = $startDate->copy()->addDays($i * $tontine->payment_frequency);
            $isPaid = Transaction::where('tontine_account_id', $tontine->id)
                ->where('transaction_type', 'deposit')
                ->whereDate('created_at', '<=', $dueDate)
                ->exists();
            $schedule[] = [
                'due_date' => $dueDate->format('d/m/Y'),
                'amount' => $tontine->expected_monthly_payment,
                'status' => $isPaid ? 'paid' : (now()->greaterThan($dueDate) ? 'overdue' : 'upcoming')
            ];
        }
        return $schedule;
    }
    /**
     * Calcul du taux de paiement à temps
     */
    private function calculateOnTimeRate($tontine)
    {
        $totalPayments = $tontine->payments_made;
        if ($totalPayments == 0) {
            return 0;
        }
        $onTimePayments = Transaction::where('tontine_account_id', $tontine->id)
            ->where('transaction_type', 'deposit')
            ->whereRaw('DATE(created_at) <= DATE_ADD(DATE(created_at), INTERVAL ' . $tontine->payment_frequency . ' DAY)')
            ->count();
        return ($onTimePayments / $totalPayments) * 100;
    }
    /**
     * Calcul de la projection financière
     */
    private function calculateProjection($tontine)
    {
        $monthlyContribution = $tontine->expected_monthly_payment;
        $totalMonths = $tontine->cycle_duration_months;
        $totalContributed = $monthlyContribution * $totalMonths;
        $estimatedBenefits = $totalContributed * 0.1; // Supposons un bénéfice de 10%
        $projection = [
            'monthly_contribution' => $monthlyContribution,
            'total_contribution' => $totalContributed,
            'estimated_benefits' => $estimatedBenefits,
            'total_projection' => $totalContributed + $estimatedBenefits,
            'end_date' => Carbon::parse($tontine->created_at)->addMonths($totalMonths)->format('d/m/Y')
        ];
        return $projection;
    }
    /**
     * Obtenir le label de statut du cycle
     */
    private function getCycleStatusLabel($status)
    {
        return match ($status) {
            'active' => 'En cours',
            'completed' => 'Terminé',
            'pending' => 'En attente',
            default => 'Inconnu',
        };
    }
}
