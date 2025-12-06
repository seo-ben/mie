@extends('layouts.app_admin')

@section('content')
<div class="min-h-screen py-8 bg-gray-50">
    <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">

        <!-- En-tête -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Historique des Transferts</h1>
                    <p class="mt-2 text-sm text-gray-600">Liste de tous les transferts effectués</p>
                </div>
                <a href="{{ route('admin.accounts.transfer.form') }}"
                   class="inline-flex items-center px-4 py-2 font-medium text-white transition bg-blue-600 rounded-lg shadow hover:bg-blue-700">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nouveau Transfert
                </a>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="grid grid-cols-1 gap-6 mb-8 md:grid-cols-2 lg:grid-cols-5">
            <div class="p-6 bg-white rounded-lg shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Transferts</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($stats['total_transfers']) }}</p>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="p-6 bg-white rounded-lg shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Montant Envoyé</p>
                        <p class="mt-2 text-2xl font-bold text-red-600">{{ number_format($stats['total_amount_sent'], 0, ',', ' ') }}</p>
                        <p class="text-xs text-gray-500">FCFA</p>
                    </div>
                    <div class="p-3 bg-red-100 rounded-lg">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="p-6 bg-white rounded-lg shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Montant Reçu</p>
                        <p class="mt-2 text-2xl font-bold text-green-600">{{ number_format($stats['total_amount_received'], 0, ',', ' ') }}</p>
                        <p class="text-xs text-gray-500">FCFA</p>
                    </div>
                    <div class="p-3 bg-green-100 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="p-6 bg-white rounded-lg shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Frais Collectés</p>
                        <p class="mt-2 text-2xl font-bold text-purple-600">{{ number_format($stats['total_fees_collected'], 0, ',', ' ') }}</p>
                        <p class="text-xs text-gray-500">FCFA</p>
                    </div>
                    <div class="p-3 bg-purple-100 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="p-6 bg-white rounded-lg shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Aujourd'hui</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($stats['transfers_today']) }}</p>
                    </div>
                    <div class="p-3 bg-yellow-100 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="p-6 mb-6 bg-white rounded-lg shadow">
            <form method="GET" action="{{ route('admin.accounts.transfer.history') }}" class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">Recherche</label>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Référence, client..."
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">Type</label>
                    <select name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">Tous</option>
                        <option value="transfer_out" {{ request('type') == 'transfer_out' ? 'selected' : '' }}>Envoyés</option>
                        <option value="transfer_in" {{ request('type') == 'transfer_in' ? 'selected' : '' }}>Reçus</option>
                    </select>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">Date début</label>
                    <input type="date"
                           name="date_from"
                           value="{{ request('date_from') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">Date fin</label>
                    <input type="date"
                           name="date_to"
                           value="{{ request('date_to') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="flex items-end space-x-2 md:col-span-4">
                    <button type="submit"
                            class="px-6 py-2 font-medium text-white transition bg-blue-600 rounded-lg hover:bg-blue-700">
                        Filtrer
                    </button>
                    <a href="{{ route('admin.accounts.transfer.history') }}"
                       class="px-6 py-2 text-gray-700 transition border border-gray-300 rounded-lg hover:bg-gray-50">
                        Réinitialiser
                    </a>
                </div>
            </form>
        </div>

        <!-- Liste des transferts -->
        <div class="overflow-hidden bg-white rounded-lg shadow">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Référence</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Compte</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Contrepartie</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">Montant</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">Frais</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($transfers as $transfer)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap">
                                    {{ $transfer->transaction_date->format('d/m/Y') }}
                                    <span class="block text-xs text-gray-500">{{ $transfer->transaction_date->format('H:i') }}</span>
                                </td>
                                <td class="px-6 py-4 font-mono text-sm text-gray-900 whitespace-nowrap">
                                    {{ $transfer->payment_reference }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($transfer->transaction_type === 'transfer_out')
                                        <span class="inline-flex items-center px-3 py-1 text-xs font-medium text-red-800 bg-red-100 rounded-full">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                            </svg>
                                            Envoyé
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 text-xs font-medium text-green-800 bg-green-100 rounded-full">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                            Reçu
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm whitespace-nowrap">
                                    <div class="font-medium text-gray-900">{{ $transfer->account->client->first_name }} {{ $transfer->account->client->last_name }}</div>
                                    <div class="text-gray-500">{{ $transfer->account->account_number }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm whitespace-nowrap">
                                    @if($transfer->relatedAccount)
                                        <div class="font-medium text-gray-900">{{ $transfer->relatedAccount->client->first_name }} {{ $transfer->relatedAccount->client->last_name }}</div>
                                        <div class="text-gray-500">{{ $transfer->relatedAccount->account_number }}</div>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold text-right whitespace-nowrap">
                                    <span class="{{ $transfer->transaction_type === 'transfer_out' ? 'text-red-600' : 'text-green-600' }}">
                                        {{ $transfer->transaction_type === 'transfer_out' ? '-' : '+' }}
                                        {{ number_format($transfer->amount, 0, ',', ' ') }} FCFA
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-right text-gray-900 whitespace-nowrap">
                                    {{ number_format($transfer->fee_amount, 0, ',', ' ') }} FCFA
                                </td>
                                <td class="px-6 py-4 text-sm text-center whitespace-nowrap">
                                    <a href="{{ route('admin.accounts.transfer.details', $transfer->id) }}"
                                       class="text-blue-600 hover:text-blue-900">
                                        Détails
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                    </svg>
                                    <p class="text-lg font-medium">Aucun transfert trouvé</p>
                                    <p class="mt-1 text-sm">Commencez par effectuer votre premier transfert</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($transfers->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $transfers->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
