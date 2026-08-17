@extends('layouts.app_admin')

@section('title', 'Balance Agée Analytique du Portefeuille')

@section('content')
<div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-8">
    <!-- En-tête -->
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between no-print">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Balance Agée Analytique</h1>
            <p class="text-slate-500 text-sm font-medium">Analyse réglementaire du portefeuille de crédits au {{ $date->format('d/m/Y') }}</p>
        </div>
        <div class="flex gap-3">
            <button onclick="window.print()" class="inline-flex items-center px-4 py-2 text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition shadow-sm">
                <i class="fas fa-print mr-2"></i> Imprimer
            </button>
            <button class="inline-flex items-center px-4 py-2 text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition shadow-sm">
                <i class="fas fa-file-excel mr-2"></i> Exporter
            </button>
        </div>
    </div>

    <!-- Tableau Principal -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th rowspan="2" class="px-4 py-3 text-left text-[10px] font-black text-slate-500 uppercase tracking-widest border-r">Type de crédits</th>
                        <th rowspan="2" class="px-4 py-3 text-center text-[10px] font-black text-slate-500 uppercase tracking-widest border-r">Encours par type de crédit</th>
                        <th colspan="5" class="px-4 py-2 text-center text-[10px] font-black text-slate-500 uppercase tracking-widest border-b border-r">Crédit en retard par tranche d'âge</th>
                        <th rowspan="2" class="px-4 py-3 text-center text-[10px] font-black text-slate-500 uppercase tracking-widest border-r">Total retards par type de crédit</th>
                        <th colspan="2" class="px-4 py-2 text-center text-[10px] font-black text-slate-500 uppercase tracking-widest border-b">Nbre de bénéf. concernés</th>
                    </tr>
                    <tr>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-slate-400 uppercase tracking-tighter border-r">1-30 jours</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-slate-400 uppercase tracking-tighter border-r">1-3 mois</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-slate-400 uppercase tracking-tighter border-r">3-6 mois</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-slate-400 uppercase tracking-tighter border-r">6-12 mois</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-slate-400 uppercase tracking-tighter border-r">> 1 an</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-slate-400 uppercase tracking-tighter border-r">Total</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-slate-400 uppercase tracking-tighter italic">Dont dirigeants et élus</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100">
                    @foreach($data as $row)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-4 py-3 text-xs font-bold text-slate-700 border-r">{{ $row['type'] }}</td>
                        <td class="px-4 py-3 text-xs font-mono text-center text-slate-600 border-r">{{ number_format($row['encours'], 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-xs font-mono text-center text-slate-500 border-r @if($row['retard_1_30'] > 0) bg-amber-50/30 @endif">{{ number_format($row['retard_1_30'], 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-xs font-mono text-center text-slate-500 border-r @if($row['retard_31_90'] > 0) bg-orange-50/30 @endif">{{ number_format($row['retard_31_90'], 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-xs font-mono text-center text-slate-500 border-r @if($row['retard_91_180'] > 0) bg-rose-50/30 @endif">{{ number_format($row['retard_91_180'], 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-xs font-mono text-center text-slate-500 border-r @if($row['retard_181_360'] > 0) bg-red-50/40 @endif">{{ number_format($row['retard_181_360'], 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-xs font-mono text-center text-slate-500 border-r @if($row['retard_plus_360'] > 0) bg-red-100/50 font-black @endif">{{ number_format($row['retard_plus_360'], 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-xs font-black text-center text-slate-900 border-r bg-slate-50/50">{{ number_format($row['total_retard'], 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-xs font-bold text-center text-slate-600 border-r italic">{{ $row['nbre_benef'] }}</td>
                        <td class="px-4 py-3 text-xs font-bold text-center text-slate-600 italic bg-slate-50/30">{{ $row['dont_dirigeants'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-900 text-white">
                    <tr class="font-bold">
                        <td class="px-4 py-3 text-[10px] uppercase tracking-widest border-r">TOTAL GENERAL</td>
                        <td class="px-4 py-3 text-xs font-mono text-center border-r">{{ number_format($totals['encours'], 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-xs font-mono text-center border-r">{{ number_format($totals['retard_1_30'], 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-xs font-mono text-center border-r">{{ number_format($totals['retard_31_90'], 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-xs font-mono text-center border-r">{{ number_format($totals['retard_91_180'], 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-xs font-mono text-center border-r">{{ number_format($totals['retard_181_360'], 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-xs font-mono text-center border-r">{{ number_format($totals['retard_plus_360'], 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-xs font-mono text-center border-r bg-emerald-600">{{ number_format($totals['total_retard'], 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-xs text-center border-r italic">{{ $totals['nbre_benef'] }}</td>
                        <td class="px-4 py-3 text-xs text-center italic">{{ $totals['dont_dirigeants'] }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Résumé et PAR -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="bank-card p-6 space-y-4">
            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                <i class="fas fa-chart-line text-blue-600"></i>
                Indicateurs de Risque (Portfolio At Risk)
            </h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">PAR 1 jour</p>
                    <p class="text-xl font-black text-slate-800">{{ number_format($summary['par_1d'], 2) }}%</p>
                </div>
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">PAR 30 jours</p>
                    <p class="text-xl font-black text-slate-800">{{ number_format($summary['par_30d'], 2) }}%</p>
                </div>
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">PAR 3 mois</p>
                    <p class="text-xl font-black text-slate-800">{{ number_format($summary['par_90d'], 2) }}%</p>
                </div>
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">PAR 6 mois</p>
                    <p class="text-xl font-black text-slate-800">{{ number_format($summary['par_180d'], 2) }}%</p>
                </div>
            </div>
        </div>

        <div class="bank-card p-6 space-y-4">
            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                <i class="fas fa-circle-info text-amber-600"></i>
                Notes de Conformité
            </h3>
            <div class="text-xs text-slate-600 space-y-3 font-medium">
                <p>• Les encours sont calculés sur la base du capital non encore remboursé (Capital restant dû).</p>
                <p>• Les tranches d'âge sont calculées à partir de la date d'échéance du premier impayé non régularisé.</p>
                <p>• Le PAR (Portfolio At Risk) représente le rapport entre l'encours des crédits ayant un retard et l'encours total.</p>
                <p>• Ce rapport est conforme aux exigences de l'instance de régulation (BCEAO/COBAC).</p>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        @page { size: landscape; margin: 10mm; }
        .no-print { display: none !important; }
        .bank-card { border: none !important; box-shadow: none !important; }
        body { background: white !important; font-size: 10px; }
        table { border: 1px solid #e2e8f0 !important; }
        th, td { border: 1px solid #e2e8f0 !important; }
    }
</style>
@endsection
