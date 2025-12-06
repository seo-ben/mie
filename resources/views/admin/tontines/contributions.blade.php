@extends('layouts.app_admin')

@section('title', 'Historique des Cotisations')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- En-tête -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 mb-2">Historique des Cotisations</h1>
            <p class="text-gray-600">{{ $tontine->account->client->first_name }} {{ $tontine->account->client->last_name }} - {{ $tontine->account->account_number }}</p>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('admin.tontines.show', $tontine->id) }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Retour
            </a>
            <a href="{{ route('admin.tontines.contribute-form', $tontine->id) }}" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>Nouvelle Cotisation
            </a>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white border-l-4 border-purple-500 rounded-lg shadow-sm p-6">
            <p class="text-gray-600 text-sm mb-1">Total Cotisé</p>
            <p class="text-2xl font-bold text-purple-600">{{ number_format($stats['total_amount'], 0, ',', ' ') }} FCFA</p>
        </div>
        <div class="bg-white border-l-4 border-blue-500 rounded-lg shadow-sm p-6">
            <p class="text-gray-600 text-sm mb-1">Nombre de Cotisations</p>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_count']) }}</p>
        </div>
        <div class="bg-white border-l-4 border-cyan-500 rounded-lg shadow-sm p-6">
            <p class="text-gray-600 text-sm mb-1">Montant Moyen</p>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['average_amount'], 0, ',', ' ') }} FCFA</p>
        </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-6">
            <form method="GET" action="{{ route('admin.tontines.contributions', $tontine->id) }}">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cycle</label>
                        <select name="cycle_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="">Tous les cycles</option>
                            @foreach($cycles as $cycle)
                                <option value="{{ $cycle->id }}" {{ request('cycle_id') == $cycle->id ? 'selected' : '' }}>
                                    Cycle #{{ $cycle->cycle_number }}
                                    ({{ $cycle->start_date->format('d/m/Y') }} - {{ $cycle->end_date->format('d/m/Y') }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date Début</label>
                        <input type="date"
                               name="date_from"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                               value="{{ request('date_from') }}">
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date Fin</label>
                        <input type="date"
                               name="date_to"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                               value="{{ request('date_to') }}">
                    </div>

                    <div class="md:col-span-3 flex items-end gap-2">
                        <button type="submit" class="flex-1 px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                            <i class="fas fa-filter mr-2"></i>Filtrer
                        </button>
                        <a href="{{ route('admin.tontines.contributions', $tontine->id) }}" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                            <i class="fas fa-redo"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des cotisations -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h5 class="text-lg font-semibold text-gray-900">Cotisations ({{ $contributions->total() }})</h5>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Référence</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Méthode de Paiement</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Référence Paiement</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Solde Après</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Traité Par</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($contributions as $contribution)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $contribution->transaction_date->format('d/m/Y') }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ $contribution->transaction_date->format('H:i') }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-mono text-gray-900">
                                {{ $contribution->transaction_reference }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-lg font-bold text-purple-600">
                                {{ number_format($contribution->amount, 0, ',', ' ') }} FCFA
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @switch($contribution->payment_method)
                                @case('cash')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-money-bill-wave mr-1"></i>Espèces
                                    </span>
                                    @break
                                @case('mobile_money')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                        <i class="fas fa-mobile-alt mr-1"></i>
                                        {{ strtoupper($contribution->mobile_money_operator) }}
                                    </span>
                                    @break
                                @case('bank_transfer')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
                                        <i class="fas fa-university mr-1"></i>Virement
                                    </span>
                                    @break
                                @default
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                        {{ $contribution->payment_method }}
                                    </span>
                            @endswitch
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($contribution->payment_reference)
                                <div class="text-sm font-mono text-gray-900">
                                    {{ $contribution->payment_reference }}
                                </div>
                            @else
                                <span class="text-sm text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-600">
                                Avant: {{ number_format($contribution->balance_before, 0, ',', ' ') }}
                            </div>
                            <div class="text-sm font-semibold text-green-600">
                                Après: {{ number_format($contribution->balance_after, 0, ',', ' ') }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                {{ $contribution->processedBy->first_name ?? 'N/A' }} {{ $contribution->processedBy->last_name ?? '' }}
                            </div>
                            @if($contribution->processed_at)
                                <div class="text-xs text-gray-500">
                                    {{ $contribution->processed_at->format('d/m/Y H:i') }}
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @switch($contribution->status)
                                @case('completed')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i>Complété
                                    </span>
                                    @break
                                @case('pending')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                        <i class="fas fa-clock mr-1"></i>En attente
                                    </span>
                                    @break
                                @case('failed')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                        <i class="fas fa-times-circle mr-1"></i>Échoué
                                    </span>
                                    @break
                                @default
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                        {{ $contribution->status }}
                                    </span>
                            @endswitch
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <i class="fas fa-inbox text-5xl text-gray-400 mb-3"></i>
                            <p class="text-gray-500">Aucune cotisation trouvée</p>
                            @if(request()->hasAny(['cycle_id', 'date_from', 'date_to']))
                                <a href="{{ route('admin.tontines.contributions', $tontine->id) }}" class="text-blue-600 hover:text-blue-800 mt-2 inline-block">
                                    Afficher toutes les cotisations
                                </a>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($contributions->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Affichage de <span class="font-medium">{{ $contributions->firstItem() ?? 0 }}</span> à
                    <span class="font-medium">{{ $contributions->lastItem() ?? 0 }}</span> sur
                    <span class="font-medium">{{ $contributions->total() }}</span> cotisations
                </div>
                <div class="flex space-x-1">
                    {{-- Bouton Précédent --}}
                    @if($contributions->onFirstPage())
                        <button disabled class="px-3 py-2 bg-gray-100 text-gray-400 rounded cursor-not-allowed">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                    @else
                        <a href="{{ $contributions->previousPageUrl() }}" class="px-3 py-2 bg-white border border-gray-300 text-gray-700 rounded hover:bg-gray-50">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    @endif

                    {{-- Numéros de page --}}
                    @php
                        $currentPage = $contributions->currentPage();
                        $lastPage = $contributions->lastPage();
                        $start = max(1, $currentPage - 2);
                        $end = min($lastPage, $currentPage + 2);
                    @endphp

                    @if($start > 1)
                        <a href="{{ $contributions->url(1) }}" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded hover:bg-gray-50">
                            1
                        </a>
                        @if($start > 2)
                            <span class="px-3 py-2">...</span>
                        @endif
                    @endif

                    @for($i = $start; $i <= $end; $i++)
                        @if($i == $currentPage)
                            <button class="px-4 py-2 bg-purple-600 text-white rounded font-medium">
                                {{ $i }}
                            </button>
                        @else
                            <a href="{{ $contributions->url($i) }}" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded hover:bg-gray-50">
                                {{ $i }}
                            </a>
                        @endif
                    @endfor

                    @if($end < $lastPage)
                        @if($end < $lastPage - 1)
                            <span class="px-3 py-2">...</span>
                        @endif
                        <a href="{{ $contributions->url($lastPage) }}" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded hover:bg-gray-50">
                            {{ $lastPage }}
                        </a>
                    @endif

                    {{-- Bouton Suivant --}}
                    @if($contributions->hasMorePages())
                        <a href="{{ $contributions->nextPageUrl() }}" class="px-3 py-2 bg-white border border-gray-300 text-gray-700 rounded hover:bg-gray-50">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    @else
                        <button disabled class="px-3 py-2 bg-gray-100 text-gray-400 rounded cursor-not-allowed">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Résumé par cycle -->
    @if($cycles->isNotEmpty())
    <div class="bg-white rounded-lg shadow-sm mt-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h5 class="text-lg font-semibold text-gray-900">Résumé par Cycle</h5>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($cycles as $cycle)
                    @php
                        $cycleContributions = $contributions->filter(function($c) use ($cycle) {
                            return $c->transaction_date >= $cycle->start_date && $c->transaction_date <= $cycle->end_date;
                        });
                        $cycleTotal = $cycleContributions->sum('amount');
                        $cycleCount = $cycleContributions->count();
                    @endphp
                    <div class="border border-gray-200 rounded-lg p-4 hover:border-purple-300 transition-colors">
                        <div class="flex items-center justify-between mb-2">
                            <h6 class="font-semibold text-gray-900">Cycle #{{ $cycle->cycle_number }}</h6>
                            @switch($cycle->status)
                                @case('active')
                                    <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-800">Actif</span>
                                    @break
                                @case('completed')
                                    <span class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-800">Complété</span>
                                    @break
                            @endswitch
                        </div>
                        <div class="text-xs text-gray-500 mb-3">
                            {{ $cycle->start_date->format('d/m/Y') }} → {{ $cycle->end_date->format('d/m/Y') }}
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Cotisations:</span>
                                <span class="font-medium text-gray-900">{{ $cycleCount }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Total:</span>
                                <span class="font-bold text-purple-600">{{ number_format($cycleTotal, 0, ',', ' ') }} FCFA</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Objectif:</span>
                                <span class="font-medium text-gray-900">{{ number_format($cycle->target_amount, 0, ',', ' ') }} FCFA</span>
                            </div>
                            @php
                                $cycleProgress = $cycle->target_amount > 0 ? round(($cycle->collected_amount / $cycle->target_amount) * 100, 1) : 0;
                            @endphp
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-purple-600 h-2 rounded-full" style="width: {{ min($cycleProgress, 100) }}%"></div>
                            </div>
                            <div class="text-xs text-gray-500 text-center">
                                {{ $cycleProgress }}% complété
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
