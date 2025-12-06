@extends('layouts.app_admin')

@section('title', 'Comparaison des Utilisateurs')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.reports.users.index') }}"
               class="flex items-center justify-center w-10 h-10 transition bg-gray-100 rounded-lg hover:bg-gray-200">
                <i class="text-gray-600 fas fa-arrow-left"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Comparaison des Utilisateurs</h2>
                <p class="text-sm text-gray-600">Analyse comparative de {{ count($comparisons) }} utilisateurs</p>
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

    <!-- Période -->
    <div class="p-6 text-white shadow-lg bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm opacity-90">Période d'analyse</p>
                <p class="mt-1 text-2xl font-bold">{{ $startDate->format('d/m/Y') }} - {{ $endDate->format('d/m/Y') }}</p>
                <p class="mt-1 text-sm opacity-75">{{ $startDate->diffInDays($endDate) }} jours</p>
            </div>
            <div class="text-right">
                <p class="text-sm opacity-90">Utilisateurs comparés</p>
                <p class="text-4xl font-bold">{{ count($comparisons) }}</p>
            </div>
        </div>
    </div>

    <!-- Tableau de comparaison des utilisateurs -->
    <div class="overflow-hidden bg-white shadow-sm rounded-xl">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b-2 border-purple-200 bg-gradient-to-r from-blue-50 to-purple-50">
                    <tr>
                        <th class="sticky left-0 z-10 px-6 py-4 text-sm font-bold text-left text-gray-700 bg-gray-50">
                            Critère
                        </th>
                        @foreach($comparisons as $comparison)
                        <th class="px-6 py-4 text-center min-w-[200px]">
                            <div class="flex flex-col items-center gap-2">
                                <div class="flex items-center justify-center w-12 h-12 font-bold text-white rounded-full bg-gradient-to-br from-blue-400 to-purple-500">
                                    {{ strtoupper(substr($comparison['user']->first_name, 0, 1) . substr($comparison['user']->last_name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-bold text-gray-800">{{ $comparison['user']->full_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $comparison['user']->email }}</p>
                                    <span class="inline-block px-2 py-1 mt-1 text-xs text-purple-700 bg-purple-100 rounded-full">
                                        {{ ucfirst(str_replace('_', ' ', $comparison['user']->role)) }}
                                    </span>
                                </div>
                            </div>
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <!-- Agence -->
                    <tr class="hover:bg-gray-50">
                        <td class="sticky left-0 z-10 px-6 py-4 font-semibold text-gray-700 bg-gray-50">
                            <i class="mr-2 text-purple-600 fas fa-building"></i>Agence
                        </td>
                        @foreach($comparisons as $comparison)
                        <td class="px-6 py-4 text-center">
                            @if($comparison['user']->agency)
                                <div>
                                    <p class="font-medium text-gray-800">{{ $comparison['user']->agency->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $comparison['user']->agency->city }}</p>
                                </div>
                            @else
                                <span class="italic text-gray-400">Non assigné</span>
                            @endif
                        </td>
                        @endforeach
                    </tr>

                    <!-- Séparateur Clients -->
                    <tr class="bg-blue-50">
                        <td colspan="{{ count($comparisons) + 1 }}" class="px-6 py-3">
                            <h3 class="font-bold text-blue-800">
                                <i class="mr-2 fas fa-users"></i>CLIENTS
                            </h3>
                        </td>
                    </tr>

                    <!-- Total Clients -->
                    <tr class="hover:bg-gray-50">
                        <td class="sticky left-0 z-10 px-6 py-4 font-medium text-gray-700 bg-gray-50">
                            Total clients
                        </td>
                        @php
                            $maxClients = collect($comparisons)->max('clients_total');
                        @endphp
                        @foreach($comparisons as $comparison)
                        <td class="px-6 py-4 text-center">
                            <div class="inline-flex items-center gap-2 px-3 py-2 rounded-lg {{ $comparison['clients_total'] == $maxClients ? 'bg-green-100' : 'bg-gray-100' }}">
                                <span class="text-2xl font-bold {{ $comparison['clients_total'] == $maxClients ? 'text-green-700' : 'text-gray-800' }}">
                                    {{ number_format($comparison['clients_total']) }}
                                </span>
                                @if($comparison['clients_total'] == $maxClients)
                                <i class="text-yellow-500 fas fa-crown"></i>
                                @endif
                            </div>
                        </td>
                        @endforeach
                    </tr>

                    <!-- Clients créés (période) -->
                    <tr class="hover:bg-gray-50">
                        <td class="sticky left-0 z-10 px-6 py-4 font-medium text-gray-700 bg-gray-50">
                            Clients créés (période)
                        </td>
                        @php
                            $maxClientsPeriod = collect($comparisons)->max('clients_period');
                        @endphp
                        @foreach($comparisons as $comparison)
                        <td class="px-6 py-4 text-center">
                            <span class="text-xl font-bold {{ $comparison['clients_period'] == $maxClientsPeriod ? 'text-green-600' : 'text-gray-800' }}">
                                {{ number_format($comparison['clients_period']) }}
                            </span>
                        </td>
                        @endforeach
                    </tr>

                    <!-- KYC Approuvés -->
                    <tr class="hover:bg-gray-50">
                        <td class="sticky left-0 z-10 px-6 py-4 font-medium text-gray-700 bg-gray-50">
                            KYC Approuvés
                        </td>
                        @foreach($comparisons as $comparison)
                        <td class="px-6 py-4 text-center">
                            <div class="space-y-1">
                                <span class="text-xl font-bold text-green-600">{{ number_format($comparison['clients_approved']) }}</span>
                                @if($comparison['clients_total'] > 0)
                                <p class="text-xs text-gray-500">
                                    {{ round(($comparison['clients_approved'] / $comparison['clients_total']) * 100) }}% du total
                                </p>
                                @endif
                            </div>
                        </td>
                        @endforeach
                    </tr>

                    <!-- Séparateur Comptes -->
                    <tr class="bg-green-50">
                        <td colspan="{{ count($comparisons) + 1 }}" class="px-6 py-3">
                            <h3 class="font-bold text-green-800">
                                <i class="mr-2 fas fa-wallet"></i>COMPTES
                            </h3>
                        </td>
                    </tr>

                    <!-- Total Comptes -->
                    <tr class="hover:bg-gray-50">
                        <td class="sticky left-0 z-10 px-6 py-4 font-medium text-gray-700 bg-gray-50">
                            Total comptes
                        </td>
                        @php
                            $maxAccounts = collect($comparisons)->max('accounts_total');
                        @endphp
                        @foreach($comparisons as $comparison)
                        <td class="px-6 py-4 text-center">
                            <div class="inline-flex items-center gap-2 px-3 py-2 rounded-lg {{ $comparison['accounts_total'] == $maxAccounts ? 'bg-green-100' : 'bg-gray-100' }}">
                                <span class="text-2xl font-bold {{ $comparison['accounts_total'] == $maxAccounts ? 'text-green-700' : 'text-gray-800' }}">
                                    {{ number_format($comparison['accounts_total']) }}
                                </span>
                                @if($comparison['accounts_total'] == $maxAccounts)
                                <i class="text-yellow-500 fas fa-crown"></i>
                                @endif
                            </div>
                        </td>
                        @endforeach
                    </tr>

                    <!-- Comptes Actifs -->
                    <tr class="hover:bg-gray-50">
                        <td class="sticky left-0 z-10 px-6 py-4 font-medium text-gray-700 bg-gray-50">
                            Comptes actifs
                        </td>
                        @foreach($comparisons as $comparison)
                        <td class="px-6 py-4 text-center">
                            <div class="space-y-1">
                                <span class="text-xl font-bold text-green-600">{{ number_format($comparison['accounts_active']) }}</span>
                                @if($comparison['accounts_total'] > 0)
                                <p class="text-xs text-gray-500">
                                    {{ round(($comparison['accounts_active'] / $comparison['accounts_total']) * 100) }}% du total
                                </p>
                                @endif
                            </div>
                        </td>
                        @endforeach
                    </tr>

                    <!-- Séparateur Transactions -->
                    <tr class="bg-purple-50">
                        <td colspan="{{ count($comparisons) + 1 }}" class="px-6 py-3">
                            <h3 class="font-bold text-purple-800">
                                <i class="mr-2 fas fa-exchange-alt"></i>TRANSACTIONS (Période)
                            </h3>
                        </td>
                    </tr>

                    <!-- Nombre de transactions -->
                    <tr class="hover:bg-gray-50">
                        <td class="sticky left-0 z-10 px-6 py-4 font-medium text-gray-700 bg-gray-50">
                            Nombre de transactions
                        </td>
                        @php
                            $maxTransactions = collect($comparisons)->max('transactions_count');
                        @endphp
                        @foreach($comparisons as $comparison)
                        <td class="px-6 py-4 text-center">
                            <div class="inline-flex items-center gap-2 px-3 py-2 rounded-lg {{ $comparison['transactions_count'] == $maxTransactions ? 'bg-purple-100' : 'bg-gray-100' }}">
                                <span class="text-2xl font-bold {{ $comparison['transactions_count'] == $maxTransactions ? 'text-purple-700' : 'text-gray-800' }}">
                                    {{ number_format($comparison['transactions_count']) }}
                                </span>
                                @if($comparison['transactions_count'] == $maxTransactions)
                                <i class="text-yellow-500 fas fa-crown"></i>
                                @endif
                            </div>
                        </td>
                        @endforeach
                    </tr>

                    <!-- Dépôts -->
                    <tr class="hover:bg-gray-50">
                        <td class="sticky left-0 z-10 px-6 py-4 font-medium text-gray-700 bg-gray-50">
                            Dépôts
                        </td>
                        @foreach($comparisons as $comparison)
                        <td class="px-6 py-4 text-center">
                            <span class="text-lg font-bold text-green-600">{{ number_format($comparison['deposits_count']) }}</span>
                        </td>
                        @endforeach
                    </tr>

                    <!-- Retraits -->
                    <tr class="hover:bg-gray-50">
                        <td class="sticky left-0 z-10 px-6 py-4 font-medium text-gray-700 bg-gray-50">
                            Retraits
                        </td>
                        @foreach($comparisons as $comparison)
                        <td class="px-6 py-4 text-center">
                            <span class="text-lg font-bold text-red-600">{{ number_format($comparison['withdrawals_count']) }}</span>
                        </td>
                        @endforeach
                    </tr>

                    <!-- Séparateur Montants -->
                    <tr class="bg-orange-50">
                        <td colspan="{{ count($comparisons) + 1 }}" class="px-6 py-3">
                            <h3 class="font-bold text-orange-800">
                                <i class="mr-2 fas fa-coins"></i>MONTANTS (Période)
                            </h3>
                        </td>
                    </tr>

                    <!-- Montant total traité -->
                    <tr class="hover:bg-gray-50">
                        <td class="sticky left-0 z-10 px-6 py-4 font-medium text-gray-700 bg-gray-50">
                            Montant total traité
                        </td>
                        @php
                            $maxAmount = collect($comparisons)->max('transactions_amount');
                        @endphp
                        @foreach($comparisons as $comparison)
                        <td class="px-6 py-4 text-center">
                            <div class="inline-flex flex-col items-center gap-1 px-3 py-2 rounded-lg {{ $comparison['transactions_amount'] == $maxAmount ? 'bg-orange-100' : 'bg-gray-100' }}">
                                <span class="text-xl font-bold {{ $comparison['transactions_amount'] == $maxAmount ? 'text-orange-700' : 'text-gray-800' }}">
                                    {{ number_format($comparison['transactions_amount'], 0, ',', ' ') }}
                                </span>
                                <span class="text-xs text-gray-600">FCFA</span>
                                @if($comparison['transactions_amount'] == $maxAmount)
                                <i class="mt-1 text-yellow-500 fas fa-crown"></i>
                                @endif
                            </div>
                        </td>
                        @endforeach
                    </tr>

                    <!-- Transaction moyenne -->
                    <tr class="hover:bg-gray-50">
                        <td class="sticky left-0 z-10 px-6 py-4 font-medium text-gray-700 bg-gray-50">
                            Transaction moyenne
                        </td>
                        @foreach($comparisons as $comparison)
                        <td class="px-6 py-4 text-center">
                            <div class="space-y-1">
                                <span class="text-lg font-bold text-blue-600">
                                    {{ number_format($comparison['avg_transaction'], 0, ',', ' ') }}
                                </span>
                                <p class="text-xs text-gray-500">FCFA</p>
                            </div>
                        </td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Graphiques de comparaison -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Graphique Clients -->
        <div class="p-6 bg-white shadow-sm rounded-xl">
            <h3 class="mb-4 text-lg font-bold text-gray-800">
                <i class="mr-2 text-blue-600 fas fa-users"></i>
                Comparaison Clients
            </h3>
            <div class="h-64">
                <canvas id="clientsChart"></canvas>
            </div>
        </div>

        <!-- Graphique Comptes -->
        <div class="p-6 bg-white shadow-sm rounded-xl">
            <h3 class="mb-4 text-lg font-bold text-gray-800">
                <i class="mr-2 text-green-600 fas fa-wallet"></i>
                Comparaison Comptes
            </h3>
            <div class="h-64">
                <canvas id="accountsChart"></canvas>
            </div>
        </div>

        <!-- Graphique Transactions -->
        <div class="p-6 bg-white shadow-sm rounded-xl">
            <h3 class="mb-4 text-lg font-bold text-gray-800">
                <i class="mr-2 text-purple-600 fas fa-exchange-alt"></i>
                Comparaison Transactions
            </h3>
            <div class="h-64">
                <canvas id="transactionsChart"></canvas>
            </div>
        </div>

        <!-- Graphique Montants -->
        <div class="p-6 bg-white shadow-sm rounded-xl">
            <h3 class="mb-4 text-lg font-bold text-gray-800">
                <i class="mr-2 text-orange-600 fas fa-coins"></i>
                Comparaison Montants
            </h3>
            <div class="h-64">
                <canvas id="amountsChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Analyse comparative -->
    <div class="p-6 bg-white shadow-sm rounded-xl">
        <h3 class="mb-4 text-lg font-bold text-gray-800">
            <i class="mr-2 text-indigo-600 fas fa-chart-bar"></i>
            Analyse Comparative
        </h3>
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            @php
                $topPerformer = collect($comparisons)->sortByDesc('transactions_amount')->first();
                $mostActive = collect($comparisons)->sortByDesc('transactions_count')->first();
                $bestAcquisition = collect($comparisons)->sortByDesc('clients_period')->first();
            @endphp

            <div class="p-4 border border-yellow-200 rounded-lg bg-gradient-to-br from-yellow-50 to-orange-50">
                <div class="flex items-center gap-3 mb-3">
                    <i class="text-3xl text-yellow-500 fas fa-trophy"></i>
                    <div>
                        <p class="text-sm text-gray-600">Meilleur Performer</p>
                        <p class="font-bold text-gray-800">{{ $topPerformer['user']->full_name }}</p>
                    </div>
                </div>
                <p class="text-2xl font-bold text-orange-600">{{ number_format($topPerformer['transactions_amount'], 0, ',', ' ') }} FCFA</p>
                <p class="mt-1 text-xs text-gray-500">Volume de transactions le plus élevé</p>
            </div>

            <div class="p-4 border border-blue-200 rounded-lg bg-gradient-to-br from-blue-50 to-purple-50">
                <div class="flex items-center gap-3 mb-3">
                    <i class="text-3xl text-blue-500 fas fa-bolt"></i>
                    <div>
                        <p class="text-sm text-gray-600">Plus Actif</p>
                        <p class="font-bold text-gray-800">{{ $mostActive['user']->full_name }}</p>
                    </div>
                </div>
                <p class="text-2xl font-bold text-blue-600">{{ number_format($mostActive['transactions_count']) }}</p>
                <p class="mt-1 text-xs text-gray-500">Nombre de transactions effectuées</p>
            </div>

            <div class="p-4 border border-green-200 rounded-lg bg-gradient-to-br from-green-50 to-emerald-50">
                <div class="flex items-center gap-3 mb-3">
                    <i class="text-3xl text-green-500 fas fa-user-plus"></i>
                    <div>
                        <p class="text-sm text-gray-600">Meilleure Acquisition</p>
                        <p class="font-bold text-gray-800">{{ $bestAcquisition['user']->full_name }}</p>
                    </div>
                </div>
                <p class="text-2xl font-bold text-green-600">{{ number_format($bestAcquisition['clients_period']) }}</p>
                <p class="mt-1 text-xs text-gray-500">Nouveaux clients sur la période</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const userNames = {!! json_encode(collect($comparisons)->pluck('user.full_name')) !!};
const colors = [
    'rgb(59, 130, 246)',
    'rgb(168, 85, 247)',
    'rgb(34, 197, 94)',
    'rgb(249, 115, 22)',
    'rgb(236, 72, 153)'
];

// Graphique Clients
new Chart(document.getElementById('clientsChart'), {
    type: 'bar',
    data: {
        labels: userNames,
        datasets: [{
            label: 'Total Clients',
            data: {!! json_encode(collect($comparisons)->pluck('clients_total')) !!},
            backgroundColor: colors[0],
        }, {
            label: 'KYC Approuvés',
            data: {!! json_encode(collect($comparisons)->pluck('clients_approved')) !!},
            backgroundColor: colors[2],
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top' }
        }
    }
});

// Graphique Comptes
new Chart(document.getElementById('accountsChart'), {
    type: 'bar',
    data: {
        labels: userNames,
        datasets: [{
            label: 'Total Comptes',
            data: {!! json_encode(collect($comparisons)->pluck('accounts_total')) !!},
            backgroundColor: colors[1],
        }, {
            label: 'Comptes Actifs',
            data: {!! json_encode(collect($comparisons)->pluck('accounts_active')) !!},
            backgroundColor: colors[2],
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top' }
        }
    }
});

// Graphique Transactions
new Chart(document.getElementById('transactionsChart'), {
    type: 'bar',
    data: {
        labels: userNames,
        datasets: [{
            label: 'Total Transactions',
            data: {!! json_encode(collect($comparisons)->pluck('transactions_count')) !!},
            backgroundColor: colors[1],
        }, {
            label: 'Dépôts',
            data: {!! json_encode(collect($comparisons)->pluck('deposits_count')) !!},
            backgroundColor: colors[2],
        }, {
            label: 'Retraits',
            data: {!! json_encode(collect($comparisons)->pluck('withdrawals_count')) !!},
            backgroundColor: colors[3],
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top' }
        }
    }
});

// Graphique Montants
new Chart(document.getElementById('amountsChart'), {
    type: 'bar',
    data: {
        labels: userNames,
        datasets: [{
            label: 'Montant Total (FCFA)',
            data: {!! json_encode(collect($comparisons)->pluck('transactions_amount')) !!},
            backgroundColor: colors[3],
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top' }
        },
        scales: {
            y: {
                ticks: {
                    callback: function(value) {
                        return new Intl.NumberFormat('fr-FR').format(value) + ' FCFA';
                    }
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
