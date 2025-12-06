<?php
namespace App\Http\Controllers\web\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientDashboardResource;
use App\Services\ClientDashboardService;
use Illuminate\Http\Request;

class ClientDashboardController extends Controller
{
    public function __construct(
        private ClientDashboardService $dashboardService
    ) {}

    /**
     * Get client dashboard data
     */
    public function index(Request $request)
    {
        $client = auth()->user()->client ?? auth()->user();
        $dashboardData = $this->dashboardService->getDashboardData($client->id);

        return new ClientDashboardResource($dashboardData);
    }

    /**
     * Get client summary
     */
    public function summary(Request $request)
    {
        $client = auth()->user()->client ?? auth()->user();
        $summary = $this->dashboardService->getClientSummary($client->id);

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }
}
