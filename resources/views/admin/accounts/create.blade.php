@extends('layouts.app_admin')

@section('title', 'Créer un Compte')

@section('content')
<div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
    <!-- En-tête -->
    <div class="mb-6">
        <nav class="flex mb-4" aria-label="breadcrumb">
            <ol class="flex items-center space-x-2 text-sm">
                <li><a href="{{ route('admin.clients.index') }}" class="text-blue-600 hover:text-blue-800">Clients</a></li>
                <li class="text-gray-500">/</li>
                <li><a href="{{ route('admin.clients.show', $client->id) }}" class="text-blue-600 hover:text-blue-800">{{ $client->first_name }} {{ $client->last_name }}</a></li>
                <li class="text-gray-500">/</li>
                <li class="text-gray-700">Créer un compte</li>
            </ol>
        </nav>

        <h1 class="text-2xl font-semibold text-gray-900">Créer un Compte pour {{ $client->first_name }} {{ $client->last_name }}</h1>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        <!-- Informations client -->
        <div class="lg:col-span-4">
            <div class="mb-6 bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h5 class="text-lg font-semibold text-gray-900">Informations Client</h5>
                </div>
                <div class="p-6">
                    <div class="mb-4 text-center">
                        @if($client->profile_photo_url)
                            <img src="{{ Storage::url($client->profile_photo_url) }}"
                                 alt="{{ $client->first_name }}"
                                 class="object-cover w-24 h-24 mx-auto rounded-full">
                        @else
                            <div class="flex items-center justify-center w-24 h-24 mx-auto text-white bg-blue-600 rounded-full">
                                <span class="text-3xl font-semibold">{{ substr($client->first_name, 0, 1) }}{{ substr($client->last_name, 0, 1) }}</span>
                            </div>
                        @endif
                    </div>

                    <table class="w-full text-sm">
                        <tr class="border-b border-gray-100">
                            <th class="py-2 font-medium text-left text-gray-700">N° Client:</th>
                            <td class="py-2 text-right text-gray-900">{{ $client->client_number }}</td>
                        </tr>
                        <tr class="border-b border-gray-100">
                            <th class="py-2 font-medium text-left text-gray-700">Téléphone:</th>
                            <td class="py-2 text-right text-gray-900">{{ $client->phone }}</td>
                        </tr>
                        <tr class="border-b border-gray-100">
                            <th class="py-2 font-medium text-left text-gray-700">Email:</th>
                            <td class="py-2 text-right text-gray-900">{{ $client->email ?? 'N/A' }}</td>
                        </tr>
                        <tr class="border-b border-gray-100">
                            <th class="py-2 font-medium text-left text-gray-700">Statut KYC:</th>
                            <td class="py-2 text-right">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $client->kyc_status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ ucfirst($client->kyc_status) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th class="py-2 font-medium text-left text-gray-700">Comptes existants:</th>
                            <td class="py-2 text-right text-gray-900">{{ $client->accounts->count() }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Comptes existants -->
            @if($client->accounts->count() > 0)
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h5 class="text-lg font-semibold text-gray-900">Comptes Existants</h5>
                </div>
                <div class="p-6">
                    @foreach($client->accounts as $existingAccount)
                    <div class="flex items-center justify-between pb-3 mb-3 border-b border-gray-200 last:border-b-0">
                        <div>
                            <div class="font-semibold text-gray-900">{{ $existingAccount->account_number }}</div>
                            <small class="text-gray-500">
                                {{ $existingAccount->account_type === 'savings' ? 'Épargne' : 'Tontine' }}
                            </small>
                        </div>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $existingAccount->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ ucfirst($existingAccount->status) }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Formulaire de création -->
        <div class="lg:col-span-8">
            <form method="POST" action="{{ route('admin.accounts.store', $client->id) }}">
                @csrf

                <!-- Choix du type de compte -->
                <div class="mb-6 bg-white rounded-lg shadow-sm">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h5 class="text-lg font-semibold text-gray-900">Type de Compte</h5>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="form-check-card">
                                <input class="hidden" type="radio" name="account_type"
                                       id="savings" value="savings"
                                       {{ !$hasSavingsAccount ? 'checked' : 'disabled' }}
                                       required>
                                <label class="block cursor-pointer" for="savings">
                                    <div class="border-2 rounded-lg p-6 transition-all hover:shadow-md {{ !$hasSavingsAccount ? 'border-gray-300 hover:border-blue-500' : 'bg-gray-50 border-gray-200 opacity-60' }}" data-radio-card>
                                        <div class="flex items-center mb-3">
                                            <i class="mr-3 text-3xl fas fa-piggy-bank text-cyan-500"></i>
                                            <div>
                                                <h5 class="text-lg font-semibold text-gray-900">Compte d'Épargne</h5>
                                                <small class="text-gray-500">Un seul par client</small>
                                            </div>
                                        </div>
                                        <p class="mb-2 text-sm text-gray-600">Compte d'épargne avec intérêts</p>
                                        <div class="font-bold text-blue-600">Frais: 7,000 FCFA</div>
                                        @if($hasSavingsAccount)
                                            <div class="p-3 mt-3 text-sm text-yellow-800 border border-yellow-200 rounded bg-yellow-50">
                                                Le client possède déjà un compte d'épargne
                                            </div>
                                        @endif
                                    </div>
                                </label>
                            </div>

                            <div class="form-check-card">
                                <input class="hidden" type="radio" name="account_type"
                                       id="tontine" value="tontine"
                                       {{ $hasSavingsAccount ? 'checked' : '' }}
                                       required>
                                <label class="block cursor-pointer" for="tontine">
                                    <div class="p-6 transition-all border-2 border-gray-300 rounded-lg hover:shadow-md hover:border-blue-500" data-radio-card>
                                        <div class="flex items-center mb-3">
                                            <i class="mr-3 text-3xl text-purple-600 fas fa-users"></i>
                                            <div>
                                                <h5 class="text-lg font-semibold text-gray-900">Compte Tontine</h5>
                                                <small class="text-gray-500">Plusieurs possibles</small>
                                            </div>
                                        </div>
                                        <p class="mb-2 text-sm text-gray-600">Épargne avec cycles</p>
                                        <div class="font-bold text-purple-600">Frais: 0,000 FCFA</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configuration compte d'épargne -->
                <div class="hidden mb-6 bg-white rounded-lg shadow-sm" id="savings-config">
                    <div class="px-6 py-4 text-white rounded-t-lg bg-cyan-600">
                        <h5 class="flex items-center text-lg font-semibold">
                            <i class="mr-2 fas fa-piggy-bank"></i>Configuration du Compte d'Épargne
                        </h5>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-700">Taux d'intérêt annuel (%)</label>
                                <input type="number" step="0.01" name="interest_rate"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       value="2.5" min="0" max="100">
                                <small class="text-gray-500">Par défaut: 2.5%</small>
                            </div>

                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-700">Solde minimum (FCFA)</label>
                                <input type="number" name="minimum_balance"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       value="5000" min="0">
                                <small class="text-gray-500">Par défaut: 5,000 FCFA</small>
                            </div>

                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-700">Frais mensuels (FCFA)</label>
                                <input type="number" name="monthly_fee"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       value="500" min="0">
                                <small class="text-gray-500">Par défaut: 500 FCFA</small>
                            </div>
                        </div>

                        <div class="flex items-start p-4 mt-4 border rounded-lg bg-cyan-50 border-cyan-200">
                            <i class="mt-1 mr-2 fas fa-info-circle text-cyan-600"></i>
                            <span class="text-sm text-cyan-800">Les intérêts seront calculés mensuellement sur le solde moyen.</span>
                        </div>
                    </div>
                </div>

                <!-- Configuration compte tontine CORRIGÉE -->
                <div class="hidden mb-6 bg-white rounded-lg shadow-sm" id="tontine-config">
                    <div class="px-6 py-4 text-white bg-purple-600 rounded-t-lg">
                        <h5 class="flex items-center text-lg font-semibold">
                            <i class="mr-2 fas fa-users"></i>Configuration du Compte Tontine
                        </h5>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 gap-4 mb-4 md:grid-cols-2">
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-700">
                                    Montant à payer par période (FCFA) <span class="text-red-600">*</span>
                                </label>
                                <input type="number"
                                    name="target_amount"
                                    id="target_amount"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    min="200"
                                    placeholder="Ex: 5000"
                                    value="5000">
                                <small class="text-gray-500">Ce que vous voulez payer à chaque période</small>
                            </div>

                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-700">
                                    Durée totale (mois) <span class="text-red-600">*</span>
                                </label>
                                <select name="cycle_duration_months"
                                        id="cycle_duration_months"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="">Sélectionner...</option>
                                    <option value="1">1 mois</option>
                                    <option value="3">3 mois</option>
                                    <option value="6">6 mois</option>
                                    <option value="12" selected>12 mois</option>
                                    <option value="18">18 mois</option>
                                    <option value="24">24 mois</option>
                                </select>
                                <small class="text-gray-500">Durée totale de la tontine</small>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block mb-3 text-sm font-medium text-gray-700">
                                Fréquence de paiement <span class="text-red-600">*</span>
                            </label>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <div>
                                    <label class="flex items-start p-4 transition-colors border-2 border-gray-300 rounded-lg cursor-pointer hover:border-blue-500 frequency-option">
                                        <input type="radio"
                                            name="payment_frequency"
                                            value="daily"
                                            class="mt-1 mr-3 text-blue-600 focus:ring-blue-500">
                                        <div>
                                            <strong class="block text-gray-900">Quotidien</strong>
                                            <small class="text-gray-500">Paiements chaque jour</small>
                                        </div>
                                    </label>
                                </div>
                                <div>
                                    <label class="flex items-start p-4 transition-colors border-2 border-gray-300 rounded-lg cursor-pointer hover:border-blue-500 frequency-option">
                                        <input type="radio"
                                            name="payment_frequency"
                                            value="weekly"
                                            class="mt-1 mr-3 text-blue-600 focus:ring-blue-500">
                                        <div>
                                            <strong class="block text-gray-900">Hebdomadaire</strong>
                                            <small class="text-gray-500">Paiements chaque semaine</small>
                                        </div>
                                    </label>
                                </div>
                                <div>
                                    <label class="flex items-start p-4 transition-colors border-2 border-gray-300 rounded-lg cursor-pointer hover:border-blue-500 frequency-option">
                                        <input type="radio"
                                            name="payment_frequency"
                                            value="monthly"
                                            checked
                                            class="mt-1 mr-3 text-blue-600 focus:ring-blue-500">
                                        <div>
                                            <strong class="block text-gray-900">Mensuel</strong>
                                            <small class="text-gray-500">Paiements chaque mois</small>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Aperçu des calculs -->
                        <div id="tontine-summary" class="p-4 border border-purple-200 rounded-lg bg-gradient-to-r from-purple-50 to-blue-50">
                            <h6 class="flex items-center mb-3 font-semibold text-gray-900">
                                <i class="mr-2 text-purple-600 fas fa-calculator"></i>
                                Récapitulatif de votre tontine
                            </h6>
                            <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-3">
                                <div class="p-3 bg-white rounded-lg">
                                    <div class="mb-1 text-gray-600">Nombre de paiements</div>
                                    <div class="text-2xl font-bold text-purple-600" id="total-periods">12</div>
                                    <div class="mt-1 text-xs text-gray-500">périodes au total</div>
                                </div>
                                <div class="p-3 bg-white rounded-lg">
                                    <div class="mb-1 text-gray-600">Par période</div>
                                    <div class="text-2xl font-bold text-blue-600" id="per-period">5,000</div>
                                    <div class="mt-1 text-xs text-gray-500">FCFA à payer</div>
                                </div>
                                <div class="p-3 bg-white rounded-lg">
                                    <div class="mb-1 text-gray-600">Total final</div>
                                    <div class="text-2xl font-bold text-green-600" id="total-expected">60,000</div>
                                    <div class="mt-1 text-xs text-gray-500">FCFA au total</div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-start p-4 mt-4 border border-yellow-200 rounded-lg bg-yellow-50">
                            <i class="mt-1 mr-2 text-yellow-600 fas fa-lightbulb"></i>
                            <div class="text-sm text-yellow-800">
                                <strong>Exemple :</strong> Si vous payez <strong>5,000 FCFA par mois</strong> pendant <strong>12 mois</strong>,
                                vous aurez cotisé un total de <strong>60,000 FCFA</strong> à la fin.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="p-6">
                        <div class="flex justify-between">
                            <a href="{{ route('admin.clients.show', $client->id) }}" class="px-6 py-2 text-white transition-colors bg-gray-600 rounded-lg hover:bg-gray-700">
                                <i class="mr-2 fas fa-arrow-left"></i>Annuler
                            </a>
                            <button type="submit" class="px-6 py-2 text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700">
                                <i class="mr-2 fas fa-save"></i>Créer le Compte
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.form-check-card input:checked + label [data-radio-card] {
    border-color: #3B82F6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const savingsRadio = document.getElementById('savings');
    const tontineRadio = document.getElementById('tontine');
    const savingsConfig = document.getElementById('savings-config');
    const tontineConfig = document.getElementById('tontine-config');

    function updateDisplay() {
        if (savingsRadio.checked) {
            savingsConfig.classList.remove('hidden');
            tontineConfig.classList.add('hidden');
        } else if (tontineRadio.checked) {
            savingsConfig.classList.add('hidden');
            tontineConfig.classList.remove('hidden');
        }
    }

    savingsRadio.addEventListener('change', updateDisplay);
    tontineRadio.addEventListener('change', updateDisplay);

    // Initialisation
    updateDisplay();
});

function updateTontineSummary() {
    const targetAmount = parseFloat(document.getElementById('target_amount').value) || 0;
    const durationMonths = parseInt(document.getElementById('cycle_duration_months').value) || 12;
    const frequency = document.querySelector('input[name="payment_frequency"]:checked')?.value || 'monthly';

    let totalPeriods = 0;
    let frequencyText = '';

    switch(frequency) {
        case 'daily':
            totalPeriods = durationMonths * 30; // Approximation
            frequencyText = 'jours';
            break;
        case 'weekly':
            totalPeriods = durationMonths * 4; // Approximation
            frequencyText = 'semaines';
            break;
        case 'monthly':
            totalPeriods = durationMonths;
            frequencyText = 'mois';
            break;
    }

    const totalExpected = targetAmount * totalPeriods;

    document.getElementById('total-periods').textContent = totalPeriods;
    document.getElementById('per-period').textContent = targetAmount.toLocaleString('fr-FR');
    document.getElementById('total-expected').textContent = totalExpected.toLocaleString('fr-FR');

    document.querySelector('#total-periods + .text-xs').textContent = frequencyText;
}

// Écouter les changements
document.getElementById('target_amount')?.addEventListener('input', updateTontineSummary);
document.getElementById('cycle_duration_months')?.addEventListener('change', updateTontineSummary);
document.querySelectorAll('input[name="payment_frequency"]').forEach(radio => {
    radio.addEventListener('change', updateTontineSummary);
});
</script>
@endpush
@endsection
