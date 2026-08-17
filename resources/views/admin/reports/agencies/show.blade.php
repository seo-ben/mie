@extends('layouts.app_admin')

@section('title', 'Intelligence Divisionnaire - ' . $agency->name)
@section('page-title', 'Protocole / Intelligence de l\'Agence')

@section('content')
<div class="space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 no-print">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.agencies.index') }}" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-xl flex items-center justify-center transition border border-slate-200 text-slate-600">
                <i class="fas fa-chevron-left text-xs"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Rapport de Division de l'Agence</h2>
                <p class="text-slate-500 text-sm font-medium">{{ $agency->name }} • Portefeuille du compte {{ $agency->code }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="btn-bank btn-bank-primary">
                <i class="fas fa-print mr-2 text-[10px]"></i> Impression Protocolaire
            </button>
        </div>
    </div>

    <!-- Sélection de la Fenêtre d'Audit -->
    <div class="bank-card p-6 no-print">
        <form method="GET" action="{{ route('admin.reports.agencies.show', $agency->id) }}" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="text-[9px] font-extrabold text-slate-400 uppercase tracking-widest block mb-2">Début Fenêtre d'Audit</label>
                <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2 text-xs focus:ring-1 focus:ring-blue-500 outline-none">
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="text-[9px] font-extrabold text-slate-400 uppercase tracking-widest block mb-2">Fin Fenêtre d'Audit</label>
                <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2 text-xs focus:ring-1 focus:ring-blue-500 outline-none">
            </div>
            <button type="submit" class="btn-bank btn-bank-primary px-8">Actualiser l'Analyse</button>
        </form>
    </div>

    <!-- Carte d'Identité Divisionnaire -->
    <div class="bank-card !bg-slate-900 p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-purple-600/10 rounded-full blur-3xl -mr-32 -mt-32"></div>
        <div class="flex flex-wrap items-center justify-between gap-8 relative z-10">
            <div class="flex items-center gap-6">
                <div class="w-24 h-24 bg-white/10 rounded-2xl flex items-center justify-center text-4xl font-black border border-white/10 backdrop-blur-md">
                    <i class="fas fa-landmark text-white/50"></i>
                </div>
                <div>
                    <h3 class="text-3xl font-black text-white leading-tight">{{ $agency->name }}</h3>
                    <p class="text-lg font-bold text-blue-400 mt-1 uppercase tracking-tight">Infrastructure de {{ $agency->city }} • {{ $agency->region }}</p>
                    <div class="flex flex-wrap items-center gap-4 mt-4">
                        <span class="px-4 py-1.5 bg-white/5 border border-white/10 rounded-lg text-[10px] font-black uppercase tracking-widest text-white/70">
                            Code compte : <span class="text-blue-400 font-mono">{{ $agency->code }}</span>
                        </span>
                        @if($agency->manager)
                        <span class="px-4 py-1.5 bg-emerald-600/20 border border-emerald-500/20 rounded-lg text-[10px] font-black uppercase tracking-widest text-emerald-400">
                            Gestionnaire : {{ $agency->manager->full_name }}
                        </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KPIs de Division -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bank-card p-6 border-trust">
            <div class="flex items-center justify-between mb-4">
                <span class="kpi-label">Équipe d'Officiers</span>
                <i class="fas fa-user-tie text-blue-500 text-xs"></i>
            </div>
            <div class="kpi-value !text-2xl mt-1">{{ $stats['total_users'] }}</div>
            <div class="mt-4 pt-4 border-t border-slate-100 flex justify-between text-[9px] font-bold uppercase">
                <span class="text-slate-400">Opérationnels :</span>
                <span class="text-emerald-600">{{ $stats['active_users'] }} Actifs</span>
            </div>
        </div>

        <div class="bank-card p-6">
            <div class="flex items-center justify-between mb-4">
                <span class="kpi-label">Adhérents de Division</span>
                <i class="fas fa-users text-emerald-500 text-xs"></i>
            </div>
            <div class="kpi-value !text-2xl mt-1">{{ number_format($stats['total_clients']) }}</div>
            <div class="mt-4 pt-4 border-t border-slate-100 flex justify-between text-[9px] font-bold uppercase">
                <span class="text-slate-400">Acquisition de Fenêtre :</span>
                <span class="text-blue-600">+{{ $stats['clients_period'] }} Entités</span>
            </div>
        </div>

        <div class="bank-card p-6">
            <div class="flex items-center justify-between mb-4">
                <span class="kpi-label">Portefeuilles Sous Gestion</span>
                <i class="fas fa-vault text-purple-500 text-xs"></i>
            </div>
            <div class="kpi-value !text-2xl mt-1">{{ number_format($stats['total_accounts']) }}</div>
            <div class="mt-4 pt-4 border-t border-slate-100 flex justify-between text-[9px] font-bold uppercase">
                <span class="text-slate-400">comptes Opérationnels :</span>
                <span class="text-emerald-600">{{ $stats['active_accounts'] }} Actifs</span>
            </div>
        </div>

        <div class="bank-card p-6">
            <div class="flex items-center justify-between mb-4">
                <span class="kpi-label">Poids du Capital Divisionnaire</span>
                <i class="fas fa-coins text-amber-500 text-xs"></i>
            </div>
            <div class="kpi-value !text-2xl mt-1">{{ number_format($stats['total_balance'], 0, ',', ' ') }} <small class="text-xs">XOF</small></div>
            <div class="mt-4 pt-4 border-t border-slate-100 text-[9px] font-bold uppercase text-slate-400">Solde Cumulé du Registre</div>
        </div>
    </div>

    <!-- Débit de Fenêtre -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bank-card p-6 bg-slate-50/50 border-blue-100">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-[10px] font-extrabold text-slate-700 uppercase tracking-widest">Débit Opérationnel de la Fenêtre</h4>
                <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-[8px] font-black rounded uppercase">Données d'Audit</span>
            </div>
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Volume d'Ops</p>
                    <p class="text-2xl font-black text-slate-900">{{ number_format($stats['transactions_period']) }}</p>
                </div>
                <div>
                    <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Flux Divisionnaire</p>
                    <p class="text-2xl font-black text-emerald-600">{{ number_format($stats['amount_period'], 0, ',', ' ') }} <small class="text-xs">XOF</small></p>
                </div>
            </div>
        </div>

        <div class="bank-card p-6 bg-slate-50/50 border-emerald-100">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-[10px] font-extrabold text-slate-700 uppercase tracking-widest">Indice de Croissance des Adhérents</h4>
                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-[8px] font-black rounded uppercase">Données d'Expansion</span>
            </div>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-3xl font-black text-emerald-600">{{ number_format($stats['clients_period']) }}</p>
                    <p class="text-[9px] font-bold text-slate-400 uppercase mt-1">Entités Nettes de Fenêtre</p>
                </div>
                @if($stats['total_clients'] > 0)
                <div class="text-right">
                    <p class="text-2xl font-black text-blue-600">{{ round(($stats['clients_period'] / $stats['total_clients']) * 100) }}%</p>
                    <p class="text-[8px] font-bold text-slate-400 uppercase">Saturation Divisionnaire</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Tendance de Performance -->
    <div class="bank-card p-8">
        <h3 class="text-xs font-extrabold text-slate-700 uppercase tracking-widest mb-6 block flex items-center gap-2">
            <i class="fas fa-chart-line text-purple-600"></i> Tendance de Performance Divisionnaire
        </h3>
        <div class="h-80">
            <canvas id="dailyPerformanceChart"></canvas>
        </div>
    </div>

    <!-- Registre de Performance du Personnel de Division -->
    <div class="bank-card overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <h3 class="text-[10px] font-extrabold text-slate-700 uppercase tracking-widest">Grand Livre de Performance du Personnel</h3>
            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Classé par Efficacité Opérationnelle</span>
        </div>
        <div class="overflow-x-auto">
            <table class="bank-table">
                <thead>
                    <tr>
                        <th class="w-12">Rang</th>
                        <th>Entité d'Officier</th>
                        <th>Gouvernance</th>
                        <th>Entités</th>
                        <th>Poids Ops</th>
                        <th>Flux d'Actifs</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($userPerformances as $index => $perf)
                    <tr class="hover:bg-slate-50/50">
                        <td class="text-center font-black text-slate-400 text-xs">#{{ $index + 1 }}</td>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center font-black text-slate-500 text-[10px]">
                                    {{ strtoupper(substr($perf['user']->first_name, 0, 1) . substr($perf['user']->last_name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-bold text-slate-800 leading-none">{{ $perf['user']->full_name }}</p>
                                    <p class="text-[9px] font-bold text-slate-400 uppercase mt-1">{{ $perf['user']->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="px-2 py-0.5 bg-purple-50 text-purple-700 text-[8px] font-black rounded uppercase border border-purple-100">
                                {{ ucfirst(str_replace('_', ' ', $perf['user']->role)) }}
                            </span>
                        </td>
                        <td>
                            <div class="flex flex-col">
                                <p class="text-xs font-bold text-slate-800">{{ $perf['clients_count'] }} Totaux</p>
                                <p class="text-[8px] font-black text-blue-600 uppercase">Fenêtre : +{{ $perf['clients_period'] }}</p>
                            </div>
                        </td>
                        <td class="font-black text-slate-900">{{ number_format($perf['transactions_count']) }} Ops</td>
                        <td>
                            <p class="text-sm font-black text-emerald-600">{{ number_format($perf['transactions_amount'], 0, ',', ' ') }} <small class="text-[9px] text-slate-400">XOF</small></p>
                        </td>
                        <td class="text-right">
                            <a href="{{ route('admin.reports.users.show', $perf['user']->id) }}" class="p-2 text-slate-400 hover:text-blue-600 transition" title="Dossier Analytique">
                                <i class="fas fa-chart-pie text-xs"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Perspectives Institutionnelles -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @if(count($userPerformances) > 0)
            @php
                $top = $userPerformances[0];
                $active = collect($userPerformances)->sortByDesc('transactions_count')->first();
                $growth = collect($userPerformances)->sortByDesc('clients_period')->first();
            @endphp
            
            <div class="bank-card p-6 border-l-4 border-l-amber-400 shadow-xl shadow-amber-500/5">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 bg-amber-50 text-amber-500 rounded-2xl flex items-center justify-center border border-amber-100">
                        <i class="fas fa-crown text-xl"></i>
                    </div>
                    <div>
                        <p class="text-[8px] font-extrabold text-slate-400 uppercase tracking-widest">Héro de Division</p>
                        <h4 class="text-sm font-black text-slate-800 leading-tight">{{ $top['user']->full_name }}</h4>
                    </div>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between text-[10px] uppercase font-bold text-slate-500">
                        <span>Poids :</span>
                        <span class="text-emerald-600">{{ number_format($top['transactions_amount'], 0, ',', ' ') }} XOF</span>
                    </div>
                </div>
            </div>

            <div class="bank-card p-6 border-l-4 border-l-blue-400 shadow-xl shadow-blue-500/5">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 bg-blue-50 text-blue-500 rounded-2xl flex items-center justify-center border border-blue-100">
                        <i class="fas fa-bolt-lightning text-xl"></i>
                    </div>
                    <div>
                        <p class="text-[8px] font-extrabold text-slate-400 uppercase tracking-widest">Lead Ops</p>
                        <h4 class="text-sm font-black text-slate-800 leading-tight">{{ $active['user']->full_name }}</h4>
                    </div>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between text-[10px] uppercase font-bold text-slate-500">
                        <span>Débit :</span>
                        <span class="text-blue-600">{{ number_format($active['transactions_count']) }} Ops</span>
                    </div>
                </div>
            </div>

            <div class="bank-card p-6 border-l-4 border-l-emerald-400 shadow-xl shadow-emerald-500/5">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-500 rounded-2xl flex items-center justify-center border border-emerald-100">
                        <i class="fas fa-up-long text-xl"></i>
                    </div>
                    <div>
                        <p class="text-[8px] font-extrabold text-slate-400 uppercase tracking-widest">Moteur de Croissance</p>
                        <h4 class="text-sm font-black text-slate-800 leading-tight">{{ $growth['user']->full_name }}</h4>
                    </div>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between text-[10px] uppercase font-bold text-slate-500">
                        <span>Acquisition :</span>
                        <span class="text-emerald-600">+{{ $growth['clients_period'] }} Entités</span>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    const dailyCtx = document.getElementById('dailyPerformanceChart').getContext('2d');
    new Chart(dailyCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($dailyPerformance->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d M'))) !!},
            datasets: [{
                label: 'Volume Ops',
                data: {!! json_encode($dailyPerformance->pluck('count')) !!},
                borderColor: '#9333ea',
                backgroundColor: 'rgba(147, 51, 234, 0.05)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                yAxisID: 'y'
            }, {
                label: 'Flux d\'Actifs (XOF)',
                data: {!! json_encode($dailyPerformance->pluck('total')) !!},
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.05)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: { color: 'rgba(0,0,0,0.03)' }, ticks: { font: { size: 9, weight: '700' } } },
                y1: { position: 'right', grid: { display: false }, ticks: { font: { size: 9, weight: '700' } } },
                x: { grid: { display: false }, ticks: { font: { size: 9, weight: '700' } } }
            }
        }
    });
</script>

<style>
    @media print {
        .no-print { display: none !important; }
        .bank-card { box-shadow: none !important; border: 1px solid #e2e8f0 !important; }
    }
</style>
@endsection
