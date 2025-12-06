<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MobileQuickActionsController extends Controller
{
    public function quickDeposit(Request $request): JsonResponse
    {
        $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:100',
            'payment_method' => 'required|in:cash,mobile_money',
            'payment_reference' => 'required_if:payment_method,mobile_money'
        ]);

        $result = $this->quickActionService->processQuickDeposit($request->all());
        
        return response()->json($result);
    }

    public function quickPayment(Request $request): JsonResponse
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'amount' => 'required|numeric|min:100',
            'payment_type' => 'required|in:loan,tontine',
            'reference_id' => 'required|exists:loans,id|exists:tontine_accounts,id'
        ]);

        $result = $this->quickActionService->processQuickPayment($request->all());
        
        return response()->json($result);
    }
}