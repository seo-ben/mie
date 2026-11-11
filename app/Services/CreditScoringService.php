<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Loan;
use Carbon\Carbon;

class CreditScoringService
{
    /**
     * Analyse avancée d'éligibilité, scoring de risque et conseil financier
     *
     * @param Client $client
     * @param float $requestedAmount
     * @param int $durationMonths
     * @param float $interestRate
     * @return array
     */
    public function analyze(Client $client, float $requestedAmount = 200000, int $durationMonths = 6, float $interestRate = 17.0): array
    {
        // 1. COLLECTE DES DONNÉES FINANCIÈRES
        $accounts = $client->accounts()->where('status', 'active')->get();
        $savings = (float) $accounts->where('account_type', 'savings')->sum('balance');
        
        $tontinePaid = 0.0;
        $tontineDaily = 0.0;
        $tontinePaymentsCount = 0;
        
        foreach ($accounts->where('account_type', 'tontine') as $tacc) {
            if ($tacc->tontineAccount) {
                $tontinePaid += (float) $tacc->tontineAccount->total_paid;
                $tontineDaily = max($tontineDaily, (float) ($tacc->tontineAccount->daily_amount ?? 0));
                $tontinePaymentsCount += (int) ($tacc->tontineAccount->payments_made ?? 0);
            } else {
                // Fallback si solde sur le compte principal
                $tontinePaid += (float) $tacc->balance;
            }
        }

        // Patrimoine total mobilisable (Épargne + Cotisations Tontine)
        $totalCapital = $savings + $tontinePaid;

        // 2. ÉVALUATION DE LA CAPACITÉ MENSUELLE DE VERSEMENT
        // Analyser les flux réels : soit basé sur la mise quotidienne (x30 jours),
        // soit sur le total cotisé rapporté à la période, soit sur les dépôts récents
        if ($tontineDaily > 0) {
            $estimatedMonthlyCashflow = $tontineDaily * 30;
        } elseif ($totalCapital > 0) {
            // Si pas de mise fixe, estimer la capacité mensuelle par l'historique
            $estimatedMonthlyCashflow = max(10000, min($totalCapital, 60000));
        } else {
            $estimatedMonthlyCashflow = 0;
        }

        // 3. PRÊTS EXISTANTS ET ANTÉCÉDENTS
        $activeLoansCount = $client->loans()->whereIn('status', ['approved', 'disbursed', 'active'])->count();
        $repaidLoansCount = $client->loans()->where('status', 'completed')->count();

        // 4. SIMULATION DU PRÊT DEMANDÉ
        $durationMonths = max(1, $durationMonths);
        $totalInterest = $requestedAmount * ($interestRate / 100) * ($durationMonths / 12);
        $totalDue = $requestedAmount + $totalInterest;
        $monthlyPayment = $totalDue / $durationMonths;

        // Ratio d'effort / taux d'endettement mensuel
        $effortRatio = ($estimatedMonthlyCashflow > 0) 
            ? round(($monthlyPayment / $estimatedMonthlyCashflow) * 100, 1) 
            : 999;

        // Levier financier : Montant demandé / Total épargne
        $leverageRatio = ($totalCapital > 0) 
            ? round($requestedAmount / $totalCapital, 1) 
            : 999;

        // 5. CALCUL DU CONSEIL FINANCIER : QUEL PRÊT LUI FAIRE ?
        // Mensualité saine recommandée = 35% de la capacité mensuelle constatée
        $healthyMonthly = max(3000, round($estimatedMonthlyCashflow * 0.35));
        
        // Montant conseillé sur la durée choisie
        $factor = 1 + ($interestRate / 100) * ($durationMonths / 12);
        $maxRecommendedPrincipal = ($healthyMonthly * $durationMonths) / $factor;
        
        // En microfinance, le premier prêt est aussi borné par un multiple de l'épargne (max 2.5x à 3.5x si pas de passif)
        $savingsCap = max(25000, $totalCapital * ($repaidLoansCount > 0 ? 3.5 : 2.5));
        
        $recommendedAmount = min($maxRecommendedPrincipal, $savingsCap);
        // Arrondir aux 5 000 FCFA les plus proches
        $recommendedAmount = max(10000, round($recommendedAmount / 5000) * 5000);
        
        // Mensualité du montant recommandé
        $recTotalDue = $recommendedAmount + ($recommendedAmount * ($interestRate / 100) * ($durationMonths / 12));
        $recommendedMonthly = round($recTotalDue / $durationMonths);

        // Capacité maximale absolue (plafond avec dérogation et 3 cautions)
        $maxSafeCeiling = max(25000, round(($totalCapital * 3) / 5000) * 5000);

        // 6. DÉTERMINATION DU SCORE DE RISQUE ET DIAGNOSTIC DU CONSEILLER
        $riskLevel = 'low'; // 'low', 'moderate', 'high'
        $badgeTitle = 'Favorable • Faible Risque';
        $badgeClass = 'bg-success';
        $alerts = [];
        $analysisText = '';

        if ($activeLoansCount > 0) {
            $riskLevel = 'high';
            $badgeTitle = 'Refus Conseillé • Prêt en cours';
            $badgeClass = 'bg-danger';
            $alerts[] = "L'adhérent a déjà un crédit actif non soldé dans l'institution.";
            $analysisText = "Risque critique de double endettement. Le client doit solder son prêt actuel avant toute nouvelle demande.";
        } elseif ($totalCapital < 5000) {
            $riskLevel = 'high';
            $badgeTitle = 'Risque Élevé • Surface financière insuffisante';
            $badgeClass = 'bg-danger';
            $alerts[] = "Moins de 5 000 FCFA d'épargne ou tontine constatée.";
            $analysisText = "Surface financière quasi nulle. Le client n'a pas démontré d'historique de versement régulier.";
        } elseif ($effortRatio > 70 || $leverageRatio > 5) {
            $riskLevel = 'high';
            $badgeTitle = 'Risque Élevé • Mensualité Insupportable';
            $badgeClass = 'bg-danger';
            
            if ($monthlyPayment > $totalCapital) {
                $alerts[] = "La mensualité requise (" . number_format($monthlyPayment, 0, ',', ' ') . " F/mois) dépasse la totalité de son capital cotisé (" . number_format($totalCapital, 0, ',', ' ') . " F) !";
            }
            if ($effortRatio > 70) {
                $alerts[] = "Taux d'effort critique : la mensualité absorbe {$effortRatio}% de sa capacité mensuelle estimée.";
            }
            if ($leverageRatio > 5) {
                $alerts[] = "Effet de levier disproportionné : le montant demandé représente {$leverageRatio}x son épargne.";
            }

            $analysisText = "Insolvabilité quasi certaine si ce montant est octroyé. Avec seulement " . number_format($totalCapital, 0, ',', ' ') . " F d'historique, une mensualité de " . number_format($monthlyPayment, 0, ',', ' ') . " F/mois provoquera un défaut immédiat.";
        } elseif ($effortRatio > 40 || $leverageRatio > 3) {
            $riskLevel = 'moderate';
            $badgeTitle = 'Attention • Risque Modéré';
            $badgeClass = 'bg-warning text-dark';
            
            $alerts[] = "Taux d'effort soutenu (" . round($effortRatio) . "% des flux réguliers).";
            if ($leverageRatio > 3) {
                $alerts[] = "Montant sollicité élevé par rapport à son épargne ({$leverageRatio}x).";
            }
            $analysisText = "Dossier sous haute surveillance. Ce crédit pèsera sur ses versements habituels. L'octroi exige impérativement la vérification physique des 3 cautions et un nantissement matériel.";
        } else {
            $riskLevel = 'low';
            $badgeTitle = 'Éligible • Profil Équilibré';
            $badgeClass = 'bg-success';
            $analysisText = "Profil sain et sécurisé. Les versements habituels du client couvrent confortablement l'échéance mensuelle sans impacter sa trésorerie.";
        }

        return [
            'success'               => true,
            'client_id'             => $client->id,
            'client_name'           => $client->full_name,
            'client_number'         => $client->client_number,
            'savings_total'         => $savings,
            'tontine_total'         => $tontinePaid,
            'total_capital'         => $totalCapital,
            'estimated_monthly_flow'=> $estimatedMonthlyCashflow,
            'active_loans'          => $activeLoansCount,
            'repaid_loans'          => $repaidLoansCount,
            
            // Simulation demandée
            'requested_amount'      => $requestedAmount,
            'duration_months'       => $durationMonths,
            'interest_rate'         => $interestRate,
            'monthly_payment'       => round($monthlyPayment),
            'total_due'             => round($totalDue),
            'effort_ratio'          => $effortRatio,
            'leverage_ratio'        => $leverageRatio,
            
            // Évaluation du Risque
            'risk_level'            => $riskLevel,       // 'low', 'moderate', 'high'
            'badge_title'           => $badgeTitle,
            'badge_class'           => $badgeClass,
            'alerts'                => $alerts,
            'analysis_text'         => $analysisText,
            
            // Recommandation Conseiller
            'recommended_amount'    => (int) $recommendedAmount,
            'recommended_monthly'   => (int) $recommendedMonthly,
            'max_safe_ceiling'      => (int) $maxSafeCeiling,
            'healthy_monthly'       => (int) $healthyMonthly,
            'advice_summary'        => "Pour un profil avec " . number_format($totalCapital, 0, ',', ' ') . " F d'historique, le conseiller recommande un prêt de " . number_format($recommendedAmount, 0, ',', ' ') . " FCFA sur {$durationMonths} mois (" . number_format($recommendedMonthly, 0, ',', ' ') . " F/mois).",
        ];
    }
}

