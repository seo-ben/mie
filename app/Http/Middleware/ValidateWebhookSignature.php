<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateWebhookSignature
{
    public function handle(Request $request, Closure $next, $provider)
    {
        switch ($provider) {
            case 'mtn':
                if (!$this->validateMTNSignature($request)) {
                    return response()->json(['error' => 'Invalid signature'], 401);
                }
                break;
            case 'orange':
                if (!$this->validateOrangeSignature($request)) {
                    return response()->json(['error' => 'Invalid signature'], 401);
                }
                break;
        }

        return $next($request);
    }

    private function validateMTNSignature(Request $request)
    {
        $signature = $request->header('X-Signature');
        $payload = $request->getContent();
        $secret = config('services.mtn_momo.webhook_secret');

        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    private function validateOrangeSignature(Request $request)
    {
        // Implémentation spécifique Orange
        return true; // Placeholder
    }
}