<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\AuditLog;

class ApiLoggerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Logger les actions importantes
        if ($this->shouldLog($request)) {
            $this->logActivity($request, $response);
        }

        return $response;
    }

    private function shouldLog(Request $request)
    {
        $sensitiveRoutes = [
            'loans', 'transactions', 'accounts', 'kyc', 'approval'
        ];

        foreach ($sensitiveRoutes as $route) {
            if (str_contains($request->path(), $route)) {
                return true;
            }
        }

        return false;
    }

    private function logActivity(Request $request, $response)
    {
        if (auth()->check()) {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => $request->method() . ' ' . $request->path(),
                'entity_type' => 'api_call',
                'entity_id' => 0,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'additional_data' => [
                    'request_data' => $request->except(['password', 'password_confirmation']),
                    'response_status' => $response->getStatusCode()
                ]
            ]);
        }
    }
}