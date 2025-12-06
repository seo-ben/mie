@extends('layouts.app_admin')

@section('title', 'Détails Tontine')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- En-tête -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 mb-2">Détails de la Tontine</h1>
            <p class="text-gray-600">{{ $tontine->account->account_number }}</p>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('admin.tontines.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Retour
            </a>
            @if($activeCycle)
                <a href="{{ route('admin.tontines.contribute-form', $tontine->id) }}" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Nouvelle Cotisation
                </a>
            @endif
        </div>
    </div>

    <!-- Informations client -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h5 class="text-lg font-semibold text-gray-900 mb-4">Informations Client</h5>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <p class="text-sm text-gray-600 mb-1">Client</p>
                <a href="{{ route('admin.clients.show', $tontine->account->client_id) }}" class="text-lg font-semibold text-blue-600 hover:text-blue-800">
                    {{ $tontine->account->client->first_name }} {{ $tontine->account->client->last_name }}
                </a>
                <p class="text-sm text-gray-500">{{ $tontine->account->client->client_number }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">Numéro de Compte</p>
                <p class="text-lg font-semibold text-gray-900">{{ $tontine->account->account_number }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">Solde Actuel</p>
                <p class="text-lg font-bold text-purple-600">{{ number_format($tontine->account->balance, 0, ',', ' ') }} FCFA</p>
            </div>
        </div>
    </div>

    <!-- Configuration tontine -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h5 class="text-lg font-semibold text-gray-900 mb-4">Configuration</h5>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <p class="text-sm text-gray-600 mb-1">Montant Tontine</p>
                <p class="text-xl font-bold text-purple-600">{{ number_format($tontine->tontine_amount, 0, ',', ' ') }} FCFA</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">Fréquence de Paiement</p>
                <p class="text-lg font-semibold text-gray-900">
                    @switch($tontine->payment_frequency)
                        @case('daily')
                            <i class="fas fa-calendar-day mr-1"></i>Journalier
                            @break
                        @case('weekly')
                            <i class="fas fa-calendar-week mr-1"></i>Hebdomadaire
                            @break
                        @case('monthly')
                            <i class="fas fa-calendar mr-1"></i>Mensuel
                            @break
                    @endswitch
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">Durée du Cycle</p>
                <p class="text-lg font-semibold text-gray-900">{{ $tontine->cycle_duration_months }} mois</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">Paiement Attendu</p>
                <p class="text-lg font-semibold text-gray-900">{{ number_format($tontine->expected_monthly_payment, 0, ',', ' ') }} FCFA</p>
            </div>
        </div>
    </div>

    <!-- Cycle actif -->
    @if($activeCycle)
    <div class="bg-gradient-to-r from-purple-50 to-pink-50 border-l-4 border-purple-500 rounded-lg shadow-sm p-6 mb-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h5 class="text-lg font-semibold text-gray-900 mb-1">Cycle Actif #{{ $activeCycle->cycle_number }}</h5>
                <p class="text-sm text-gray-600">
                    Du {{ $activeCycle->start_date->format('d/m/Y') }} au {{ $activeCycle->end_date->format('d/m/Y') }}
                </p>
            </div>
            @if($daysRemaining !== null)
                <span class="px-4 py-2 rounded-lg {{ $daysRemaining > 7 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    <i class="fas fa-clock mr-1"></i>
                    {{ abs($daysRemaining) }} jour(s) {{ $daysRemaining < 0 ? 'restant(s)' : 'dépassé(s)' }}
                </span>
            @endif
        </div>

        @php
            $progressPercent = $activeCycle->target_amount > 0
                ? round(($activeCycle->collected_amount / $activeCycle->target_amount) * 100, 2)
                : 0;
        @endphp

        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-lg p-4">
                    <p class="text-sm text-gray-600 mb-1">Objectif</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($activeCycle->target_amount, 0, ',', ' ') }} FCFA</p>
                </div>
                <div class="bg-white rounded-lg p-4">
                    <p class="text-sm text-gray-600 mb-1">Collecté</p>
                    <p class="text-2xl font-bold text-purple-600">{{ number_format($activeCycle->collected_amount, 0, ',', ' ') }} FCFA</p>
                </div>
                <div class="bg-white rounded-lg p-4">
                    <p class="text-sm text-gray-600 mb-1">Restant</p>
                    <p class="text-2xl font-bold text-orange-600">{{ number_format($activeCycle->target_amount - $activeCycle->collected_amount, 0, ',', ' ') }} FCFA</p>
                </div>

            </div>

            <div>
                <div class="flex justify-between text-sm mb-2">
                    <span class="font-medium text-gray-700">Progression</span>
                    <span class="font-bold text-purple-600">{{ $progressPercent }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-4">
                    <div class="bg-gradient-to-r from-purple-600 to-pink-600 h-4 rounded-full transition-all duration-500" style="width: {{ min($progressPercent, 100) }}%"></div>
                </div>
            </div>

            @if($progressPercent >= 100)
            <div class="bg-green-100 border border-green-300 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-600 text-2xl mr-3"></i>
                        <div>
                            <p class="font-semibold text-green-800">Cycle Complété !</p>
                            <p class="text-sm text-green-600">Le cycle peut maintenant être clôturé et le montant débloqué.</p>
                        </div>
                    </div>
                    <form action="{{ route('admin.tontines.payout', $tontine->id) }}" method="POST"
                          onsubmit="return confirm('Êtes-vous sûr de vouloir débloquer le montant ?');">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            <i class="fas fa-hand-holding-usd mr-2"></i>Débloquer
                        </button>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>
    @else
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6">
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle text-yellow-600 text-2xl mr-3"></i>
            <div>
                <p class="font-semibold text-yellow-800">Aucun cycle actif</p>
                <p class="text-sm text-yellow-600">Cette tontine n'a pas de cycle en cours.</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Statistiques globales -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white border-l-4 border-blue-500 rounded-lg shadow-sm p-6">
            <p class="text-gray-600 text-sm mb-1">Total Cycles</p>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['total_cycles'] }}</p>
        </div>
        <div class="bg-white border-l-4 border-green-500 rounded-lg shadow-sm p-6">
            <p class="text-gray-600 text-sm mb-1">Cycles Complétés</p>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['completed_cycles'] }}</p>
        </div>
        <div class="bg-white border-l-4 border-purple-500 rounded-lg shadow-sm p-6">
            <p class="text-gray-600 text-sm mb-1">Total Cotisé</p>
            <p class="text-xl font-bold text-gray-900">{{ number_format($stats['total_contributions'], 0, ',', ' ') }} FCFA</p>
        </div>
        <div class="bg-white border-l-4 border-cyan-500 rounded-lg shadow-sm p-6">
            <p class="text-gray-600 text-sm mb-1">Taux de Complétion</p>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['completion_rate'] }}%</p>
        </div>
    </div>



    <!-- Dernières cotisations -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h5 class="text-lg font-semibold text-gray-900">Dernières Cotisations</h5>
            <a href="{{ route('admin.tontines.contributions', $tontine->id) }}" class="text-blue-600 hover:text-blue-800">
                Voir tout <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Référence</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Méthode</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Traité par</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($tontine->account->transactions->take(10) as $transaction)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $transaction->transaction_date->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">
                            {{ $transaction->transaction_reference }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap font-semibold text-purple-600">
                            {{ number_format($transaction->amount, 0, ',', ' ') }} FCFA
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @switch($transaction->payment_method)
                                @case('cash')
                                    <span class="text-gray-700"><i class="fas fa-money-bill-wave mr-1"></i>Espèces</span>
                                    @break
                                @case('mobile_money')
                                    <span class="text-blue-700">
                                        <i class="fas fa-mobile-alt mr-1"></i>
                                        {{ strtoupper($transaction->mobile_money_operator) }}
                                    </span>
                                    @break
                                @case('bank_transfer')
                                    <span class="text-green-700"><i class="fas fa-university mr-1"></i>Virement</span>
                                    @break
                            @endswitch
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $transaction->processedBy->first_name ?? 'N/A' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            Aucune cotisation enregistrée
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
