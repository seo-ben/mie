<?php

namespace App\Http\Controllers\Web\Manager;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class ManagerTeamController extends Controller
{
    /**
     * Liste des agents de l'agence
     */
    public function agents(Request $request)
    {
        $user = auth()->user();

        $agents = User::where('agency_id', $user->agency_id)
            ->where('role', 'LIKE', 'agent%')
            ->with(['clients'])
            ->when($request->get('search'), function($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->has('is_active'), function($query) use ($request) {
                $query->where('is_active', $request->boolean('is_active'));
            })
            ->paginate($request->get('per_page', 15));

        return UserResource::collection($agents);
    }

    /**
     * Performance d'un agent spécifique
     */
    public function agentPerformance($agentId, Request $request)
    {
        $user = auth()->user();
        $period = $request->get('period', '30d');

        // Vérifier que l'agent appartient à l'agence
        $agent = User::where('agency_id', $user->agency_id)
            ->where('role', 'LIKE', 'agent%')
            ->findOrFail($agentId);

        $performance = $this->teamService->getAgentPerformance($agentId, $period);

        return response()->json([
            'success' => true,
            'data' => [
                'agent' => new UserResource($agent),
                'performance' => $performance
            ]
        ]);
    }

    /**
     * Classement des agents
     */
    public function ranking(Request $request)
    {
        $user = auth()->user();
        $metric = $request->get('metric', 'clients_registered'); // clients_registered, collections, loan_recovery
        $period = $request->get('period', '30d');

        $ranking = $this->teamService->getAgentRanking($user->agency_id, $metric, $period);

        return response()->json([
            'success' => true,
            'data' => $ranking
        ]);
    }

    /**
     * Objectifs et réalisations de l'équipe
     */
    public function targets(Request $request)
    {
        $user = auth()->user();
        $month = $request->get('month', now()->format('Y-m'));

        $targets = $this->teamService->getTeamTargets($user->agency_id, $month);

        return response()->json([
            'success' => true,
            'data' => $targets
        ]);
    }

    /**
     * Définir les objectifs d'un agent
     */
    public function setAgentTargets(Request $request, $agentId)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'targets' => 'required|array',
            'targets.clients_registration' => 'integer|min:0',
            'targets.collection_amount' => 'numeric|min:0',
            'targets.loan_recovery_rate' => 'numeric|min:0|max:100'
        ]);

        try {
            $user = auth()->user();

            // Vérifier que l'agent appartient à l'agence
            $agent = User::where('agency_id', $user->agency_id)
                ->where('role', 'LIKE', 'agent%')
                ->findOrFail($agentId);

            $result = $this->teamService->setAgentTargets(
                $agentId,
                $request->get('month'),
                $request->get('targets')
            );

            return response()->json([
                'success' => true,
                'message' => 'Objectifs définis avec succès',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la définition des objectifs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rapport d'activité de l'équipe
     */
    public function activityReport(Request $request)
    {
        $user = auth()->user();
        $date = $request->get('date', now()->format('Y-m-d'));

        $report = $this->teamService->getTeamActivityReport($user->agency_id, $date);

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    /**
     * Planning de l'équipe
     */
    public function schedule(Request $request)
    {
        $user = auth()->user();
        $date = $request->get('date', now()->format('Y-m-d'));

        $schedule = $this->teamService->getTeamSchedule($user->agency_id, $date);

        return response()->json([
            'success' => true,
            'data' => $schedule
        ]);
    }
}
