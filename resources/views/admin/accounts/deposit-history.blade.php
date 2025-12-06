@extends('layouts.app_admin')

@section('title', 'Historique des Dépôts')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- En-tête -->
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900 mb-2">Historique des Dépôts</h1>
        <p class="text-gray-600">Tous les dépôts (épargne) et cotisations (tontine)</p>
    </div>

    <!-- Statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-6">
        <div class="bg-white border-l-4 border-blue-500 rounded-lg shadow-sm p-4">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-600 text-sm">Total Dépôts</p>
                    <h4 class="text-xl font-bold text-gray-900">{{ number_format($stats['total_deposits']) }}</h4>
                </div>
                <i class="fas fa-list text-2xl text-blue-500"></i>
            </div>
        </div>

        <div class="bg-white border-l-4 border-green-500 rounded-lg shadow-sm p-4">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-600 text-sm">Montant Total</p>
                    <h4 class="text-lg font-bold text-gray-900">{{ number_format($stats['total_amount'], 0, ',', ' ') }}</h4>
                    <p class="text-xs text-gray-500">FCFA</p>
                </div>
                <i class="fas fa-coins text-2xl text-green-500"></i>
            </div>
        </div>

        <div class="bg-white border-l-4 border-cyan-500 rounded-lg shadow-sm p-4">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-600 text-sm">Épargne</p>
                    <h4 class="text-xl font-bold text-gray-900">{{ number_format($stats['savings_deposits']) }}</h4>
                </div>
                <i class="fas fa-piggy-bank text-2xl text-cyan-500"></i>
            </div>
        </div>

        <div class="bg-white border-l-4 border-purple-500 rounded-lg shadow-sm p-4">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-600 text-sm">Tontine</p>
                    <h4 class="text-xl font-bold text-gray-900">{{ number_format($stats['tontine_contributions']) }}</h4>
                </div>
                <i class="fas fa-users text-2xl text-purple-500"></i>
            </div>
        </div>

        <div class="bg-white border-l-4 border-orange-500 rounded-lg shadow-sm p-4">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-600 text-sm">Aujourd'hui</p>
                    <h4 class="text-xl font-bold text-gray-900">{{ number_format($stats['deposits_today']) }}</h4>
                </div>
                <i class="fas fa-calendar-day text-2xl text-orange-500"></i>
            </div>
        </div>

        <div class="bg-white border-l-4 border-yellow-500 rounded-lg shadow-sm p-4">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-600 text-sm">Montant Jour</p>
                    <h4 class="text-lg font-bold text-gray-900">{{ number_format($stats['amount_today'], 0, ',', ' ') }}</h4>
                    <p class="text-xs text-gray-500">FCFA</p>
                </div>
                <i class="fas fa-chart-line text-2xl text-yellow-500"></i>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-6">
            <form method="GET" action="{{ route('admin.deposits.history') }}">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
                        <input type="text"
                               name="search"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                               placeholder="Réf., client..."
                               value="{{ request('search') }}">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Type Compte</label>
                        <select name="account_type"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Tous</option>
                            <option value="savings" {{ request('account_type') === 'savings' ? 'selected' : '' }}>
                                Épargne
                            </option>
                            <option value="tontine" {{ request('account_type') === 'tontine' ? 'selected' : '' }}>
                                Tontine
                            </option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Méthode</label>
                        <select name="payment_method"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Toutes</option>
                            <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>
                                Espèces
                            </option>
                            <option value="mobile_money" {{ request('payment_method') === 'mobile_money' ? 'selected' : '' }}>
                                Mobile Money
                            </option>
                            <option value="bank_transfer" {{ request('payment_method') === 'bank_transfer' ? 'selected' : '' }}>
                                Virement
                            </option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date Début</label>
                        <input type="date"
                               name="date_from"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                               value="{{ request('date_from') }}">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date Fin</label>
                        <input type="date"
                               name="date_to"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                               value="{{ request('date_to') }}">
                    </div>

                    <div class="md:col-span-1 flex items-end">
                        <button type="submit"
                                class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des dépôts -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h5 class="text-lg font-semibold text-gray-900">
                Liste des Dépôts ({{ $deposits->total() }})
            </h5>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Référence</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Compte / Client</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Méthode</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Montant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Traité Par</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($deposits as $deposit)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $deposit->transaction_date->format('d/m/Y') }}
                            <div class="text-xs text-gray-500">
                                {{ $deposit->transaction_date->format('H:i') }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-blue-600">
                                {{ $deposit->transaction_reference }}
                            </div>
                            @if($deposit->payment_reference)
                                <div class="text-xs text-gray-500">
                                    {{ $deposit->payment_reference }}
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ route('admin.accounts.show', $deposit->account_id) }}"
                               class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                {{ $deposit->account->account_number }}
                            </a>
                            <div class="text-sm text-gray-900">
                                {{ $deposit->account->client->first_name }}
                                {{ $deposit->account->client->last_name }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ $deposit->account->client->client_number }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($deposit->account->account_type === 'savings')
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-cyan-100 text-cyan-800">
                                    <i class="fas fa-piggy-bank mr-1"></i>Épargne
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                    <i class="fas fa-users mr-1"></i>Tontine
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @switch($deposit->payment_method)
                                @case('cash')
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-money-bill-wave mr-1"></i>Espèces
                                    </span>
                                    @break
                                @case('bank_transfer')
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                        <i class="fas fa-university mr-1"></i>Virement
                                    </span>
                                    @break
                                @case('mobile_money')
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                        <i class="fas fa-mobile-alt mr-1"></i>
                                        @if($deposit->mobile_money_operator === 'tmoney')
                                            TMoney
                                        @elseif($deposit->mobile_money_operator === 'flooz')
                                            Flooz
                                        @else
                                            Mobile Money
                                        @endif
                                    </span>
                                    @break
                            @endswitch
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <span class="text-sm font-bold text-green-600">
                                {{ number_format($deposit->amount, 0, ',', ' ') }}
                            </span>
                            <div class="text-xs text-gray-500">FCFA</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $deposit->processedBy->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($deposit->status === 'completed')
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-1"></i>Complété
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    {{ ucfirst($deposit->status) }}
                                </span>
                            @endif 
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <i class="fas fa-inbox text-5xl text-gray-400 mb-3"></i>
                            <p class="text-gray-500">Aucun dépôt trouvé</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($deposits->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $deposits->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
