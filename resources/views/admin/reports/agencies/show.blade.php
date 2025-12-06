@extends('layouts.app_admin')

@section('title', 'Rapport - ' . $agency->name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.agencies.index') }}"
               class="flex items-center justify-center w-10 h-10 transition bg-gray-100 rounded-lg hover:bg-gray-200">
                <i class="text-gray-600 fas fa-arrow-left"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Rapport d'Agence</h2>
                <p class="text-sm text-gray-600">{{ $agency->name }} - {{ $agency->city }}</p>
            </div>
        </div>
        <div class="flex gap-2">
            <button onclick="window.print()"
                    class="flex items-center gap-2 px-4 py-2 text-white transition bg-blue-600 rounded-lg hover:bg-blue-700">
                <i class="fas fa-print"></i>
                <span>Imprimer</span>
            </button>
        </div>
    </div>

    <!-- Filtre de période -->
    <div class="p-4 bg-white shadow-sm rounded-xl">
        <form method="GET" action="{{ route('admin.reports.agencies.show', $agency->id) }}"
              class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block mb-2 text-sm font-medium text-gray-700">Date début</label>
                <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block mb-2 text-sm font-medium text-gray-700">Date fin</label>
                <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="px-6 py-2 text-white transition bg-blue-600 rounded-lg hover:bg-blue-700">
                <i class="mr-2 fas fa-filter"></i>Appliquer
            </button>
        </form>
        <p class="mt-2 text-sm text-gray-500">
            <i class="mr-1 fas fa-calendar-alt"></i>
            Période: {{ $startDate->format('d/m/Y') }} au {{ $endDate->format('d/m/Y') }}
            ({{ $startDate->diffInDays($endDate) }} jours)
        </p>
    </div>

    <!-- Informations de l'agence -->
    <div class="p-6 text-white shadow-lg bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <div class="flex items-center gap-4">
                <div class="flex items-center justify-center w-16 h-16 text-2xl rounded-full bg-white/20 backdrop-blur-sm">
                    <i class="fas fa-building"></i>
                </div>
                <div>
                    <p class="text-sm opacity-80">Agence</p>
                    <h3 class="text-2xl font-bold">{{ $agency->name }}</h3>
                    <p class="mt-1 text-sm">{{ $agency->city }} - {{ $agency->region }}</p>
                </div>
            </div>

            <div class="p-4 rounded-lg bg-white/20 backdrop-blur-sm">
                <p class="text-sm opacity-80">Code Agence</p>
                <p class="font-mono text-2xl font-bold">{{ $agency->code }}</p>
            </div>

            <div class="p-4 rounded-lg bg-white/20 backdrop-blur-sm">
                <p class="text-sm opacity-80">Manager</p>
                @if($agency->manager)
                    <p class="text-xl font-bold">{{ $agency->manager->full_name }}</p>
                    <p class="text-sm opacity-80">{{ $agency->manager->phone }}</p>
                @else
                    <p class="text-xl font-bold opacity-60">Non assigné</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Statistiques Globales -->
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
        <div class="p-6 bg-white border-l-4 border-blue-500 shadow-sm rounded-xl">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-lg">
                    <i class="text-xl text-blue-600 fas fa-users"></i>
                </div>
                <span class="px-2 py-1 text-xs text-blue-700 rounded-full bg-blue-50">Équipe</span>
            </div>
            <p class="text-3xl font-bold text-gray-800">{{ $stats['total_users'] }}</p>
            <p class="mt-1 text-sm text-gray-600">Utilisateurs</p>
            <div class="pt-4 mt-4 text-sm border-t border-gray-100">
                <div class="flex justify-between">
                    <span class="text-gray-600">Actifs:</span>
                    <span class="font-semibold text-green-600">{{ $stats['active_users'] }}</span>
                </div>
            </div>
        </div>

        <div class="p-6 bg-white border-l-4 border-green-500 shadow-sm rounded-xl">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-lg">
                    <i class="text-xl text-green-600 fas fa-user-friends"></i>
                </div>
                <span class="px-2 py-1 text-xs text-green-700 rounded-full bg-green-50">Clients</span>
            </div>
            <p class="text-3xl font-bold text-gray-800">{{ number_format($stats['total_clients']) }}</p>
            <p class="mt-1 text-sm text-gray-600">Total clients</p>
            <div class="pt-4 mt-4 text-sm border-t border-gray-100">
                <div class="flex justify-between">
                    <span class="text-gray-600">Période:</span>
                    <span class="font-semibold text-blue-600">{{ $stats['clients_period'] }}</span>
                </div>
            </div>
        </div>

        <div class="p-6 bg-white border-l-4 border-purple-500 shadow-sm rounded-xl">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center justify-center w-12 h-12 bg-purple-100 rounded-lg">
                    <i class="text-xl text-purple-600 fas fa-wallet"></i>
                </div>
                <span class="px-2 py-1 text-xs text-purple-700 rounded-full bg-purple-50">Comptes</span>
            </div>
            <p class="text-3xl font-bold text-gray-800">{{ number_format($stats['total_accounts']) }}</p>
            <p class="mt-1 text-sm text-gray-600">Total comptes</p>
            <div class="pt-4 mt-4 text-sm border-t border-gray-100">
                <div class="flex justify-between">
                    <span class="text-gray-600">Actifs:</span>
                    <span class="font-semibold text-green-600">{{ $stats['active_accounts'] }}</span>
                </div>
            </div>
        </div>

        <div class="p-6 bg-white border-l-4 border-orange-500 shadow-sm rounded-xl">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center justify-center w-12 h-12 bg-orange-100 rounded-lg">
                    <i class="text-xl text-orange-600 fas fa-coins"></i>
                </div>
                <span class="px-2 py-1 text-xs text-orange-700 rounded-full bg-orange-50">Soldes</span>
            </div>
            <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['total_balance'], 0, ',', ' ') }}</p>
            <p class="mt-1 text-sm text-gray-600">FCFA en comptes</p>
        </div>
    </div>

    <!-- Statistiques de la période -->
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div class="p-6 shadow-sm bg-gradient-to-br from-blue-50 to-indigo-100 rounded-xl">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800">
                    <i class="mr-2 text-blue-600 fas fa-exchange-alt"></i>
                    Transactions
                </h3>
                <span class="px-3 py-1 text-sm text-blue-700 bg-blue-100 rounded-full">Période</span>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600">Nombre</p>
                    <p class="text-3xl font-bold text-blue-600">{{ number_format($stats['transactions_period']) }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Montant</p>
                    <p class="text-2xl font-bold text-green-600">{{ number_format($stats['amount_period'], 0, ',', ' ') }}</p>
                    <p class="text-xs text-gray-500">FCFA</p>
                </div>
            </div>
        </div>

        <div class="p-6 shadow-sm bg-gradient-to-br from-green-50 to-emerald-100 rounded-xl">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800">
                    <i class="mr-2 text-green-600 fas fa-user-plus"></i>
                    Nouveaux Clients
                </h3>
                <span class="px-3 py-1 text-sm text-green-700 bg-green-100 rounded-full">Période</span>
            </div>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-4xl font-bold text-green-600">{{ number_format($stats['clients_period']) }}</p>
                    <p class="mt-1 text-sm text-gray-600">Clients acquis</p>
                </div>
                @if($stats['total_clients'] > 0)
                <div class="text-center">
                    <div class="text-3xl font-bold text-blue-600">{{ round(($stats['clients_period'] / $stats['total_clients']) * 100) }}%</div>
                    <p class="text-xs text-gray-500">du total</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Performance quotidienne -->
    <div class="p-6 bg-white shadow-sm rounded-xl">
        <h3 class="mb-4 text-lg font-bold text-gray-800">
            <i class="mr-2 text-purple-600 fas fa-chart-line"></i>
            Performance Quotidienne de l'Agence
        </h3>
        <div class="h-80">
            <canvas id="dailyPerformanceChart"></canvas>
        </div>
    </div>

    <!-- Performance par utilisateur -->
    <div class="overflow-hidden bg-white shadow-sm rounded-xl">
        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-pink-50">
            <h3 class="text-lg font-bold text-gray-800">
                <i class="mr-2 text-purple-600 fas fa-users-cog"></i>
                Performance par Utilisateur
            </h3>
            <p class="mt-1 text-sm text-gray-600">Classé par nombre de transactions</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-xs font-semibold text-left text-gray-600 uppercase">Rang</th>
                        <th class="px-6 py-3 text-xs font-semibold text-left text-gray-600 uppercase">Utilisateur</th>
                        <th class="px-6 py-3 text-xs font-semibold text-left text-gray-600 uppercase">Rôle</th>
                        <th class="px-6 py-3 text-xs font-semibold text-left text-gray-600 uppercase">Clients Total</th>
                        <th class="px-6 py-3 text-xs font-semibold text-left text-gray-600 uppercase">Clients Période</th>
                        <th class="px-6 py-3 text-xs font-semibold text-left text-gray-600 uppercase">Transactions</th>
                        <th class="px-6 py-3 text-xs font-semibold text-left text-gray-600 uppercase">Montant Total</th>
                        <th class="px-6 py-3 text-xs font-semibold text-left text-gray-600 uppercase">Dernière Activité</th>
                        <th class="px-6 py-3 text-xs font-semibold text-right text-gray-600 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($userPerformances as $index => $performance)
                    <tr class="transition hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <span class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm
                                {{ $index === 0 ? 'bg-yellow-100 text-yellow-700' : '' }}
                                {{ $index === 1 ? 'bg-gray-200 text-gray-700' : '' }}
                                {{ $index === 2 ? 'bg-orange-100 text-orange-700' : '' }}
                                {{ $index > 2 ? 'bg-gray-100 text-gray-600' : '' }}">
                                {{ $index + 1 }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-10 h-10 text-sm font-semibold text-white rounded-full bg-gradient-to-br from-blue-400 to-purple-500">
                                    {{ strtoupper(substr($performance['user']->first_name, 0, 1) . substr($performance['user']->last_name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800">{{ $performance['user']->full_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $performance['user']->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 text-xs font-medium text-purple-700 bg-purple-100 rounded-full">
                                {{ ucfirst(str_replace('_', ' ', $performance['user']->role)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-lg font-bold text-gray-800">{{ number_format($performance['clients_count']) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 text-sm font-medium text-blue-700 bg-blue-100 rounded-full">
                                +{{ number_format($performance['clients_period']) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-lg font-bold text-purple-600">{{ number_format($performance['transactions_count']) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div>
                                <p class="font-bold text-green-600">{{ number_format($performance['transactions_amount'], 0, ',', ' ') }}</p>
                                <p class="text-xs text-gray-500">FCFA</p>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($performance['last_activity'])
                                <div>
                                    <p class="text-sm text-gray-800">{{ $performance['last_activity']->format('d/m/Y') }}</p>
                                    <p class="text-xs text-gray-500">{{ $performance['last_activity']->diffForHumans() }}</p>
                                </div>
                            @else
                                <span class="text-sm italic text-gray-400">Aucune</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.reports.users.show', $performance['user']->id) }}"
                               class="inline-flex items-center gap-2 px-3 py-2 text-blue-600 transition rounded-lg hover:bg-blue-50"
                               title="Voir le rapport détaillé">
                                <i class="fas fa-chart-line"></i>
                                <span class="text-sm">Rapport</span>
                            </a>
                        </td>
                    </tr>
                    @endforeach

                    @if(count($userPerformances) === 0)
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                            <i class="mb-4 text-4xl text-gray-300 fas fa-users"></i>
                            <p class="text-lg font-medium">Aucun utilisateur dans cette agence</p>
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- Classement et insights -->
    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
        @if(count($userPerformances) > 0)
            @php
                $topPerformer = $userPerformances[0] ?? null;
                $mostActiveUser = collect($userPerformances)->sortByDesc('transactions_count')->first();
                $bestAcquirer = collect($userPerformances)->sortByDesc('clients_period')->first();
            @endphp

            @if($topPerformer)
            <div class="p-6 border-2 border-yellow-200 shadow-sm bg-gradient-to-br from-yellow-50 to-orange-50 rounded-xl">
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex items-center justify-center w-12 h-12 bg-yellow-100 rounded-full">
                        <i class="text-2xl text-yellow-600 fas fa-trophy"></i>
                    </div>
                    <div>
                        <p class="text-xs tracking-wide text-gray-600 uppercase">Top Performer</p>
                        <p class="font-bold text-gray-800">{{ $topPerformer['user']->full_name }}</p>
                    </div>
                </div>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Transactions:</span>
                        <span class="font-bold">{{ number_format($topPerformer['transactions_count']) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Montant:</span>
                        <span class="font-bold text-green-600">{{ number_format($topPerformer['transactions_amount'], 0, ',', ' ') }} F</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Clients:</span>
                        <span class="font-bold text-blue-600">{{ $topPerformer['clients_count'] }}</span>
                    </div>
                </div>
            </div>
            @endif

            @if($mostActiveUser)
            <div class="p-6 border-2 border-blue-200 shadow-sm bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl">
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-full">
                        <i class="text-2xl text-blue-600 fas fa-bolt"></i>
                    </div>
                    <div>
                        <p class="text-xs tracking-wide text-gray-600 uppercase">Plus Actif</p>
                        <p class="font-bold text-gray-800">{{ $mostActiveUser['user']->full_name }}</p>
                    </div>
                </div>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Transactions:</span>
                        <span class="font-bold text-blue-600">{{ number_format($mostActiveUser['transactions_count']) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Dernière activité:</span>
                        <span class="font-bold">{{ $mostActiveUser['last_activity'] ? $mostActiveUser['last_activity']->format('d/m/Y') : 'N/A' }}</span>
                    </div>
                </div>
            </div>
            @endif

            @if($bestAcquirer)
            <div class="p-6 border-2 border-green-200 shadow-sm bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl">
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-full">
                        <i class="text-2xl text-green-600 fas fa-user-plus"></i>
                    </div>
                    <div>
                        <p class="text-xs tracking-wide text-gray-600 uppercase">Meilleur Acquéreur</p>
                        <p class="font-bold text-gray-800">{{ $bestAcquirer['user']->full_name }}</p>
                    </div>
                </div>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Nouveaux clients:</span>
                        <span class="font-bold text-green-600">+{{ number_format($bestAcquirer['clients_period']) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total clients:</span>
                        <span class="font-bold">{{ $bestAcquirer['clients_count'] }}</span>
                    </div>
                </div>
            </div>
            @endif
        @endif
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Graphique de performance quotidienne
const dailyPerformanceCtx = document.getElementById('dailyPerformanceChart').getContext('2d');
new Chart(dailyPerformanceCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode($dailyPerformance->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d/m'))) !!},
        datasets: [{
            label: 'Nombre de Transactions',
            data: {!! json_encode($dailyPerformance->pluck('count')) !!},
            borderColor: 'rgb(147, 51, 234)',
            backgroundColor: 'rgba(147, 51, 234, 0.1)',
            tension: 0.4,
            fill: true,
            yAxisID: 'y'
        }, {
            label: 'Montant (FCFA)',
            data: {!! json_encode($dailyPerformance->pluck('total')) !!},
            borderColor: 'rgb(34, 197, 94)',
            backgroundColor: 'rgba(34, 197, 94, 0.1)',
            tension: 0.4,
            fill: true,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        plugins: {
            legend: {
                display: true,
                position: 'top',
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        if (context.parsed.y !== null) {
                            if (context.datasetIndex === 1) {
                                label += new Intl.NumberFormat('fr-FR').format(context.parsed.y) + ' FCFA';
                            } else {
                                label += context.parsed.y;
                            }
                        }
                        return label;
                    }
                }
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Nombre de transactions'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Montant (FCFA)'
                },
                grid: {
                    drawOnChartArea: false,
                }
            }
        }
    }
});
</script>

<style>
@media print {
    .no-print {
        display: none !important;
    }
}
</style>
@endsection
