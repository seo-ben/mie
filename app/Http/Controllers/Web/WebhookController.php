<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\MobileMoneyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private MobileMoneyService $mobileMoneyService
    ) {}

    /**
     * Webhook MTN Mobile Money
     */
    public function mtnCallback(Request $request)
    {
        Log::info('MTN Webhook received', $request->all());

        $transactionId = $request->input('externalId');
        $status = $request->input('status');

        if (!$transactionId) {
            return response()->json(['status' => 'error', 'message' => 'Transaction ID missing'], 400);
        }

        $transaction = Transaction::where('payment_reference', $transactionId)->first();
        
        if (!$transaction) {
            Log::warning('Transaction not found for MTN callback', ['transaction_id' => $transactionId]);
            return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
        }

        switch (strtolower($status)) {
            case 'successful':
                $this->completeTransaction($transaction);
                break;
            case 'failed':
                $transaction->update(['status' => 'failed']);
                break;
            case 'pending':
                // Garde le statut pending
                break;
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Webhook Orange Money
     */
    public function orangeCallback(Request $request)
    {
        Log::info('Orange Webhook received', $request->all());

        $orderId = $request->input('order_id');
        $status = $request->input('status');

        $transaction = Transaction::where('payment_reference', $orderId)->first();
        
        if (!$transaction) {
            return response()->json(['status' => 'error'], 404);
        }

        if ($status === 'SUCCESS') {
            $this->completeTransaction($transaction);
        } elseif ($status === 'FAILED') {
            $transaction->update(['status' => 'failed']);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Webhook Moov Money
     */
    public function moovCallback(Request $request)
    {
        // Similaire aux autres webhooks
        Log::info('Moov Webhook received', $request->all());
        return response()->json(['status' => 'success']);
    }

    private function completeTransaction($transaction)
    {
        try {
            $transaction->update(['status' => 'completed']);
            
            // Mettre à jour le solde du compte
            $account = $transaction->account;
            $newBalance = $account->balance + $transaction->amount;
            
            $account->update([
                'balance' => $newBalance,
                'last_transaction_at' => now()
            ]);

            // Déclencher les notifications
            // dispatch(new SendNotificationJob(...));

            Log::info('Transaction completed successfully', [
                'transaction_id' => $transaction->id,
                'amount' => $transaction->amount
            ]);

        } catch (\Exception $e) {
            Log::error('Error completing transaction', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}