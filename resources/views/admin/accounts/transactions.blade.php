@extends('layouts.app_admin')

@section('title', 'Transactions du Compte')

@section('content')
<div class="px-4 py-8 mx-auto space-y-8 max-w-7xl sm:px-6 lg:px-8">

    <!-- Breadcrumb -->
    <nav class="text-sm text-gray-600" aria-label="breadcrumb">
        <ol class="flex flex-wrap items-center space-x-2">
            <li><a href="{{ route('admin.accounts.index') }}" class="font-medium text-blue-600 hover:text-blue-800">Comptes</a></li>
            <li><span class="mx-2 text-gray-400">/</span></li>
            <li><a href="{{ route('admin.accounts.show', $account->id) }}" class="font-medium text-blue-600 hover:text-blue-800">{{ $account->account_number }}</a></li>
            <li><span class="mx-2 text-gray-400">/</span></li>
            <li class="font-medium text-gray-700">Transactions</li>
        </ol>
    </nav>

    <!-- En-tête principal -->
    <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 md:text-3xl">Transactions du compte</h1>
            <p class="mt-1 text-gray-600">
                <span class="font-semibold">{{ $account->client->first_name }} {{ $account->client->last_name }}</span>
                <span class="mx-2">•</span>
                <span class="capitalize">{{ $account->account_type === 'savings' ? 'Compte d\'Épargne' : 'Compte Tontine' }}</span>
                <span class="mx-2">•</span>
                <span class="font-mono text-gray-800">#{{ $account->account_number }}</span>
            </p>
        </div>
        <a href="{{ route('admin.accounts.show', $account->id) }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-700 text-white text-sm font-medium rounded-xl hover:bg-gray-800 transition-all duration-200 shadow-sm">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>

    <!-- Filtres -->
    <div class="p-6 bg-white border border-gray-100 shadow-sm rounded-2xl">
        <form method="GET" action="{{ route('admin.accounts.transactions', $account->id) }}" class="space-y-4">
            <div class="grid items-end grid-cols-1 gap-4 md:grid-cols-12">
                <!-- Type -->
                <div class="md:col-span-3">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Type de transaction</label>
                    <select name="type" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition">
                        <option value="">Tous les types</option>
                        <option value="deposit" {{ request('type') === 'deposit' ? 'selected' : '' }}>Dépôt</option>
                        <option value="withdrawal" {{ request('type') === 'withdrawal' ? 'selected' : '' }}>Retrait</option>
                        <option value="transfer" {{ request('type') === 'transfer' ? 'selected' : '' }}>Transfert</option>
                        <option value="fee" {{ request('type') === 'fee' ? 'selected' : '' }}>Frais</option>
                        <option value="interest" {{ request('type') === 'interest' ? 'selected' : '' }}>Intérêts</option>
                    </select>
                </div>

                <!-- Statut -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Statut</label>
                    <select name="status" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition">
                        <option value="">Tous</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Complété</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Échoué</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Annulé</option>
                    </select>
                </div>

                <!-- Date début -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Du</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition">
                </div>

                <!-- Date fin -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Au</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition">
                </div>

                <!-- Boutons -->
                <div class="flex gap-2 md:col-span-3">
                    <button type="submit"
                            class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 text-sm font-medium transition-all shadow-sm">
                        <i class="fas fa-search"></i> Filtrer
                    </button>
                    <a href="{{ route('admin.accounts.transactions', $account->id) }}"
                       class="p-2.5 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition-all" title="Réinitialiser">
                        <i class="fas fa-redo"></i>
                    </a>
                    <button type="button" onclick="window.print()"
                            class="p-2.5 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-all" title="Imprimer">
                        <i class="fas fa-print"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Résumé financier -->
    @php
        $totalDeposits = $transactions->where('transaction_type', 'deposit')->where('status', 'completed')->sum('amount');
        $totalWithdrawals = $transactions->where('transaction_type', 'withdrawal')->where('status', 'completed')->sum('amount');
        $totalFees = $transactions->where('status', 'completed')->sum('fee_amount');
    @endphp

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Dépôts -->
        <div class="p-6 transition-shadow border-l-4 border-green-500 shadow-sm bg-gradient-to-br from-green-50 to-green-100 rounded-2xl hover:shadow-md">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-semibold text-green-700">Total Dépôts</p>
                    <p class="mt-1 text-2xl font-bold text-green-900">{{ number_format($totalDeposits, 0, ',', ' ') }} FCFA</p>
                </div>
                <div class="p-3 bg-green-200 rounded-full">
                    <i class="text-green-700 fas fa-arrow-down"></i>
                </div>
            </div>
        </div>

        <!-- Retraits -->
        <div class="p-6 transition-shadow border-l-4 border-red-500 shadow-sm bg-gradient-to-br from-red-50 to-red-100 rounded-2xl hover:shadow-md">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-semibold text-red-700">Total Retraits</p>
                    <p class="mt-1 text-2xl font-bold text-red-900">{{ number_format($totalWithdrawals, 0, ',', ' ') }} FCFA</p>
                </div>
                <div class="p-3 bg-red-200 rounded-full">
                    <i class="text-red-700 fas fa-arrow-up"></i>
                </div>
            </div>
        </div>

        <!-- Frais -->
        <div class="p-6 transition-shadow border-l-4 border-yellow-500 shadow-sm bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-2xl hover:shadow-md">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-semibold text-yellow-700">Total Frais</p>
                    <p class="mt-1 text-2xl font-bold text-yellow-900">{{ number_format($totalFees, 0, ',', ' ') }} FCFA</p>
                </div>
                <div class="p-3 bg-yellow-200 rounded-full">
                    <i class="text-yellow-700 fas fa-receipt"></i>
                </div>
            </div>
        </div>

        <!-- Solde actuel -->
        <div class="p-6 transition-shadow border-l-4 border-blue-500 shadow-sm bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl hover:shadow-md">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-semibold text-blue-700">Solde Actuel</p>
                    <p class="mt-1 text-2xl font-bold text-blue-900">{{ number_format($account->balance, 0, ',', ' ') }} FCFA</p>
                </div>
                <div class="p-3 bg-blue-200 rounded-full">
                    <i class="text-blue-700 fas fa-wallet"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau des transactions -->
    <div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-2xl">
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100">
            <h3 class="text-lg font-bold text-gray-900">
                Historique des transactions
                <span class="ml-2 text-sm font-normal text-gray-600">({{ $transactions->total() }})</span>
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-xs font-bold tracking-wider text-left text-gray-600 uppercase">Date</th>
                        <th class="px-5 py-3 text-xs font-bold tracking-wider text-left text-gray-600 uppercase">Réf.</th>
                        <th class="px-5 py-3 text-xs font-bold tracking-wider text-left text-gray-600 uppercase">Type</th>
                        <th class="px-5 py-3 text-xs font-bold tracking-wider text-right text-gray-600 uppercase">Montant</th>
                        <th class="px-5 py-3 text-xs font-bold tracking-wider text-right text-gray-600 uppercase">Frais</th>
                        <th class="px-5 py-3 text-xs font-bold tracking-wider text-right text-gray-600 uppercase">Avant</th>
                        <th class="px-5 py-3 text-xs font-bold tracking-wider text-right text-gray-600 uppercase">Après</th>
                        <th class="px-5 py-3 text-xs font-bold tracking-wider text-left text-gray-600 uppercase">Méthode</th>
                        <th class="px-5 py-3 text-xs font-bold tracking-wider text-left text-gray-600 uppercase">Par</th>
                        <th class="px-5 py-3 text-xs font-bold tracking-wider text-left text-gray-600 uppercase">Statut</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($transactions as $t)
                    <tr class="transition-colors duration-150 hover:bg-gray-50">
                        <!-- Date -->
                        <td class="px-5 py-4 text-sm">
                            <div class="font-medium text-gray-900">{{ $t->transaction_date?->format('d/m/Y') ?? '—' }}</div>
                            <div class="text-xs text-gray-500">{{ $t->transaction_date?->format('H:i') ?? '' }}</div>
                        </td>

                        <!-- Référence -->
                        <td class="px-5 py-4">
                            <code class="text-xs bg-gray-100 px-2.5 py-1 rounded font-mono text-gray-700">{{ $t->transaction_reference ?? '—' }}</code>
                        </td>

                        <!-- Type -->
                        <td class="px-5 py-4">
                            @switch($t->transaction_type)
                                @case('deposit')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-arrow-down"></i> Dépôt
                                    </span>
                                    @break
                                @case('withdrawal')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <i class="fas fa-arrow-up"></i> Retrait
                                    </span>
                                    @break
                                @case('transfer')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-cyan-100 text-cyan-800">
                                        <i class="fas fa-exchange-alt"></i> Transfert
                                    </span>
                                    @break
                                @case('fee')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <i class="fas fa-receipt"></i> Frais
                                    </span>
                                    @break
                                @case('interest')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <i class="fas fa-percentage"></i> Intérêts
                                    </span>
                                    @break
                                @default
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ ucfirst($t->transaction_type) }}
                                    </span>
                            @endswitch
                        </td>

                        <!-- Montant -->
                        <td class="px-5 py-4 text-sm font-semibold text-right">
                            @if(in_array($t->transaction_type, ['deposit', 'interest']))
                                <span class="text-green-600">+{{ number_format($t->amount, 0, ',', ' ') }}</span>
                            @else
                                <span class="text-red-600">-{{ number_format($t->amount, 0, ',', ' ') }}</span>
                            @endif
                            <span class="ml-1 text-gray-500">FCFA</span>
                        </td>
                        <!-- Montant -->
                        <td class="px-5 py-4 text-sm font-semibold text-right">
                           
                            <span class="text-red-600">-{{ number_format($t->fee_amount, 0, ',', ' ') }}</span>
                           
                            <span class="ml-1 text-gray-500">FCFA</span>
                        </td>

                        <!-- Solde avant -->
                        <td class="px-5 py-4 text-sm text-right text-gray-700">
                            {{ number_format($t->balance_before, 0, ',', ' ') }}
                        </td>

                        <!-- Solde après -->
                        <td class="px-5 py-4 text-sm font-medium text-right text-gray-900">
                            {{ number_format($t->balance_after, 0, ',', ' ') }}
                        </td>

                        <!-- Méthode -->
                        <td class="px-5 py-4 text-sm">
                            @if($t->payment_method)
                                @switch($t->payment_method)
                                    @case('cash')
                                        <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-gray-800 bg-gray-100 rounded-full">Espèces</span>
                                        @break
                                    @case('mobile_money')
                                        <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-cyan-100 text-cyan-800">Mobile Money</span>
                                        @break
                                    @case('bank_transfer')
                                        <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-blue-800 bg-blue-100 rounded-full">Virement</span>
                                        @break
                                    @default
                                        <span class="text-gray-900">{{ ucfirst(str_replace('_', ' ', $t->payment_method)) }}</span>
                                @endswitch
                                @if($t->payment_reference)
                                    <div class="mt-1 text-xs text-gray-500">{{ $t->payment_reference }}</div>
                                @endif
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>

                        <!-- Traité par -->
                        <td class="px-5 py-4 text-sm">
                            @if($t->processedBy)
                                <div class="font-medium text-gray-900">{{ $t->processedBy->full_name }}</div>
                                <div class="text-xs text-gray-500">{{ $t->processed_at?->format('d/m H:i') }}</div>
                            @else
                                <span class="text-xs text-gray-400">Système</span>
                            @endif
                        </td>

                        <!-- Statut -->
                        <td class="px-5 py-4">
                            @switch($t->status)
                                @case('completed')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Complété</span>
                                    @break
                                @case('pending')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">En attente</span>
                                    @break
                                @case('failed')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">Échoué</span>
                                    @break
                                @case('cancelled')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Annulé</span>
                                    @break
                                @default
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ ucfirst($t->status) }}</span>
                            @endswitch
                        </td>

                        
                    </tr>

                    <!-- Modal Détails -->
                    <div class="modal fade" id="modal-{{ $t->id }}" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="shadow-xl modal-content rounded-2xl">
                                <div class="px-6 py-4 border-b border-gray-200 modal-header">
                                    <h5 class="text-lg font-bold text-gray-900 modal-title">Détails de la transaction</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="p-6 modal-body">
                                    <div class="grid grid-cols-1 gap-6 text-sm md:grid-cols-2">
                                        <div class="space-y-3">
                                            <div class="flex justify-between pb-2 border-b border-gray-100">
                                                <span class="font-medium text-gray-600">Référence</span>
                                                <code class="px-2 py-1 font-mono text-xs bg-gray-100 rounded">{{ $t->transaction_reference }}</code>
                                            </div>
                                            <div class="flex justify-between pb-2 border-b border-gray-100">
                                                <span class="font-medium text-gray-600">Type</span>
                                                <span class="text-gray-900 capitalize">{{ $t->transaction_type }}</span>
                                            </div>
                                            <div class="flex justify-between pb-2 border-b border-gray-100">
                                                <span class="font-medium text-gray-600">Montant</span>
                                                <span class="font-bold text-gray-900">{{ number_format($t->amount, 0, ',', ' ') }} FCFA</span>
                                            </div>
                                            <div class="flex justify-between pb-2 border-b border-gray-100">
                                                <span class="font-medium text-gray-600">Frais</span>
                                                <span class="font-bold text-gray-900">{{ number_format($t->fee_amount, 0, ',', ' ') }} FCFA</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="font-medium text-gray-600">Date</span>
                                                <span class="text-gray-900">{{ $t->transaction_date?->format('d/m/Y à H:i:s') ?? '—' }}</span>
                                            </div>
                                        </div>
                                        <div class="space-y-3">
                                            <div class="flex justify-between pb-2 border-b border-gray-100">
                                                <span class="font-medium text-gray-600">Solde avant</span>
                                                <span class="text-gray-900">{{ number_format($t->balance_before, 0, ',', ' ') }} FCFA</span>
                                            </div>
                                            <div class="flex justify-between pb-2 border-b border-gray-100">
                                                <span class="font-medium text-gray-600">Solde après</span>
                                                <span class="font-medium text-gray-900">{{ number_format($t->balance_after, 0, ',', ' ') }} FCFA</span>
                                            </div>
                                            <div class="flex justify-between pb-2 border-b border-gray-100">
                                                <span class="font-medium text-gray-600">Méthode</span>
                                                <span class="text-gray-900">{{ $t->payment_method ? ucfirst(str_replace('_', ' ', $t->payment_method)) : '—' }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="font-medium text-gray-600">Réf. paiement</span>
                                                <span class="text-gray-900">{{ $t->payment_reference ?? '—' }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    @if($t->description)
                                    <div class="p-4 mt-6 bg-gray-50 rounded-xl">
                                        <p class="mb-1 text-sm font-medium text-gray-700">Description</p>
                                        <p class="text-sm text-gray-900">{{ $t->description }}</p>
                                    </div>
                                    @endif

                                    @if($t->processedBy)
                                    <div class="flex items-center gap-2 mt-5 text-sm text-gray-600">
                                        <i class="fas fa-user-check"></i>
                                        <span><strong>Traité par :</strong> {{ $t->processedBy->full_name }}
                                            @if($t->processed_at) le {{ $t->processed_at->format('d/m/Y à H:i') }} @endif
                                        </span>
                                    </div>
                                    @endif
                                </div>
                                <div class="px-6 py-4 border-t border-gray-200 modal-footer">
                                    <button type="button" class="px-5 py-2.5 bg-gray-600 text-white rounded-xl hover:bg-gray-700 text-sm font-medium transition" data-bs-dismiss="modal">
                                        Fermer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <tr>
                        <td colspan="10" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center text-gray-500">
                                <i class="mb-4 text-6xl text-gray-300 fas fa-inbox"></i>
                                <p class="text-lg font-medium">Aucune transaction trouvée</p>
                                <p class="mt-1 text-sm">Essayez de modifier vos filtres.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($transactions->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            {{ $transactions->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Styles pour l'impression -->
<style>
@media print {
    body * { visibility: hidden; }
    .max-w-7xl, .max-w-7xl * { visibility: visible; }
    .max-w-7xl { position: absolute; left: 0; top: 0; width: 100%; padding: 0 !important; }
    nav, button, [data-bs-toggle], .modal, .hover\:*, .shadow-sm { display: none !important; }
    .bg-white { box-shadow: none !important; border: 1px solid #ddd !important; }
    table { font-size: 10pt; }
    th, td { padding: 8px !important; border: 1px solid #eee !important; }
    .text-xs { font-size: 9pt; }
    .rounded-2xl { border-radius: 0 !important; }
}
</style>
@endsection
