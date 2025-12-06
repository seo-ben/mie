@extends('layouts.app_admin')

@section('title', 'Analytics Transactions')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <!-- En-tête -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Analytics Transactions</h1>
                <p class="text-gray-600">Analyse détaillée des performances</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('admin.transactions.index') }}"
                   class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Retour
                </a>
            </div>
        </div>

        <!-- Sélecteur de période -->
        <div class="bg-white rounded-xl shadow-sm mb-6 p-6">
            <form method="GET" action="{{ route('admin.transactions.analytics') }}" class="flex items-end gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Période</label>
                    <select name="period"
                            onchange="toggleCustomDates(this)"
                            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="7days" {{ $period === '7days' ? 'selected' : '' }}>7 derniers jours</option>
                        <option value="30days" {{ $period === '30days' ? 'selected' : '' }}>30 derniers jours</option>
                        <option value="90days" {{ $period === '90days' ? 'selected' : '' }}>90 derniers jours</option>
                        <option value="year" {{ $period === 'year' ? 'selected' : '' }}>Cette année</option>
                        <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Personnalisé</option>
                    </select>
                </div>

                <div id="custom-dates" class="flex gap-4 {{ $period !== 'custom' ? 'hidden' : '' }}">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date début</label>
                        <input type="date"
                               name="start_date"
                               value="{{ request('start_date', $startDate->format('Y-m-d')) }}"
                               class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date fin</label>
                        <input type="date"
                               name="end_date"
                               value="{{ request('end_date', $endDate->format('Y-m-d')) }}"
                               class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-sync mr-2"></i>Actualiser
                </button>
            </form>
        </div>

        <!-- KPIs principaux -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Volume -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-gray-600">Volume Total</h3>
                    <div class="bg-blue-100 p-3 rounded-lg">
                        <i class="fas fa-chart-line text-blue-600 text-xl"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-900 mb-2">{{ number_format($analytics['volume']['total']) }}</p>
                <p class="text-sm text-gray-600">transactions</p>
                @if($analytics['comparison']['count_change'] != 0)
                    <div class="mt-3 flex items-center text-sm {{ $analytics['comparison']['count_change'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                        <i class="fas fa-arrow-{{ $analytics['comparison']['count_change'] > 0 ? 'up' : 'down' }} mr-1"></i>
                        {{ abs($analytics['comparison']['count_change']) }}% vs période précédente
                    </div>
                @endif
            </div>

            <!-- Montant -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-gray-600">Montant Total</h3>
                    <div class="bg-green-100 p-3 rounded-lg">
                        <i class="fas fa-coins text-green-600 text-xl"></i>
                    </div>
                </div>
                <p class="text-2xl font-bold text-gray-900 mb-1">{{ number_format($analytics['volume']['amount'], 0, ',', ' ') }}</p>
                <p class="text-sm text-gray-600">FCFA</p>
                @if($analytics['comparison']['amount_change'] != 0)
                    <div class="mt-3 flex items-center text-sm {{ $analytics['comparison']['amount_change'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                        <i class="fas fa-arrow-{{ $analytics['comparison']['amount_change'] > 0 ? 'up' : 'down' }} mr-1"></i>
                        {{ abs($analytics['comparison']['amount_change']) }}% vs période précédente
                    </div>
                @endif
            </div>

            <!-- Taux de réussite -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-gray-600">Taux de Réussite</h3>
                    <div class="bg-purple-100 p-3 rounded-lg">
                        <i class="fas fa-check-circle text-purple-600 text-xl"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-900 mb-2">{{ $analytics['success_rate'] }}%</p>
                <div class="w-full bg-gray-200 rounded-full h-2 mt-3">
                    <div class="bg-purple-600 h-2 rounded-full" style="width: {{ $analytics['success_rate'] }}%"></div>
                </div>
            </div>

            <!-- Ticket moyen -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-gray-600">Ticket Moyen</h3>
                    <div class="bg-cyan-100 p-3 rounded-lg">
                        <i class="fas fa-calculator text-cyan-600 text-xl"></i>
                    </div>
                </div>
                <p class="text-2xl font-bold text-gray-900 mb-1">
                    {{ $analytics['volume']['total'] > 0 ? number_format($analytics['volume']['amount'] / $analytics['volume']['total'], 0, ',', ' ') : 0 }}
                </p>
                <p class="text-sm text-gray-600">FCFA / transaction</p>
            </div>
        </div>

        <!-- Graphiques -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Évolution temporelle -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Évolution des Transactions</h3>
                <div class="relative h-64">
                    <canvas id="timeline-chart"></canvas>
                </div>
            </div>

            <!-- Répartition par type -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Répartition par Type</h3>
                <div class="relative h-64">
                    <canvas id="type-chart"></canvas>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Répartition par méthode de paiement -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Méthodes de Paiement</h3>
                <div class="relative h-64">
                    <canvas id="payment-method-chart"></canvas>
                </div>
            </div>

            <!-- Transactions par heure -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Distribution Horaire</h3>
                <div class="relative h-64">
                    <canvas id="hourly-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Clients -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Top 10 Clients</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rang</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Compte</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nb Transactions</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket Moyen</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($analytics['top_clients'] as $index => $client)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center justify-center w-8 h-8 rounded-full {{ $index < 3 ? 'bg-gradient-to-r from-yellow-400 to-yellow-600 text-white' : 'bg-gray-100 text-gray-600' }} font-bold">
                                    {{ $index + 1 }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.clients.show', $client->account->client_id) }}"
                                   class="font-semibold text-blue-600 hover:text-blue-800">
                                    {{ $client->account->client->first_name }} {{ $client->account->client->last_name }}
                                </a>
                                <div class="text-sm text-gray-500">{{ $client->account->client->client_number }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('admin.accounts.show', $client->account_id) }}"
                                   class="text-sm text-blue-600 hover:text-blue-800">
                                    {{ $client->account->account_number }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-semibold text-gray-900">
                                {{ number_format($client->transaction_count) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-bold text-green-600">
                                {{ number_format($client->total_amount, 0, ',', ' ') }} FCFA
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-900">
                                {{ number_format($client->total_amount / $client->transaction_count, 0, ',', ' ') }} FCFA
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    function toggleCustomDates(select) {
        const customDates = document.getElementById('custom-dates');
        if (select.value === 'custom') {
            customDates.classList.remove('hidden');
        } else {
            customDates.classList.add('hidden');
        }
    }

    // Configuration des couleurs
    const colors = {
        blue: 'rgb(59, 130, 246)',
        green: 'rgb(34, 197, 94)',
        red: 'rgb(239, 68, 68)',
        yellow: 'rgb(234, 179, 8)',
        purple: 'rgb(168, 85, 247)',
        cyan: 'rgb(6, 182, 212)',
        orange: 'rgb(249, 115, 22)',
        pink: 'rgb(236, 72, 153)'
    };

    // Configuration commune pour tous les graphiques
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: true,
        aspectRatio: 2
    };

    // Graphique d'évolution temporelle
    const timelineCtx = document.getElementById('timeline-chart').getContext('2d');
    new Chart(timelineCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($analytics['timeline']->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d/m'))) !!},
            datasets: [{
                label: 'Nombre de transactions',
                data: {!! json_encode($analytics['timeline']->pluck('count')) !!},
                borderColor: colors.blue,
                backgroundColor: colors.blue + '20',
                tension: 0.4,
                fill: true,
                borderWidth: 2
            }]
        },
        options: {
            ...commonOptions,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    // Graphique par type
    const typeCtx = document.getElementById('type-chart').getContext('2d');
    new Chart(typeCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($analytics['by_type']->pluck('transaction_type')->map(fn($t) => ucfirst(str_replace('_', ' ', $t)))) !!},
            datasets: [{
                data: {!! json_encode($analytics['by_type']->pluck('total')) !!},
                backgroundColor: [colors.cyan, colors.red, colors.purple, colors.green, colors.orange, colors.pink],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            ...commonOptions,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: {
                            size: 11
                        }
                    }
                }
            }
        }
    });

    // Graphique par méthode de paiement
    const paymentCtx = document.getElementById('payment-method-chart').getContext('2d');
    new Chart(paymentCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($analytics['by_payment_method']->pluck('payment_method')->map(fn($m) => ucfirst(str_replace('_', ' ', $m)))) !!},
            datasets: [{
                label: 'Montant (FCFA)',
                data: {!! json_encode($analytics['by_payment_method']->pluck('total')) !!},
                backgroundColor: [colors.green, colors.blue, colors.purple],
                borderRadius: 6
            }]
        },
        options: {
            ...commonOptions,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString() + ' FCFA';
                        }
                    }
                }
            }
        }
    });

    // Graphique par heure
    const hourlyCtx = document.getElementById('hourly-chart').getContext('2d');
    new Chart(hourlyCtx, {
        type: 'bar',
        data: {
            labels: Array.from({length: 24}, (_, i) => i + 'h'),
            datasets: [{
                label: 'Transactions',
                data: (() => {
                    const hourlyData = {!! json_encode($analytics['by_hour']) !!};
                    const dataArray = new Array(24).fill(0);
                    hourlyData.forEach(item => {
                        dataArray[item.hour] = item.count;
                    });
                    return dataArray;
                })(),
                backgroundColor: colors.purple,
                borderRadius: 4
            }]
        },
        options: {
            ...commonOptions,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
</script>
@endpush
@endsection
