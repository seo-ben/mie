<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class InvestorReportExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // On transforme les données du rapport en collection pour l'export
        return collect([
            [
                'section' => 'PROFITABILITÉ GLOBALE',
                'label' => 'Revenus Totaux',
                'value' => $this->data['profitability']['total_revenue'] ?? 0,
            ],
            [
                'section' => 'PROFITABILITÉ GLOBALE',
                'label' => 'Coûts Totaux',
                'value' => $this->data['profitability']['total_costs'] ?? 0,
            ],
            [
                'section' => 'PROFITABILITÉ GLOBALE',
                'label' => 'Bénéfice Net',
                'value' => $this->data['profitability']['net_profit'] ?? 0,
            ],
            [
                'section' => 'KPIs INVESTISSEURS',
                'label' => 'Total Clients',
                'value' => $this->data['kpis']['total_clients'] ?? 0,
            ],
            [
                'section' => 'KPIs INVESTISSEURS',
                'label' => 'Encours Crédits',
                'value' => $this->data['kpis']['loan_portfolio'] ?? 0,
            ],
            [
                'section' => 'KPIs INVESTISSEURS',
                'label' => 'Dépôts Totaux',
                'value' => $this->data['kpis']['total_deposits'] ?? 0,
            ],
            [
                'section' => 'KPIs INVESTISSEURS',
                'label' => 'ROI (%)',
                'value' => ($this->data['kpis']['roi'] ?? 0) . '%',
            ],
            [
                'section' => 'ANALYSE DES RISQUES',
                'label' => 'Taux de Défaut (%)',
                'value' => ($this->data['risk_analysis']['npl_ratio'] ?? 0) . '%',
            ],
            [
                'section' => 'ANALYSE DES RISQUES',
                'label' => 'Qualité Portefeuille (%)',
                'value' => ($this->data['risk_analysis']['portfolio_quality'] ?? 0) . '%',
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'Section',
            'Indicateur',
            'Valeur'
        ];
    }

    public function map($row): array
    {
        return [
            $row['section'],
            $row['label'],
            $row['value'],
        ];
    }

    public function title(): string
    {
        return 'Rapport Investisseur';
    }
}
