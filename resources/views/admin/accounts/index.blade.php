@extends('layouts.app_admin')

@section('title', 'Gestion des Comptes')

@section('content')
<div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
    <!-- En-tête -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="mb-2 text-2xl font-semibold text-gray-900">Gestion des Comptes</h1>
            <p class="text-gray-600">Liste et gestion de tous les comptes clients</p>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-4">
        <div class="bg-white border-l-4 border-blue-500 rounded-lg shadow-sm">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="mb-1 text-sm text-gray-600">Total Comptes</p>
                        <h4 class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_accounts']) }}</h4>
                    </div>
                    <div class="text-blue-500">
                        <i class="text-3xl fas fa-wallet"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white border-l-4 border-green-500 rounded-lg shadow-sm">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="mb-1 text-sm text-gray-600">Comptes Actifs</p>
                        <h4 class="text-2xl font-bold text-gray-900">{{ number_format($stats['active_accounts']) }}</h4>
                    </div>
                    <div class="text-green-500">
                        <i class="text-3xl fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white border-l-4 border-yellow-500 rounded-lg shadow-sm">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="mb-1 text-sm text-gray-600">En Attente</p>
                        <h4 class="text-2xl font-bold text-gray-900">{{ number_format($stats['pending_accounts']) }}</h4>
                    </div>
                    <div class="text-yellow-500">
                        <i class="text-3xl fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white border-l-4 rounded-lg shadow-sm border-cyan-500">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="mb-1 text-sm text-gray-600">Solde Total</p>
                        <h4 class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_balance'], 0, ',', ' ') }} FCFA</h4>
                    </div>
                    <div class="text-cyan-500">
                        <i class="text-3xl fas fa-coins"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="mb-6 bg-white rounded-lg shadow-sm">
        <div class="p-6">
            <form method="GET" action="{{ route('admin.accounts.index') }}">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
                    <div class="md:col-span-4">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Recherche</label>
                        <input type="text" name="search" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Numéro compte, nom client..."
                               value="{{ request('search') }}">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Type</label>
                        <select name="account_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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
                        <label class="block mb-2 text-sm font-medium text-gray-700">Statut</label>
                        <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Tous</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>
                                Actif
                            </option>
                            <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>
                                Suspendu
                            </option>
                            <option value="pending_activation" {{ request('status') === 'pending_activation' ? 'selected' : '' }}>
                                En attente
                            </option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2 md:col-span-4">
                        <button type="submit" class="px-6 py-2 text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700">
                            <i class="mr-2 fas fa-search"></i>Rechercher
                        </button>
                        <a href="{{ route('admin.accounts.index') }}" class="px-6 py-2 text-white transition-colors bg-gray-600 rounded-lg hover:bg-gray-700">
                            <i class="mr-2 fas fa-redo"></i>Réinitialiser
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des comptes -->
    <div class="overflow-hidden bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h5 class="text-lg font-semibold text-gray-900">Liste des Comptes ({{ $accounts->total() }})</h5>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">N° Compte</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Client</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Type / Montant</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Solde</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Statut</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Date Création</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($accounts as $account)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="{{ route('admin.accounts.show', $account->id) }}"
                               class="font-semibold text-blue-600 hover:text-blue-800">
                                {{ $account->account_number }}
                            </a>
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ route('admin.clients.show', $account->client_id) }}"
                               class="text-gray-900 hover:text-blue-600">
                                {{ $account->client->first_name }} {{ $account->client->last_name }}
                            </a>
                            <div class="text-sm text-gray-500">{{ $account->client->client_number }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @if($account->account_type === 'savings')
                                <span class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full bg-cyan-100 text-cyan-800">
                                    <i class="mr-1 fas fa-piggy-bank"></i>Épargne
                                </span>
                            @else
                                <div class="space-y-1">
                                    <span class="inline-flex items-center px-3 py-1 text-sm font-medium text-purple-800 bg-purple-100 rounded-full">
                                        <i class="mr-1 fas fa-users"></i>Tontine
                                    </span>
                                    @if($account->tontineAccount)
                                        <div class="text-sm font-semibold text-purple-700">
                                            {{ number_format($account->tontineAccount->tontine_amount, 0, ',', ' ') }} FCFA
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ ucfirst(__($account->tontineAccount->payment_frequency)) }} •
                                            {{ $account->tontineAccount->cycle_duration_months }} mois
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-semibold text-gray-900 whitespace-nowrap">
                            {{ number_format($account->balance, 0, ',', ' ') }} FCFA
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @switch($account->status)
                                @case('active')
                                    <span class="inline-flex items-center px-3 py-1 text-sm font-medium text-green-800 bg-green-100 rounded-full">Actif</span>
                                    @break
                                @case('suspended')
                                    <span class="inline-flex items-center px-3 py-1 text-sm font-medium text-red-800 bg-red-100 rounded-full">Suspendu</span>
                                    @break
                                @case('pending_activation')
                                    <span class="inline-flex items-center px-3 py-1 text-sm font-medium text-yellow-800 bg-yellow-100 rounded-full">En attente</span>
                                    @break
                                @default
                                    <span class="inline-flex items-center px-3 py-1 text-sm font-medium text-gray-800 bg-gray-100 rounded-full">{{ $account->status }}</span>
                            @endswitch
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap">
                            {{ $account->created_at->format('d/m/Y') }}
                            @if($account->activated_at)
                                <div class="text-xs text-gray-500">
                                    Activé: {{ $account->activated_at->format('d/m/Y') }}
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex space-x-2">
                                <a href="{{ route('admin.accounts.deposit.form', $account->id) }}"
                                   class="px-3 py-1 text-blue-600 border border-blue-600 rounded hover:bg-blue-50"
                                   title="Dépots">
                                    <i class="fas fa-plus-circle"></i>
                                </a>
                                <a href="{{ route('admin.accounts.show', $account->id) }}"
                                   class="px-3 py-1 text-blue-600 border border-blue-600 rounded hover:bg-blue-50"
                                   title="Détails">
                                    <i class="fas fa-eye"></i>
                                </a>

                                @if($account->status === 'suspended')
                                    <a href="{{ route('admin.accounts.edit', $account->id) }}"
                                       class="px-3 py-1 text-yellow-600 border border-yellow-600 rounded hover:bg-yellow-50"
                                       title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <form action="{{ route('admin.accounts.reactivate', $account->id) }}" method="POST"
                                          onsubmit="return confirm('Êtes-vous sûr de vouloir réactiver ce compte ?');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="px-3 py-1 text-green-600 border border-green-600 rounded hover:bg-green-50"
                                                title="Activer le compte">
                                            <i class="fas fa-check-circle"></i>
                                        </button>
                                    </form>
                                @endif

                                <a href="{{ route('admin.accounts.transactions', $account->id) }}"
                                   class="px-3 py-1 border rounded border-cyan-600 text-cyan-600 hover:bg-cyan-50"
                                   title="Transactions">
                                    <i class="fas fa-list"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <i class="mb-3 text-5xl text-gray-400 fas fa-inbox"></i>
                            <p class="text-gray-500">Aucun compte trouvé</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($accounts->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Affichage de <span class="font-medium">{{ $accounts->firstItem() ?? 0 }}</span> à
                    <span class="font-medium">{{ $accounts->lastItem() ?? 0 }}</span> sur
                    <span class="font-medium">{{ $accounts->total() }}</span> comptes
                </div>
                <div class="flex space-x-1" id="pagination-container">
                    {{-- Bouton Précédent --}}
                    @if($accounts->onFirstPage())
                        <button disabled class="px-3 py-2 text-gray-400 bg-gray-100 rounded cursor-not-allowed">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                    @else
                        <a href="{{ $accounts->previousPageUrl() }}" class="px-3 py-2 text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    @endif

                    {{-- Numéros de page --}}
                    <div class="flex space-x-1" id="page-numbers">
                        @php
                            $currentPage = $accounts->currentPage();
                            $lastPage = $accounts->lastPage();
                            $start = max(1, $currentPage - 2);
                            $end = min($lastPage, $currentPage + 2);
                        @endphp

                        @if($start > 1)
                            <a href="{{ $accounts->url(1) }}" class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">
                                1
                            </a>
                            @if($start > 2)
                                <span class="px-3 py-2">...</span>
                            @endif
                        @endif

                        @for($i = $start; $i <= $end; $i++)
                            @if($i == $currentPage)
                                <button class="px-4 py-2 font-medium text-white bg-blue-600 rounded">
                                    {{ $i }}
                                </button>
                            @else
                                <a href="{{ $accounts->url($i) }}" class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">
                                    {{ $i }}
                                </a>
                            @endif
                        @endfor

                        @if($end < $lastPage)
                            @if($end < $lastPage - 1)
                                <span class="px-3 py-2">...</span>
                            @endif
                            <a href="{{ $accounts->url($lastPage) }}" class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">
                                {{ $lastPage }}
                            </a>
                        @endif
                    </div>

                    {{-- Bouton Suivant --}}
                    @if($accounts->hasMorePages())
                        <a href="{{ $accounts->nextPageUrl() }}" class="px-3 py-2 text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    @else
                        <button disabled class="px-3 py-2 text-gray-400 bg-gray-100 rounded cursor-not-allowed">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
