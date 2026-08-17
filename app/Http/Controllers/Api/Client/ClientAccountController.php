<?php
namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccountResource;
use App\Http\Requests\CreateAccountRequest;
use App\Services\AccountService;
use Illuminate\Http\Request;

class ClientAccountController extends Controller
{
    public function __construct(
        private AccountService $accountService
    ) {}

    /**
     * List all client accounts
     */
    public function index(Request $request)
    {
        $client = auth()->user()->client ?? auth()->user();
        $accounts = $client->accounts()
            ->with(['savingsAccount', 'tontineAccount'])
            ->get();

        return AccountResource::collection($accounts);
    }

    /**
     * Create new account
     */
    public function store(CreateAccountRequest $request)
    {
        $client = auth()->user()->client ?? auth()->user();
        
        // Vérification KYC pour les comptes épargne
        if ($request->account_type === 'savings' && $client->kyc_status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Le client doit avoir un KYC approuvé pour ouvrir un compte d\'épargne.'
            ], 403);
        }

        $account = $this->accountService->createAccount(
            $client->id, 
            $request->validated()
        );

        return new AccountResource($account);
    }

    /**
     * Show specific account
     */
    public function show(Request $request, $accountId)
    {
        $client = auth()->user()->client ?? auth()->user();
        
        $account = $client->accounts()
            ->with(['transactions' => function($query) {
                $query->latest()->limit(20);
            }])
            ->findOrFail($accountId);

        return new AccountResource($account);
    }

    /**
     * Get account balance history
     */
    public function balanceHistory(Request $request, $accountId)
    {
        $client = auth()->user()->client ?? auth()->user();
        $account = $client->accounts()->findOrFail($accountId);
        
        $period = $request->get('period', '30'); // 7, 30, 90 days
        $history = $this->accountService->getBalanceHistory($account->id, $period);

        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }

    /**
     * Activate account after payment
     */
    public function activate(Request $request, $accountId)
    {
        $client = auth()->user()->client ?? auth()->user();
        $account = $client->accounts()->findOrFail($accountId);

        $result = $this->accountService->activateAccount(
            $account->id, 
            $request->all()
        );

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result['data'] ?? null
        ]);
    }
}
