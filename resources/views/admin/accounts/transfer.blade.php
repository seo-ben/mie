@extends('layouts.app_admin')

@section('content')
<div class="min-h-screen py-8 bg-gray-50">
    <div class="max-w-5xl px-4 mx-auto sm:px-6 lg:px-8">

        <!-- En-tête -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Transfert d'Argent</h1>
                    <p class="mt-2 text-sm text-gray-600">Transférer de l'argent entre comptes clients</p>
                </div>
                <a href="{{ route('admin.accounts.transfer.history') }}"
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 transition bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Historique des transferts
                </a>
            </div>
        </div>

        <!-- Messages -->
        @if(session('success'))
            <div class="p-4 mb-6 border-l-4 border-green-500 rounded bg-green-50">
                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 mb-6 border-l-4 border-red-500 rounded bg-red-50">
                <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
            </div>
        @endif

        @if($errors->any())
            <div class="p-4 mb-6 border-l-4 border-red-500 rounded bg-red-50">
                <h3 class="font-medium text-red-800">Erreurs de validation</h3>
                <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Formulaire -->
        <form action="{{ route('admin.accounts.transfer.process') }}" method="POST" id="transferForm" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                <!-- Compte Source -->
                <div class="p-6 bg-white rounded-lg shadow">
                    <h2 class="flex items-center mb-4 text-lg font-semibold text-gray-900">
                        <svg class="w-5 h-5 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Compte Émetteur
                    </h2>

                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Rechercher le compte source *</label>
                        <input type="text"
                               id="sourceSearch"
                               placeholder="Nom, téléphone, numéro de compte..."
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                               value="{{ $sourceAccount ? $sourceAccount->client->first_name . ' ' . $sourceAccount->client->last_name : '' }}">
                        <input type="hidden" name="source_account_id" id="sourceAccountId" value="{{ $sourceAccount->id ?? '' }}" required>

                        <!-- Résultats de recherche -->
                        <div id="sourceResults" class="hidden mt-2 overflow-hidden bg-white border border-gray-300 rounded-lg shadow-lg"></div>
                    </div>

                    <!-- Info compte source sélectionné -->
                    <div id="sourceAccountInfo" class="hidden p-4 rounded-lg bg-red-50">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900" id="sourceClientName"></p>
                                <p class="text-sm text-gray-600" id="sourceAccountNumber"></p>
                                <p class="text-sm text-gray-600" id="sourceClientPhone"></p>
                                <div class="mt-2">
                                    <span class="text-xs font-medium text-gray-500">Solde disponible:</span>
                                    <p class="text-lg font-bold text-red-600" id="sourceBalance"></p>
                                </div>
                            </div>
                            <button type="button" onclick="clearSource()" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Compte Destinataire -->
                <div class="p-6 bg-white rounded-lg shadow">
                    <h2 class="flex items-center mb-4 text-lg font-semibold text-gray-900">
                        <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        Compte Bénéficiaire
                    </h2>

                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Rechercher le compte destinataire *</label>
                        <input type="text"
                               id="destinationSearch"
                               placeholder="Nom, téléphone, numéro de compte..."
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <input type="hidden" name="destination_account_id" id="destinationAccountId" required>

                        <!-- Résultats de recherche -->
                        <div id="destinationResults" class="hidden mt-2 overflow-hidden bg-white border border-gray-300 rounded-lg shadow-lg"></div>
                    </div>

                    <!-- Info compte destinataire sélectionné -->
                    <div id="destinationAccountInfo" class="hidden p-4 rounded-lg bg-green-50">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900" id="destClientName"></p>
                                <p class="text-sm text-gray-600" id="destAccountNumber"></p>
                                <p class="text-sm text-gray-600" id="destClientPhone"></p>
                                <div class="mt-2">
                                    <span class="text-xs font-medium text-gray-500">Solde actuel:</span>
                                    <p class="text-lg font-bold text-green-600" id="destBalance"></p>
                                </div>
                            </div>
                            <button type="button" onclick="clearDestination()" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Détails du transfert -->
            <div class="p-6 bg-white rounded-lg shadow">
                <h2 class="flex items-center mb-4 text-lg font-semibold text-gray-900">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Détails du Transfert
                </h2>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Montant à transférer (FCFA) *</label>
                        <input type="number"
                               name="amount"
                               id="transferAmount"
                               value="{{ old('amount') }}"
                               min="100"
                               step="0.01"
                               required
                               onkeyup="calculateTotal()"
                               class="w-full px-4 py-2 text-lg font-semibold border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Frais de transfert (FCFA)</label>
                        <input type="number"
                               name="transfer_fee"
                               id="transferFee"
                               value="{{ old('transfer_fee', 0) }}"
                               min="0"
                               step="0.01"
                               onkeyup="calculateTotal()"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <p class="mt-1 text-xs text-gray-500">Par défaut: 0.5% du montant</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Description (optionnel)</label>
                        <textarea name="description"
                                  rows="3"
                                  placeholder="Motif du transfert..."
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">{{ old('description') }}</textarea>
                    </div>
                </div>

                <!-- Résumé du transfert -->
                <div class="p-4 mt-6 border border-blue-200 rounded-lg bg-blue-50">
                    <h3 class="mb-3 font-semibold text-gray-900">Résumé du Transfert</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Montant:</span>
                            <span class="font-medium" id="summaryAmount">0 FCFA</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Frais:</span>
                            <span class="font-medium" id="summaryFee">0 FCFA</span>
                        </div>
                        <div class="pt-2 border-t border-blue-200"></div>
                        <div class="flex justify-between text-base">
                            <span class="font-semibold text-gray-900">Total à débiter:</span>
                            <span class="font-bold text-blue-600" id="summaryTotal">0 FCFA</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-semibold text-gray-900">Bénéficiaire recevra:</span>
                            <span class="font-bold text-green-600" id="summaryReceived">0 FCFA</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="flex items-center justify-end space-x-4">
                <a href="{{ route('admin.accounts.index') }}"
                   class="px-6 py-3 text-gray-700 transition border border-gray-300 rounded-lg hover:bg-gray-50">
                    Annuler
                </a>
                <button type="submit"
                        id="submitBtn"
                        disabled
                        class="px-6 py-3 font-medium text-white transition bg-blue-600 rounded-lg shadow disabled:bg-gray-400 disabled:cursor-not-allowed hover:bg-blue-700">
                    <span class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Effectuer le Transfert
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let sourceAccountData = null;
let destinationAccountData = null;
let searchTimeout = null;

// Initialisation si compte source pré-rempli
@if($sourceAccount)
    sourceAccountData = {
        id: {{ $sourceAccount->id }},
        account_number: '{{ $sourceAccount->account_number }}',
        balance: {{ $sourceAccount->balance }},
        client: {
            name: '{{ $sourceAccount->client->first_name }} {{ $sourceAccount->client->last_name }}',
            phone: '{{ $sourceAccount->client->phone }}'
        }
    };
    displaySourceAccount(sourceAccountData);
@endif

// Recherche compte source
document.getElementById('sourceSearch').addEventListener('input', function(e) {
    clearTimeout(searchTimeout);
    const query = e.target.value.trim();

    if (query.length < 2) {
        document.getElementById('sourceResults').classList.add('hidden');
        return;
    }

    searchTimeout = setTimeout(() => {
        searchAccounts(query, 'source');
    }, 300);
});

// Recherche compte destinataire
document.getElementById('destinationSearch').addEventListener('input', function(e) {
    clearTimeout(searchTimeout);
    const query = e.target.value.trim();

    if (query.length < 2) {
        document.getElementById('destinationResults').classList.add('hidden');
        return;
    }

    searchTimeout = setTimeout(() => {
        searchAccounts(query, 'destination');
    }, 300);
});

// Fonction de recherche
async function searchAccounts(query, type) {
    const excludeId = type === 'destination' ? sourceAccountData?.id : destinationAccountData?.id;

    try {
        const response = await fetch(`{{ route('admin.accounts.search-for-transfer') }}?query=${encodeURIComponent(query)}&exclude_account_id=${excludeId || ''}`);
        const data = await response.json();

        if (data.success) {
            displaySearchResults(data.data, type);
        }
    } catch (error) {
        console.error('Erreur de recherche:', error);
    }
}

// Afficher les résultats
function displaySearchResults(accounts, type) {
    const resultsDiv = document.getElementById(type + 'Results');

    if (accounts.length === 0) {
        resultsDiv.innerHTML = '<p class="p-4 text-sm text-gray-500">Aucun compte trouvé</p>';
        resultsDiv.classList.remove('hidden');
        return;
    }

    let html = '<div class="divide-y divide-gray-200">';
    accounts.forEach(account => {
        html += `
            <div class="p-3 cursor-pointer hover:bg-gray-50" onclick="selectAccount(${JSON.stringify(account).replace(/"/g, '&quot;')}, '${type}')">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-900">${account.client.name}</p>
                        <p class="text-xs text-gray-500">${account.account_number} • ${account.client.phone}</p>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full ${account.account_type === 'savings' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'}">
                            ${account.account_type === 'savings' ? 'Épargne' : 'Tontine'}
                        </span>
                        <p class="mt-1 text-sm font-semibold text-gray-900">${formatMoney(account.balance)} FCFA</p>
                    </div>
                </div>
            </div>
        `;
    });
    html += '</div>';

    resultsDiv.innerHTML = html;
    resultsDiv.classList.remove('hidden');
}

// Sélectionner un compte
function selectAccount(account, type) {
    if (type === 'source') {
        sourceAccountData = account;
        displaySourceAccount(account);
        document.getElementById('sourceSearch').value = account.client.name;
        document.getElementById('sourceResults').classList.add('hidden');
    } else {
        destinationAccountData = account;
        displayDestinationAccount(account);
        document.getElementById('destinationSearch').value = account.client.name;
        document.getElementById('destinationResults').classList.add('hidden');
    }

    checkFormValidity();
}

// Afficher compte source
function displaySourceAccount(account) {
    document.getElementById('sourceAccountId').value = account.id;
    document.getElementById('sourceClientName').textContent = account.client.name;
    document.getElementById('sourceAccountNumber').textContent = account.account_number;
    document.getElementById('sourceClientPhone').textContent = account.client.phone;
    document.getElementById('sourceBalance').textContent = formatMoney(account.balance) + ' FCFA';
    document.getElementById('sourceAccountInfo').classList.remove('hidden');
}

// Afficher compte destinataire
function displayDestinationAccount(account) {
    document.getElementById('destinationAccountId').value = account.id;
    document.getElementById('destClientName').textContent = account.client.name;
    document.getElementById('destAccountNumber').textContent = account.account_number;
    document.getElementById('destClientPhone').textContent = account.client.phone;
    document.getElementById('destBalance').textContent = formatMoney(account.balance) + ' FCFA';
    document.getElementById('destinationAccountInfo').classList.remove('hidden');
}

// Effacer sélection source
function clearSource() {
    sourceAccountData = null;
    document.getElementById('sourceAccountId').value = '';
    document.getElementById('sourceSearch').value = '';
    document.getElementById('sourceAccountInfo').classList.add('hidden');
    checkFormValidity();
}

// Effacer sélection destinataire
function clearDestination() {
    destinationAccountData = null;
    document.getElementById('destinationAccountId').value = '';
    document.getElementById('destinationSearch').value = '';
    document.getElementById('destinationAccountInfo').classList.add('hidden');
    checkFormValidity();
}

// Calculer le total
function calculateTotal() {
    const amount = parseFloat(document.getElementById('transferAmount').value) || 0;
    let fee = parseFloat(document.getElementById('transferFee').value);

    // Si pas de frais spécifié, calculer 0.5%
    if (isNaN(fee) || fee === 0) {
        fee = Math.round(amount * 0.005 * 100) / 100;
        document.getElementById('transferFee').value = fee;
    }

    const total = amount + fee;

    document.getElementById('summaryAmount').textContent = formatMoney(amount) + ' FCFA';
    document.getElementById('summaryFee').textContent = formatMoney(fee) + ' FCFA';
    document.getElementById('summaryTotal').textContent = formatMoney(total) + ' FCFA';
    document.getElementById('summaryReceived').textContent = formatMoney(amount) + ' FCFA';

    checkFormValidity();
}

// Vérifier validité du formulaire
function checkFormValidity() {
    const amount = parseFloat(document.getElementById('transferAmount').value) || 0;
    const sourceId = document.getElementById('sourceAccountId').value;
    const destId = document.getElementById('destinationAccountId').value;
    const sourceBalance = sourceAccountData?.balance || 0;
    const fee = parseFloat(document.getElementById('transferFee').value) || (amount * 0.005);
    const total = amount + fee;

    const isValid = sourceId && destId && amount >= 100 && total <= sourceBalance;

    document.getElementById('submitBtn').disabled = !isValid;
}

// Formater les montants
function formatMoney(amount) {
    return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
}

// Fermer les résultats en cliquant ailleurs
document.addEventListener('click', function(e) {
    if (!e.target.closest('#sourceSearch') && !e.target.closest('#sourceResults')) {
        document.getElementById('sourceResults').classList.add('hidden');
    }
    if (!e.target.closest('#destinationSearch') && !e.target.closest('#destinationResults')) {
        document.getElementById('destinationResults').classList.add('hidden');
    }
});

// Initialiser le calcul
calculateTotal();
</script>
@endsection
