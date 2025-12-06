<?php
namespace App\Http\Controllers\web\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Services\AdminReportService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminReportController extends Controller
{
    public function __construct(
        private ReportService $reportService,
        private AdminReportService $adminReportService
    ) {}

    /**
     * Dashboard global avec toutes les métriques
     */
    public function globalDashboard(Request $request)
    {
        $period = $request->get('period', '30d');

        $dashboard = $this->adminReportService->getGlobalDashboard($period);

        return response()->json([
            'success' => true,
            'data' => $dashboard,
            'period' => $period,
            'generated_at' => now()->toISOString()
        ]);
    }

    /**
     * Rapport réglementaire (BCEAO/COBAC)
     */
    public function regulatoryReport(Request $request)
    {
        $reportType = $request->get('type', 'monthly'); // monthly, quarterly, annual
        $date = $request->get('date', now()->format('Y-m'));

        try {
            $report = $this->adminReportService->generateRegulatoryReport($reportType, $date);

            return response()->json([
                'success' => true,
                'data' => $report,
                'export_links' => [
                    'pdf' => route('api.admin.reports.regulatory.export', ['format' => 'pdf', 'type' => $reportType, 'date' => $date]),
                    'excel' => route('api.admin.reports.regulatory.export', ['format' => 'excel', 'type' => $reportType, 'date' => $date])
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport réglementaire',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Piste d'audit complète
     */
    public function auditTrail(Request $request)
    {
        $filters = [
            'start_date' => $request->get('start_date', now()->subMonth()->format('Y-m-d')),
            'end_date' => $request->get('end_date', now()->format('Y-m-d')),
            'user_id' => $request->get('user_id'),
            'entity_type' => $request->get('entity_type'),
            'action_type' => $request->get('action_type'),
            'risk_level' => $request->get('risk_level', 'all') // all, high, medium, low
        ];

        $auditTrail = $this->adminReportService->getAuditTrail($filters);

        return response()->json([
            'success' => true,
            'data' => $auditTrail,
            'filters_applied' => $filters
        ]);
    }

    /**
     * Rapport de performance financière
     */
    public function financialPerformance(Request $request)
    {
        $period = $request->get('period', 'quarterly');
        $year = $request->get('year', now()->year);

        $performance = $this->adminReportService->getFinancialPerformance($period, $year);

        return response()->json([
            'success' => true,
            'data' => $performance
        ]);
    }

    /**
     * Analyse de risque globale
     */
    public function riskAnalysis(Request $request)
    {
        $analysis = $this->adminReportService->getRiskAnalysis();

        return response()->json([
            'success' => true,
            'data' => $analysis
        ]);
    }

    /**
     * Rapport de croissance et tendances
     */
    public function growthTrends(Request $request)
    {
        $period = $request->get('period', '12m'); // 3m, 6m, 12m, 24m

        $trends = $this->adminReportService->getGrowthTrends($period);

        return response()->json([
            'success' => true,
            'data' => $trends
        ]);
    }

    /**
     * Rapport de qualité de portefeuille
     */
    public function portfolioQuality(Request $request)
    {
        $quality = $this->adminReportService->getPortfolioQuality();

        return response()->json([
            'success' => true,
            'data' => $quality
        ]);
    }

    /**
     * Export de rapport (PDF/Excel)
     */
    public function exportReport(Request $request, $reportType)
    {
        $format = $request->get('format', 'pdf');
        $params = $request->except(['format']);

        try {
            $file = $this->adminReportService->exportReport($reportType, $format, $params);

            return response()->download($file)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Planification de rapports automatiques
     */
    public function scheduleReport(Request $request)
    {
        $schedule = $request->validate([
            'report_type' => 'required|string',
            'frequency' => 'required|in:daily,weekly,monthly,quarterly',
            'recipients' => 'required|array',
            'recipients.*' => 'email',
            'format' => 'required|in:pdf,excel',
            'parameters' => 'array'
        ]);

        try {
            $scheduledReport = $this->adminReportService->scheduleReport($schedule);

            return response()->json([
                'success' => true,
                'message' => 'Rapport programmé avec succès',
                'data' => $scheduledReport
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la programmation',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
