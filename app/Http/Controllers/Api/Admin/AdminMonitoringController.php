<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminMonitoringController extends Controller
{


    /**
     * Journal des activités système
     */
    public function activities(Request $request)
    {
        $activities = AuditLog::with(['user'])
            ->when($request->get('user_id'), function($query, $userId) {
                $query->where('user_id', $userId);
            })
            ->when($request->get('action'), function($query, $action) {
                $query->where('action', 'like', "%{$action}%");
            })
            ->when($request->get('entity_type'), function($query, $entityType) {
                $query->where('entity_type', $entityType);
            })
            ->when($request->get('date_from'), function($query, $dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($request->get('date_to'), function($query, $dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $activities,
            'filters' => $this->getActivityFilters()
        ]);
    }

    /**
     * Alertes de sécurité
     */
    public function securityAlerts(Request $request)
    {
        $alerts = $this->monitoringService->getSecurityAlerts();
        
        // Ajouter les alertes de fraude
        $fraudAlerts = $this->monitoringService->getFraudAlerts();
        
        // Ajouter les tentatives de connexion suspectes
        $loginAlerts = $this->monitoringService->getSuspiciousLogins();

        return response()->json([
            'success' => true,
            'data' => [
                'security_alerts' => $alerts,
                'fraud_alerts' => $fraudAlerts,
                'login_alerts' => $loginAlerts,
                'summary' => [
                    'total_alerts' => count($alerts) + count($fraudAlerts) + count($loginAlerts),
                    'high_priority' => $this->countHighPriorityAlerts($alerts, $fraudAlerts, $loginAlerts),
                    'last_update' => now()->toISOString()
                ]
            ]
        ]);
    }

    /**
     * Métriques de performance système
     */
    public function performanceMetrics(Request $request)
    {
        $period = $request->get('period', '24h'); // 1h, 24h, 7d, 30d
        
        $metrics = [
            'system_health' => $this->monitoringService->getSystemHealth(),
            'api_performance' => $this->monitoringService->getAPIPerformance($period),
            'database_stats' => $this->monitoringService->getDatabaseStats(),
            'cache_stats' => $this->monitoringService->getCacheStats(),
            'queue_stats' => $this->monitoringService->getQueueStats(),
            'error_rates' => $this->monitoringService->getErrorRates($period)
        ];

        return response()->json([
            'success' => true,
            'data' => $metrics,
            'generated_at' => now()->toISOString()
        ]);
    }

    /**
     * Logs système en temps réel
     */
    public function systemLogs(Request $request)
    {
        $logType = $request->get('type', 'application'); // application, error, security
        $lines = $request->get('lines', 100);
        
        try {
            $logs = $this->monitoringService->getSystemLogs($logType, $lines);

            return response()->json([
                'success' => true,
                'data' => [
                    'logs' => $logs,
                    'type' => $logType,
                    'lines_requested' => $lines,
                    'timestamp' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la lecture des logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques d'utilisation
     */
    public function usageStatistics(Request $request)
    {
        $period = $request->get('period', '30d');
        
        $stats = [
            'active_users' => $this->monitoringService->getActiveUsersStats($period),
            'api_usage' => $this->monitoringService->getAPIUsageStats($period),
            'feature_usage' => $this->monitoringService->getFeatureUsageStats($period),
            'geographic_distribution' => $this->monitoringService->getGeographicStats(),
            'device_statistics' => $this->monitoringService->getDeviceStats($period)
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Rapport de conformité
     */
    public function complianceReport(Request $request)
    {
        $report = $this->monitoringService->generateComplianceReport();

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    private function getActivityFilters()
    {
        return [
            'actions' => AuditLog::distinct()->pluck('action'),
            'entity_types' => AuditLog::distinct()->pluck('entity_type'),
            'users' => \App\Models\User::select('id', 'first_name', 'last_name')->get()
        ];
    }

    private function countHighPriorityAlerts($security, $fraud, $login)
    {
        $count = 0;
        
        foreach ($security as $alert) {
            if (($alert['severity'] ?? '') === 'high') $count++;
        }
        
        foreach ($fraud as $alert) {
            if (($alert['risk_level'] ?? '') === 'high') $count++;
        }
        
        foreach ($login as $alert) {
            if (($alert['priority'] ?? '') === 'high') $count++;
        }
        
        return $count;
    }
}
