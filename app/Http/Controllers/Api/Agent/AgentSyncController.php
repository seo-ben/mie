<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Services\SyncService;
use Illuminate\Http\Request;

class AgentSyncController extends Controller
{
    public function __construct(
        private SyncService $syncService
    ) {}

    /**
     * Upload des données offline de l'agent
     */
    public function upload(Request $request)
    {
        $request->validate([
            'data' => 'required|array',
            'device_id' => 'required|string',
            'last_sync' => 'nullable|date',
            'data_types' => 'array'
        ]);

        try {
            $user = auth()->user();
            $result = $this->syncService->uploadOfflineData(
                $user->id,
                $request->get('data'),
                $request->get('device_id'),
                $request->get('last_sync')
            );

            return response()->json([
                'success' => true,
                'message' => 'Données synchronisées avec succès',
                'data' => $result,
                'sync_timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la synchronisation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Téléchargement des données pour mode offline
     */
    public function download(Request $request)
    {
        $user = auth()->user();
        $lastSync = $request->get('last_sync');
        $dataTypes = $request->get('data_types', ['clients', 'accounts', 'transactions']);

        try {
            $data = $this->syncService->getOfflineData($user->id, $lastSync, $dataTypes);

            return response()->json([
                'success' => true,
                'data' => $data,
                'sync_timestamp' => now()->toISOString(),
                'next_sync_recommended' => now()->addHours(6)->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statut de synchronisation
     */
    public function status(Request $request)
    {
        $user = auth()->user();
        $deviceId = $request->get('device_id');
        
        $status = $this->syncService->getSyncStatus($user->id, $deviceId);

        return response()->json([
            'success' => true,
            'data' => $status
        ]);
    }

    /**
     * Résolution des conflits de données
     */
    public function resolveConflicts(Request $request)
    {
        $request->validate([
            'conflicts' => 'required|array',
            'conflicts.*.id' => 'required',
            'conflicts.*.resolution' => 'required|in:server_wins,client_wins,merge',
            'conflicts.*.merged_data' => 'required_if:conflicts.*.resolution,merge'
        ]);

        try {
            $user = auth()->user();
            $result = $this->syncService->resolveConflicts(
                $user->id,
                $request->get('conflicts')
            );

            return response()->json([
                'success' => true,
                'message' => 'Conflits résolus avec succès',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la résolution des conflits',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Purger les anciennes données de synchronisation
     */
    public function cleanup(Request $request)
    {
        $user = auth()->user();
        $olderThanDays = $request->get('older_than_days', 30);
        
        try {
            $result = $this->syncService->cleanupOldSyncData($user->id, $olderThanDays);

            return response()->json([
                'success' => true,
                'message' => 'Nettoyage effectué avec succès',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du nettoyage',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
