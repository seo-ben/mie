@extends('layouts.app_admin')

@section('title', 'Nouvelle Demande de Prêt')

@section('content')
<div class="max-w-4xl px-4 mx-auto sm:px-6 lg:px-8">
    <!-- En-tête -->
    <div class="mb-6">
        <div class="flex items-center mb-4">
            <a href="{{ route('admin.loans.index') }}" class="mr-4 text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="text-2xl font-semibold text-gray-900">Nouvelle Demande de Prêt</h1>
        </div>
        <p class="text-gray-600">Créer une demande de prêt pour un client</p>
    </div>

    @if(!$client)
    <!-- Recherche de client -->
    <div class="p-6 mb-6 bg-white rounded-lg shadow-sm">
        <h3 class="mb-4 text-lg font-semibold text-gray-900">1. Sélectionner un client</h3>

        <div class="mb-4">
            <label class="block mb-2 text-sm font-medium text-gray-700">Rechercher un client</label>
            <input type="text" id="clientSearch"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                   placeholder="Nom, téléphone ou numéro client...">
        </div>

        <div id="searchResults" class="hidden">
            <!-- Les résultats apparaîtront ici via AJAX -->
        </div>
    </div>
    @endif

    @if($client)
    <!-- Informations du client -->
    <div class="p-6 mb-6 bg-white rounded-lg shadow-sm">
        <h3 class="mb-4 text-lg font-semibold text-gray-900">Client Sélectionné</h3>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <p class="text-sm text-gray-600">Nom complet</p>
                <p class="font-semibold text-gray-900">{{ $client->first_name }} {{ $client->last_name }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Téléphone</p>
                <p class="font-semibold text-gray-900">{{ $client->phone }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">N° Client</p>
                <p class="font-semibold text-gray-900">{{ $client->client_number }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Solde Épargne</p>
                <p class="font-semibold text-green-600">
                    {{ number_format($client->accounts->where('account_type', 'savings')->sum('balance'), 0, ',', ' ') }} FCFA
                </p>
            </div>
        </div>
    </div>

    <!-- Formulaire de demande -->
    <form method="POST" action="{{ route('admin.loans.store') }}">
        @csrf
        <input type="hidden" name="client_id" value="{{ $client->id }}">

        <div class="p-6 mb-6 bg-white rounded-lg shadow-sm">
            <h3 class="mb-4 text-lg font-semibold text-gray-900">Informations du Prêt</h3>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Montant demandé -->
                <div class="md:col-span-2">
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        Montant Demandé <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="requested_amount" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('requested_amount') border-red-500 @enderror"
                           placeholder="Ex: 500000"
                           value="{{ old('requested_amount') }}"
                           min="1000" max="5000000" >
                    @error('requested_amount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Minimum: 1,000 FCFA - Maximum: 5,000,000 FCFA</p>
                </div>

                <!-- Durée -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        Durée (mois) <span class="text-red-500">*</span>
                    </label>
                    <select name="duration_months" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('duration_months') border-red-500 @enderror">
                        <option value="">Sélectionner...</option>
                        <option value="3" {{ old('duration_months') == 3 ? 'selected' : '' }}>3 mois</option>
                        <option value="6" {{ old('duration_months') == 6 ? 'selected' : '' }}>6 mois</option>
                        <option value="12" {{ old('duration_months') == 12 ? 'selected' : '' }}>12 mois</option>
                        <option value="18" {{ old('duration_months') == 18 ? 'selected' : '' }}>18 mois</option>
                        <option value="24" {{ old('duration_months') == 24 ? 'selected' : '' }}>24 mois</option>
                    </select>
                    @error('duration_months')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Type de Crédit -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        Type de Crédit <span class="text-red-500">*</span>
                    </label>
                    <select name="loan_type" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('loan_type') border-red-500 @enderror">
                        <option value="">Sélectionner le type...</option>
                        <option value="CREDIT ORDINAIRE" {{ old('loan_type') == 'CREDIT ORDINAIRE' ? 'selected' : '' }}>CREDIT ORDINAIRE</option>
                        <option value="CREDIT SUR TONTINE" {{ old('loan_type') == 'CREDIT SUR TONTINE' ? 'selected' : '' }}>CREDIT SUR TONTINE</option>
                        <option value="FNFI AGRISEF" {{ old('loan_type') == 'FNFI AGRISEF' ? 'selected' : '' }}>FNFI AGRISEF</option>
                        <option value="FNFI AGRISEF ACOMPAGNEMENT SPC" {{ old('loan_type') == 'FNFI AGRISEF ACOMPAGNEMENT SPC' ? 'selected' : '' }}>FNFI AGRISEF ACOMPAGNEMENT SPC</option>
                        <option value="FNFI AGRISEF INTEGRE" {{ old('loan_type') == 'FNFI AGRISEF INTEGRE' ? 'selected' : '' }}>FNFI AGRISEF INTEGRE</option>
                        <option value="FNFI APSEF INTEGRER" {{ old('loan_type') == 'FNFI APSEF INTEGRER' ? 'selected' : '' }}>FNFI APSEF INTEGRER</option>
                    </select>
                    @error('loan_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Objectif -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        Catégorie d'Objectif <span class="text-red-500">*</span>
                    </label>
                    <select name="purpose_category"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="commerce" {{ old('purpose_category') == 'commerce' ? 'selected' : '' }}>Commerce</option>
                        <option value="agriculture" {{ old('purpose_category') == 'agriculture' ? 'selected' : '' }}>Agriculture</option>
                        <option value="education" {{ old('purpose_category') == 'education' ? 'selected' : '' }}>Éducation</option>
                        <option value="sante" {{ old('purpose_category') == 'sante' ? 'selected' : '' }}>Santé</option>
                        <option value="equipement" {{ old('purpose_category') == 'equipement' ? 'selected' : '' }}>Équipement</option>
                        <option value="autre" {{ old('purpose_category') == 'autre' ? 'selected' : '' }}>Autre</option>
                    </select>
                </div>

                <!-- Description de l'objectif -->
                <div class="md:col-span-2">
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        Description Détaillée <span class="text-red-500">*</span>
                    </label>
                    <textarea name="purpose" required rows="4"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('purpose') border-red-500 @enderror"
                              placeholder="Décrivez l'utilisation prévue du prêt...">{{ old('purpose') }}</textarea>
                    @error('purpose')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Garanties -->
                <div class="md:col-span-2">
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        Description des Garanties
                    </label>
                    <textarea name="collateral_description" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="Décrivez les garanties ou biens offerts en garantie...">{{ old('collateral_description') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">Optionnel mais recommandé pour les montants élevés</p>
                </div>
            </div>
        </div>

        <!-- Informations du garant (optionnel) -->
        <div class="p-6 mb-6 bg-white rounded-lg shadow-sm">
            <h3 class="mb-4 text-lg font-semibold text-gray-900">Informations du Garant (Optionnel)</h3>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">Nom du Garant</label>
                    <input type="text" name="guarantor_name"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Nom complet"
                           value="{{ old('guarantor_name') }}">
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">Téléphone du Garant</label>
                    <input type="text" name="guarantor_phone"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="+228 XX XX XX XX"
                           value="{{ old('guarantor_phone') }}">
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">Relation</label>
                    <input type="text" name="guarantor_relationship"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Ex: Frère, Ami, Collègue"
                           value="{{ old('guarantor_relationship') }}">
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-4">
            <a href="{{ route('admin.loans.index') }}"
               class="px-6 py-2 text-gray-700 transition-colors border border-gray-300 rounded-lg hover:bg-gray-50">
                Annuler
            </a>
            <button type="submit"
                    class="px-6 py-2 text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700">
                <i class="mr-2 fas fa-check"></i>Créer la Demande
            </button>
        </div>
    </form>
    @endif
</div>

@if(!$client)
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('clientSearch');
    const searchResults = document.getElementById('searchResults');
    let searchTimeout;

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();

        console.log('Recherche pour:', query);

        if (query.length < 2) {
            searchResults.classList.add('hidden');
            return;
        }

        searchTimeout = setTimeout(() => {
            const url = `{{ route('admin.clients.search') }}?query=${encodeURIComponent(query)}`;
            console.log('URL appelée:', url);

            fetch(url)
                .then(response => {
                    console.log('Statut de la réponse:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Données reçues:', data);

                    if (data.success && data.data.length > 0) {
                        displayResults(data.data);
                    } else {
                        searchResults.innerHTML = '<p class="py-4 text-center text-gray-500">Aucun client trouvé</p>';
                        searchResults.classList.remove('hidden');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    searchResults.innerHTML = '<p class="py-4 text-center text-red-500">Erreur lors de la recherche</p>';
                    searchResults.classList.remove('hidden');
                });
        }, 300);
    });

    function displayResults(clients) {
        const html = clients.map(client => `
            <div class="p-4 border-b border-gray-200 cursor-pointer last:border-0 hover:bg-gray-50"
                 onclick="selectClient(${client.id})">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-gray-900">${client.first_name} ${client.last_name}</p>
                        <p class="text-sm text-gray-600">${client.phone} • ${client.client_number}</p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-medium ${
                        client.kyc_status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                    }">
                        ${client.kyc_status === 'approved' ? 'KYC Approuvé' : 'KYC Pending'}
                    </span>
                </div>
            </div>
        `).join('');

        searchResults.innerHTML = html;
        searchResults.classList.remove('hidden');
    }

    window.selectClient = function(clientId) {
        window.location.href = `{{ route('admin.loans.create') }}?client_id=${clientId}`;
    };
});
</script>
@endif
@endsection
