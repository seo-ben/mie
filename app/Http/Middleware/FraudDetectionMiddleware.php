<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class FraudDetectionMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $userId = auth()->id();
        $ip = $request->ip();
        
        // Détection de tentatives multiples
        if ($this->detectSuspiciousActivity($userId, $ip, $request->path())) {
            return response()->json([
                'error' => 'Activité suspecte détectée',
                'code' => 'FRAUD_DETECTED'
            ], 429);
        }

        $response = $next($request);

        // Logger l'activité
        $this->logActivity($userId, $ip, $request->path(), $response->getStatusCode());

        return $response;
    }

    private function detectSuspiciousActivity($userId, $ip, $path)
    {
        $key = "fraud_detection:{$userId}:{$ip}";
        $attempts = Redis::incr($key);
        
        if ($attempts === 1) {
            Redis::expire($key, 3600); // 1 heure
        }

        // Plus de 100 requêtes par heure = suspect
        if ($attempts > 100) {
            return true;
        }

        // Vérifier les patterns suspects
        if (str_contains($path, 'loan') && $attempts > 10) {
            return true; // Trop de demandes de prêt
        }

        return false;
    }

    private function logActivity($userId, $ip, $path, $statusCode)
    {
        Redis::lpush('security_log', json_encode([
            'user_id' => $userId,
            'ip' => $ip,
            'path' => $path,
            'status' => $statusCode,
            'timestamp' => now()->toISOString()
        ]));

        // Garder seulement les 10000 derniers logs
        Redis::ltrim('security_log', 0, 9999);
    }
}