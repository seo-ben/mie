@extends('layouts.app_admin')

@section('title', 'Analytique Opérationnelle Tontines')
@section('page-title', 'Audit / Analyse de Rentabilité Tontine')

@section('content')
<div class="space-y-8">
    <!-- En-tête -->
    <div class="flex items-center justify-between no-print">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.tontines.index') }}" class="w-10 h-10 bg-white border border-slate-200 rounded-xl flex items-center justify-center text-slate-600 hover:bg-slate-50 transition shadow-sm">
                <i class="fas fa-chevron-left text-xs"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Rapport Analytique Tontine</h2>
                <p class="text-slate-500 text-sm font-medium uppercase tracking-widest">État du Capital Mutuel - {{ now()->format('M Y') }}</p>
            </div>
        </div>
        <button onclick="window.print()" class="btn-bank btn-bank-outline">
            <i class="fas fa-print mr-2"></i> Exporter PDF
        </button>
    </div>

    <!-- KPI Matrix -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bank-card p-6 border-trust relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-5 text-4xl">
                <i class="fas fa-vault text-blue-600"></i>
            </div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Volume Sous Gestion</p>
            <p class="text-3xl font-black text-slate-900 font-numeric">{{ number_format($reportData['total_collected'], 0, ',', ' ') }} <small class="text-sm">XOF</small></p>
            <div class="mt-4 flex items-center gap-2">
                <span class="text-[9px] font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full uppercase">Capital Liquide Actif</span>
            </div>
        </div>

        <div class="bank-card p-6 relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-5 text-4xl">
                <i class="fas fa-bullseye text-purple-600"></i>
            </div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Objectif Total Engagé</p>
            <p class="text-3xl font-black text-purple-600 font-numeric">{{ number_format($reportData['total_target'], 0, ',', ' ') }} <small class="text-sm">XOF</small></p>
            <div class="mt-4 flex items-center gap-2">
                <span class="text-[9px] font-bold text-purple-600 bg-purple-50 px-2 py-0.5 rounded-full uppercase">Cible Finale Contractuelle</span>
            </div>
        </div>

        <div class="bank-card p-6 relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-5 text-4xl">
                <i class="fas fa-chart-line text-emerald-600"></i>
            </div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Taux de Recouvrement</p>
            <p class="text-3xl font-black text-emerald-600 font-numeric">{{ round($reportData['average_completion'], 1) }}<small class="text-xl">%</small></p>
            <div class="mt-4 w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ $reportData['average_completion'] }}%"></div>
            </div>
        </div>

        <div class="bank-card p-6 relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-5 text-4xl">
                <i class="fas fa-users text-amber-600"></i>
            </div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Portefeuille Actif</p>
            <p class="text-3xl font-black text-slate-900 font-numeric">{{ number_format($reportData['total_tontines']) }}</p>
            <div class="mt-4 flex items-center gap-2">
                <span class="text-[9px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full uppercase">Comptes Mutuels</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Distribution par Fréquence -->
        <div class="lg:col-span-1 bank-card p-8">
            <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest mb-8 border-b border-slate-100 pb-4 flex items-center gap-2">
                <i class="fas fa-pie-chart text-blue-600"></i> Structure du Risque / Fréquence
            </h3>
            <div class="space-y-6">
                @foreach($reportData['by_frequency'] as $freq => $count)
                    @php
                        $perc = ($reportData['total_tontines'] > 0) ? ($count / $reportData['total_tontines']) * 100 : 0;
                        $color = match($freq) { 'daily' => 'rose', 'weekly' => 'blue', 'monthly' => 'purple', default => 'slate' };
                        $label = match($freq) { 'daily' => 'Journalière', 'weekly' => 'Hebdomadaire', 'monthly' => 'Mensuelle', default => $freq };
                    @endphp
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-[10px] font-black text-slate-600 uppercase">{{ $label }}</span>
                            <span class="text-[10px] font-black text-slate-900">{{ $count }} ({{ round($perc) }}%)</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2">
                            <div class="bg-{{ $color }}-500 h-2 rounded-full shadow-sm" style="width: {{ $perc }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <div class="mt-12 p-4 bg-blue-50 rounded-2xl border border-blue-100">
                <p class="text-[9px] font-bold text-blue-800 leading-relaxed uppercase tracking-tight italic">
                    <i class="fas fa-info-circle mr-1"></i> La concentration sur le journalier indique un flux de liquidité constant mais nécessite un audit opérationnel plus rigoureux.
                </p>
            </div>
        </div>

        <!-- Classement des Performances -->
        <div class="lg:col-span-2 bank-card overflow-hidden">
            <div class="px-8 py-5 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                    <i class="fas fa-ranking-star text-amber-500"></i> Top 10 Performances du Portefeuille
                </h3>
            </div>
            <div class="p-0">
                <table class="bank-table">
                    <thead>
                        <tr>
                            <th>Adhérent</th>
                            <th>Volume Collecté</th>
                            <th>Progression</th>
                            <th class="text-right">Bénéfice Prévu (1/31)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($tontines->sortByDesc('total_paid')->take(10) as $t)
                            @php
                                $prog = $t->total_expected > 0 ? ($t->total_paid / $t->total_expected) * 100 : 0;
                                $commissionPotential = $t->total_expected / 31;
                            @endphp
                            <tr class="hover:bg-slate-50/30 transition-colors">
                                <td class="py-4">
                                    <div class="flex flex-col">
                                        <span class="text-[11px] font-black text-slate-900 uppercase tracking-tight">{{ $t->account->client->full_name }}</span>
                                        <span class="text-[9px] font-bold text-slate-400 font-mono">{{ $t->account->account_number }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-[11px] font-black text-slate-800 font-numeric">{{ number_format($t->total_paid, 0, ',', ' ') }} <small class="text-slate-400">XOF</small></span>
                                </td>
                                <td class="min-w-[120px]">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                            <div class="bg-blue-600 h-1.5 rounded-full" style="width: {{ min($prog, 100) }}%"></div>
                                        </div>
                                        <span class="text-[10px] font-black text-blue-700 w-8">{{ round($prog) }}%</span>
                                    </div>
                                </td>
                                <td class="text-right">
                                    <span class="text-[11px] font-black text-emerald-600 font-numeric">+ {{ number_format($commissionPotential, 0, ',', ' ') }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
