<?php
namespace App\Http\Controllers\web\Client;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Services\LoanService;
use App\Http\Requests\LoanApplicationRequest;
use App\Http\Resources\LoanResource;
use Illuminate\Http\Request;

class ClientLoanController extends Controller
{
    public function __construct(
        private LoanService $loanService
    ) {}

    /**
     * Liste des prêts du client
     */
    public function index(Request $request)
    {
        $client = auth()->user()->client ?? auth()->user();

        $loans = $client->loans()
            ->with(['payments'])
            ->when($request->get('status'), function($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('application_date', 'desc')
            ->paginate($request->get('per_page', 10));

        return LoanResource::collection($loans);
    }

    /**
     * Créer une demande de prêt
     */
    public function store(LoanApplicationRequest $request)
    {
        try {
            $client = auth()->user()->client ?? auth()->user();

            // Vérifier l'éligibilité
            $eligibility = $this->loanService->checkEligibility($client->id);
            if (!$eligibility['eligible']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas éligible pour un prêt actuellement',
                    'eligibility' => $eligibility
                ], 422);
            }

            $loan = $this->loanService->createLoanApplication($client->id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Demande de prêt soumise avec succès',
                'data' => new LoanResource($loan)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la demande de prêt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Détails d'un prêt spécifique
     */
    public function show($loanId)
    {
        $client = auth()->user()->client ?? auth()->user();
        $loan = $client->loans()->with(['payments'])->findOrFail($loanId);

        return new LoanResource($loan);
    }

    /**
     * Simuler un prêt
     */
    public function simulate(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10000|max:5000000',
            'duration_months' => 'required|integer|min:6|max:24'
        ]);

        $client = auth()->user()->client ?? auth()->user();

        $simulation = $this->loanService->simulateLoan(
            $request->get('amount'),
            $request->get('duration_months'),
            $client->id
        );

        return response()->json([
            'success' => true,
            'data' => $simulation
        ]);
    }

    /**
     * Échéancier d'un prêt
     */
    public function schedule($loanId)
    {
        $client = auth()->user()->client ?? auth()->user();
        $loan = $client->loans()->findOrFail($loanId);

        $payments = LoanPayment::where('loan_id', $loanId)
            ->orderBy('payment_number')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'loan' => new LoanResource($loan),
                'schedule' => $payments
            ]
        ]);
    }

    /**
     * Effectuer un paiement d'échéance
     */
    public function payment(Request $request, $loanId)
    {
        $request->validate([
            'payment_method' => 'required|in:mobile_money,bank_transfer',
            'amount' => 'required|numeric|min:1',
            'payment_reference' => 'nullable|string',
            'mobile_money_operator' => 'required_if:payment_method,mobile_money'
        ]);

        try {
            $client = auth()->user()->client ?? auth()->user();
            $loan = $client->loans()->findOrFail($loanId);

            $result = $this->loanService->processLoanPayment(
                $loanId,
                $request->get('amount'),
                $request->get('payment_method'),
                $request->only(['payment_reference', 'mobile_money_operator'])
            );

            return response()->json([
                'success' => true,
                'message' => 'Paiement initié avec succès',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifier l'éligibilité aux prêts
     */
    public function eligibility()
    {
        $client = auth()->user()->client ?? auth()->user();
        $eligibility = $this->loanService->checkEligibility($client->id);

        return response()->json([
            'success' => true,
            'data' => $eligibility
        ]);
    }

    /**
     * Historique des paiements
     */
    public function paymentHistory($loanId)
    {
        $client = auth()->user()->client ?? auth()->user();
        $loan = $client->loans()->findOrFail($loanId);

        $payments = LoanPayment::where('loan_id', $loanId)
            ->where('paid_amount', '>', 0)
            ->orderBy('paid_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }
}
