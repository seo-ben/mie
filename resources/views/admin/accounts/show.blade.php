@extends('layouts.app_admin')

@section('title', 'Détails du Compte')

@section('content')
<div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
    <!-- En-tête -->
    <div class="mb-6">
        <nav class="flex mb-4" aria-label="breadcrumb">
            <ol class="flex items-center space-x-2 text-sm">
                <li><a href="{{ route('admin.accounts.index') }}" class="text-blue-600 hover:text-blue-800">Comptes</a></li>
                <li class="text-gray-500">/</li>
                <li class="text-gray-700">{{ $account->account_number }}</li>
            </ol>
        </nav>

        <div class="flex items-center justify-between">
            <div>
                <h1 class="mb-2 text-2xl font-semibold text-gray-900">Compte {{ $account->account_number }}</h1>
                <p class="text-gray-600">
                    {{ $account->account_type === 'savings' ? 'Compte d\'Épargne' : 'Compte Tontine' }}
                </p>
            </div>

            <div class="flex space-x-2">
                @if($account->status === 'suspended')
                    <a href="{{ route('admin.accounts.edit', $account->id) }}" class="px-4 py-2 text-white transition-colors bg-yellow-600 rounded-lg hover:bg-yellow-700">
                        <i class="mr-2 fas fa-edit"></i>Modifier
                    </a>
                @endif

                @if($account->status === 'active')
                    <button type="button" class="px-4 py-2 text-white transition-colors bg-red-600 rounded-lg hover:bg-red-700" data-bs-toggle="modal" data-bs-target="#suspendModal">
                        <i class="mr-2 fas fa-ban"></i>Suspendre
                    </button>
                @elseif($account->status === 'suspended')
                    <form method="POST" action="{{ route('admin.accounts.reactivate', $account->id) }}" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2 text-white transition-colors bg-green-600 rounded-lg hover:bg-green-700" onclick="return confirm('Confirmer la réactivation ?')">
                            <i class="mr-2 fas fa-check"></i>Réactiver
                        </button>
                    </form>
                @endif
                @if($account->status === 'active')
                    <a href="{{ route('admin.accounts.deposit.form', $account->id) }}"
                    class="flex items-center justify-center px-6 py-4 text-white transition-colors bg-green-600 rounded-lg hover:bg-green-700">
                        <i class="mr-3 text-2xl fas fa-plus-circle"></i>
                        <div>
                            <div class="font-semibold">
                                @if($account->account_type === 'tontine')
                                    Faire une Cotisation
                                @else
                                    Effectuer un Dépôt
                                @endif
                            </div>
                            <div class="text-sm opacity-90">Ajouter des fonds</div>
                        </div>
                    </a>
                @else
                    <div class="flex items-center justify-center px-6 py-4 text-gray-600 bg-gray-300 rounded-lg cursor-not-allowed">
                        <i class="mr-3 text-2xl fas fa-plus-circle"></i>
                        <div>
                            <div class="font-semibold">Dépôt Indisponible</div>
                            <div class="text-sm">Compte non actif</div>
                        </div>
                    </div>
                @endif

                {{-- Bouton Retrait --}}
                @if($account->status === 'active' && $account->balance > 0)
                    <a href="{{ route('admin.accounts.withdrawal.form', $account->id) }}"
                    class="flex items-center justify-center px-4 py-2 text-white transition-colors bg-red-600 rounded-lg hover:bg-red-700">
                        <i class="mr-3 text-2xl fas fa-minus-circle"></i>
                        <div>
                            <div class="font-semibold">Effectuer un Retrait</div>
                            <div class="text-sm opacity-90">Retirer des fonds</div>
                        </div>
                    </a>
                @else
                    <div class="flex items-center justify-center px-4 py-2 text-gray-600 bg-gray-300 rounded-lg cursor-not-allowed">
                        <i class="mr-3 text-2xl fas fa-minus-circle"></i>
                        <div>
                            <div class="font-semibold">Retrait Indisponible</div>
                            <div class="text-sm">
                                @if($account->status !== 'active')
                                    Compte non actif
                                @else
                                    Solde insuffisant
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <a href="{{ route('admin.accounts.transactions', $account->id) }}" class="px-4 py-2 text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700">
                    <i class="mr-2 fas fa-list"></i>Transactions
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        <!-- Informations principales -->
        <div class="lg:col-span-4">
            <!-- Statut du compte -->
            <div class="mb-6 bg-white rounded-lg shadow-sm">
                <div class="p-6 text-center">
                    <div class="mb-4">
                        @switch($account->status)
                            @case('active')
                                <span class="inline-flex items-center px-6 py-3 text-lg font-semibold text-green-800 bg-green-100 rounded-full">
                                    <i class="mr-2 fas fa-check-circle"></i>COMPTE ACTIF
                                </span>
                                @break
                            @case('suspended')
                                <span class="inline-flex items-center px-6 py-3 text-lg font-semibold text-red-800 bg-red-100 rounded-full">
                                    <i class="mr-2 fas fa-ban"></i>SUSPENDU
                                </span>
                                @break
                            @case('pending_activation')
                                <span class="inline-flex items-center px-6 py-3 text-lg font-semibold text-yellow-800 bg-yellow-100 rounded-full">
                                    <i class="mr-2 fas fa-clock"></i>EN ATTENTE
                                </span>
                                @break
                        @endswitch
                    </div>

                    <h2 class="text-5xl font-bold text-gray-900">{{ number_format($account->balance, 0, ',', ' ') }} <small class="text-lg font-normal text-gray-500">FCFA</small></h2>
                    <p class="mt-2 text-gray-600">Solde actuel</p>
                </div>
            </div>

            <!-- Informations client -->
            <div class="mb-6 bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h5 class="text-lg font-semibold text-gray-900">Titulaire du Compte</h5>
                </div>
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        @if($account->client->profile_photo_url)
                            <img src="{{ Storage::url($account->client->profile_photo_url) }}"
                                 alt="{{ $account->client->first_name }}"
                                 class="object-cover w-16 h-16 mr-3 rounded-full">
                        @else
                            <div class="flex items-center justify-center w-16 h-16 mr-3 text-white bg-blue-600 rounded-full">
                                <span class="text-xl font-semibold">{{ substr($account->client->first_name, 0, 1) }}{{ substr($account->client->last_name, 0, 1) }}</span>
                            </div>
                        @endif

                        <div>
                            <h6 class="font-semibold text-gray-900">
                                <a href="{{ route('admin.clients.show', $account->client_id) }}" class="hover:text-blue-600">
                                    {{ $account->client->first_name }} {{ $account->client->last_name }}
                                </a>
                            </h6>
                            <small class="text-gray-500">{{ $account->client->client_number }}</small>
                        </div>
                    </div>

                    <table class="w-full text-sm">
                        <tr class="border-b border-gray-100">
                            <td class="py-2 text-gray-600">Téléphone:</td>
                            <td class="py-2 text-right text-gray-900">{{ $account->client->phone }}</td>
                        </tr>
                        <tr class="border-b border-gray-100">
                            <td class="py-2 text-gray-600">Email:</td>
                            <td class="py-2 text-right text-gray-900">{{ $account->client->email ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600">Statut KYC:</td>
                            <td class="py-2 text-right">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $account->client->kyc_status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ ucfirst($account->client->kyc_status) }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Informations du compte -->
            <div class="mb-6 bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h5 class="text-lg font-semibold text-gray-900">Informations du Compte</h5>
                </div>
                <div class="p-6">

                    <table class="w-full text-sm">
                        <tr class="border-b border-gray-100">
                            <td class="py-2 text-gray-600">Date de création:</td>
                            <td class="py-2 text-right text-gray-900">{{ $account->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @if($account->activated_at)
                        <tr class="border-b border-gray-100">
                            <td class="py-2 text-gray-600">Date d'activation:</td>
                            <td class="py-2 text-right text-gray-900">{{ $account->activated_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @endif
                        @if($account->activatedBy)
                        <tr class="border-b border-gray-100">
                            <td class="py-2 text-gray-600">Activé par:</td>
                            <td class="py-2 text-right text-gray-900">{{ $account->activatedBy->full_name ?? 'non renseigné' }}</td>
                        </tr>
                        @endif
                        @if($account->last_transaction_at)
                        <tr class="border-b border-gray-100">
                            <td class="py-2 text-gray-600">Dernière transaction:</td>
                            <td class="py-2 text-right text-gray-900">{{ $account->last_transaction_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="py-2 text-gray-600">Créé par:</td>
                            <td class="py-2 text-right text-gray-900">{{ $account->createdBy->full_name ?? 'non renseigner' }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            @if($account->status === 'suspended' && $account->suspension_reason)
            <div class="mb-6 bg-white border-l-4 border-red-600 rounded-lg shadow-sm">
                <div class="px-6 py-4 text-white bg-red-600 rounded-t-lg">
                    <h5 class="text-lg font-semibold">Raison de la Suspension</h5>
                </div>
                <div class="p-6">
                    <p class="text-gray-900">{{ $account->suspension_reason }}</p>
                    @if($account->suspended_at)
                        <small class="text-gray-500">
                            Suspendu le {{ $account->suspended_at->format('d/m/Y H:i') }}
                        </small>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- Détails selon le type -->
        <div class="lg:col-span-8">
            @if($account->account_type === 'savings')
                @include('admin.accounts.partials.savings-details', ['account' => $account, 'stats' => $stats])
            @else
                @include('admin.accounts.partials.tontine-details', ['account' => $account, 'stats' => $stats])
            @endif

            <!-- Statistiques des transactions -->
            <div class="mb-6 bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h5 class="text-lg font-semibold text-gray-900">Statistiques des Transactions</h5>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-4 text-center md:grid-cols-4">
                        <div class="border-r border-gray-200 last:border-r-0">
                            <h4 class="text-2xl font-bold text-green-600">{{ number_format($stats['total_deposits'], 0, ',', ' ') }}</h4>
                            <small class="text-gray-600">Total Dépôts (FCFA)</small>
                        </div>
                        <div class="border-r border-gray-200 last:border-r-0">
                            <h4 class="text-2xl font-bold text-red-600">{{ number_format($stats['total_withdrawals'], 0, ',', ' ') }}</h4>
                            <small class="text-gray-600">Total Retraits (FCFA)</small>
                        </div>
                        <div class="border-r border-gray-200 last:border-r-0">
                            <h4 class="text-2xl font-bold text-blue-600">{{ number_format($stats['transaction_count']) }}</h4>
                            <small class="text-gray-600">Nombre de Transactions</small>
                        </div>
                        <div>
                            <h4 class="text-2xl font-bold text-cyan-600">
                                @if($stats['last_transaction'])
                                    {{ $stats['last_transaction']->diffForHumans() }}
                                @else
                                    Aucune
                                @endif
                            </h4>
                            <small class="text-gray-600">Dernière Transaction</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dernières transactions -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h5 class="text-lg font-semibold text-gray-900">Dernières Transactions</h5>
                    <a href="{{ route('admin.accounts.transactions', $account->id) }}" class="px-4 py-2 text-sm text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700">
                        Voir tout
                    </a>
                </div>
                <div class="overflow-x-auto">
                    @if($account->transactions->count() > 0)
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Type</th>
                                <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Montant</th>
                                <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Frais</th>
                                <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Solde Après</th>
                                <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($account->transactions as $transaction)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap">
                                    {{ $transaction->transaction_date ? $transaction->transaction_date->format('d/m/Y H:i') : 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @switch($transaction->transaction_type)
                                        @case('deposit')
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-800 bg-green-100 rounded-full">Dépôt</span>
                                            @break
                                        @case('withdrawal')
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-red-800 bg-red-100 rounded-full">Retrait</span>
                                            @break
                                        @case('transfer')
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-cyan-100 text-cyan-800">Transfert</span>
                                            @break
                                        @case('fee')
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-yellow-800 bg-yellow-100 rounded-full">Frais</span>
                                            @break
                                        @default
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-gray-800 bg-gray-100 rounded-full">{{ $transaction->transaction_type }}</span>
                                    @endswitch
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900 whitespace-nowrap">{{ number_format($transaction->amount, 0, ',', ' ') }} FCFA</td>
                                <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap">{{ number_format($transaction->fee_amount, 0, ',', ' ') }} FCFA</td>
                                <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap">{{ number_format($transaction->balance_after, 0, ',', ' ') }} FCFA</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $transaction->status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ ucfirst($transaction->status) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <div class="py-12 text-center">
                        <i class="mb-3 text-5xl text-gray-400 fas fa-inbox"></i>
                        <p class="text-gray-500">Aucune transaction enregistrée</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de suspension -->
<div class="modal fade" id="suspendModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.accounts.suspend', $account->id) }}">
                @csrf
                <div class="text-white bg-red-600 modal-header">
                    <h5 class="modal-title">Suspendre le Compte</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="p-4 mb-4 border border-yellow-200 rounded-lg bg-yellow-50">
                        <i class="mr-2 text-yellow-600 fas fa-exclamation-triangle"></i>
                        <span class="text-sm text-yellow-800">Cette action suspendra temporairement le compte. Le client ne pourra plus effectuer de transactions.</span>
                    </div>

                    <div class="mb-3">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Raison de la suspension <span class="text-red-600">*</span></label>
                        <textarea name="reason" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" rows="4" required
                                  placeholder="Indiquez la raison de la suspension..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="px-4 py-2 text-white bg-gray-600 rounded-lg hover:bg-gray-700" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="px-4 py-2 text-white bg-red-600 rounded-lg hover:bg-red-700">Confirmer la Suspension</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
