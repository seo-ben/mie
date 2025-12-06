@extends('layouts.app_admin')

@section('content')
<div class="min-h-screen py-8 bg-gray-50">
    <div class="max-w-4xl px-4 mx-auto sm:px-6 lg:px-8">

        <!-- En-tête -->
        <div class="mb-8">
            <a href="{{ route('admin.accounts.transfer.history') }}"
               class="inline-flex items-center mb-4 text-sm text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Retour à l'historique
            </a>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Détails du Transfert</h1>
                    <p class="mt-2 text-sm text-gray-600">Référence: {{ $transaction->payment_reference }}</p>
                </div>
                <span class="inline-flex items-center px-4 py-2 text-sm font-medium text-green-800 bg-green-100 rounded-full">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Complété
                </span>
            </div>
        </div>

        <!-- Message de succès -->
        @if(session('success'))
            <div class="p-4 mb-6 border-l-4 border-green-500 rounded bg-green-50">
                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
            </div>
        @endif

        <!-- Flux du transfert -->
        <div class="p-8 mb-6 bg-white rounded-lg shadow">
            <div class="grid grid-cols-1 gap-8 md:grid-cols-3">

                <!-- Compte émetteur -->
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 mb-4 bg-red-100 rounded-full">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <h3 class="mb-2 text-sm font-medium text-gray-500 uppercase">Émetteur</h3>
                    @if($transaction->transaction_type === 'transfer_out')
                        <p class="text-lg font-bold text-gray-900">{{ $transaction->account->client->first_name }} {{ $transaction->account->client->last_name }}</p>
                        <p class="text-sm text-gray-600">{{ $transaction->account->account_number }}</p>
                        <p class="text-sm text-gray-500">{{ $transaction->account->client->phone }}</p>
                    @elseif($relatedTransaction)
                        <p class="text-lg font-bold text-gray-900">{{ $relatedTransaction->account->client->first_name }} {{ $relatedTransaction->account->client->last_name }}</p>
                        <p class="text-sm text-gray-600">{{ $relatedTransaction->account->account_number }}</p>
                        <p class="text-sm text-gray-500">{{ $relatedTransaction->account->client->phone }}</p>
                    @endif
                </div>

                <!-- Flèche et montant -->
                <div class="flex flex-col items-center justify-center">
                    <svg class="w-12 h-12 mb-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-blue-600">{{ number_format($transaction->amount, 0, ',', ' ') }}</p>
                        <p class="text-sm text-gray-600">FCFA</p>
                        @if($transaction->fee_amount > 0)
                            <p class="mt-2 text-xs text-gray-500">Frais: {{ number_format($transaction->fee_amount, 0, ',', ' ') }} FCFA</p>
                        @endif
                    </div>
                </div>

                <!-- Compte bénéficiaire -->
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 mb-4 bg-green-100 rounded-full">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <h3 class="mb-2 text-sm font-medium text-gray-500 uppercase">Bénéficiaire</h3>
                    @if($transaction->transaction_type === 'transfer_in')
                        <p class="text-lg font-bold text-gray-900">{{ $transaction->account->client->first_name }} {{ $transaction->account->client->last_name }}</p>
                        <p class="text-sm text-gray-600">{{ $transaction->account->account_number }}</p>
                        <p class="text-sm text-gray-500">{{ $transaction->account->client->phone }}</p>
                    @elseif($relatedTransaction)
                        <p class="text-lg font-bold text-gray-900">{{ $relatedTransaction->account->client->first_name }} {{ $relatedTransaction->account->client->last_name }}</p>
                        <p class="text-sm text-gray-600">{{ $relatedTransaction->account->account_number }}</p>
                        <p class="text-sm text-gray-500">{{ $relatedTransaction->account->client->phone }}</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Détails de la transaction -->
        <div class="grid grid-cols-1 gap-6 mb-6 md:grid-cols-2">

            <!-- Informations principales -->
            <div class="p-6 bg-white rounded-lg shadow">
                <h2 class="flex items-center mb-4 text-lg font-semibold text-gray-900">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Informations du Transfert
                </h2>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500">Référence transfert:</dt>
                        <dd class="font-mono text-sm font-semibold text-gray-900">{{ $transaction->payment_reference }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500">Référence transaction:</dt>
                        <dd class="font-mono text-sm text-gray-900">{{ $transaction->transaction_reference }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500">Date et heure:</dt>
                        <dd class="text-sm text-gray-900">{{ $transaction->transaction_date->format('d/m/Y à H:i') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500">Type de transfert:</dt>
                        <dd class="text-sm font-medium text-gray-900">Transfert interne</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500">Traité par:</dt>
                        <dd class="text-sm text-gray-900">
                            {{ $transaction->processedBy->first_name ?? '' }} {{ $transaction->processedBy->last_name ?? '' }}
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Détails financiers -->
            <div class="p-6 bg-white rounded-lg shadow">
                <h2 class="flex items-center mb-4 text-lg font-semibold text-gray-900">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Détails Financiers
                </h2>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500">Montant transféré:</dt>
                        <dd class="text-lg font-bold text-blue-600">{{ number_format($transaction->amount, 0, ',', ' ') }} FCFA</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500">Frais de transfert:</dt>
                        <dd class="text-sm font-semibold text-gray-900">{{ number_format($transaction->fee_amount, 0, ',', ' ') }} FCFA</dd>
                    </div>
                    <div class="pt-3 border-t border-gray-200"></div>
                    @if($transaction->transaction_type === 'transfer_out')
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Total débité:</dt>
                            <dd class="text-lg font-bold text-red-600">{{ number_format($transaction->amount + $transaction->fee_amount, 0, ',', ' ') }} FCFA</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Solde avant:</dt>
                            <dd class="text-sm text-gray-900">{{ number_format($transaction->balance_before, 0, ',', ' ') }} FCFA</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Solde après:</dt>
                            <dd class="text-sm font-semibold text-gray-900">{{ number_format($transaction->balance_after, 0, ',', ' ') }} FCFA</dd>
                        </div>
                    @else
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Montant reçu:</dt>
                            <dd class="text-lg font-bold text-green-600">{{ number_format($transaction->amount, 0, ',', ' ') }} FCFA</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Solde avant:</dt>
                            <dd class="text-sm text-gray-900">{{ number_format($transaction->balance_before, 0, ',', ' ') }} FCFA</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Solde après:</dt>
                            <dd class="text-sm font-semibold text-gray-900">{{ number_format($transaction->balance_after, 0, ',', ' ') }} FCFA</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>

        <!-- Description -->
        @if($transaction->description)
            <div class="p-6 mb-6 bg-white rounded-lg shadow">
                <h2 class="flex items-center mb-3 text-lg font-semibold text-gray-900">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                    </svg>
                    Description
                </h2>
                <p class="text-sm text-gray-700">{{ $transaction->description }}</p>
            </div>
        @endif

        <!-- Transactions liées -->
        @if($relatedTransaction)
            <div class="p-6 bg-white rounded-lg shadow">
                <h2 class="flex items-center mb-4 text-lg font-semibold text-gray-900">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    Transaction Liée
                </h2>
                <div class="p-4 rounded-lg bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $relatedTransaction->transaction_reference }}</p>
                            <p class="text-xs text-gray-500">
                                {{ $relatedTransaction->transaction_type === 'transfer_out' ? 'Débit' : 'Crédit' }} -
                                {{ $relatedTransaction->account->client->first_name }} {{ $relatedTransaction->account->client->last_name }}
                            </p>
                        </div>
                        <span class="text-lg font-bold {{ $relatedTransaction->transaction_type === 'transfer_out' ? 'text-red-600' : 'text-green-600' }}">
                            {{ $relatedTransaction->transaction_type === 'transfer_out' ? '-' : '+' }}
                            {{ number_format($relatedTransaction->amount, 0, ',', ' ') }} FCFA
                        </span>
                    </div>
                </div>
            </div>
        @endif

        <!-- Actions -->
        <div class="flex justify-end mt-6 space-x-4">
            <a href="{{ route('admin.accounts.show', $transaction->account_id) }}"
               class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 transition bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                Voir le compte
            </a>
            <button onclick="window.print()"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white transition bg-blue-600 rounded-lg hover:bg-blue-700">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Imprimer
            </button>
        </div>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    .print-area, .print-area * {
        visibility: visible;
    }
    .print-area {
        position: absolute;
        left: 0;
        top: 0;
    }
}
</style>
@endsection
