@extends('layouts.app_admin')

@section('title', 'Dossier Analytique - ' . $user->full_name)
@section('page-title', 'Protocole / Dossier d\'Intelligence')

@section('content')
<div class="space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 no-print">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.reports.users.index') }}" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-xl flex items-center justify-center transition border border-slate-200 text-slate-600">
                <i class="fas fa-chevron-left text-xs"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Rapport d'Intelligence de l'Officier</h2>
                <p class="text-slate-500 text-sm font-medium">{{ $user->full_name }} • Portefeuille de {{ ucfirst(str_replace('_', ' ', $user->role)) }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.reports.users.export', $user->id) }}?start_date={{ $startDate->format('Y-m-d') }}&end_date={{ $endDate->format('Y-m-d') }}" class="btn-bank btn-bank-outline">
                <i class="fas fa-file-arrow-down mr-2 text-[10px]"></i> Exportation Secondaire
            </a>
            <button onclick="window.print()" class="btn-bank btn-bank-primary">
                <i class="fas fa-print mr-2 text-[10px]"></i> Impression Protocolaire
            </button>
        </div>
    </div>

    <!-- Sélection de la Fenêtre d'Audit -->
    <div class="bank-card p-6 no-print">
        <form method="GET" action="{{ route('admin.reports.users.show', $user->id) }}" class="flex flex-wrap items-end gap-4">
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

    <!-- Carte de Résumé d'Identité -->
    <div class="bank-card !bg-slate-900 p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-blue-600/10 rounded-full blur-3xl -mr-32 -mt-32"></div>
        <div class="flex flex-wrap items-center justify-between gap-8 relative z-10">
            <div class="flex items-center gap-6">
                <div class="w-24 h-24 bg-white/10 rounded-2xl flex items-center justify-center text-4xl font-black border border-white/10 backdrop-blur-md">
                    {{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}
                </div>
                <div>
                    <h3 class="text-3xl font-black text-white leading-tight">{{ $user->full_name }}</h3>
                    <div class="flex flex-wrap items-center gap-4 mt-3">
                        <span class="flex items-center gap-2 text-[10px] font-bold text-white/50 uppercase tracking-widest bg-white/5 px-3 py-1.5 rounded-lg border border-white/5">
                            <i class="fas fa-envelope text-blue-400"></i> {{ $user->email }}
                        </span>
                        @if($user->phone)
                        <span class="flex items-center gap-2 text-[10px] font-bold text-white/50 uppercase tracking-widest bg-white/5 px-3 py-1.5 rounded-lg border border-white/5">
                            <i class="fas fa-phone text-blue-400"></i> {{ $user->phone }}
                        </span>
                        @endif
                        <span class="px-3 py-1.5 bg-blue-600 text-[10px] font-black uppercase tracking-widest rounded-lg">
                            {{ ucfirst(str_replace('_', ' ', $user->role)) }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="text-right">
                <div class="bg-white/5 backdrop-blur-md rounded-xl p-5 border border-white/10 min-w-[200px]">
                    <p class="text-[9px] font-extrabold text-white/40 uppercase tracking-widest mb-1">Division Assignée</p>
                    <p class="text-[9px] font-extrabold text-white/40 uppercase tracking-widest mb-1">Division Assignée</p>
                    <p class="text-xl font-black text-white capitalize">{{ $user->agency->name ?? 'Administration Centrale' }}</p>
                    <p class="text-[10px] font-bold text-blue-400 mt-1 uppercase">{{ $user->agency->city ?? 'Siège' }} • {{ $user->agency->code ?? 'DIR-GEN' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- KPIs de Performance -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bank-card p-6 border-trust">
            <div class="flex items-center justify-between mb-4">
                <span class="kpi-label">Adhérents Capturés</span>
                <i class="fas fa-users text-blue-500 text-xs"></i>
            </div>
            <div class="kpi-value !text-2xl mt-1">{{ number_format($clientStats['total']) }}</div>
            <div class="mt-4 space-y-2 border-t border-slate-100 pt-4">
                <div class="flex justify-between text-[9px] font-bold uppercase">
                    <span class="text-slate-400">KYC Vérifiés</span>
                    <span class="text-emerald-600">{{ $clientStats['kyc_approved'] }}</span>
                </div>
                <div class="flex justify-between text-[9px] font-bold uppercase">
                    <span class="text-slate-400">Créations (Fenêtre)</span>
                    <span class="text-blue-600">{{ $clientStats['created_period'] }}</span>
                </div>
            </div>
        </div>

        <div class="bank-card p-6">
            <div class="flex items-center justify-between mb-4">
                <span class="kpi-label">Portefeuilles de comptes Actifs</span>
                <i class="fas fa-vault text-emerald-500 text-xs"></i>
            </div>
            <div class="kpi-value !text-2xl mt-1">{{ number_format($accountStats['total']) }}</div>
            <div class="mt-4 space-y-2 border-t border-slate-100 pt-4">
                <div class="flex justify-between text-[9px] font-bold uppercase">
                    <span class="text-slate-400">Épargne Mutuelle</span>
                    <span class="text-purple-600">{{ $accountStats['tontine_count'] }}</span>
                </div>
                <div class="flex justify-between text-[9px] font-bold uppercase">
                    <span class="text-slate-400">Comptes d'Actifs</span>
                    <span class="text-blue-600">{{ $accountStats['savings_count'] }}</span>
                </div>
            </div>
        </div>

        <div class="bank-card p-6">
            <div class="flex items-center justify-between mb-4">
                <span class="kpi-label">Débit Opérationnel</span>
                <i class="fas fa-right-left text-purple-500 text-xs"></i>
            </div>
            <div class="kpi-value !text-2xl mt-1">{{ number_format($transactionStats['completed_count']) }}</div>
            <div class="mt-4 space-y-2 border-t border-slate-100 pt-4">
                <div class="flex justify-between text-[9px] font-bold uppercase">
                    <span class="text-slate-400">Injections (Entrées)</span>
                    <span class="text-emerald-600">{{ $transactionStats['deposits_count'] }}</span>
                </div>
                <div class="flex justify-between text-[9px] font-bold uppercase">
                    <span class="text-slate-400">Règlements (Sorties)</span>
                    <span class="text-rose-600">{{ $transactionStats['withdrawals_count'] }}</span>
                </div>
            </div>
        </div>

        <div class="bank-card p-6">
            <div class="flex items-center justify-between mb-4">
                <span class="kpi-label">Classe d'Actifs Gérée</span>
                <i class="fas fa-coins text-amber-500 text-xs"></i>
            </div>
            <div class="kpi-value !text-2xl mt-1">{{ number_format($transactionStats['total_amount'], 0, ',', ' ') }} <small class="text-xs">XOF</small></div>
            <div class="mt-4 space-y-2 border-t border-slate-100 pt-4">
                <div class="flex justify-between text-[9px] font-bold uppercase">
                    <span class="text-slate-400">Solde Sous Gestion</span>
                    <span class="text-blue-600">{{ number_format($accountStats['total_balance'], 0, ',', ' ') }}</span>
                </div>
                <div class="flex justify-between text-[9px] font-bold uppercase">
                    <span class="text-slate-400">Frais Institutionnels</span>
                    <span class="text-emerald-600">{{ number_format($transactionStats['total_fees'], 0, ',', ' ') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Visualiseur de Tendances -->
    <div class="bank-card p-8">
        <h3 class="text-xs font-extrabold text-slate-700 uppercase tracking-widest mb-6 block flex items-center gap-2">
            <i class="fas fa-chart-line text-blue-600"></i> Tendance de la Performance Opérationnelle
        </h3>
        <div class="h-80">
            <canvas id="dailyPerformanceChart"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Matrice de Distribution -->
        <div class="bank-card p-8">
            <h3 class="text-xs font-extrabold text-slate-700 uppercase tracking-widest mb-6 block flex items-center gap-2">
                <i class="fas fa-chart-pie text-purple-600"></i> Distribution du Portefeuille
            </h3>
            <div class="h-64">
                <canvas id="accountTypeChart"></canvas>
            </div>
            <div class="mt-8 space-y-3">
                @foreach($accountTypeDistribution as $type)
                <div class="flex items-center justify-between p-4 bg-slate-50 border border-slate-100 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="w-1.5 h-6 rounded-full {{ $type->account_type === 'savings' ? 'bg-blue-500' : 'bg-purple-500' }}"></div>
                        @php
                            $translatedType = match($type->account_type) {
                                'savings' => 'Épargne Standard',
                                'tontine' => 'Épargne Tontine',
                                default => ucfirst($type->account_type)
                            };
                        @endphp
                        <span class="text-[10px] font-extrabold text-slate-700 uppercase">Module {{ $translatedType }}</span>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-black text-slate-800">{{ $type->count }} comptes</p>
                        <p class="text-[9px] font-bold text-slate-400">{{ number_format($type->total_balance, 0, ',', ' ') }} XOF</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Matrice des Canaux -->
        <div class="bank-card p-8">
            <h3 class="text-xs font-extrabold text-slate-700 uppercase tracking-widest mb-6 block flex items-center gap-2">
                <i class="fas fa-network-wired text-emerald-600"></i> Matrice des Canaux de Règlement
            </h3>
            <div class="h-64">
                <canvas id="paymentMethodChart"></canvas>
            </div>
            <div class="mt-8 space-y-3">
                @foreach($paymentMethodDistribution as $method)
                <div class="flex items-center justify-between p-4 bg-slate-50 border border-slate-100 rounded-xl">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-{{ $method->payment_method === 'cash' ? 'money-bill-wave' : ($method->payment_method === 'mobile_money' ? 'mobile-screen' : 'building-columns') }} text-slate-400 text-xs"></i>
                        @php
                            $translatedMethod = match($method->payment_method) {
                                'cash' => 'Espèces (Caisse)',
                                'mobile_money' => 'Paiement Mobile',
                                'bank_transfer' => 'Virement Bancaire',
                                default => ucfirst(str_replace('_', ' ', $method->payment_method))
                            };
                        @endphp
                        <span class="text-[10px] font-extrabold text-slate-700 uppercase">Canal {{ $translatedMethod }}</span>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-black text-slate-800">{{ $method->count }} Ops</p>
                        <p class="text-[9px] font-bold text-slate-400">{{ number_format($method->total_amount, 0, ',', ' ') }} XOF</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Performance du Registre Individuel -->
    <div class="bank-card overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <h3 class="text-[10px] font-extrabold text-slate-700 uppercase tracking-widest">Matrice des Adhérents Cibles (Top 10)</h3>
            <span class="text-[9px] font-bold text-slate-400 uppercase">Filtré par Indice d'Activité</span>
        </div>
        <div class="overflow-x-auto">
            <table class="bank-table">
                <thead>
                    <tr>
                        <th class="w-16">Audit #</th>
                        <th>Identité du Membre</th>
                        <th>comptes</th>
                        <th>Décompte Opérationnel</th>
                        <th>Total Actifs</th>
                        <th class="text-right">Statut de Validation</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($topClients as $index => $client)
                    <tr class="hover:bg-slate-50/50">
                        <td class="text-center font-black text-slate-400 text-xs">#{{ $index + 1 }}</td>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-500">
                                    {{ strtoupper(substr($client->first_name, 0, 1) . substr($client->last_name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-bold text-slate-800 leading-none">{{ $client->first_name }} {{ $client->last_name }}</p>
                                    <p class="text-[9px] font-mono text-blue-600 font-bold mt-1">{{ $client->client_number }}</p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="px-2 py-0.5 bg-blue-50 text-blue-700 text-[10px] font-black rounded border border-blue-100">
                                {{ $client->active_accounts_count }} Actifs
                            </span>
                        </td>
                        <td class="font-bold text-slate-700">{{ number_format($client->transactions_count) }} Ops</td>
                        <td><span class="font-black text-slate-900">{{ number_format($client->total_balance, 0, ',', ' ') }} <small class="text-[9px] text-slate-400">XOF</small></span></td>
                        <td class="text-right">
                            <span class="bank-badge {{ $client->kyc_status === 'approved' ? 'badge-success' : 'badge-warning' }} !text-[8px]">
                                KYC {{ $client->kyc_status === 'approved' ? 'APPROUVÉ' : 'EN ATTENTE' }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Journal des Activités Récentes -->
    <div class="bank-card overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <h3 class="text-[10px] font-extrabold text-slate-700 uppercase tracking-widest">Journal des Opérations Récentes</h3>
            <span class="text-[9px] font-bold text-slate-400 uppercase">Audit des 20 dernières transactions</span>
        </div>
        <div class="overflow-x-auto">
            <table class="bank-table">
                <thead>
                    <tr>
                        <th>Date & Heure</th>
                        <th>Référence</th>
                        <th>Client / Compte</th>
                        <th>Type d'Opération</th>
                        <th>Montant</th>
                        <th class="text-right">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recentActivities as $activity)
                    <tr class="hover:bg-slate-50/50">
                        <td class="text-[10px] font-bold text-slate-500">{{ $activity->transaction_date->format('d/m/Y H:i') }}</td>
                        <td class="text-[10px] font-mono font-bold text-blue-600">{{ $activity->transaction_reference }}</td>
                        <td>
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-slate-800">{{ $activity->account->client->full_name }}</span>
                                <span class="text-[9px] text-slate-400">{{ $activity->account->account_number }}</span>
                            </div>
                        </td>
                        <td>
                            @php
                                $typeColor = match($activity->transaction_type) {
                                    'deposit' => 'emerald',
                                    'withdrawal' => 'rose',
                                    'transfer' => 'blue',
                                    default => 'slate'
                                };
                            @endphp
                            <span class="text-[9px] font-black uppercase text-{{ $typeColor }}-600 bg-{{ $typeColor }}-50 px-2 py-0.5 rounded border border-{{ $typeColor }}-100">
                                {{ match($activity->transaction_type) {
                                    'deposit' => 'Dépôt',
                                    'withdrawal' => 'Retrait',
                                    'transfer' => 'Transfert',
                                    'payout' => 'Décaissement',
                                    default => ucfirst($activity->transaction_type)
                                } }}
                            </span>
                        </td>
                        <td class="font-black text-slate-800 text-xs">{{ number_format($activity->amount, 0, ',', ' ') }} <small class="text-slate-400">XOF</small></td>
                        <td class="text-right">
                            <span class="bank-badge {{ $activity->status === 'completed' ? 'badge-success' : ($activity->status === 'pending' ? 'badge-warning' : 'badge-danger') }} !text-[8px]">
                                {{ strtoupper($activity->status) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-400 text-xs italic">Aucune activité enregistrée sur la période</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // Configuration Thème Système
    const chartTheme = {
        font: { family: "'Inter', sans-serif" },
        grid: { color: 'rgba(0,0,0,0.03)' }
    };

    // Tendance de Performance
    const dailyPerformanceCtx = document.getElementById('dailyPerformanceChart').getContext('2d');
    new Chart(dailyPerformanceCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($dailyPerformance->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d M'))) !!},
            datasets: [{
                label: 'Volume Ops',
                data: {!! json_encode($dailyPerformance->pluck('transactions_count')) !!},
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.05)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                yAxisID: 'y'
            }, {
                label: 'Valeur Actifs (XOF)',
                data: {!! json_encode($dailyPerformance->pluck('total_amount')) !!},
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
                y: { display: true, grid: chartTheme.grid, ticks: { font: { size: 9, weight: '700' } } },
                y1: { position: 'right', display: true, grid: { display: false }, ticks: { font: { size: 9, weight: '700' } } },
                x: { grid: { display: false }, ticks: { font: { size: 9, weight: '700' } } }
            }
        }
    });

    // Distribution par Type de Compte
    const accountTypeCtx = document.getElementById('accountTypeChart').getContext('2d');
    new Chart(accountTypeCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($accountTypeDistribution->pluck('account_type')->map(fn($t) => match($t) { 'savings' => 'Épargne', 'tontine' => 'Tontine', default => ucfirst($t) })) !!},
            datasets: [{
                data: {!! json_encode($accountTypeDistribution->pluck('count')) !!},
                backgroundColor: ['#3b82f6', '#a855f7', '#10b981'],
                borderWidth: 0,
                cutout: '75%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { weight: '700', size: 9 } } } }
        }
    });

    // Distribution par Méthode de Paiement
    const paymentMethodCtx = document.getElementById('paymentMethodChart').getContext('2d');
    new Chart(paymentMethodCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($paymentMethodDistribution->pluck('payment_method')->map(fn($m) => match($m) { 'cash' => 'Espèces', 'mobile_money' => 'Paiement Mobile', 'bank_transfer' => 'Banque', default => ucfirst(str_replace('_', ' ', $m)) })) !!},
            datasets: [{
                data: {!! json_encode($paymentMethodDistribution->pluck('count')) !!},
                backgroundColor: ['#10b981', '#3b82f6', '#f59e0b'],
                borderWidth: 0,
                cutout: '75%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { weight: '700', size: 9 } } } }
        }
    });
</script>

<style>
    @media print {
        .no-print { display: none !important; }
        .bank-card { box-shadow: none !important; border: 1px solid #e2e8f0 !important; }
        body { background: white !important; }
    }
</style>
@endsection
