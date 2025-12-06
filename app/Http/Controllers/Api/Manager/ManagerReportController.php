<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ManagerReportController extends Controller
{
   

    /**
     * Rapport de performance de l'agence
     */
    public function agencyPerformance(Request $request)
    {
        $user = auth()->user();
        $period = $request->get('period', '30d');
        
        $report = $this->reportService->getAgencyPerformanceReport($user->agency_id, $period);

        return response()->json([
            'success' => true,
            'data' => $report,
            'export_links' => [
                'pdf' => route('api.manager.reports.export', ['type' => 'agency_performance', 'format' => 'pdf']),
                'excel' => route('api.manager.reports.export', ['type' => 'agency_performance', 'format' => 'excel'])
            ]
        ]);
    }

    /**
     * Rapport de portefeuille de prêts
     */
    public function loanPortfolio(Request $request)
    {
        $user = auth()->user();
        
        $portfolio = $this->reportService->getLoanPortfolioReport($user->agency_id);

        return response()->json([
            'success' => true,
            'data' => $portfolio
        ]);
    }

    /**
     * Rapport de collecte
     */
    public function collectionReport(Request $request)
    {
        $user = auth()->user();
        $date = $request->get('date', now()->format('Y-m-d'));
        
        $report = $this->reportService->getCollectionReport($user->agency_id, $date);

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    /**
     * Performance des agents
     */
    public function agentPerformance(Request $request)
    {
        $user = auth()->user();
        $period = $request->get('period', '30d');
        
        $performance = $this->reportService->getAgentPerformanceReport($user->agency_id, $period);

        return response()->json([
            'success' => true,
            'data' => $performance
        ]);
    }

    /**
     * Rapport financier de l'agence
     */
    public function financial(Request $request)
    {
        $user = auth()->user();
        $period = $request->get('period', 'monthly'); // monthly, quarterly
        $year = $request->get('year', now()->year);
        
        $financial = $this->reportService->getFinancialReport($user->agency_id, $period, $year);

        return response()->json([
            'success' => true,
            'data' => $financial
        ]);
    }

    /**
     * Rapport de conformité
     */
    public function compliance(Request $request)
    {
        $user = auth()->user();
        
        $compliance = $this->reportService->getComplianceReport($user->agency_id);

        return response()->json([
            'success' => true,
            'data' => $compliance
        ]);
    }

    /**
     * Export de rapport
     */
    public function export(Request $request, $reportType)
    {
        $user = auth()->user();
        $format = $request->get('format', 'pdf');
        $params = array_merge($request->all(), ['agency_id' => $user->agency_id]);
        
        try {
            $file = $this->reportService->exportReport($reportType, $format, $params);

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
     * Rapport personnalisé
     */
    public function custom(Request $request)
    {
        $request->validate([
            'metrics' => 'required|array',
            'date_range' => 'required|array',
            'date_range.start' => 'required|date',
            'date_range.end' => 'required|date|after:date_range.start',
            'filters' => 'array'
        ]);

        try {
            $user = auth()->user();
            
            $report = $this->reportService->generateCustomReport(
                $user->agency_id,
                $request->get('metrics'),
                $request->get('date_range'),
                $request->get('filters', [])
            );

            return response()->json([
                'success' => true,
                'data' => $report
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}