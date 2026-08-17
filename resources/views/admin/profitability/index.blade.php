@extends('layouts.app_admin')

@section('title', 'Audit de Rentabilité Institutionnelle')
@section('page-title', 'Protocole / Analyse des Capitaux')

@section('content')
<div class="space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Intelligence des Revenus & du Capital</h2>
            <p class="text-slate-500 text-sm font-medium">Fenêtre d'Audit : <span class="text-blue-600 font-bold uppercase">{{ $startDate->format('d M Y') }} — {{ $endDate->format('d M Y') }}</span></p>
        </div>
        <div class="flex items-center gap-3">
            <form method="GET" action="{{ route('admin.profitability.index') }}" id="periodForm">
                <select name="period" class="bg-slate-50 border border-slate-200 rounded-lg px-4 py-2 text-xs font-bold text-slate-600 focus:ring-1 focus:ring-blue-500 outline-none transition uppercase" onchange="this.form.submit()">
                    <option value="7days" {{ $period == '7days' ? 'selected' : '' }}>Cycle de 7 Jours</option>
                    <option value="30days" {{ $period == '30days' ? 'selected' : '' }}>Cycle de 30 Jours</option>
                    <option value="90days" {{ $period == '90days' ? 'selected' : '' }}>Cycle de 90 Jours</option>
                    <option value="6months" {{ $period == '6months' ? 'selected' : '' }}>Semestriel</option>
                    <option value="year" {{ $period == 'year' ? 'selected' : '' }}>Audit Annuel</option>
                </select>
            </form>
            <a href="{{ route('admin.profitability.investor-report') }}" class="btn-bank btn-bank-primary">
                <i class="fas fa-file-invoice-dollar mr-2 text-[10px]"></i> Audit Secondaire
            </a>
        </div>
    </div>

    <!-- Matrice de Vélocité du Capital -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bank-card p-6 border-trust relative overflow-hidden">
            <div class="flex items-center justify-between mb-4">
                <span class="kpi-label">Revenu Opérationnel Brut</span>
                <i class="fas fa-arrow-trend-up text-emerald-500 text-xs"></i>
            </div>
            <div class="kpi-value !text-3xl mt-1 text-emerald-600">{{ number_format($profitability['total_revenue'], 0, ',', ' ') }} <small class="text-xs">XOF</small></div>
            @if(isset($previousPeriodComparison['revenue_change']))
            <div class="mt-4 flex items-center gap-2">
                <span class="text-[9px] font-black uppercase {{ $previousPeriodComparison['revenue_change'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }} border border-current px-2 py-0.5 rounded">
                    {{ $previousPeriodComparison['revenue_change'] >= 0 ? '↑' : '↓' }} {{ abs($previousPeriodComparison['revenue_change']) }}% Vélocité
                </span>
                <span class="text-[8px] font-bold text-slate-400 uppercase">vs Période Préc.</span>
            </div>
            @endif
        </div>

        <div class="bank-card p-6 border-t-2 border-t-rose-500">
            <div class="flex items-center justify-between mb-4">
                <span class="kpi-label">Charge Opérationnelle Totale</span>
                <i class="fas fa-arrow-trend-down text-rose-500 text-xs"></i>
            </div>
            <div class="kpi-value !text-3xl mt-1 text-rose-600">{{ number_format($profitability['total_costs'], 0, ',', ' ') }} <small class="text-xs">XOF</small></div>
            @php
                $costRatio = $profitability['total_revenue'] > 0 ? ($profitability['total_costs'] / $profitability['total_revenue']) * 100 : 0;
            @endphp
            <div class="mt-4">
                <div class="w-full h-1 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-rose-500" style="width:{{ min($costRatio, 100) }}%"></div>
                </div>
                <p class="text-[8px] font-black text-rose-400 uppercase mt-2">Ratio de Charge : {{ round($costRatio, 1) }}% du Revenu</p>
            </div>
        </div>

        <div class="bank-card p-6 border-t-2 border-t-blue-500">
            <div class="flex items-center justify-between mb-4">
                <span class="kpi-label">Surplus Fiscal Net</span>
                <i class="fas fa-chart-line text-blue-500 text-xs"></i>
            </div>
            <div class="kpi-value !text-3xl mt-1 {{ $profitability['net_profit'] >= 0 ? 'text-blue-600' : 'text-rose-600' }}">
                {{ number_format($profitability['net_profit'], 0, ',', ' ') }} <small class="text-xs">XOF</small>
            </div>
            @if(isset($previousPeriodComparison['profit_change']))
            <div class="mt-4 flex items-center gap-2">
                <span class="text-[9px] font-black uppercase {{ $previousPeriodComparison['profit_change'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }} border border-current px-2 py-0.5 rounded">
                    {{ $previousPeriodComparison['profit_change'] >= 0 ? '↑' : '↓' }} {{ abs($previousPeriodComparison['profit_change']) }}% Alpha
                </span>
            </div>
            @endif
        </div>

        <div class="bank-card p-6 border-t-2 border-t-cyan-500">
            <div class="flex items-center justify-between mb-4">
                <span class="kpi-label">Marge Alpha Institutionnelle</span>
                <i class="fas fa-percent text-cyan-500 text-xs"></i>
            </div>
            <div class="kpi-value !text-3xl mt-1 text-cyan-600">{{ $profitability['profit_margin'] }}%</div>
            <div class="mt-4 flex items-center justify-between">
                <span class="text-[8px] font-black text-slate-400 uppercase">Objectif Protocole : 25%</span>
                <span class="text-[8px] font-black uppercase {{ $profitability['profit_margin'] >= 25 ? 'text-emerald-600' : 'text-amber-600' }}">
                    {{ $profitability['profit_margin'] >= 25 ? 'Objectif Atteint' : 'Sous l\'Objectif' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Analyse de la Matrice des Revenus -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 bank-card p-8">
            <h3 class="text-xs font-extrabold text-slate-700 uppercase tracking-widest mb-8 block flex items-center gap-2">
                <i class="fas fa-chart-pie text-blue-600"></i> Matrice d'Origine des Revenus
            </h3>
            <div class="h-80"><canvas id="revenueSourceChart"></canvas></div>
        </div>

        <div class="bank-card p-0 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="text-[10px] font-extrabold text-slate-700 uppercase tracking-widest">Flux de Revenus</h3>
            </div>
            <div class="p-6 space-y-4">
                @php
                    $totalRevenueSum = array_sum(array_column($revenueBySource, 'amount'));
                    $colors = [
                        'loan_interest' => 'bg-blue-600',
                        'loan_penalties' => 'bg-rose-500',
                        'fees' => 'bg-amber-500',
                        'tontine' => 'bg-purple-600'
                    ];
                @endphp

                @foreach($revenueBySource as $key => $source)
                    @php
                        $pct = $totalRevenueSum > 0 ? round(($source['amount'] / $totalRevenueSum) * 100, 1) : 0;
                        $color = $colors[$key] ?? 'bg-slate-600';
                        $translatedLabel = match($key) {
                            'loan_interest' => 'Intérêts sur Crédit',
                            'loan_penalties' => 'Pénalités de Retard',
                            'fees' => 'Frais de Service',
                            'tontine' => 'Produits de la Tontine',
                            default => $source['label']
                        };
                    @endphp
                    <div class="p-4 bg-slate-50 border border-slate-100 rounded-xl hover:border-slate-200 transition-colors">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[10px] font-black text-slate-700 uppercase">{{ $translatedLabel }}</span>
                            <span class="text-xs font-black text-slate-900">{{ $pct }}%</span>
                        </div>
                        <div class="w-full h-1.5 bg-white rounded-full overflow-hidden border border-slate-200">
                            <div class="h-full {{ $color }}" style="width:{{ $pct }}%"></div>
                        </div>
                        <div class="flex items-center justify-between mt-3">
                            <p class="text-sm font-black text-slate-900">{{ number_format($source['amount'], 0, ',', ' ') }} <small class="text-[9px] text-slate-400">XOF</small></p>
                            <p class="text-[8px] font-bold text-slate-400 tracking-tighter uppercase">{{ number_format($source['amount'] / max($startDate->diffInDays($endDate), 1), 0, ',', ' ') }} / Jour Opérationnel</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Dossier d'Intelligence Investisseur -->
    <div class="bank-card p-0 overflow-hidden">
        <div class="px-8 py-6 border-b border-slate-100 bg-slate-900 flex items-center justify-between">
            <div>
                <h3 class="text-xs font-extrabold text-white uppercase tracking-widest">Portefeuille de Performance Investisseur</h3>
                <p class="text-[10px] text-white/50 font-medium mt-1">Évaluation de la Croissance Institutionnelle & des Risques</p>
            </div>
            <i class="fas fa-shield-halved text-blue-400 text-xl"></i>
        </div>
        <div class="p-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-12">
                <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100 text-center">
                    <i class="fas fa-users text-blue-500 mb-3 text-lg"></i>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Adhérents Actifs</p>
                    <h4 class="text-2xl font-black text-slate-900">{{ number_format($kpis['total_clients']) }}</h4>
                </div>
                <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100 text-center">
                    <i class="fas fa-vault text-emerald-500 mb-3 text-lg"></i>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">comptes Opérationnels</p>
                    <h4 class="text-2xl font-black text-slate-900">{{ number_format($kpis['active_accounts']) }}</h4>
                </div>
                <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100 text-center">
                    <i class="fas fa-piggy-bank text-amber-500 mb-3 text-lg"></i>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Dépôts Agrégés</p>
                    <h4 class="text-2xl font-black text-slate-900">{{ number_format($kpis['total_deposits'] / 1000000, 1) }}M <small class="text-xs">XOF</small></h4>
                </div>
                <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100 text-center">
                    <i class="fas fa-handshake-angle text-purple-500 mb-3 text-lg"></i>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Portefeuille de Crédit</p>
                    <h4 class="text-2xl font-black text-slate-900">{{ number_format($kpis['loan_portfolio'] / 1000000, 1) }}M <small class="text-xs">XOF</small></h4>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="p-6 bg-emerald-50 border border-emerald-100 rounded-2xl text-center">
                    <p class="text-[9px] font-black text-emerald-700 uppercase tracking-widest mb-2">Indice ROI</p>
                    <h4 class="text-3xl font-black text-emerald-600">{{ $kpis['roi'] }}%</h4>
                    <p class="text-[8px] font-bold text-emerald-500 uppercase mt-1">Retour sur Investissement</p>
                </div>
                <div class="p-6 bg-blue-50 border border-blue-100 rounded-2xl text-center">
                    <p class="text-[9px] font-black text-blue-700 uppercase tracking-widest mb-2">Alpha Net</p>
                    <h4 class="text-3xl font-black text-blue-600">{{ $kpis['profit_margin'] }}%</h4>
                    <p class="text-[8px] font-bold text-blue-500 uppercase mt-1">Marge Fiscale</p>
                </div>
                <div class="p-6 bg-amber-50 border border-amber-100 rounded-2xl text-center">
                    <p class="text-[9px] font-black text-amber-700 uppercase tracking-widest mb-2">Indice de Défaut</p>
                    <h4 class="text-3xl font-black text-amber-600">{{ $kpis['default_rate'] }}%</h4>
                    <p class="text-[8px] font-bold text-amber-500 uppercase mt-1">Risque du Portefeuille</p>
                </div>
                <div class="p-6 bg-cyan-50 border border-cyan-100 rounded-2xl text-center">
                    <p class="text-[9px] font-black text-cyan-700 uppercase tracking-widest mb-2">comptes de Crédit Actifs</p>
                    <h4 class="text-3xl font-black text-cyan-600">{{ number_format($kpis['active_loans']) }}</h4>
                    <p class="text-[8px] font-bold text-cyan-500 uppercase mt-1">Contrats en Cours</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Trajectoire Fiscale -->
    <div class="bank-card p-8">
        <h3 class="text-xs font-extrabold text-slate-700 uppercase tracking-widest mb-8 block flex items-center gap-2">
            <i class="fas fa-chart-line text-blue-600"></i> Matrice de Trajectoire des Revenus
        </h3>
        <div class="h-80"><canvas id="revenueTimelineChart"></canvas></div>
    </div>

    <!-- Projections d'Intelligence -->
    <div class="bank-card p-0 overflow-hidden">
        <div class="px-8 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <h3 class="text-[10px] font-extrabold text-slate-700 uppercase tracking-widest">Intelligence Prédictive Future</h3>
            <span class="px-2 py-0.5 bg-slate-900 text-white text-[8px] font-black rounded uppercase">Estimations IA</span>
        </div>
        <div class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-4">Prévision Proximale (30J)</p>
                    <p class="text-2xl font-black text-slate-900">{{ number_format($projections['next_month_revenue'] / 1000000, 2) }}M <small class="text-xs">XOF</small></p>
                </div>
                <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-4">Prévision Trimestrielle</p>
                    <p class="text-2xl font-black text-slate-900">{{ number_format($projections['next_quarter_revenue'] / 1000000, 2) }}M <small class="text-xs">XOF</small></p>
                </div>
                <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-4">Capacité de Financement Annuelle</p>
                    <p class="text-2xl font-black text-slate-900">{{ number_format($projections['annual_revenue'] / 1000000, 2) }}M <small class="text-xs">XOF</small></p>
                </div>
                <div class="p-6 bg-blue-600 rounded-2xl text-white shadow-xl shadow-blue-500/20">
                    <p class="text-[9px] font-black text-white/60 uppercase tracking-widest mb-4">Potentiel de Croissance Institutionnelle</p>
                    <p class="text-3xl font-black text-white">{{ $projections['growth_potential']['growth_potential'] }}%</p>
                    <p class="text-[10px] font-bold text-white/50 mt-1 uppercase">Indice d'Expansion du Marché</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Audit Détaillé des Flux -->
    <div class="bank-card p-8">
        <h3 class="text-xs font-extrabold text-slate-700 uppercase tracking-widest mb-8 block flex items-center gap-2">
            <i class="fas fa-list-check text-blue-600"></i> Audit Granulaire des Flux d'Actifs
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            @php
                $breakdownLabels = [
                    'loan_interest' => 'Rendement Crédit',
                    'loan_penalties' => 'Frais de Recouvrement',
                    'account_fees' => 'Activation de compte',
                    'transaction_fees' => 'Frais de Cycle',
                    'withdrawal_fees' => 'Frais de Règlement',
                    'transfer_fees' => 'Frais de Protocole',
                    'monthly_fees' => 'Abo. Service',
                    'tontine_revenue' => 'Rendement Mutuel'
                ];
            @endphp
            @foreach($breakdownLabels as $key => $label)
            <div class="p-4 bg-slate-50 border border-slate-100 rounded-xl">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-black text-slate-500 uppercase">{{ $label }}</span>
                    <span class="text-[9px] font-black text-blue-600">{{ $revenuePercentages[$key] ?? 0 }}%</span>
                </div>
                <p class="text-lg font-black text-slate-900">{{ number_format($revenueBreakdown[$key] ?? 0, 0, ',', ' ') }} <small class="text-[10px] text-slate-400">XOF</small></p>
            </div>
            @endforeach
        </div>
        <div class="p-6 bg-slate-900 rounded-2xl flex items-center justify-between text-white">
            <div>
                <p class="text-[9px] font-black text-white/40 uppercase tracking-widest">Revenu Agrégé du Registre</p>
                <p class="text-[10px] text-blue-400 font-bold mt-1">Données de l'Environnement de Production Vérifiées</p>
            </div>
            <p class="text-3xl font-black text-white">{{ number_format($totalRevenueSum, 0, ',', ' ') }} <small class="text-xs text-white/40">XOF</small></p>
        </div>
    </div>

    <!-- Analyse de la Matrice des Risques -->
    @if(isset($riskAnalysis))
    <div class="bank-card p-8">
        <h3 class="text-xs font-extrabold text-slate-700 uppercase tracking-widest mb-8 block flex items-center gap-2">
            <i class="fas fa-triangle-exclamation text-rose-500"></i> Matrice des Risques de Contrepartie & de Portefeuille
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
            <div class="p-6 bg-slate-50 border border-slate-100 rounded-2xl text-center">
                <i class="fas fa-layer-group text-amber-500 mb-3 text-lg"></i>
                <p class="text-[8px] font-black text-slate-400 uppercase mb-2">Concentration</p>
                <h4 class="text-2xl font-black text-slate-900">{{ $riskAnalysis['loan_concentration'] }}%</h4>
                <p class="text-[8px] font-bold text-slate-400 uppercase mt-1">Exposition Top 10</p>
            </div>
            <div class="p-6 bg-slate-50 border border-slate-100 rounded-2xl text-center">
                <i class="fas fa-shield-check text-emerald-500 mb-3 text-lg"></i>
                <p class="text-[8px] font-black text-slate-400 uppercase mb-2">PAR 1 (Alerte)</p>
                <h4 class="text-2xl font-black text-emerald-600">{{ $riskAnalysis['par_1'] }}%</h4>
                <p class="text-[8px] font-bold text-slate-400 uppercase mt-1">Retards Mineurs</p>
            </div>
            <div class="p-6 bg-slate-50 border border-slate-100 rounded-2xl text-center">
                <i class="fas fa-droplet text-blue-500 mb-3 text-lg"></i>
                <p class="text-[8px] font-black text-slate-400 uppercase mb-2">PAR 30 (Marché)</p>
                <h4 class="text-2xl font-black text-blue-600">{{ $riskAnalysis['par_30'] }}%</h4>
                <p class="text-[8px] font-bold text-slate-400 uppercase mt-1">Benchmark MFI</p>
            </div>
            <div class="p-6 border-2 border-orange-200 bg-orange-50 rounded-2xl text-center">
                <i class="fas fa-biohazard text-orange-500 mb-3 text-lg"></i>
                <p class="text-[8px] font-black text-orange-700 uppercase mb-2">PAR 60 (Critique)</p>
                <h4 class="text-2xl font-black text-orange-600">{{ $riskAnalysis['par_60'] }}%</h4>
                <p class="text-[8px] font-bold text-orange-400 uppercase mt-1">Érosion du Capital</p>
            </div>
            <div class="p-6 bg-rose-600 border border-rose-700 rounded-2xl text-center text-white shadow-lg shadow-rose-900/20">
                <i class="fas fa-fire-flame-curved text-rose-200 mb-3 text-lg"></i>
                <p class="text-[8px] font-black text-white/60 uppercase mb-2">PAR 90 (Perte Est.)</p>
                <h4 class="text-2xl font-black text-white">{{ $riskAnalysis['par_90'] }}%</h4>
                <p class="text-[8px] font-bold text-rose-300 uppercase mt-1">Défaut Institutionnel</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Audit de la Vélocité de Trésorerie -->
    @if(isset($cashFlow))
    <div class="bank-card p-0 overflow-hidden">
        <div class="px-8 py-4 border-b border-slate-100 bg-slate-50/50">
            <h3 class="text-[10px] font-extrabold text-slate-700 uppercase tracking-widest">Grand Livre de la Vélocité de Trésorerie Divisionnaire</h3>
        </div>
        <div class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="space-y-4">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-emerald-500 text-white rounded-xl flex items-center justify-center">
                            <i class="fas fa-arrow-down-left"></i>
                        </div>
                        <div>
                            <h4 class="text-[10px] font-black text-slate-800 uppercase">Entrées Opérationnelles</h4>
                            <p class="text-[8px] font-bold text-slate-400 uppercase">Liquidités Entrantes</p>
                        </div>
                    </div>
                    @php
                        $inflowLabels = ['deposits' => 'Dépôts aux Détails', 'loan_repayments' => 'Recouvrement de Crédit', 'fees' => 'Frais Institutionnels', 'tontine_contributions' => 'Capital Mutuel'];
                    @endphp
                    @foreach($inflowLabels as $key => $lbl)
                    <div class="flex justify-between items-center py-2 border-b border-slate-50 last:border-0">
                        <span class="text-[10px] font-bold text-slate-500 uppercase">{{ $lbl }}</span>
                        <span class="text-sm font-black text-slate-900">{{ number_format(($cashFlow['inflows'][$key] ?? 0) / 1000000, 2) }}M</span>
                    </div>
                    @endforeach
                    <div class="pt-4 flex justify-between items-center">
                        <span class="text-[10px] font-black text-emerald-600 uppercase">Entrée Totale</span>
                        <span class="text-xl font-black text-emerald-600">{{ number_format(($cashFlow['total_inflows'] ?? 0) / 1000000, 2) }}M</span>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-rose-500 text-white rounded-xl flex items-center justify-center">
                            <i class="fas fa-arrow-up-right"></i>
                        </div>
                        <div>
                            <h4 class="text-[10px] font-black text-slate-800 uppercase">Sorties Opérationnelles</h4>
                            <p class="text-[8px] font-bold text-slate-400 uppercase">Liquidités Sortantes</p>
                        </div>
                    </div>
                    @php
                        $outflowLabels = ['withdrawals' => 'Règlements aux Détails', 'loan_disbursements' => 'Déploiement de Crédit', 'tontine_payouts' => 'Versements Mutuels', 'operational_costs' => 'Charge Institutionnelle'];
                    @endphp
                    @foreach($outflowLabels as $key => $lbl)
                    <div class="flex justify-between items-center py-2 border-b border-slate-50 last:border-0">
                        <span class="text-[10px] font-bold text-slate-500 uppercase">{{ $lbl }}</span>
                        <span class="text-sm font-black text-slate-900">{{ number_format(($cashFlow['outflows'][$key] ?? 0) / 1000000, 2) }}M</span>
                    </div>
                    @endforeach
                    <div class="pt-4 flex justify-between items-center">
                        <span class="text-[10px] font-black text-rose-600 uppercase">Sortie Totale</span>
                        <span class="text-xl font-black text-rose-600">{{ number_format(($cashFlow['total_outflows'] ?? 0) / 1000000, 2) }}M</span>
                    </div>
                </div>

                <div class="p-8 bg-slate-900 rounded-3xl text-center flex flex-col justify-center border border-white/10 shadow-2xl shadow-slate-900/40">
                    <p class="text-[9px] font-black text-blue-400 uppercase tracking-widest mb-4">Vélocité Divisionnaire Nette</p>
                    <h2 class="text-5xl font-black text-white leading-none mb-2">
                        {{ number_format(($cashFlow['net_cash_flow'] ?? 0) / 1000000, 2) }}M
                    </h2>
                    <p class="text-[10px] font-bold text-white/50 uppercase">Poids du Capital XOF</p>
                    <div class="mt-8 pt-8 border-t border-white/5">
                        <span class="px-4 py-1.5 rounded-full {{ ($cashFlow['net_cash_flow'] ?? 0) >= 0 ? 'bg-emerald-600/20 text-emerald-400' : 'bg-rose-600/20 text-rose-400' }} text-[9px] font-black uppercase tracking-widest">
                            {{ ($cashFlow['net_cash_flow'] ?? 0) >= 0 ? 'Équilibre Surplus' : 'Risque Déficitaire' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.font.weight = '700';

    // Matrice d'Origine des Revenus
    new Chart(document.getElementById('revenueSourceChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: [{!! collect($revenueBySource)->map(fn($s, $k) => match($k) { 'loan_interest' => 'Intérêts Crédit', 'loan_penalties' => 'Pénalités', 'fees' => 'Frais', 'tontine' => 'Tontine', default => $s['label'] })->map(fn($l) => "'$l'")->implode(',') !!}],
            datasets: [{
                data: [{!! collect($revenueBySource)->pluck('amount')->implode(',') !!}],
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6'],
                borderWidth: 0,
                cutout: '75%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 10, padding: 25, font: { size: 9 } } }
            }
        }
    });

    // Matrice de Trajectoire
    const timelineCtx = document.getElementById('revenueTimelineChart').getContext('2d');
    new Chart(timelineCtx, {
        type: 'line',
        data: {
            labels: [{!! collect($revenueTimeline)->pluck('date')->map(fn($d) => "'".\Carbon\Carbon::parse($d)->format('d M')."'")->implode(',') !!}],
            datasets: [{
                label: 'Flux d\'Actifs',
                data: [{!! collect($revenueTimeline)->pluck('total')->implode(',') !!}],
                borderColor: '#3b82f6',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                backgroundColor: 'rgba(59, 130, 246, 0.05)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: { color: 'rgba(0,0,0,0.02)' }, ticks: { font: { size: 9 } } },
                x: { grid: { display: false }, ticks: { font: { size: 9 } } }
            }
        }
    });
</script>
@endpush
@endsection
