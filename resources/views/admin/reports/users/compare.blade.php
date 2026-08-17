@extends('layouts.app_admin')

@section('title', 'Analyse Comparative de Performance - ' . count($comparisons) . ' Entités')
@section('page-title', 'Protocole / Analyse Comparative')

@section('content')
<div class="space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 no-print">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.reports.users.index') }}" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-xl flex items-center justify-center transition border border-slate-200 text-slate-600">
                <i class="fas fa-chevron-left text-xs"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Analyse Comparative du Personnel</h2>
                <p class="text-slate-500 text-sm font-medium">Audit comparatif de {{ count($comparisons) }} entités d'officiers sur les indicateurs clés de performance</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="btn-bank btn-bank-primary">
                <i class="fas fa-print mr-2 text-[10px]"></i> Impression Protocolaire
            </button>
        </div>
    </div>

    <!-- En-tête de la Fenêtre Comparative -->
    <div class="bank-card !bg-indigo-900 p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full blur-3xl -mr-32 -mt-32"></div>
        <div class="flex flex-wrap items-center justify-between gap-8 relative z-10">
            <div>
                <p class="text-[9px] font-extrabold text-white/40 uppercase tracking-widest mb-1">Portefeuille de la Fenêtre d'Audit</p>
                <p class="text-2xl font-black text-white leading-none">{{ $startDate->format('d M Y') }} — {{ $endDate->format('d M Y') }}</p>
                <p class="text-[10px] font-bold text-indigo-300 mt-2 uppercase">{{ $startDate->diffInDays($endDate) }} Jours du Cycle Opérationnel</p>
            </div>
            <div class="text-right">
                <p class="text-[9px] font-extrabold text-white/40 uppercase tracking-widest mb-1">Poids Comparatif</p>
                <p class="text-5xl font-black text-white leading-none">{{ count($comparisons) }} <small class="text-xs text-white/30">Entités</small></p>
            </div>
        </div>
    </div>

    <!-- Matrice de Comparaison -->
    <div class="bank-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="sticky left-0 z-20 px-8 py-6 text-[10px] font-extrabold text-slate-400 uppercase tracking-widest bg-slate-50 text-left border-r border-slate-100">Protocole d'Audit</th>
                        @foreach($comparisons as $comparison)
                        <th class="px-8 py-6 min-w-[250px] border-r border-slate-100 last:border-r-0">
                            <div class="flex flex-col items-center">
                                <div class="w-12 h-12 rounded-xl bg-slate-900 flex items-center justify-center text-xs font-black text-white mb-3 shadow-lg shadow-slate-900/10">
                                    {{ strtoupper(substr($comparison['user']->first_name, 0, 1) . substr($comparison['user']->last_name, 0, 1)) }}
                                </div>
                                <h4 class="text-sm font-black text-slate-800 leading-tight text-center">{{ $comparison['user']->full_name }}</h4>
                                <span class="text-[8px] font-black uppercase text-blue-600 tracking-tighter mt-1">{{ ucfirst(str_replace('_', ' ', $comparison['user']->role)) }}</span>
                            </div>
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <!-- compte Divisionnaire -->
                    <tr class="hover:bg-slate-50/30 transition-colors">
                        <td class="sticky left-0 z-20 px-8 py-4 text-[9px] font-black text-slate-500 uppercase tracking-widest bg-white border-r border-slate-100">compte Divisionnaire</td>
                        @foreach($comparisons as $comparison)
                        <td class="px-8 py-4 text-center border-r border-slate-100 last:border-r-0">
                            <p class="text-[10px] font-black text-slate-800 uppercase tracking-tight">{{ $comparison['user']->agency->name ?? 'Siège' }}</p>
                            <p class="text-[8px] font-bold text-slate-400 uppercase">Division {{ $comparison['user']->agency->city ?? 'Centrale' }}</p>
                        </td>
                        @endforeach
                    </tr>

                    <!-- Infrastructure des Adhérents -->
                    <tr class="bg-blue-50/50">
                        <td colspan="{{ count($comparisons) + 1 }}" class="px-8 py-2 text-[8px] font-black text-blue-700 uppercase tracking-[0.2em]">Infrastructure des Adhérents</td>
                    </tr>
                    
                    <tr class="hover:bg-slate-50/30 transition-colors">
                        <td class="sticky left-0 z-20 px-8 py-6 text-[9px] font-black text-slate-500 uppercase tracking-widest bg-white border-r border-slate-100">Total Entités Capturées</td>
                        @php $maxClients = collect($comparisons)->max('clients_total'); @endphp
                        @foreach($comparisons as $comparison)
                        <td class="px-8 py-6 text-center border-r border-slate-100 last:border-r-0">
                            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl {{ $comparison['clients_total'] == $maxClients ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-slate-50 text-slate-800 border border-slate-100' }}">
                                <span class="text-xl font-black">{{ number_format($comparison['clients_total']) }}</span>
                                @if($comparison['clients_total'] == $maxClients) <i class="fas fa-crown text-[10px] text-amber-500"></i> @endif
                            </div>
                        </td>
                        @endforeach
                    </tr>

                    <tr class="hover:bg-slate-50/30 transition-colors">
                        <td class="sticky left-0 z-20 px-8 py-6 text-[9px] font-black text-slate-500 uppercase tracking-widest bg-white border-r border-slate-100">Validation Conforme (KYC)</td>
                        @foreach($comparisons as $comparison)
                        <td class="px-8 py-6 text-center border-r border-slate-100 last:border-r-0">
                            <p class="text-lg font-black text-slate-800">{{ number_format($comparison['clients_approved']) }}</p>
                            <p class="text-[8px] font-bold text-slate-400 uppercase tracking-tight mt-0.5">{{ $comparison['clients_total'] > 0 ? round(($comparison['clients_approved'] / $comparison['clients_total']) * 100) : 0 }}% de Saturation</p>
                        </td>
                        @endforeach
                    </tr>

                    <!-- Supervision du Portefeuille -->
                    <tr class="bg-emerald-50/50">
                        <td colspan="{{ count($comparisons) + 1 }}" class="px-8 py-2 text-[8px] font-black text-emerald-700 uppercase tracking-[0.2em]">Supervision du Portefeuille</td>
                    </tr>

                    <tr class="hover:bg-slate-50/30 transition-colors">
                        <td class="sticky left-0 z-20 px-8 py-6 text-[9px] font-black text-slate-500 uppercase tracking-widest bg-white border-r border-slate-100">comptes d'Actifs Sécurisés</td>
                        @php $maxAccounts = collect($comparisons)->max('accounts_total'); @endphp
                        @foreach($comparisons as $comparison)
                        <td class="px-8 py-6 text-center border-r border-slate-100 last:border-r-0">
                            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl {{ $comparison['accounts_total'] == $maxAccounts ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-slate-50 text-slate-800 border border-slate-100' }}">
                                <span class="text-xl font-black">{{ number_format($comparison['accounts_total']) }}</span>
                            </div>
                        </td>
                        @endforeach
                    </tr>

                    <!-- Débit Opérationnel -->
                    <tr class="bg-purple-50/50">
                        <td colspan="{{ count($comparisons) + 1 }}" class="px-8 py-2 text-[8px] font-black text-purple-700 uppercase tracking-[0.2em]">Débit Opérationnel (Fenêtre d'Audit)</td>
                    </tr>

                    <tr class="hover:bg-slate-50/30 transition-colors">
                        <td class="sticky left-0 z-20 px-8 py-6 text-[9px] font-black text-slate-500 uppercase tracking-widest bg-white border-r border-slate-100">Volume de Commandes Ops</td>
                        @php $maxTrans = collect($comparisons)->max('transactions_count'); @endphp
                        @foreach($comparisons as $comparison)
                        <td class="px-8 py-6 text-center border-r border-slate-100 last:border-r-0">
                            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl {{ $comparison['transactions_count'] == $maxTrans ? 'bg-purple-50 text-purple-700 border border-purple-100' : 'bg-slate-50 text-slate-800 border border-slate-100' }}">
                                <span class="text-xl font-black">{{ number_format($comparison['transactions_count']) }}</span>
                            </div>
                        </td>
                        @endforeach
                    </tr>

                    <tr class="hover:bg-slate-50/30 transition-colors">
                        <td class="sticky left-0 z-20 px-8 py-6 text-[9px] font-black text-slate-500 uppercase tracking-widest bg-white border-r border-slate-100">Flux de Capital Cumulé</td>
                        @php $maxAmt = collect($comparisons)->max('transactions_amount'); @endphp
                        @foreach($comparisons as $comparison)
                        <td class="px-8 py-6 text-center border-r border-slate-100 last:border-r-0">
                            <div class="flex flex-col items-center gap-1">
                                <span class="text-lg font-black {{ $comparison['transactions_amount'] == $maxAmt ? 'text-blue-600' : 'text-slate-800' }}">{{ number_format($comparison['transactions_amount'], 0, ',', ' ') }}</span>
                                <span class="text-[8px] font-bold text-slate-400 uppercase tracking-widest">Matrice XOF</span>
                            </div>
                        </td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Visualiseurs Comparatifs -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="bank-card p-8">
            <h3 class="text-xs font-extrabold text-slate-700 uppercase tracking-widest mb-6 block flex items-center gap-2">
                <i class="fas fa-users-viewfinder text-blue-600"></i> Comparaison de Capture d'Entités
            </h3>
            <div class="h-80"><canvas id="clientsChart"></canvas></div>
        </div>

        <div class="bank-card p-8">
            <h3 class="text-xs font-extrabold text-slate-700 uppercase tracking-widest mb-6 block flex items-center gap-2">
                <i class="fas fa-money-bill-transfer text-purple-600"></i> Équilibre du Poids Opérationnel
            </h3>
            <div class="h-80"><canvas id="amountsChart"></canvas></div>
        </div>
    </div>

    <!-- Perspectives d'Intelligence Stratégique -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @php
            $topVal = collect($comparisons)->sortByDesc('transactions_amount')->first();
            $topOps = collect($comparisons)->sortByDesc('transactions_count')->first();
            $topAcq = collect($comparisons)->sortByDesc('clients_period')->first();
        @endphp

        <div class="bank-card p-6 border-l-4 border-l-amber-400 bg-slate-50/50">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center text-amber-500 border border-slate-100">
                    <i class="fas fa-medal text-xl"></i>
                </div>
                <div>
                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Lead Actifs du Capital</p>
                    <h4 class="text-sm font-black text-slate-800">{{ $topVal['user']->full_name }}</h4>
                </div>
            </div>
            <p class="text-2xl font-black text-slate-900 leading-none">{{ number_format($topVal['transactions_amount'], 0, ',', ' ') }} <small class="text-xs text-slate-400">XOF</small></p>
        </div>

        <div class="bank-card p-6 border-l-4 border-l-blue-400 bg-slate-50/50">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center text-blue-500 border border-slate-100">
                    <i class="fas fa-gauge-high text-xl"></i>
                </div>
                <div>
                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Lead Efficacité</p>
                    <h4 class="text-sm font-black text-slate-800">{{ $topOps['user']->full_name }}</h4>
                </div>
            </div>
            <p class="text-2xl font-black text-slate-900 leading-none">{{ number_format($topOps['transactions_count']) }} <small class="text-xs text-slate-400">Ops</small></p>
        </div>

        <div class="bank-card p-6 border-l-4 border-l-emerald-400 bg-slate-50/50">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center text-emerald-500 border border-slate-100">
                    <i class="fas fa-chart-line text-xl"></i>
                </div>
                <div>
                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Alpha d'Acquisition</p>
                    <h4 class="text-sm font-black text-slate-800">{{ $topAcq['user']->full_name }}</h4>
                </div>
            </div>
            <p class="text-2xl font-black text-slate-900 leading-none">{{ number_format($topAcq['clients_period']) }} <small class="text-xs text-slate-400">Entités</small></p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    const userNames = {!! json_encode(collect($comparisons)->pluck('user.full_name')) !!};
    const institutionalPalette = ['#3b82f6', '#8b5cf6', '#10b981', '#f59e0b', '#ef4444'];

    // Visual Theme Config
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.font.weight = '700';
    Chart.defaults.font.size = 10;

    // Comparaison Clients
    new Chart(document.getElementById('clientsChart'), {
        type: 'bar',
        data: {
            labels: userNames,
            datasets: [{
                label: 'Capture Totale',
                data: {!! json_encode(collect($comparisons)->pluck('clients_total')) !!},
                backgroundColor: institutionalPalette[0],
                borderRadius: 4
            }, {
                label: 'Vérifiés (KYC)',
                data: {!! json_encode(collect($comparisons)->pluck('clients_approved')) !!},
                backgroundColor: institutionalPalette[2],
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 20 } } },
            scales: { y: { grid: { color: 'rgba(0,0,0,0.03)' } }, x: { grid: { display: false } } }
        }
    });

    // Comparaison Montants
    new Chart(document.getElementById('amountsChart'), {
        type: 'bar',
        data: {
            labels: userNames,
            datasets: [{
                label: 'Volume du flux d\'actifs (XOF)',
                data: {!! json_encode(collect($comparisons)->pluck('transactions_amount')) !!},
                backgroundColor: institutionalPalette[1],
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { 
                y: { 
                    grid: { color: 'rgba(0,0,0,0.03)' },
                    ticks: { callback: v => new Intl.NumberFormat('fr-FR').format(v) }
                }, 
                x: { grid: { display: false } } 
            }
        }
    });
</script>

<style>
    @media print {
        .no-print { display: none !important; }
        .bank-card { box-shadow: none !important; border: 1px solid #e2e8f0 !important; }
        .sticky { position: static !important; }
    }
</style>
@endsection
