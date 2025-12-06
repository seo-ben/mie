<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Transaction;
use App\Models\SystemParameter;

class MobileMoneyService
{
    private $operators = ['MTN', 'Orange', 'Moov'];

    public function initiatePayment($phoneNumber, $amount, $operator, $transactionId)
    {
        switch (strtoupper($operator)) {
            case 'MTN':
                return $this->initiateMTNPayment($phoneNumber, $amount, $transactionId);
            case 'ORANGE':
                return $this->initiateOrangePayment($phoneNumber, $amount, $transactionId);
            case 'MOOV':
                return $this->initiateMoovPayment($phoneNumber, $amount, $transactionId);
            default:
                throw new \Exception('Opérateur non supporté');
        }
    }

    private function initiateMTNPayment($phoneNumber, $amount, $transactionId)
    {
        $apiKey = config('services.mtn_momo.api_key');
        $subscriptionKey = config('services.mtn_momo.subscription_key');
        
        $response = Http::withHeaders([
            'X-Reference-Id' => $transactionId,
            'X-Target-Environment' => config('services.mtn_momo.environment'),
            'Ocp-Apim-Subscription-Key' => $subscriptionKey,
            'Authorization' => 'Bearer ' . $this->getMTNToken(),
            'Content-Type' => 'application/json'
        ])->post('https://sandbox.momodeveloper.mtn.com/collection/v1_0/requesttopay', [
            'amount' => (string) $amount,
            'currency' => 'XOF',
            'externalId' => $transactionId,
            'payer' => [
                'partyIdType' => 'MSISDN',
                'partyId' => $phoneNumber
            ],
            'payerMessage' => 'Paiement MIE Microfinance',
            'payeeNote' => 'Frais activation compte'
        ]);

        return [
            'success' => $response->successful(),
            'transaction_id' => $transactionId,
            'status' => $response->successful() ? 'pending' : 'failed',
            'message' => $response->successful() ? 'Paiement initié' : 'Erreur lors du paiement',
            'raw_response' => $response->json()
        ];
    }

    private function initiateOrangePayment($phoneNumber, $amount, $transactionId)
    {
        $clientId = config('services.orange_money.client_id');
        $clientSecret = config('services.orange_money.client_secret');
        
        // Obtenir le token d'accès
        $tokenResponse = Http::asForm()->post('https://api.orange.com/oauth/v3/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $clientId,
            'client_secret' => $clientSecret
        ]);

        if (!$tokenResponse->successful()) {
            return ['success' => false, 'message' => 'Erreur d\'authentification Orange'];
        }

        $token = $tokenResponse->json()['access_token'];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json'
        ])->post('https://api.orange.com/orange-money-webpay/tg/v1/webpayment', [
            'merchant_key' => config('services.orange_money.merchant_key'),
            'currency' => 'XOF',
            'order_id' => $transactionId,
            'amount' => $amount,
            'return_url' => config('app.url') . '/api/v1/callback/orange',
            'cancel_url' => config('app.url') . '/api/v1/callback/orange/cancel',
            'notif_url' => config('app.url') . '/api/v1/webhook/orange',
            'lang' => 'fr',
            'reference' => 'MIE-' . $transactionId
        ]);

        return [
            'success' => $response->successful(),
            'payment_url' => $response->json()['payment_url'] ?? null,
            'transaction_id' => $transactionId,
            'status' => $response->successful() ? 'pending' : 'failed'
        ];
    }

    public function checkPaymentStatus($transaction)
    {
        if (!$transaction->payment_reference) {
            return false;
        }

        $operator = $this->detectOperator($transaction->mobile_money_operator);
        
        switch ($operator) {
            case 'MTN':
                return $this->checkMTNStatus($transaction);
            case 'Orange':
                return $this->checkOrangeStatus($transaction);
            case 'Moov':
                return $this->checkMoovStatus($transaction);
        }

        return false;
    }

    private function checkMTNStatus($transaction)
    {
        $response = Http::withHeaders([
            'X-Target-Environment' => config('services.mtn_momo.environment'),
            'Ocp-Apim-Subscription-Key' => config('services.mtn_momo.subscription_key'),
            'Authorization' => 'Bearer ' . $this->getMTNToken()
        ])->get('https://sandbox.momodeveloper.mtn.com/collection/v1_0/requesttopay/' . $transaction->payment_reference);

        if ($response->successful()) {
            $data = $response->json();
            $status = strtolower($data['status']);
            
            if ($status === 'successful') {
                $transaction->update(['status' => 'completed']);
                return true;
            } elseif ($status === 'failed') {
                $transaction->update(['status' => 'failed']);
            }
        }

        return false;
    }

    private function getMTNToken()
    {
        // Cache le token pendant 1 heure
        return cache()->remember('mtn_token', 3600, function () {
            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => config('services.mtn_momo.subscription_key')
            ])->post('https://sandbox.momodeveloper.mtn.com/collection/token/', [
                'grant_type' => 'client_credentials'
            ]);

            return $response->json()['access_token'];
        });
    }

    private function detectOperator($phoneNumber)
    {
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Préfixes Togo
        $prefixes = [
            'MTN' => ['90', '91', '96', '97'],
            'Moov' => ['93', '94', '95', '98', '99'],
            'Orange' => ['92']  // À vérifier
        ];

        $prefix = substr($phoneNumber, -8, 2);
        
        foreach ($prefixes as $operator => $operatorPrefixes) {
            if (in_array($prefix, $operatorPrefixes)) {
                return $operator;
            }
        }

        return 'Unknown';
    }
}