@extends('layouts.app_admin')

@section('title', 'Rapport Global des Prêts')

@section('content')
<div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
    <!-- En-tête -->
    <div class="flex flex-col gap-4 mb-6 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="mb-2 text-2xl font-semibold text-gray-900">Rapport Global des Prêts</h1>
            <p class="text-gray-600">Analyse et statistiques du portefeuille de prêts</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.loans.index') }}" class="inline-flex items-center px-4 py-2 text-gray-700 transition-colors border border-gray-300 rounded-lg hover:bg-gray-50">
                <i class="mr-2 fas fa-arrow-left"></i>Retour
            </a>
            {{-- <button onclick="window.print()" class="inline-flex items-center px-4 py-2 text-gray-700 transition-colors border border-gray-300 rounded-lg no-print hover:bg-gray-50">
                <i class="mr-2 fas fa-print"></i>Imprimer
            </button>
            <a href="{{ route('admin.loans.export-report') }}?period={{ $period }}&start_date={{ $startDate->format('Y-m-d') }}&end_date={{ $endDate->format('Y-m-d') }}"
               class="inline-flex items-center px-4 py-2 text-white transition-colors bg-green-600 rounded-lg no-print hover:bg-green-700">
                <i class="mr-2 fas fa-file-excel"></i>Exporter Excel
            </a> --}}
        </div>
    </div>

    <!-- Filtres de période -->
    <div class="p-6 mb-6 bg-white rounded-lg shadow-sm no-print">
        <form method="GET" action="{{ route('admin.loans.report') }}" id="reportForm">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
                <div class="md:col-span-3">
                    <label class="block mb-2 text-sm font-medium text-gray-700">Période</label>
                    <select name="period" id="period" onchange="toggleCustomDates()"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="7days" {{ $period === '7days' ? 'selected' : '' }}>7 derniers jours</option>
                        <option value="30days" {{ $period === '30days' ? 'selected' : '' }}>30 derniers jours</option>
                        <option value="90days" {{ $period === '90days' ? 'selected' : '' }}>90 derniers jours</option>
                        <option value="year" {{ $period === 'year' ? 'selected' : '' }}>Cette année</option>
                        <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Personnalisée</option>
                    </select>
                </div>

                <div class="md:col-span-3" id="start_date_field" style="display: {{ $period === 'custom' ? 'block' : 'none' }};">
                    <label class="block mb-2 text-sm font-medium text-gray-700">Date de Début</label>
                    <input type="date" name="start_date" value="{{ request('start_date', $startDate->format('Y-m-d')) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div class="md:col-span-3" id="end_date_field" style="display: {{ $period === 'custom' ? 'block' : 'none' }};">
                    <label class="block mb-2 text-sm font-medium text-gray-700">Date de Fin</label>
                    <input type="date" name="end_date" value="{{ request('end_date', $endDate->format('Y-m-d')) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                {{-- <div class="flex items-end md:col-span-3">
                    <button type="submit" class="w-full px-6 py-2 text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700">
                        <i class="mr-2 fas fa-search"></i>Générer le Rapport
                    </button>
                </div> --}}
            </div>
        </form>
    </div>

    <!-- Période sélectionnée -->
    <div class="p-4 mb-6 border border-blue-200 rounded-lg bg-blue-50">
        <p class="text-sm text-blue-800">
            <i class="mr-2 fas fa-calendar-alt"></i>
            <strong>Période analysée:</strong> Du {{ $startDate->format('d/m/Y') }} au {{ $endDate->format('d/m/Y') }}
            <span class="ml-2">({{ $startDate->diffInDays($endDate) + 1 }} jours)</span>
        </p>
    </div>

    <!-- Statistiques principales -->
    <div class="grid grid-cols-1 gap-4 mb-6 sm:grid-cols-2 lg:grid-cols-4">
        <div class="p-6 bg-white border-l-4 border-blue-500 rounded-lg shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="mb-1 text-sm text-gray-600">Total Demandes</p>
                    <h4 class="text-3xl font-bold text-gray-900">{{ number_format($stats['total_loans']) }}</h4>
                    @if(isset($stats['pending_loans']))
                    <p class="mt-1 text-xs text-gray-500">{{ $stats['pending_loans'] }} en attente</p>
                    @endif
                </div>
                <div class="text-blue-500">
                    <i class="text-4xl fas fa-file-invoice-dollar"></i>
                </div>
            </div>
        </div>

        <div class="p-6 bg-white border-l-4 border-green-500 rounded-lg shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="mb-1 text-sm text-gray-600">Prêts Approuvés</p>
                    <h4 class="text-3xl font-bold text-gray-900">{{ number_format($stats['approved_loans']) }}</h4>
                    @php
                        $approvalRate = $stats['total_loans'] > 0 ? ($stats['approved_loans'] / $stats['total_loans']) * 100 : 0;
                    @endphp
                    <p class="mt-1 text-xs text-green-600">{{ number_format($approvalRate, 1) }}% de taux d'approbation</p>
                </div>
                <div class="text-green-500">
                    <i class="text-4xl fas fa-check-circle"></i>
                </div>
            </div>
        </div>

        <div class="p-6 bg-white border-l-4 border-purple-500 rounded-lg shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="mb-1 text-sm text-gray-600">Prêts Décaissés</p>
                    <h4 class="text-3xl font-bold text-gray-900">{{ number_format($stats['disbursed_loans']) }}</h4>
                    @php
                        $disbursementRate = $stats['approved_loans'] > 0 ? ($stats['disbursed_loans'] / $stats['approved_loans']) * 100 : 0;
                    @endphp
                    <p class="mt-1 text-xs text-purple-600">{{ number_format($disbursementRate, 1) }}% de taux de décaissement</p>
                </div>
                <div class="text-purple-500">
                    <i class="text-4xl fas fa-money-bill-wave"></i>
                </div>
            </div>
        </div>

        <div class="p-6 bg-white border-l-4 rounded-lg shadow-sm border-cyan-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="mb-1 text-sm text-gray-600">Portfolio Actif</p>
                    <h4 class="text-xl font-bold text-gray-900">{{ number_format($stats['active_portfolio'], 0, ',', ' ') }}</h4>
                    <p class="mt-1 text-xs text-gray-500">FCFA</p>
                </div>
                <div class="text-cyan-500">
                    <i class="text-4xl fas fa-wallet"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Montants financiers -->
    <div class="grid grid-cols-1 gap-6 mb-6 lg:grid-cols-3">
        <div class="p-6 bg-white rounded-lg shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Montants Décaissés</h3>
                <i class="text-purple-500 fas fa-arrow-circle-up"></i>
            </div>
            <div class="text-center">
                <p class="text-4xl font-bold text-purple-600">{{ number_format($stats['total_disbursed'], 0, ',', ' ') }}</p>
                <p class="mt-2 text-sm text-gray-600">FCFA</p>
            </div>
            <div class="pt-4 mt-4 space-y-2 border-t border-gray-200">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Nombre de prêts:</span>
                    <span class="font-semibold text-gray-900">{{ number_format($stats['disbursed_loans']) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Montant moyen:</span>
                    <span class="font-semibold text-gray-900">
                        {{ $stats['disbursed_loans'] > 0 ? number_format($stats['total_disbursed'] / $stats['disbursed_loans'], 0, ',', ' ') : 0 }} FCFA
                    </span>
                </div>
            </div>
        </div>

        <div class="p-6 bg-white rounded-lg shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Montants Collectés</h3>
                <i class="text-green-500 fas fa-arrow-circle-down"></i>
            </div>
            <div class="text-center">
                <p class="text-4xl font-bold text-green-600">{{ number_format($stats['total_collected'], 0, ',', ' ') }}</p>
                <p class="mt-2 text-sm text-gray-600">FCFA</p>
            </div>
            <div class="pt-4 mt-4 space-y-2 border-t border-gray-200">
                @php
                    $collectionRate = $stats['total_disbursed'] > 0 ? ($stats['total_collected'] / $stats['total_disbursed']) * 100 : 0;
                @endphp
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Taux de recouvrement:</span>
                    <span class="font-semibold {{ $collectionRate >= 80 ? 'text-green-600' : 'text-orange-600' }}">
                        {{ number_format($collectionRate, 1) }}%
                    </span>
                </div>
                <div class="w-full h-2 mt-2 bg-gray-200 rounded-full">
                    <div class="h-2 transition-all duration-300 bg-green-600 rounded-full" style="width: {{ min($collectionRate, 100) }}%"></div>
                </div>
                @if(isset($stats['repayment_rate']))
                <div class="flex justify-between pt-2 mt-2 text-xs border-t">
                    <span class="text-gray-500">Taux de remboursement:</span>
                    <span class="font-semibold text-green-600">{{ number_format($stats['repayment_rate'], 1) }}%</span>
                </div>
                @endif
            </div>
        </div>

        <div class="p-6 bg-white rounded-lg shadow-sm border-l-4 border-red-500">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 uppercase tracking-tighter">Surveillance du Risque (PAR)</h3>
                <i class="text-red-500 fas fa-shield-virus"></i>
            </div>
            
            <div class="space-y-4">
                <!-- PAR 30 principal -->
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase">PAR 30 (Benchmark)</p>
                        <p class="text-3xl font-black text-red-600 font-numeric">{{ number_format($stats['par_30'], 1) }}%</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-bold text-gray-500 uppercase">PAR 90 (Critique)</p>
                        <p class="text-xl font-black text-rose-800 font-numeric">{{ number_format($stats['par_90'], 1) }}%</p>
                    </div>
                </div>

                <!-- Barre de progression Matrice -->
                <div class="w-full h-3 bg-gray-100 rounded-full overflow-hidden flex shadow-inner">
                    <div class="bg-amber-400 h-full border-r border-white/20" style="width: {{ $stats['par_1'] }}%" title="PAR 1: {{ $stats['par_1'] }}%"></div>
                    <div class="bg-orange-500 h-full border-r border-white/20" style="width: {{ $stats['par_30'] }}%" title="PAR 30: {{ $stats['par_30'] }}%"></div>
                    <div class="bg-red-600 h-full" style="width: {{ $stats['par_90'] }}%" title="PAR 90: {{ $stats['par_90'] }}%"></div>
                </div>

                <!-- Détails Matrice -->
                <div class="grid grid-cols-2 gap-x-4 gap-y-2 border-t pt-3 border-gray-100">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-gray-400 uppercase">PAR 1 Jours +</span>
                        <span class="text-xs font-bold text-amber-600">{{ number_format($stats['par_1'], 1) }}%</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-gray-400 uppercase">PAR 60 Jours +</span>
                        <span class="text-xs font-bold text-orange-700">{{ number_format($stats['par_60'], 1) }}%</span>
                    </div>
                </div>
                
                <p class="text-[9px] text-gray-400 italic font-medium leading-tight">
                    * Le PAR représente la part de l'encours total des crédits dont au moins une échéance est en retard de plus de X jours.
                </p>
            </div>
        </div>
    </div>

    <!-- Statistiques de remboursement -->
    @if(isset($repaymentStats))
    <div class="grid grid-cols-1 gap-6 mb-6 md:grid-cols-3">
        <div class="p-6 bg-white rounded-lg shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="mb-1 text-sm text-gray-600">Paiements à Temps</p>
                    <h4 class="text-2xl font-bold text-green-600">{{ number_format($repaymentStats['on_time_payments']) }}</h4>
                </div>
                <i class="text-3xl text-green-500 fas fa-check-double"></i>
            </div>
        </div>

        <div class="p-6 bg-white rounded-lg shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="mb-1 text-sm text-gray-600">Paiements en Retard</p>
                    <h4 class="text-2xl font-bold text-orange-600">{{ number_format($repaymentStats['late_payments']) }}</h4>
                </div>
                <i class="text-3xl text-orange-500 fas fa-clock"></i>
            </div>
        </div>

        <div class="p-6 bg-white rounded-lg shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="mb-1 text-sm text-gray-600">Total Pénalités</p>
                    <h4 class="text-xl font-bold text-red-600">{{ number_format($repaymentStats['total_penalties'], 0, ',', ' ') }}</h4>
                    <p class="mt-1 text-xs text-gray-500">FCFA</p>
                </div>
                <i class="text-3xl text-red-500 fas fa-gavel"></i>
            </div>
        </div>
    </div>
    @endif

    <!-- Graphiques -->
    <div class="grid grid-cols-1 gap-6 mb-6 lg:grid-cols-2">
        <!-- Prêts par statut -->
        <div class="p-6 bg-white rounded-lg shadow-sm">
            <h3 class="flex items-center mb-4 text-lg font-semibold text-gray-900">
                <i class="mr-2 text-blue-500 fas fa-chart-pie"></i>
                Répartition par Statut
            </h3>

            @if($loansByStatus->count() > 0)
            <div class="space-y-3">
                @php
                    $statusLabels = [
                        'pending' => ['label' => 'En Attente', 'color' => 'yellow', 'icon' => 'fa-clock'],
                        'approved' => ['label' => 'Approuvé', 'color' => 'green', 'icon' => 'fa-check'],
                        'disbursed' => ['label' => 'Décaissé', 'color' => 'blue', 'icon' => 'fa-money-bill-wave'],
                        'active' => ['label' => 'Actif', 'color' => 'indigo', 'icon' => 'fa-play-circle'],
                        'completed' => ['label' => 'Soldé', 'color' => 'gray', 'icon' => 'fa-check-circle'],
                        'rejected' => ['label' => 'Rejeté', 'color' => 'red', 'icon' => 'fa-times-circle'],
                        'defaulted' => ['label' => 'En Défaut', 'color' => 'red', 'icon' => 'fa-exclamation-circle'],
                    ];
                    $totalCount = $loansByStatus->sum('count');
                @endphp

                @foreach($loansByStatus as $item)
                @php
                    $config = $statusLabels[$item->status] ?? ['label' => ucfirst($item->status), 'color' => 'gray', 'icon' => 'fa-circle'];
                    $percentage = $totalCount > 0 ? ($item->count / $totalCount) * 100 : 0;
                @endphp
                <div class="p-3 transition-all border border-gray-100 rounded-lg hover:shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="flex items-center text-sm font-medium text-gray-900">
                            <i class="mr-2 text-{{ $config['color'] }}-600 fas {{ $config['icon'] }}"></i>
                            {{ $config['label'] }}
                        </span>
                        <span class="text-sm text-gray-600">{{ $item->count }} <span class="text-xs">({{ number_format($percentage, 1) }}%)</span></span>
                    </div>
                    <div class="w-full h-3 bg-gray-200 rounded-full">
                        <div class="bg-{{ $config['color'] }}-600 h-3 rounded-full transition-all duration-300" style="width: {{ $percentage }}%"></div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">
                        Montant total: <span class="font-semibold">{{ number_format($item->total, 0, ',', ' ') }} FCFA</span>
                    </p>
                </div>
                @endforeach
            </div>
            @else
            <div class="py-12 text-center">
                <i class="mb-3 text-4xl text-gray-300 fas fa-chart-pie"></i>
                <p class="text-gray-500">Aucune donnée disponible pour cette période</p>
            </div>
            @endif
        </div>

        <!-- Prêts par niveau de risque -->
        <div class="p-6 bg-white rounded-lg shadow-sm">
            <h3 class="flex items-center mb-4 text-lg font-semibold text-gray-900">
                <i class="mr-2 text-orange-500 fas fa-exclamation-triangle"></i>
                Répartition par Niveau de Risque
            </h3>

            @if($loansByRisk->count() > 0)
            <div class="space-y-3">
                @php
                    $riskLabels = [
                        'low' => ['label' => 'Risque Faible', 'color' => 'green', 'icon' => 'fa-shield-alt'],
                        'medium' => ['label' => 'Risque Moyen', 'color' => 'yellow', 'icon' => 'fa-shield-alt'],
                        'high' => ['label' => 'Risque Élevé', 'color' => 'orange', 'icon' => 'fa-exclamation-triangle'],
                        'very_high' => ['label' => 'Risque Très Élevé', 'color' => 'red', 'icon' => 'fa-skull-crossbones'],
                    ];
                    $totalRiskCount = $loansByRisk->sum('count');
                @endphp

                @foreach($loansByRisk as $item)
                @php
                    $config = $riskLabels[$item->risk_level] ?? ['label' => ucfirst($item->risk_level), 'color' => 'gray', 'icon' => 'fa-question-circle'];
                    $percentage = $totalRiskCount > 0 ? ($item->count / $totalRiskCount) * 100 : 0;
                @endphp
                <div class="p-3 transition-all border border-gray-100 rounded-lg hover:shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="flex items-center text-sm font-medium text-gray-900">
                            <i class="mr-2 text-{{ $config['color'] }}-600 fas {{ $config['icon'] }}"></i>
                            {{ $config['label'] }}
                        </span>
                        <span class="text-sm text-gray-600">{{ $item->count }} <span class="text-xs">({{ number_format($percentage, 1) }}%)</span></span>
                    </div>
                    <div class="w-full h-3 bg-gray-200 rounded-full">
                        <div class="bg-{{ $config['color'] }}-600 h-3 rounded-full transition-all duration-300" style="width: {{ $percentage }}%"></div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">
                        Montant total: <span class="font-semibold">{{ number_format($item->total, 0, ',', ' ') }} FCFA</span>
                    </p>
                </div>
                @endforeach
            </div>
            @else
            <div class="py-12 text-center">
                <i class="mb-3 text-4xl text-gray-300 fas fa-exclamation-triangle"></i>
                <p class="text-gray-500">Aucune donnée disponible pour cette période</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Évolution des décaissements -->
    <div class="p-6 mb-6 bg-white rounded-lg shadow-sm">
        <h3 class="flex items-center mb-4 text-lg font-semibold text-gray-900">
            <i class="mr-2 text-purple-500 fas fa-chart-line"></i>
            Évolution des Décaissements
        </h3>

        @if($disbursementTimeline->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">Nombre</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">Montant Total</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Progression</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @php
                        $maxAmount = $disbursementTimeline->max('total');
                    @endphp
                    @foreach($disbursementTimeline as $item)
                    @php
                        $barWidth = $maxAmount > 0 ? ($item->total / $maxAmount) * 100 : 0;
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($item->date)->format('d/m/Y') }}
                            <span class="text-xs text-gray-500">({{ \Carbon\Carbon::parse($item->date)->locale('fr')->isoFormat('dddd') }})</span>
                        </td>
                        <td class="px-6 py-4 text-sm font-semibold text-center text-blue-600 whitespace-nowrap">
                            {{ $item->count }}
                        </td>
                        <td class="px-6 py-4 text-sm font-semibold text-right text-gray-900 whitespace-nowrap">
                            {{ number_format($item->total, 0, ',', ' ') }} <span class="text-xs text-gray-500">FCFA</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-1 h-4 bg-gray-200 rounded-full">
                                    <div class="h-4 transition-all duration-300 bg-blue-600 rounded-full" style="width: {{ $barWidth }}%"></div>
                                </div>
                                <span class="ml-2 text-xs text-gray-600">{{ number_format($barWidth, 0) }}%</span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="font-semibold bg-gray-50">
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-900">TOTAL</td>
                        <td class="px-6 py-4 text-sm text-center text-blue-600">{{ $disbursementTimeline->sum('count') }}</td>
                        <td class="px-6 py-4 text-sm text-right text-gray-900">{{ number_format($disbursementTimeline->sum('total'), 0, ',', ' ') }} FCFA</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @else
        <div class="py-12 text-center">
            <i class="mb-3 text-4xl text-gray-300 fas fa-chart-line"></i>
            <p class="text-gray-500">Aucun décaissement durant cette période</p>
        </div>
        @endif
    </div>

    <!-- Indicateurs de performance -->
    <div class="p-6 mb-6 bg-white rounded-lg shadow-sm">
        <h3 class="flex items-center mb-6 text-lg font-semibold text-gray-900">
            <i class="mr-2 text-indigo-500 fas fa-chart-bar"></i>
            Indicateurs de Performance Clés (KPI)
        </h3>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
            <!-- Taux d'approbation -->
            <div class="p-4 transition-all border border-gray-200 rounded-lg hover:shadow-md">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-medium text-gray-600">Taux d'Approbation</p>
                    <i class="text-blue-500 fas fa-percentage"></i>
                </div>
                @php
                    $approvalRate = $stats['total_loans'] > 0 ? ($stats['approved_loans'] / $stats['total_loans']) * 100 : 0;
                @endphp
                <p class="mb-2 text-3xl font-bold text-blue-600">{{ number_format($approvalRate, 1) }}%</p>
                <div class="w-full h-2 bg-gray-200 rounded-full">
                    <div class="h-2 transition-all duration-300 bg-blue-600 rounded-full" style="width: {{ $approvalRate }}%"></div>
                </div>
                <p class="mt-2 text-xs text-gray-500">{{ $stats['approved_loans'] }} / {{ $stats['total_loans'] }} demandes</p>
            </div>

            <!-- Taux de décaissement -->
            <div class="p-4 transition-all border border-gray-200 rounded-lg hover:shadow-md">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-medium text-gray-600">Taux de Décaissement</p>
                    <i class="text-green-500 fas fa-percentage"></i>
                </div>
                @php
                    $disbursementRate = $stats['approved_loans'] > 0 ? ($stats['disbursed_loans'] / $stats['approved_loans']) * 100 : 0;
                @endphp
                <p class="mb-2 text-3xl font-bold text-green-600">{{ number_format($disbursementRate, 1) }}%</p>
                <div class="w-full h-2 bg-gray-200 rounded-full">
                    <div class="h-2 transition-all duration-300 bg-green-600 rounded-full" style="width: {{ $disbursementRate }}%"></div>
                </div>
                <p class="mt-2 text-xs text-gray-500">{{ $stats['disbursed_loans'] }} / {{ $stats['approved_loans'] }} approuvés</p>
            </div>

            <!-- Montant moyen par prêt -->
            <div class="p-4 transition-all border border-gray-200 rounded-lg hover:shadow-md">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-medium text-gray-600">Montant Moyen</p>
                    <i class="text-purple-500 fas fa-coins"></i>
                </div>
                @php
                    $avgLoanAmount = $stats['disbursed_loans'] > 0 ? $stats['total_disbursed'] / $stats['disbursed_loans'] : 0;
                @endphp
                <p class="mb-2 text-2xl font-bold text-purple-600">{{ number_format($avgLoanAmount, 0, ',', ' ') }}</p>
                <p class="text-xs text-gray-500">FCFA par prêt</p>
            </div>

            <!-- Taux de remboursement -->
            <div class="p-4 transition-all border border-gray-200 rounded-lg hover:shadow-md">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-medium text-gray-600">Taux de Remboursement</p>
                    <i class="text-indigo-500 fas fa-redo-alt"></i>
                </div>
                @php
                    $repaymentRate = isset($stats['repayment_rate']) ? $stats['repayment_rate'] : ($stats['total_disbursed'] > 0 ? ($stats['total_collected'] / $stats['total_disbursed']) * 100 : 0);
                @endphp
                <p class="mb-2 text-3xl font-bold text-indigo-600">{{ number_format($repaymentRate, 1) }}%</p>
                <div class="w-full h-2 bg-gray-200 rounded-full">
                    <div class="h-2 transition-all duration-300 {{ $repaymentRate >= 90 ? 'bg-green-600' : ($repaymentRate >= 70 ? 'bg-yellow-600' : 'bg-red-600') }} rounded-full"
                         style="width: {{ min($repaymentRate, 100) }}%"></div>
                </div>
                <p class="mt-2 text-xs text-gray-500">
                    @if($repaymentRate >= 90)
                        <span class="text-green-600">Excellent</span>
                    @elseif($repaymentRate >= 70)
                        <span class="text-yellow-600">Bon</span>
                    @else
                        <span class="text-red-600">À améliorer</span>
                    @endif
                </p>
            </div>
        </div>
    </div>

    <!-- Analyse comparative -->
    @if(isset($stats['pending_loans']) && isset($stats['rejected_loans']))
    <div class="p-6 bg-white rounded-lg shadow-sm">
        <h3 class="flex items-center mb-6 text-lg font-semibold text-gray-900">
            <i class="mr-2 text-teal-500 fas fa-balance-scale"></i>
            Analyse Comparative des Demandes
        </h3>

        <div class="grid grid-cols-2 gap-6 md:grid-cols-5">
            <div class="text-center">
                <div class="flex items-center justify-center w-16 h-16 mx-auto mb-3 bg-blue-100 rounded-full">
                    <i class="text-2xl text-blue-600 fas fa-file-alt"></i>
                </div>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_loans']) }}</p>
                <p class="text-xs text-gray-600">Total Demandes</p>
            </div>

            <div class="text-center">
                <div class="flex items-center justify-center w-16 h-16 mx-auto mb-3 bg-yellow-100 rounded-full">
                    <i class="text-2xl text-yellow-600 fas fa-clock"></i>
                </div>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['pending_loans']) }}</p>
                <p class="text-xs text-gray-600">En Attente</p>
            </div>

            <div class="text-center">
                <div class="flex items-center justify-center w-16 h-16 mx-auto mb-3 bg-green-100 rounded-full">
                    <i class="text-2xl text-green-600 fas fa-check-circle"></i>
                </div>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['approved_loans']) }}</p>
                <p class="text-xs text-gray-600">Approuvés</p>
            </div>

            <div class="text-center">
                <div class="flex items-center justify-center w-16 h-16 mx-auto mb-3 bg-red-100 rounded-full">
                    <i class="text-2xl text-red-600 fas fa-times-circle"></i>
                </div>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['rejected_loans']) }}</p>
                <p class="text-xs text-gray-600">Rejetés</p>
            </div>

            <div class="text-center">
                <div class="flex items-center justify-center w-16 h-16 mx-auto mb-3 bg-purple-100 rounded-full">
                    <i class="text-2xl text-purple-600 fas fa-money-bill-wave"></i>
                </div>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['disbursed_loans']) }}</p>
                <p class="text-xs text-gray-600">Décaissés</p>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
function toggleCustomDates() {
    const period = document.getElementById('period').value;
    const startField = document.getElementById('start_date_field');
    const endField = document.getElementById('end_date_field');

    if (period === 'custom') {
        startField.style.display = 'block';
        endField.style.display = 'block';
    } else {
        startField.style.display = 'none';
        endField.style.display = 'none';
    }
}

// Soumission automatique du formulaire si changement de période (hors custom)
document.getElementById('period')?.addEventListener('change', function() {
    if (this.value !== 'custom') {
        document.getElementById('reportForm').submit();
    }
});

// Gestion de l'impression
window.onbeforeprint = function() {
    // Cacher les éléments non imprimables
    document.querySelectorAll('.no-print').forEach(el => {
        el.style.display = 'none';
    });

    // Ajuster les marges pour l'impression
    document.body.style.margin = '0';
    document.body.style.padding = '10mm';
}

window.onafterprint = function() {
    // Restaurer l'affichage normal
    document.querySelectorAll('.no-print').forEach(el => {
        el.style.display = '';
    });

    document.body.style.margin = '';
    document.body.style.padding = '';
}

// Animation au chargement
document.addEventListener('DOMContentLoaded', function() {
    // Animer les barres de progression
    const progressBars = document.querySelectorAll('[style*="width"]');
    progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => {
            bar.style.width = width;
        }, 100);
    });
});
</script>

<style>
/* Styles généraux */
@media print {
    body {
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }

    .no-print {
        display: none !important;
    }

    /* Éviter les sauts de page au milieu des sections */
    .shadow-sm, .rounded-lg {
        page-break-inside: avoid;
        break-inside: avoid;
    }

    /* Ajuster les tailles pour l'impression */
    .text-4xl {
        font-size: 2rem;
    }

    .text-3xl {
        font-size: 1.5rem;
    }

    /* Forcer l'affichage des couleurs d'arrière-plan */
    .bg-blue-50, .bg-green-50, .bg-red-50, .bg-yellow-50,
    .bg-purple-50, .bg-gray-50, .border-l-4 {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}

/* Animations */
.transition-all {
    transition: all 0.3s ease-in-out;
}

/* Hover effects */
.hover\:shadow-md:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.hover\:shadow-sm:hover {
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

/* Barres de progression avec animation */
.rounded-full > div {
    transition: width 0.8s ease-in-out;
}

/* Couleurs de background pour les classes Tailwind dynamiques */
.bg-yellow-600 { background-color: #d97706; }
.bg-green-600 { background-color: #059669; }
.bg-blue-600 { background-color: #2563eb; }
.bg-gray-600 { background-color: #4b5563; }
.bg-red-600 { background-color: #dc2626; }
.bg-indigo-600 { background-color: #4f46e5; }
.bg-orange-600 { background-color: #ea580c; }
.bg-purple-600 { background-color: #9333ea; }
.bg-cyan-600 { background-color: #0891b2; }

.text-yellow-600 { color: #d97706; }
.text-green-600 { color: #059669; }
.text-blue-600 { color: #2563eb; }
.text-gray-600 { color: #4b5563; }
.text-red-600 { color: #dc2626; }
.text-indigo-600 { color: #4f46e5; }
.text-orange-600 { color: #ea580c; }
.text-purple-600 { color: #9333ea; }
.text-cyan-600 { color: #0891b2; }

/* Style pour les tooltips (optionnel) */
[data-tooltip]:hover::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    padding: 0.5rem;
    background-color: #1f2937;
    color: white;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    white-space: nowrap;
    z-index: 10;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .text-4xl {
        font-size: 1.875rem;
    }

    .text-3xl {
        font-size: 1.5rem;
    }

    .text-2xl {
        font-size: 1.25rem;
    }
}
</style>
@endsection
