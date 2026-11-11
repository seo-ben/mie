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
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        Montant Demandé <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="requested_amount" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('requested_amount') border-red-500 @enderror"
                           placeholder="Ex: 500000"
                           value="{{ old('requested_amount') }}"
                           min="1000" max="5000000" step="any">
                    @error('requested_amount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Min: 1 000 FCFA - Max: 5 000 000 FCFA</p>
                </div>

                <!-- Frais de dossier de crédit (selon client/négocié) -->
                <div>
                    <label class="block mb-2 text-sm font-semibold text-emerald-800">
                        Frais de Dossier de Crédit Perçus (FCFA) <span class="text-xs font-normal text-gray-500">(Variable)</span>
                    </label>
                    <div class="relative">
                        <input type="number" step="any" min="0" name="application_fee"
                               class="w-full px-4 py-2 font-bold text-gray-900 bg-emerald-50/50 border border-emerald-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                               placeholder="Ex: 5000, 10000, 25000..."
                               value="{{ old('application_fee', 0) }}">
                        <span class="absolute right-4 top-2 text-xs font-bold text-emerald-700">FCFA</span>
                    </div>
                    <p class="mt-1 text-xs text-emerald-600">Montant des frais d'étude encaissés lors du montage du dossier.</p>
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

        <!-- Section des 3 Cautionnaires Solidaires -->
        <div class="p-6 mb-6 bg-white rounded-lg shadow-sm border border-slate-100">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-gray-100 flex-wrap gap-2">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg">
                        <i class="fas fa-users-viewfinder"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Cautionnaires Solidaires (Jusqu'à 3 Cautions)</h3>
                        <p class="text-xs text-gray-500">Saisie des garanties personnelles et actes de cautionnement</p>
                    </div>
                </div>
                <span class="inline-flex items-center px-3 py-1 text-xs font-semibold text-purple-700 bg-purple-50 rounded-full border border-purple-200">
                    <i class="mr-1 fas fa-shield-halved"></i> 3 Cautions Requises
                </span>
            </div>

            <div class="space-y-6">
                @for($i = 1; $i <= 3; $i++)
                <div class="p-4 rounded-xl border border-gray-200 bg-gray-50/50">
                    <div class="flex items-center justify-between mb-3">
                        <span class="inline-flex items-center px-3 py-1 text-xs font-bold rounded-full bg-slate-900 text-white">
                            Cautionnaire {{ $i }} {{ $i === 1 ? '(Obligatoire)' : '(Recommandé)' }}
                        </span>
                        <label class="inline-flex items-center gap-2 text-xs font-semibold text-emerald-700 cursor-pointer">
                            <input type="checkbox" name="guarantors[{{ $i }}][is_verified]" value="1" checked class="w-4 h-4 text-emerald-600 rounded">
                            <span>Identité & Accord vérifiés</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label class="block mb-1 text-xs font-medium text-gray-700">Nom & Prénoms {{ $i === 1 ? '*' : '' }}</label>
                            <input type="text" name="guarantors[{{ $i }}][full_name]"
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                   placeholder="Nom complet"
                                   value="{{ old("guarantors.$i.full_name") }}" {{ $i === 1 ? 'required' : '' }}>
                        </div>

                        <div>
                            <label class="block mb-1 text-xs font-medium text-gray-700">Téléphone {{ $i === 1 ? '*' : '' }}</label>
                            <input type="text" name="guarantors[{{ $i }}][phone]"
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg font-mono focus:ring-2 focus:ring-blue-500"
                                   placeholder="90123456"
                                   value="{{ old("guarantors.$i.phone") }}" {{ $i === 1 ? 'required' : '' }}>
                        </div>

                        <div>
                            <label class="block mb-1 text-xs font-medium text-gray-700">N° CNI / Passeport</label>
                            <input type="text" name="guarantors[{{ $i }}][id_card_number]"
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg font-mono focus:ring-2 focus:ring-blue-500"
                                   placeholder="TG-LOM-1990-..."
                                   value="{{ old("guarantors.$i.id_card_number") }}">
                        </div>

                        <div>
                            <label class="block mb-1 text-xs font-medium text-gray-700">Lien de Parenté / Relation</label>
                            <select name="guarantors[{{ $i }}][relationship]" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="Frère / Sœur">Frère / Sœur</option>
                                <option value="Conjoint(e)">Conjoint(e)</option>
                                <option value="Parent (Père / Mère)">Parent (Père / Mère)</option>
                                <option value="Collègue de travail">Collègue de travail</option>
                                <option value="Partenaire d'affaires">Partenaire d'affaires</option>
                                <option value="Autre caution solidaire">Autre caution solidaire</option>
                            </select>
                        </div>

                        <div>
                            <label class="block mb-1 text-xs font-medium text-gray-700">Profession / Activité</label>
                            <input type="text" name="guarantors[{{ $i }}][profession]"
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                   placeholder="Commerçant, artisan..."
                                   value="{{ old("guarantors.$i.profession") }}">
                        </div>

                        <div>
                            <label class="block mb-1 text-xs font-medium text-gray-700">Adresse / Domicile</label>
                            <input type="text" name="guarantors[{{ $i }}][address]"
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                   placeholder="Quartier, Ville"
                                   value="{{ old("guarantors.$i.address") }}">
                        </div>

                        <div>
                            <label class="block mb-1 text-xs font-medium text-gray-700">Valeur de l'Engagement (FCFA)</label>
                            <input type="number" name="guarantors[{{ $i }}][collateral_value]"
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                   placeholder="Ex: 250000" step="any"
                                   value="{{ old("guarantors.$i.collateral_value") }}">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block mb-1 text-xs font-medium text-gray-700">Nature du Gage / Caution</label>
                            <input type="text" name="guarantors[{{ $i }}][collateral_description]"
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                   placeholder="Nantissement stock, gage moto/véhicule, engagement solidaire sur salaire"
                                   value="{{ old("guarantors.$i.collateral_description") }}">
                        </div>
                    </div>
                </div>
                @endfor
            </div>
        </div>

        <!-- Section Grille d'Audit & Contrôles Prêt (Checklist à cocher) -->
        <div class="p-6 mb-6 bg-white rounded-lg shadow-sm border border-slate-100">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-gray-100 flex-wrap gap-2">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                        <i class="fas fa-list-check"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Grille d'Audit & Contrôles Réglementaires Obligatoires</h3>
                        <p class="text-xs text-gray-500">Cochez les vérifications effectuées pour ce dossier de prêt</p>
                    </div>
                </div>
                <span class="inline-flex items-center px-3 py-1 text-xs font-bold rounded-full bg-emerald-100 text-emerald-800">
                    9 Contrôles Réglementaires
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach(\App\Models\Loan::VERIFICATION_ITEMS as $k => $item)
                <div class="p-3 rounded-xl border border-gray-200 bg-gray-50/50 flex items-center justify-between gap-3">
                    <label class="flex items-center gap-3 cursor-pointer select-none mb-0">
                        <input type="checkbox" name="verifications[{{ $k }}]" value="1" checked class="w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500">
                        <div>
                            <span class="text-xs font-semibold text-gray-900 flex items-center gap-1.5">
                                <i class="fas {{ $item['icon'] }} text-gray-400"></i> {{ $item['label'] }}
                            </span>
                            <span class="text-[10px] text-gray-400 block mt-0.5">{{ $item['category'] }}</span>
                        </div>
                    </label>
                </div>
                @endforeach
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
