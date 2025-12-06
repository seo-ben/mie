@extends('layouts.app_admin')

@section('title', 'Gestion des Transactions')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <!-- En-tête avec actions -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="mb-2 text-3xl font-bold text-gray-900">Transactions</h1>
                <p class="text-gray-600">Analyse et gestion de toutes les transactions</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('admin.transactions.analytics') }}"
                   class="px-4 py-2 text-white transition-colors bg-purple-600 rounded-lg hover:bg-purple-700">
                    <i class="mr-2 fas fa-chart-line"></i>Analytics
                </a>
                <div class="relative">
                    <button onclick="toggleExportMenu()"
                            class="px-4 py-2 text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700">
                        <i class="mr-2 fas fa-download"></i>Export
                    </button>
                    <div id="export-menu" class="absolute right-0 z-50 hidden w-48 mt-2 bg-white border border-gray-200 rounded-lg shadow-lg">
                        <a href="{{ route('admin.transactions.export', array_merge(request()->all(), ['format' => 'csv'])) }}"
                           class="block px-4 py-2 text-gray-700 hover:bg-gray-50">
                            <i class="mr-2 fas fa-file-csv"></i>CSV
                        </a>
                        <a href="{{ route('admin.transactions.export', array_merge(request()->all(), ['format' => 'excel'])) }}"
                           class="block px-4 py-2 text-gray-700 hover:bg-gray-50">
                            <i class="mr-2 fas fa-file-excel"></i>Excel
                        </a>
                        <a href="{{ route('admin.transactions.export', array_merge(request()->all(), ['format' => 'pdf'])) }}"
                           class="block px-4 py-2 text-gray-700 hover:bg-gray-50">
                            <i class="mr-2 fas fa-file-pdf"></i>PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques en cartes -->
        <div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-2 lg:grid-cols-6">
            <div class="p-5 bg-white border-l-4 border-blue-500 shadow-sm rounded-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="mb-1 text-xs tracking-wider text-gray-600 uppercase">Total Transactions</p>
                        <h3 class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_transactions']) }}</h3>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <i class="text-xl text-blue-600 fas fa-exchange-alt"></i>
                    </div>
                </div>
            </div>

            <div class="p-5 bg-white border-l-4 border-green-500 shadow-sm rounded-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="mb-1 text-xs tracking-wider text-gray-600 uppercase">Montant Total</p>
                        <h3 class="text-xl font-bold text-gray-900">{{ number_format($stats['total_amount'], 0, ',', ' ') }}</h3>
                        <p class="text-xs text-gray-500">FCFA</p>
                    </div>
                    <div class="p-3 bg-green-100 rounded-lg">
                        <i class="text-xl text-green-600 fas fa-coins"></i>
                    </div>
                </div>
            </div>

            <div class="p-5 bg-white border-l-4 shadow-sm rounded-xl border-cyan-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="mb-1 text-xs tracking-wider text-gray-600 uppercase">Dépôts</p>
                        <h3 class="text-xl font-bold text-gray-900">{{ number_format($stats['total_deposits'], 0, ',', ' ') }}</h3>
                        <p class="text-xs text-gray-500">FCFA</p>
                    </div>
                    <div class="p-3 rounded-lg bg-cyan-100">
                        <i class="text-xl fas fa-arrow-down text-cyan-600"></i>
                    </div>
                </div>
            </div>

            {{-- <div class="p-5 bg-white border-l-4 border-red-500 shadow-sm rounded-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="mb-1 text-xs tracking-wider text-gray-600 uppercase">Retraits</p>
                        <h3 class="text-xl font-bold text-gray-900">{{ number_format($stats['total_withdrawals'], 0, ',', ' ') }}</h3>
                        <p class="text-xs text-gray-500">FCFA</p>
                    </div>
                    <div class="p-3 bg-red-100 rounded-lg">
                        <i class="text-xl text-red-600 fas fa-arrow-up"></i>
                    </div>
                </div>
            </div> --}}

            <div class="p-5 bg-white border-l-4 border-yellow-500 shadow-sm rounded-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="mb-1 text-xs tracking-wider text-gray-600 uppercase">En Attente</p>
                        <h3 class="text-2xl font-bold text-gray-900">{{ number_format($stats['pending_count']) }}</h3>
                        <p class="text-xs text-gray-500">{{ number_format($stats['pending_amount'], 0, ',', ' ') }} FCFA</p>
                    </div>
                    <div class="p-3 bg-yellow-100 rounded-lg">
                        <i class="text-xl text-yellow-600 fas fa-clock"></i>
                    </div>
                </div>
            </div>

            <div class="p-5 bg-white border-l-4 border-purple-500 shadow-sm rounded-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="mb-1 text-xs tracking-wider text-gray-600 uppercase">Aujourd'hui</p>
                        <h3 class="text-2xl font-bold text-gray-900">{{ number_format($stats['today_transactions']) }}</h3>
                        <p class="text-xs text-gray-500">{{ number_format($stats['today_amount'], 0, ',', ' ') }} FCFA</p>
                    </div>
                    <div class="p-3 bg-purple-100 rounded-lg">
                        <i class="text-xl text-purple-600 fas fa-calendar-day"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres avancés -->
        <div class="mb-6 bg-white shadow-sm rounded-xl">
            <div class="p-6">
                <form method="GET" action="{{ route('admin.transactions.index') }}" id="filter-form">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
                        <div class="md:col-span-3">
                            <label class="block mb-2 text-sm font-medium text-gray-700">Recherche</label>
                            <input type="text"
                                   name="search"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Référence, client..."
                                   value="{{ request('search') }}">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block mb-2 text-sm font-medium text-gray-700">Type</label>
                            <select name="transaction_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Tous</option>
                                <option value="deposit" {{ request('transaction_type') === 'deposit' ? 'selected' : '' }}>Dépôt</option>
                                <option value="withdrawal" {{ request('transaction_type') === 'withdrawal' ? 'selected' : '' }}>Retrait</option>
                                <option value="tontine_payout" {{ request('transaction_type') === 'tontine_payout' ? 'selected' : '' }}>Déblocage Tontine</option>
                                <option value="loan_disbursement" {{ request('transaction_type') === 'loan_disbursement' ? 'selected' : '' }}>Décaissement Prêt</option>
                                <option value="loan_repayment" {{ request('transaction_type') === 'loan_repayment' ? 'selected' : '' }}>Remboursement Prêt</option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block mb-2 text-sm font-medium text-gray-700">Méthode</label>
                            <select name="payment_method" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Toutes</option>
                                <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>Espèces</option>
                                <option value="mobile_money" {{ request('payment_method') === 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
                                <option value="bank_transfer" {{ request('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Virement</option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block mb-2 text-sm font-medium text-gray-700">Statut</label>
                            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Tous</option>
                                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Complété</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Échoué</option>
                                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejeté</option>
                            </select>
                        </div>

                        <div class="flex items-end gap-2 md:col-span-3">
                            <button type="submit" class="flex-1 px-6 py-2 text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700">
                                <i class="mr-2 fas fa-search"></i>Filtrer
                            </button>
                            <a href="{{ route('admin.transactions.index') }}" class="px-6 py-2 text-white transition-colors bg-gray-600 rounded-lg hover:bg-gray-700">
                                <i class="fas fa-redo"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Filtres de date (pliables) -->
                    <div class="pt-4 mt-4 border-t border-gray-200">
                        <button type="button" onclick="toggleDateFilters()" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                            <i class="mr-2 fas fa-calendar"></i>Filtres de date
                            <i class="ml-1 fas fa-chevron-down" id="date-chevron"></i>
                        </button>
                        <div id="date-filters" class="grid hidden grid-cols-1 gap-4 mt-4 md:grid-cols-2">
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-700">Date Début</label>
                                <input type="date"
                                       name="date_from"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       value="{{ request('date_from') }}">
                            </div>
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-700">Date Fin</label>
                                <input type="date"
                                       name="date_to"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       value="{{ request('date_to') }}">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Layout principal: Liste + Détails -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Liste des transactions (2/3) -->
            <div class="lg:col-span-2">
                <div class="overflow-hidden bg-white shadow-sm rounded-xl">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                        <h5 class="text-lg font-semibold text-gray-900">Transactions ({{ $transactions->total() }})</h5>
                        <div class="flex items-center space-x-2 text-sm">
                            <span class="text-gray-600">Trier par:</span>
                            <select onchange="updateSort(this)" class="px-2 py-1 text-sm border border-gray-300 rounded">
                                <option value="transaction_date-desc" {{ request('sort_by') === 'transaction_date' && request('sort_order') === 'desc' ? 'selected' : '' }}>Date (récent)</option>
                                <option value="transaction_date-asc" {{ request('sort_by') === 'transaction_date' && request('sort_order') === 'asc' ? 'selected' : '' }}>Date (ancien)</option>
                                <option value="amount-desc" {{ request('sort_by') === 'amount' && request('sort_order') === 'desc' ? 'selected' : '' }}>Montant (élevé)</option>
                                <option value="amount-asc" {{ request('sort_by') === 'amount' && request('sort_order') === 'asc' ? 'selected' : '' }}>Montant (faible)</option>
                            </select>
                        </div>
                    </div>

                    <div class="divide-y divide-gray-200 max-h-[800px] overflow-y-auto" id="transactions-list">
                        @forelse($transactions as $transaction)
                        <div class="p-4 transition-colors cursor-pointer transaction-item hover:bg-gray-50"
                             onclick="loadTransactionDetails({{ $transaction->id }})"
                             data-transaction-id="{{ $transaction->id }}">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center flex-1 space-x-4">
                                    <!-- Icône de type -->
                                    <div class="flex-shrink-0">
                                        @switch($transaction->transaction_type)
                                            @case('deposit')
                                                <div class="flex items-center justify-center w-12 h-12 rounded-full bg-cyan-100">
                                                    <i class="text-lg fas fa-arrow-down text-cyan-600"></i>
                                                </div>
                                                @break
                                            @case('withdrawal')
                                                <div class="flex items-center justify-center w-12 h-12 bg-red-100 rounded-full">
                                                    <i class="text-lg text-red-600 fas fa-arrow-up"></i>
                                                </div>
                                                @break

                                            @default
                                                <div class="flex items-center justify-center w-12 h-12 bg-gray-100 rounded-full">
                                                    <i class="text-lg text-gray-600 fas fa-exchange-alt"></i>
                                                </div>
                                        @endswitch
                                    </div>

                                    <!-- Informations -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center mb-1 space-x-2">
                                            <p class="text-sm font-semibold text-gray-900 truncate">
                                                {{ $transaction->account->client->first_name }} {{ $transaction->account->client->last_name }}
                                            </p>
                                            <span class="text-xs text-gray-500">•</span>
                                            <span class="font-mono text-xs text-gray-500">{{ $transaction->transaction_reference }}</span>
                                        </div>
                                        <div class="flex items-center space-x-2 text-xs text-gray-600">
                                            <span>{{ $transaction->transaction_date->format('d/m/Y H:i') }}</span>
                                            <span>•</span>
                                            <span class="capitalize">{{ str_replace('_', ' ', $transaction->transaction_type) }}</span>
                                            @if($transaction->payment_method)
                                                <span>•</span>
                                                <span class="capitalize">{{ str_replace('_', ' ', $transaction->payment_method) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Montant et statut -->
                                <div class="flex flex-col items-end ml-4 space-y-1">
                                    <p class="text-lg font-bold {{ in_array($transaction->transaction_type, ['deposit', 'tontine_contribution']) ? 'text-green-600' : 'text-red-600' }}">
                                        {{ in_array($transaction->transaction_type, ['deposit', 'tontine_contribution']) ? '+' : '-' }}{{ number_format($transaction->amount, 0, ',', ' ') }} FCFA
                                    </p>
                                    @switch($transaction->status)
                                        @case('completed')
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-800 bg-green-100 rounded-full">
                                                <i class="mr-1 fas fa-check-circle"></i>Complété
                                            </span>
                                            @break
                                        @case('pending')
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-yellow-800 bg-yellow-100 rounded-full">
                                                <i class="mr-1 fas fa-clock"></i>En attente
                                            </span>
                                            @break
                                        @case('failed')
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-red-800 bg-red-100 rounded-full">
                                                <i class="mr-1 fas fa-times-circle"></i>Échoué
                                            </span>
                                            @break
                                        @case('rejected')
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-gray-800 bg-gray-100 rounded-full">
                                                <i class="mr-1 fas fa-ban"></i>Rejeté
                                            </span>
                                            @break
                                    @endswitch
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="p-12 text-center">
                            <i class="mb-3 text-5xl text-gray-400 fas fa-inbox"></i>
                            <p class="text-gray-500">Aucune transaction trouvée</p>
                        </div>
                        @endforelse
                    </div>

                    @if($transactions->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $transactions->appends(request()->query())->links() }}
                    </div>
                    @endif
                </div>
            </div>

            <!-- Panneau de détails (1/3) -->
            <div class="lg:col-span-1">
                <div class="sticky bg-white shadow-sm rounded-xl top-6">
                    <div id="transaction-detail-panel">
                        <!-- État initial -->
                        <div class="p-12 text-center text-gray-400">
                            <i class="mb-4 text-5xl fas fa-hand-pointer"></i>
                            <p class="text-sm">Cliquez sur une transaction pour voir les détails</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Toggle menu export
    function toggleExportMenu() {
        document.getElementById('export-menu').classList.toggle('hidden');
    }

    // Fermer le menu si on clique ailleurs
    document.addEventListener('click', function(event) {
        const menu = document.getElementById('export-menu');
        const button = event.target.closest('button');
        if (!button || button.getAttribute('onclick') !== 'toggleExportMenu()') {
            menu.classList.add('hidden');
        }
    });

    // Toggle filtres de date
    function toggleDateFilters() {
        const filters = document.getElementById('date-filters');
        const chevron = document.getElementById('date-chevron');
        filters.classList.toggle('hidden');
        chevron.classList.toggle('fa-chevron-down');
        chevron.classList.toggle('fa-chevron-up');
    }

    // Mettre à jour le tri
    function updateSort(select) {
        const [sortBy, sortOrder] = select.value.split('-');
        const form = document.getElementById('filter-form');

        // Ajouter les champs de tri
        let sortByInput = form.querySelector('input[name="sort_by"]');
        let sortOrderInput = form.querySelector('input[name="sort_order"]');

        if (!sortByInput) {
            sortByInput = document.createElement('input');
            sortByInput.type = 'hidden';
            sortByInput.name = 'sort_by';
            form.appendChild(sortByInput);
        }

        if (!sortOrderInput) {
            sortOrderInput = document.createElement('input');
            sortOrderInput.type = 'hidden';
            sortOrderInput.name = 'sort_order';
            form.appendChild(sortOrderInput);
        }

        sortByInput.value = sortBy;
        sortOrderInput.value = sortOrder;

        form.submit();
    }

    // Charger les détails d'une transaction
    function loadTransactionDetails(transactionId) {
        // Retirer la classe active de tous les items
        document.querySelectorAll('.transaction-item').forEach(item => {
            item.classList.remove('bg-blue-50', 'border-l-4', 'border-blue-500');
        });

        // Ajouter la classe active à l'item cliqué
        const clickedItem = document.querySelector(`[data-transaction-id="${transactionId}"]`);
        if (clickedItem) {
            clickedItem.classList.add('bg-blue-50', 'border-l-4', 'border-blue-500');
        }

        // Afficher un loader
        document.getElementById('transaction-detail-panel').innerHTML = `
            <div class="p-12 text-center">
                <i class="mb-4 text-4xl text-blue-600 fas fa-spinner fa-spin"></i>
                <p class="text-gray-600">Chargement...</p>
            </div>
        `;

        // Charger les détails via AJAX
        fetch(`/admin/transactions/${transactionId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('transaction-detail-panel').innerHTML = data.html;
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('transaction-detail-panel').innerHTML = `
                <div class="p-12 text-center text-red-600">
                    <i class="mb-4 text-4xl fas fa-exclamation-triangle"></i>
                    <p>Erreur lors du chargement des détails</p>
                </div>
            `;
        });
    }

    // Auto-ouvrir le premier élément si aucun filtre
    @if($transactions->isNotEmpty() && !request()->has('search'))
        document.addEventListener('DOMContentLoaded', function() {
            loadTransactionDetails({{ $transactions->first()->id }});
        });
    @endif
</script>
@endpush
@endsection
