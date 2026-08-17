@extends('layouts.app_admin')

@section('title', 'Pilotage Stratégique - Dashboard')
@section('page-title', 'Supervision du Marché et Performance')

@section('content')
<div class="space-y-8">
    <!-- Contrôle de la Période d'Audit -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight uppercase">Console de Pilotage Stratégique</h2>
            <p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">Analyse de Performance & Rentabilité • Fenêtre de {{ $period }} Jours</p>
        </div>
        <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl border border-slate-200">
            @foreach([7, 30, 90, 365] as $p)
                <a href="?period={{ $p }}" class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all {{ ($period ?? 30) == $p ? 'bg-white text-blue-600 shadow-sm border border-slate-200' : 'text-slate-500 hover:text-slate-700' }}">
                    {{ $p == 365 ? 'An' : $p.'J' }}
                </a>
            @endforeach
        </div>
    </div>

    <!-- Cockpit de Rentabilité Stratégique -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
        <!-- BÉNÉFICE NET (Estimé) -->
        <div class="bank-card p-6 border-l-4 border-emerald-600 bg-emerald-50/10">
            <div class="flex items-center justify-between mb-4">
                <span class="kpi-label text-emerald-700">Profit Net (Bénéfices)</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-600">
                    <i class="fas fa-chart-line text-xs"></i>
                </div>
            </div>
            <div class="kpi-value font-numeric text-emerald-900">{{ number_format($financial['profitability']['net_profit'] ?? 0, 0, ',', ' ') }} <small class="text-sm">XOF</small></div>
            <div class="flex items-center gap-2 mt-2">
                <span class="text-[9px] px-1.5 py-0.5 rounded bg-emerald-600 text-white font-black uppercase tracking-widest">
                    Marge : {{ $financial['profitability']['margin'] ?? 0 }}%
                </span>
            </div>
        </div>

        <!-- CHIFFRE D'AFFAIRES (PNB) -->
        <div class="bank-card p-6 border-l-4 border-blue-600 bg-blue-50/10">
            <div class="flex items-center justify-between mb-4">
                <span class="kpi-label text-blue-700">Chiffre d'Affaires (PNB)</span>
                <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600">
                    <i class="fas fa-vault text-xs"></i>
                </div>
            </div>
            <div class="kpi-value font-numeric text-blue-900">{{ number_format($financial['revenue']['total'] ?? 0, 0, ',', ' ') }} <small class="text-sm">XOF</small></div>
            <div class="flex items-center gap-2 mt-2">
                <span class="text-[9px] text-slate-400 font-bold uppercase">Architecture des Revenus</span>
            </div>
        </div>

        <!-- Surveillance du Risque (Matrice PAR) -->
        <div class="bank-card p-6 border-l-4 border-rose-600 bg-rose-50/10">
            <div class="flex items-center justify-between mb-2">
                <span class="kpi-label text-rose-700 uppercase tracking-tighter">Surveillance du Risque (PAR)</span>
                <div class="w-8 h-8 rounded-lg bg-rose-100 flex items-center justify-center text-rose-600">
                    <i class="fas fa-triangle-exclamation text-xs"></i>
                </div>
            </div>
            
            <div class="space-y-3 mt-2">
                <!-- PAR 30 (L'indice pivot) -->
                <div class="flex items-end justify-between">
                    <div>
                        <span class="text-[10px] font-bold text-rose-500 uppercase">PAR 30 (Pivot)</span>
                        <div class="kpi-value font-numeric text-rose-900 leading-none">{{ number_format($financial['loan_quality']['par_30'] ?? 0, 1) }}%</div>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] font-bold text-slate-400 uppercase">PAR 90 (Critique)</span>
                        <div class="text-sm font-black text-rose-700">{{ number_format($financial['loan_quality']['par_90'] ?? 0, 1) }}%</div>
                    </div>
                </div>

                <!-- Barre de progression comparative -->
                <div class="flex h-1.5 w-full bg-slate-200 rounded-full overflow-hidden">
                    <div class="bg-amber-400 h-full border-r border-white/20" style="width: {{ $financial['loan_quality']['par_1'] ?? 0 }}%"></div>
                    <div class="bg-orange-500 h-full border-r border-white/20" style="width: {{ $financial['loan_quality']['par_30'] ?? 0 }}%"></div>
                    <div class="bg-rose-600 h-full" style="width: {{ $financial['loan_quality']['par_90'] ?? 0 }}%"></div>
                </div>

                <!-- Légende Matrice -->
                <div class="grid grid-cols-2 gap-2 pt-1 border-t border-rose-200/50">
                    <div class="flex items-center gap-1.5">
                        <div class="w-2 h-2 rounded-full bg-amber-400"></div>
                        <span class="text-[9px] font-bold text-slate-500">PAR 1 : {{ number_format($financial['loan_quality']['par_1'] ?? 0, 1) }}%</span>
                    </div>
                    <div class="flex items-center gap-1.5 justify-end">
                        <div class="w-2 h-2 rounded-full bg-rose-700"></div>
                        <span class="text-[9px] font-bold text-rose-800 uppercase">Perte Sèche Est.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Efficacité de la Trésorerie -->
        <div class="bank-card p-6 border-l-4 border-indigo-600">
            <div class="flex items-center justify-between mb-4">
                <span class="kpi-label text-indigo-700">Liquidité Disponible</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600">
                    <i class="fas fa-scale-balanced text-xs"></i>
                </div>
            </div>
            <div class="kpi-value font-numeric text-indigo-900">{{ number_format($financial['liquidity']['cash_reserves'], 0, ',', ' ') }} <small class="text-sm">XOF</small></div>
            <div class="flex items-center gap-2 mt-2 text-[10px] text-slate-400 font-bold uppercase tracking-tighter">
                Ratio Prêts/Dépôts : {{ number_format($financial['liquidity']['loan_to_deposit_ratio'] ?? 0, 1) }}%
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        <!-- Analyses de Croissance -->
        <div class="xl:col-span-2 bank-card">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Expansion Opérationnelle</h3>
                    <p class="text-[11px] text-slate-400 font-medium">Trajectoire des indicateurs clés de performance</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                        <span class="text-[10px] font-bold text-slate-500 uppercase">Acquisitions</span>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="h-[350px] relative">
                    <canvas id="growthChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Distribution Régionale -->
        <div class="bank-card">
            <div class="p-6 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-800">Poids du Réseau</h3>
                <p class="text-[11px] text-slate-400 font-medium">Concentration géographique de l'adhésion</p>
            </div>
            <div class="p-8">
                <div class="h-[300px] relative mb-6">
                    <canvas id="geographicChart"></canvas>
                </div>
                <!-- Légende -->
                <div class="space-y-3">
                    @foreach($geographicChartData['labels'] as $index => $label)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="w-1.5 h-1.5 rounded-full" style="background-color: {{ ['#2563EB', '#059669', '#D97706', '#DC2626', '#7C3AED', '#DB2777'][$index % 6] }}"></div>
                                <span class="text-xs font-semibold text-slate-600">{{ $label }}</span>
                            </div>
                            <span class="text-xs font-bold text-slate-900">{{ $geographicChartData['percentages'][$index] }}%</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Sections de Supervision Spécialisées -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Performance Tontines -->
        <div class="bank-card">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Épargne Mutuelle (Tontines)</h3>
                    <p class="text-[11px] text-slate-400 font-medium">Participation aux cycles et flux de trésorerie</p>
                </div>
                <i class="fas fa-rotate text-emerald-500"></i>
            </div>
            <div class="p-6 divide-y divide-slate-100 font-numeric">
                <div class="py-4 flex items-center justify-between">
                    <span class="text-xs font-medium text-slate-500">Volume des Collectes</span>
                    <span class="text-sm font-bold text-slate-800">{{ number_format($stats['tontine_collections_sum'], 0, ',', ' ') }} XOF</span>
                </div>
                <div class="py-4 flex items-center justify-between">
                    <span class="text-xs font-medium text-slate-500">Commissions Générées (2%)</span>
                    <span class="text-sm font-bold text-emerald-600">+{{ number_format($financial['revenue']['tontine'], 0, ',', ' ') }} XOF</span>
                </div>
                <div class="py-4 flex items-center justify-between">
                    <span class="text-xs font-medium text-slate-500">Décaissements (Mises à disposition)</span>
                    <span class="text-sm font-bold text-rose-600">-{{ number_format($stats['tontine_payouts_sum'], 0, ',', ' ') }} XOF</span>
                </div>
                <div class="py-4 flex items-center justify-between">
                    <span class="text-xs font-medium text-slate-500">Indice de Vitalité</span>
                    <span class="text-sm font-extrabold text-blue-600">82.4%</span>
                </div>
            </div>
        </div>

        <!-- Architecture des Bénéfices -->
        <div class="bank-card shadow-xl border-t-4 border-emerald-500">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-emerald-50/20">
                <div>
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Architecture des Bénéfices</h3>
                    <p class="text-[10px] text-slate-400 font-bold uppercase">Origine des flux de profit net</p>
                </div>
                <i class="fas fa-coins text-emerald-600"></i>
            </div>
            <div class="p-6 space-y-5 font-numeric">
                <!-- Source : Intérêts Prêts -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-[11px] font-bold uppercase tracking-tighter">
                        <span class="text-slate-500">Intérêts & Pénalités (Prêts)</span>
                        <span class="text-emerald-600">+{{ number_format(($financial['revenue']['loan_interest'] ?? 0) + ($financial['revenue']['loan_penalties'] ?? 0), 0, ',', ' ') }}</span>
                    </div>
                    <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                        @php $loanScale = ($financial['revenue']['total'] ?? 0) > 0 ? ((($financial['revenue']['loan_interest'] ?? 0) + ($financial['revenue']['loan_penalties'] ?? 0)) / $financial['revenue']['total']) * 100 : 0; @endphp
                        <div class="bg-emerald-500 h-full" style="width: {{ $loanScale }}%"></div>
                    </div>
                </div>

                <!-- Source : Commissions Tontine -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-[11px] font-bold uppercase tracking-tighter">
                        <span class="text-slate-500">Commissions Tontines</span>
                        <span class="text-blue-600">+{{ number_format($financial['revenue']['tontine'] ?? 0, 0, ',', ' ') }}</span>
                    </div>
                    <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                        @php $tontineScale = ($financial['revenue']['total'] ?? 0) > 0 ? (($financial['revenue']['tontine'] ?? 0) / $financial['revenue']['total']) * 100 : 0; @endphp
                        <div class="bg-blue-500 h-full" style="width: {{ $tontineScale }}%"></div>
                    </div>
                </div>

                <!-- Source : Frais de Service -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-[11px] font-bold uppercase tracking-tighter">
                        <span class="text-slate-500">Frais de Service & Tenue</span>
                        <span class="text-indigo-600">+{{ number_format($financial['revenue']['fees'] ?? 0, 0, ',', ' ') }}</span>
                    </div>
                    <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                        @php $feesScale = ($financial['revenue']['total'] ?? 0) > 0 ? (($financial['revenue']['fees'] ?? 0) / $financial['revenue']['total']) * 100 : 0; @endphp
                        <div class="bg-indigo-500 h-full" style="width: {{ $feesScale }}%"></div>
                    </div>
                </div>

                <!-- Coûts de Trésorerie -->
                <div class="pt-4 border-t border-slate-100">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-black text-rose-400 uppercase tracking-widest">Charges de Trésorerie (-)</span>
                        <span class="text-xs font-bold text-rose-600">-{{ number_format($financial['profitability']['total_costs'] ?? 0, 0, ',', ' ') }}</span>
                    </div>
                    <p class="text-[9px] text-slate-400 italic mt-1 leading-tight">Inclut les intérêts créditeurs versés aux épargnants et les règlements salariaux du personnel sur la période d'audit.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // graphique de Croissance
    const growthCtx = document.getElementById('growthChart').getContext('2d');
    const growthChart = new Chart(growthCtx, {
        type: 'line',
        data: {
            labels: @json($growthChartData['labels']),
            datasets: [
                {
                    label: 'Nouvelles Acquisitions',
                    data: @json($growthChartData['datasets'][0]['data']),
                    borderColor: '#2563EB',
                    backgroundColor: 'rgba(37, 99, 235, 0.05)',
                    tension: 0,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderWidth: 2,
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1E293B',
                    titleFont: { family: 'Inter', size: 12, weight: 'bold' },
                    bodyFont: { family: 'Inter', size: 12 },
                    padding: 12,
                    cornerRadius: 8
                }
            },
            scales: {
                y: { 
                    beginAtZero: true,
                    grid: { color: '#F1F5F9' },
                    ticks: { font: { family: 'Inter', size: 10 }, color: '#64748B' }
                },
                x: { 
                    grid: { display: false },
                    ticks: { font: { family: 'Inter', size: 10 }, color: '#64748B' }
                }
            }
        }
    });

    // Graphique Géo institutionnel
    const geographicCtx = document.getElementById('geographicChart').getContext('2d');
    new Chart(geographicCtx, {
        type: 'doughnut',
        data: {
            labels: @json($geographicChartData['labels']),
            datasets: [{
                data: @json($geographicChartData['clients']),
                backgroundColor: ['#2563EB', '#059669', '#D97706', '#DC2626', '#7C3AED', '#DB2777'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '75%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1E293B',
                    padding: 12,
                    cornerRadius: 8
                }
            }
        }
    });
</script>
@endsection
