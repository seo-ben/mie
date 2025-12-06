<?php
namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\LightDashboardResource;
use Illuminate\Http\Request;

class MobileDashboardController extends Controller
{


    /**
     * Version allégée du dashboard pour mobile
     * Optimisée pour réduire la taille des données
     */
    public function lightVersion(Request $request)
    {
        $client = auth()->user()->client ?? auth()->user();
        
        // Cache pendant 5 minutes
        $dashboardData = cache()->remember(
            "mobile_dashboard_{$client->id}", 
            300, 
            fn() => $this->dashboardService->getLightDashboardData($client->id)
        );

        return new LightDashboardResource($dashboardData);
    }

    /**
     * Synchronisation différentielle
     * Retourne seulement les données modifiées depuis la dernière sync
     */
    public function deltaSync(Request $request)
    {
        $lastSync = $request->input('last_sync', '1970-01-01 00:00:00');
        $client = auth()->user()->client ?? auth()->user();

        $delta = [
            'accounts' => $client->accounts()
                ->where('updated_at', '>', $lastSync)
                ->with(['transactions' => function($query) use ($lastSync) {
                    $query->where('updated_at', '>', $lastSync);
                }])
                ->get(),
            'loans' => $client->loans()
                ->where('updated_at', '>', $lastSync)
                ->get(),
            'notifications' => $client->notifications()
                ->where('created_at', '>', $lastSync)
                ->get()
        ];

        return response()->json([
            'success' => true,
            'sync_timestamp' => now()->toISOString(),
            'data' => $delta
        ]);
    }
}