@extends('layouts.app_admin')

@section('title', 'Historique des Retraits')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- En-tête -->
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900 mb-2">Historique des Retraits</h1>
        <p class="text-gray-600">Consultation de tous les retraits effectués</p>
    </div>

    <!-- Statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white border-l-4 border-blue-500 rounded-lg shadow-sm p-4">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-600 text-sm">Total Retraits</p>
                    <h4 class="text-xl font-bold text-gray-900">{{ number_format($stats['total_withdrawals']) }}</h4>
                </div>
                <i class="fas fa-hand-holding-usd text-2xl text-blue-500"></i>
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

        <div class="bg-white border-l-4 border-red-500 rounded-lg shadow-sm p-4">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-600 text-sm">Frais Collectés</p>
                    <h4 class="text-lg font-bold text-gray-900">{{ number_format($stats['total_fees'], 0, ',', ' ') }}</h4>
                    <p class="text-xs text-gray-500">FCFA</p>
                </div>
                <i class="fas fa-percent text-2xl text-red-500"></i>
            </div>
        </div>

        <div class="bg-white border-l-4 border-purple-500 rounded-lg shadow-sm p-4">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-600 text-sm">Montant Net</p>
                    <h4 class="text-lg font-bold text-gray-900">{{ number_format($stats['total_net'], 0, ',', ' ') }}</h4>
                    <p class="text-xs text-gray-500">FCFA</p>
                </div>
                <i class="fas fa-wallet text-2xl text-purple-500"></i>
            </div>
        </div>

        <div class="bg-white border-l-4 border-orange-500 rounded-lg shadow-sm p-4">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-600 text-sm">Aujourd'hui</p>
                    <h4 class="text-xl font-bold text-gray-900">{{ number_format($stats['withdrawals_today']) }}</h4>
                    <p class="text-xs text-gray-500">{{ number_format($stats['amount_today'], 0, ',', ' ') }} FCFA</p>
                </div>
                <i class="fas fa-calendar-day text-2xl text-orange-500"></i>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-6">
            <form method="GET" action="{{ route('admin.withdrawals.history') }}">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                    <div class="md:col-span-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
                        <input type="text"
                               name="search"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                               placeholder="Réf., client, bénéficiaire..."
                               value="{{ request('search') }}">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Méthode</label>
                        <select name="payment_method"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Toutes</option>
                            <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>Espèces</option>
                            <option value="bank_transfer" {{ request('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Virement</option>
                            <option value="mobile_money" {{ request('payment_method') === 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
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

                    <div class="md:col-span-2 flex items-end">
                        <button type="submit"
                                class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            <i class="fas fa-search mr-2"></i>Filtrer
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des retraits -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h5 class="text-lg font-semibold text-gray-900">
                Liste des Retraits ({{ $withdrawals->total() }})
            </h5>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Référence</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Compte / Client</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bénéficiaire</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Méthode</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Montant</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Frais</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Net</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Traité Par</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($withdrawals as $withdrawal)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $withdrawal->transaction_date->format('d/m/Y') }}
                            <div class="text-xs text-gray-500">
                                {{ $withdrawal->transaction_date->format('H:i') }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-blue-600">
                                {{ $withdrawal->transaction_reference }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ $withdrawal->payment_reference }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ route('admin.accounts.show', $withdrawal->account_id) }}"
                               class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                {{ $withdrawal->account->account_number }}
                            </a>
                            <div class="text-sm text-gray-900">
                                {{ $withdrawal->account->client->first_name }}
                                {{ $withdrawal->account->client->last_name }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ $withdrawal->account->client->client_number }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $withdrawal->recipient_name }}
                            </div>
                            @if($withdrawal->recipient_phone)
                                <div class="text-xs text-gray-500">
                                    <i class="fas fa-phone mr-1"></i>{{ $withdrawal->recipient_phone }}
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @switch($withdrawal->payment_method)
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
                                        <i class="fas fa-mobile-alt mr-1"></i>Mobile Money
                                    </span>
                                    @break
                            @endswitch
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <span class="text-sm font-semibold text-gray-900">
                                {{ number_format($withdrawal->amount, 0, ',', ' ') }}
                            </span>
                            <div class="text-xs text-gray-500">FCFA</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <span class="text-sm font-semibold text-red-600">
                                {{ number_format($withdrawal->fee_amount, 0, ',', ' ') }}
                            </span>
                            <div class="text-xs text-gray-500">FCFA</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <span class="text-sm font-bold text-green-600">
                                {{ number_format($withdrawal->net_amount, 0, ',', ' ') }}
                            </span>
                            <div class="text-xs text-gray-500">FCFA</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $withdrawal->processedBy->name ?? 'N/A' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center">
                            <i class="fas fa-inbox text-5xl text-gray-400 mb-3"></i>
                            <p class="text-gray-500">Aucun retrait trouvé</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($withdrawals->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $withdrawals->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
