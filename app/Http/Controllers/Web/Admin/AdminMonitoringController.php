<?php
namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Services\MonitoringService;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminMonitoringController extends Controller
{
    public function __construct(
        private MonitoringService $monitoringService
    ) {}

    /**
     * Tableau de bord de monitoring
     */
    public function index(Request $request)
    {
        $period = $request->get('period', '24h');

        $overview = [
            'system_health' => $this->monitoringService->getSystemHealth(),
            'total_alerts' => $this->countTotalAlerts(),
            'active_users' => $this->monitoringService->getActiveUsersStats($period)['count'] ?? 0,
            'api_requests' => $this->monitoringService->getAPIUsageStats($period)['total_requests'] ?? 0
        ];

        return view('admin.monitoring.index', compact('overview', 'period'));
    }

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

            // ->when($request->get('entity_type'), function($query, $entityType) {
            //     $query->where('entity_type', $entityType);
            // })
            ->when($request->get('date_from'), function($query, $dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($request->get('date_to'), function($query, $dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        $filters = $this->getActivityFilters();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $activities,
                'filters' => $filters
            ]);
        }

        return view('admin.monitoring.activities', compact('activities', 'filters'));
    }

    /**
     * Alertes de sécurité
     */
    public function securityAlerts(Request $request)
    {
        $alerts = $this->monitoringService->getSecurityAlerts();
        $fraudAlerts = $this->monitoringService->getFraudAlerts();
        $loginAlerts = $this->monitoringService->getSuspiciousLogins();
        $summary = [
            'total_alerts' => count($alerts) + count($fraudAlerts) + count($loginAlerts),
            'high_priority' => $this->countHighPriorityAlerts($alerts, $fraudAlerts, $loginAlerts),
            'last_update' => now()->toDateTimeString()
        ];

        return view('admin.monitoring.security-alerts', compact('alerts', 'fraudAlerts', 'loginAlerts', 'summary'));
    }

    /**
     * Métriques de performance système
     */
    public function performanceMetrics(Request $request)
    {
        $period = $request->get('period', '24h');

        $metrics = [
            'system_health' => $this->monitoringService->getSystemHealth(),
            'api_performance' => $this->monitoringService->getAPIPerformance($period),
            'database_stats' => $this->monitoringService->getDatabaseStats(),
            'cache_stats' => $this->monitoringService->getCacheStats(),
            'queue_stats' => $this->monitoringService->getQueueStats(),
            'error_rates' => $this->monitoringService->getErrorRates($period)
        ];

        return view('admin.monitoring.performance-metrics', compact('metrics', 'period'));
    }

    /**
     * Logs système en temps réel
     */
    public function systemLogs(Request $request)
    {
        $logType = $request->get('type', 'application');
        $lines = $request->get('lines', 100);

        try {
            $logs = $this->monitoringService->getSystemLogs($logType, $lines);
            $data = [
                'logs' => $logs,
                'type' => $logType,
                'lines_requested' => $lines,
                'timestamp' => now()->toDateTimeString()
            ];

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $data
                ]);
            }

            return view('admin.monitoring.system-logs', compact('data'));

        } catch (\Exception $e) {
            return view('admin.monitoring.system-logs', [
                'error' => 'Erreur lors de la lecture des logs : ' . $e->getMessage()
            ]);
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

        return view('admin.monitoring.usage-statistics', compact('stats', 'period'));
    }

    /**
     * Rapport de conformité
     */
    public function complianceReport(Request $request)
    {
        $report = $this->monitoringService->generateComplianceReport();

        return view('admin.monitoring.compliance-report', compact('report'));
    }

    private function getActivityFilters()
    {
        return [
            'actions' => AuditLog::distinct()->pluck('action'),
            // 'entity_types' => AuditLog::distinct()->pluck('entity_type'),
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

    private function countTotalAlerts()
    {
        $alerts = $this->monitoringService->getSecurityAlerts();
        $fraudAlerts = $this->monitoringService->getFraudAlerts();
        $loginAlerts = $this->monitoringService->getSuspiciousLogins();
        return count($alerts) + count($fraudAlerts) + count($loginAlerts);
    }
}

