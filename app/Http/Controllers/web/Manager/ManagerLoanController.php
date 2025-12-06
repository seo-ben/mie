<?php

namespace App\Http\Controllers\web\Manager;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Services\ManagerLoanService;
use App\Http\Resources\LoanResource;
use Illuminate\Http\Request;

class ManagerLoanController extends Controller
{
    public function __construct(
        private ManagerLoanService $loanService
    ) {}

    /**
     * Demandes de prêt en attente
     */
    public function pending(Request $request)
    {
        $user = auth()->user();

        $pendingLoans = Loan::whereHas('client', function($query) use ($user) {
                $query->where('agency_id', $user->agency_id);
            })
            ->whereIn('status', ['pending', 'under_review'])
            ->with(['client'])
            ->when($request->get('risk_level'), function($query, $riskLevel) {
                $query->where('risk_level', $riskLevel);
            })
            ->when($request->get('amount_range'), function($query, $range) {
                switch($range) {
                    case 'small':
                        $query->where('requested_amount', '<=', 100000);
                        break;
                    case 'medium':
                        $query->whereBetween('requested_amount', [100001, 500000]);
                        break;
                    case 'large':
                        $query->where('requested_amount', '>', 500000);
                        break;
                }
            })
            ->orderBy('application_date')
            ->paginate($request->get('per_page', 15));

        return LoanResource::collection($pendingLoans);
    }

    /**
     * Détails d'une demande de prêt
     */
    public function show($loanId)
    {
        $user = auth()->user();

        $loan = Loan::whereHas('client', function($query) use ($user) {
                $query->where('agency_id', $user->agency_id);
            })
            ->with(['client.accounts', 'client.documents'])
            ->findOrFail($loanId);

        return response()->json([
            'success' => true,
            'data' => [
                'loan' => new LoanResource($loan),
                'client_summary' => $this->loanService->getClientSummary($loan->client_id),
                'risk_analysis' => $this->loanService->getRiskAnalysis($loanId),
                'recommendations' => $this->loanService->getRecommendations($loanId)
            ]
        ]);
    }

    /**
     * Approuver un prêt
     */
    public function approve(Request $request, $loanId)
    {
        $request->validate([
            'approved_amount' => 'required|numeric|min:1',
            'conditions' => 'nullable|array',
            'comment' => 'nullable|string|max:500'
        ]);

        try {
            $user = auth()->user();

            $loan = Loan::whereHas('client', function($query) use ($user) {
                $query->where('agency_id', $user->agency_id);
            })->findOrFail($loanId);

            $result = $this->loanService->approveLoan(
                $loanId,
                $request->get('approved_amount'),
                $user->id,
                $request->get('conditions', []),
                $request->get('comment')
            );

            return response()->json([
                'success' => true,
                'message' => 'Prêt approuvé avec succès',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'approbation du prêt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rejeter un prêt
     */
    public function reject(Request $request, $loanId)
    {
        $request->validate([
            'reasons' => 'required|array|min:1',
            'reasons.*' => 'string',
            'comment' => 'nullable|string|max:500'
        ]);

        try {
            $user = auth()->user();

            $loan = Loan::whereHas('client', function($query) use ($user) {
                $query->where('agency_id', $user->agency_id);
            })->findOrFail($loanId);

            $result = $this->loanService->rejectLoan(
                $loanId,
                $user->id,
                $request->get('reasons'),
                $request->get('comment')
            );

            return response()->json([
                'success' => true,
                'message' => 'Prêt rejeté avec succès',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rejet du prêt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Analyse détaillée d'un prêt
     */
    public function analysis($loanId)
    {
        $user = auth()->user();

        $loan = Loan::whereHas('client', function($query) use ($user) {
            $query->where('agency_id', $user->agency_id);
        })->findOrFail($loanId);

        $analysis = $this->loanService->getDetailedAnalysis($loanId);

        return response()->json([
            'success' => true,
            'data' => $analysis
        ]);
    }

    /**
     * Décaisser un prêt approuvé
     */
    public function disburse(Request $request, $loanId)
    {
        $request->validate([
            'disbursement_method' => 'required|in:cash,bank_transfer,mobile_money',
            'disbursement_reference' => 'nullable|string',
            'disbursement_notes' => 'nullable|string|max:500'
        ]);

        try {
            $user = auth()->user();

            $loan = Loan::whereHas('client', function($query) use ($user) {
                $query->where('agency_id', $user->agency_id);
            })->findOrFail($loanId);

            $result = $this->loanService->disburseLoan(
                $loanId,
                $user->id,
                $request->only(['disbursement_method', 'disbursement_reference', 'disbursement_notes'])
            );

            return response()->json([
                'success' => true,
                'message' => 'Prêt décaissé avec succès',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du décaissement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques des prêts de l'agence
     */
    public function statistics(Request $request)
    {
        $user = auth()->user();
        $period = $request->get('period', '30d');

        $stats = $this->loanService->getLoanStatistics($user->agency_id, $period);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Pipeline des demandes de prêt
     */
    public function pipeline()
    {
        $user = auth()->user();

        $pipeline = $this->loanService->getLoanPipeline($user->agency_id);

        return response()->json([
            'success' => true,
            'data' => $pipeline
        ]);
    }
}
