@extends('layouts.app_admin')

@section('title', 'Créer un Compte')

@section('content')
<div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
    <!-- ===== EN-TÊTE ===== -->
    <div class="mb-6">
        <h1 class="mb-2 text-3xl font-bold text-gray-900">
            <i class="mr-2 text-yellow-500 fas fa-bolt animate-pulse"></i>Dépôt Rapide
        </h1>
        <p class="text-gray-600">Recherchez un compte et effectuez un dépôt instantanément</p>
    </div>

    <!-- ===== LAYOUT PRINCIPAL : 2 COLONNES ===== -->
    <div class="grid grid-cols-1 gap-6 mb-6 lg:grid-cols-3">

        <!-- ===== COLONNE GAUCHE : RECHERCHE ===== -->
        <div class="lg:col-span-1">
            <div class="sticky bg-white rounded-lg shadow-sm top-4">
                <div class="p-6">
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        <i class="mr-2 text-blue-600 fas fa-search"></i>Rechercher un compte
                    </label>
                    <div class="relative">
                        <input type="text"
                               id="searchInput"
                               class="w-full px-4 py-3 pl-12 transition-all border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Nom, numéro, téléphone..."
                               autocomplete="off"
                               autofocus>
                        <i class="absolute text-gray-400 fas fa-search left-4 top-4"></i>
                        <div id="searchSpinner" class="absolute hidden right-4 top-4">
                            <i class="text-blue-600 fas fa-circle-notch fa-spin"></i>
                        </div>
                    </div>

                    <!-- Instructions -->
                    <div class="p-4 mt-4 border border-blue-200 rounded-lg bg-gradient-to-r from-blue-50 to-indigo-50">
                        <p class="mb-2 text-sm font-semibold text-blue-800">
                            <i class="mr-1 fas fa-info-circle"></i>Comment rechercher ?
                        </p>
                        <ul class="text-xs text-blue-700 space-y-1.5">
                            <li class="flex items-center">
                                <i class="mr-2 text-blue-500 fas fa-check-circle"></i>
                                Numéro de compte
                            </li>
                            <li class="flex items-center">
                                <i class="mr-2 text-blue-500 fas fa-check-circle"></i>
                                Numéro client
                            </li>
                            <li class="flex items-center">
                                <i class="mr-2 text-blue-500 fas fa-check-circle"></i>
                                Nom du client
                            </li>
                            <li class="flex items-center">
                                <i class="mr-2 text-blue-500 fas fa-check-circle"></i>
                                Numéro de téléphone
                            </li>
                        </ul>
                    </div>

                    <!-- Raccourcis -->
                    <div class="pt-4 mt-4 border-t border-gray-200">
                        <p class="mb-2 text-xs font-semibold tracking-wide text-gray-500">RACCOURCIS</p>
                        <button onclick="focusSearch()"
                                class="flex items-center w-full px-3 py-2 text-sm text-left text-gray-700 transition-colors rounded-lg hover:bg-blue-50">
                            <i class="mr-2 text-gray-400 fas fa-keyboard"></i>Focus recherche (Ctrl+K)
                        </button>
                        <button onclick="resetAll()"
                                class="flex items-center w-full px-3 py-2 mt-1 text-sm text-left text-gray-700 transition-colors rounded-lg hover:bg-red-50">
                            <i class="mr-2 text-gray-400 fas fa-redo"></i>Réinitialiser tout
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== COLONNE DROITE : RÉSULTATS ===== -->
        <div class="lg:col-span-2">
            <!-- État initial -->
            <div id="initialState" class="bg-white rounded-lg shadow-sm">
                <div class="p-12 text-center">
                    <div class="inline-flex items-center justify-center w-24 h-24 mb-6 rounded-full shadow-lg bg-gradient-to-br from-blue-100 to-indigo-100">
                        <i class="text-5xl text-blue-600 fas fa-search"></i>
                    </div>
                    <h3 class="mb-2 text-xl font-bold text-gray-900">Recherchez un compte</h3>
                    <p class="mb-6 text-gray-500">Entrez au moins 2 caractères pour démarrer la recherche</p>
                    <div class="flex justify-center gap-6 text-sm text-gray-600">
                        <div class="flex items-center px-4 py-2 rounded-full bg-cyan-50">
                            <i class="mr-2 fas fa-piggy-bank text-cyan-600"></i>Épargne
                        </div>
                        <div class="flex items-center px-4 py-2 rounded-full bg-purple-50">
                            <i class="mr-2 text-purple-600 fas fa-users"></i>Tontine
                        </div>
                    </div>
                </div>
            </div>

            <!-- Résultats de recherche -->
            <div id="searchResults" class="hidden">
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold text-gray-900">
                                <i class="mr-2 text-blue-600 fas fa-list"></i>Résultats de recherche
                            </h3>
                            <span id="resultsCount" class="text-sm font-semibold text-blue-600"></span>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4" id="accountsList"></div>
                    </div>
                </div>
            </div>

            <!-- Message vide -->
            <div id="emptyMessage" class="hidden bg-white rounded-lg shadow-sm">
                <div class="p-12 text-center">
                    <div class="inline-flex items-center justify-center w-24 h-24 mb-6 bg-gray-100 rounded-full">
                        <i class="text-5xl text-gray-400 fas fa-inbox"></i>
                    </div>
                    <h3 class="mb-2 text-xl font-bold text-gray-900">Aucun compte trouvé</h3>
                    <p class="text-gray-500">Essayez avec d'autres critères de recherche</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== FORMULAIRE DE DÉPÔT (en bas) ===== -->
    <div id="depositFormContainer" class="hidden mt-8">
        <div class="pt-8 border-t-4 border-green-500">

            <!-- Carte d'informations du compte -->
            <div id="accountInfoCard" class="mb-6"></div>

            <!-- Formulaire -->
            <div class="overflow-hidden bg-white rounded-lg shadow-xl">
                <div class="px-6 py-5 bg-gradient-to-r from-green-600 to-emerald-700">
                    <h5 class="text-2xl font-bold text-white">
                        <i class="mr-2 fas fa-money-bill-wave"></i>Formulaire de Dépôt
                    </h5>
                </div>

                <form id="depositForm">
                    @csrf
                    <input type="hidden" id="accountId" name="account_id">

                    <div class="p-8 space-y-6">
                        <!-- Montant -->
                        <div>
                            <label class="block mb-2 text-sm font-semibold text-gray-700">
                                Montant du Dépôt <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="number"
                                       name="amount"
                                       id="amount"
                                       class="w-full px-4 py-4 pr-24 text-xl font-bold transition-all border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                       placeholder="0"
                                       min="100"
                                       step="100"
                                       required>
                                <span class="absolute text-lg font-bold text-gray-500 right-4 top-4">FCFA</span>
                            </div>
                            <div id="amountHint" class="mt-2 text-sm"></div>
                            <div id="amountError" class="hidden mt-2 text-sm font-semibold text-red-600"></div>
                        </div>

                        <!-- Méthode de paiement -->
                        <div>
                            <label class="block mb-3 text-sm font-semibold text-gray-700">
                                Méthode de Paiement <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                @foreach([
                                    ['value' => 'cash', 'icon' => 'money-bill-wave', 'color' => 'green', 'label' => 'Espèces', 'desc' => 'Paiement cash'],
                                    ['value' => 'mobile_money', 'icon' => 'mobile-alt', 'color' => 'blue', 'label' => 'Mobile Money', 'desc' => 'TMoney / Flooz'],
                                    ['value' => 'bank_transfer', 'icon' => 'university', 'color' => 'purple', 'label' => 'Virement', 'desc' => 'Virement bancaire']
                                ] as $method)
                                <label class="relative flex items-center p-4 transition-all border-2 border-gray-300 rounded-lg cursor-pointer payment-method-label hover:bg-gray-50">
                                    <input type="radio"
                                           name="payment_method"
                                           value="{{ $method['value'] }}"
                                           class="sr-only payment-method-radio"
                                           {{ $loop->first ? 'checked' : '' }}
                                           required>
                                    <div class="flex items-center w-full">
                                        <i class="fas fa-{{ $method['icon'] }} text-2xl text-{{ $method['color'] }}-600 mr-3"></i>
                                        <div>
                                            <p class="font-bold text-gray-900">{{ $method['label'] }}</p>
                                            <p class="text-sm text-gray-500">{{ $method['desc'] }}</p>
                                        </div>
                                    </div>
                                    <i class="fas fa-check-circle text-2xl text-{{ $method['color'] }}-600 ml-auto hidden check-icon"></i>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Opérateur Mobile Money -->
                        <div id="mobile-money-operator-field" class="hidden">
                            <label class="block mb-3 text-sm font-semibold text-gray-700">
                                Opérateur Mobile Money <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                @foreach([
                                    ['value' => 'tmoney', 'color' => 'red', 'letter' => 'T', 'name' => 'TMoney', 'provider' => 'Togocom'],
                                    ['value' => 'flooz', 'color' => 'orange', 'letter' => 'F', 'name' => 'Flooz', 'provider' => 'Moov Africa']
                                ] as $operator)
                                <label class="relative flex items-center p-4 transition-all border-2 border-gray-300 rounded-lg cursor-pointer operator-label hover:bg-gray-50">
                                    <input type="radio"
                                           name="mobile_money_operator"
                                           value="{{ $operator['value'] }}"
                                           class="sr-only operator-radio">
                                    <div class="flex items-center w-full">
                                        <div class="w-14 h-14 bg-{{ $operator['color'] }}-100 rounded-lg flex items-center justify-center mr-3 shadow-md">
                                            <span class="text-{{ $operator['color'] }}-600 font-black text-2xl">{{ $operator['letter'] }}</span>
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-900">{{ $operator['name'] }}</p>
                                            <p class="text-sm text-gray-500">{{ $operator['provider'] }}</p>
                                        </div>
                                    </div>
                                    <i class="fas fa-check-circle text-2xl text-{{ $operator['color'] }}-600 ml-auto hidden operator-check-icon"></i>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Référence de paiement -->
                        <div>
                            <label class="block mb-2 text-sm font-semibold text-gray-700">
                                Référence de Paiement (Optionnel)
                            </label>
                            <input type="text"
                                   name="payment_reference"
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                   placeholder="Ex: TRX123456789">
                            <p class="mt-1 text-sm text-gray-500">
                                <i class="mr-1 fas fa-info-circle"></i>
                                Numéro de transaction mobile money ou virement bancaire
                            </p>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block mb-2 text-sm font-semibold text-gray-700">
                                Notes / Description (Optionnel)
                            </label>
                            <textarea name="description"
                                      rows="3"
                                      class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                      placeholder="Informations complémentaires..."></textarea>
                        </div>

                        <!-- Récapitulatif -->
                        <div class="p-6 border-2 border-blue-300 shadow-lg bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 rounded-xl">
                            <h6 class="flex items-center mb-4 text-lg font-bold text-gray-900">
                                <i class="mr-2 text-blue-600 fas fa-clipboard-check"></i>
                                Récapitulatif du Dépôt
                            </h6>
                            <div id="recapContent" class="space-y-3 text-sm">
                                <!-- Contenu dynamique -->
                            </div>
                        </div>
                    </div>

                    <!-- Boutons d'action -->
                    <div class="flex flex-col gap-3 px-8 py-5 border-t-2 border-gray-200 bg-gray-50 sm:flex-row">
                        <button type="submit"
                                id="submitBtn"
                                class="flex-1 px-6 py-4 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-lg hover:from-green-700 hover:to-emerald-700 transition-all font-bold text-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                            <i class="mr-2 fas fa-check-circle"></i>Enregistrer le Dépôt
                        </button>
                        <button type="button"
                                onclick="resetAll()"
                                class="px-6 py-4 font-bold text-gray-800 transition-all bg-gray-300 rounded-lg shadow-md hover:bg-gray-400">
                            <i class="mr-2 fas fa-times-circle"></i>Annuler
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal de succès -->
<div id="successModal" class="fixed inset-0 z-50 flex items-center justify-center hidden p-4 bg-black bg-opacity-50">
    <div class="w-full max-w-md transition-all transform bg-white shadow-2xl rounded-xl">
        <div class="p-6">
            <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 bg-green-100 rounded-full">
                <i class="text-4xl text-green-600 fas fa-check-circle"></i>
            </div>
            <h3 class="mb-2 text-2xl font-bold text-center text-gray-900">Dépôt Réussi !</h3>
            <div id="successMessage" class="mb-6 text-center text-gray-700"></div>
            <button onclick="closeSuccessModal()"
                    class="w-full px-6 py-3 font-bold text-white bg-green-600 rounded-lg hover:bg-green-700">
                Fermer
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script >


    /**
 * ==============================================
 * QUICK DEPOSIT - JavaScript Refactorisé
 * ==============================================
 * Gestion complète du dépôt rapide avec :
 * - Recherche avec debounce
 * - Logique multi-cycles tontine
 * - Validation en temps réel
 * - Interface utilisateur fluide
 * ==============================================
 */

// ===== VARIABLES GLOBALES =====
let searchTimeout = null;
let selectedAccount = null;

// ===== CONSTANTES =====
const SEARCH_DEBOUNCE_MS = 500;
const MIN_SEARCH_LENGTH = 2;
const MIN_DEPOSIT_AMOUNT = 100;

// ===== INITIALISATION AU CHARGEMENT =====
document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
    initializeKeyboardShortcuts();
    initializePaymentMethods();
});

/**
 * Initialiser les écouteurs d'événements
 */
function initializeEventListeners() {
    // Recherche avec debounce
    document.getElementById('searchInput').addEventListener('input', handleSearchInput);

    // Montant - mise à jour du récapitulatif
    document.getElementById('amount').addEventListener('input', updateRecapAndValidate);

    // Soumission du formulaire
    document.getElementById('depositForm').addEventListener('submit', handleFormSubmit);
}

/**
 * Initialiser les raccourcis clavier
 */
function initializeKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl+K : Focus sur recherche
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            focusSearch();
        }

        // Escape : Réinitialiser
        if (e.key === 'Escape') {
            resetAll();
        }
    });
}

/**
 * Initialiser les méthodes de paiement
 */
function initializePaymentMethods() {
    // Gestion des méthodes de paiement
    document.querySelectorAll('.payment-method-radio').forEach(radio => {
        radio.addEventListener('change', handlePaymentMethodChange);
    });

    // Gestion des opérateurs
    document.querySelectorAll('.operator-radio').forEach(radio => {
        radio.addEventListener('change', handleOperatorChange);
    });

    // Activer le premier par défaut
    const firstRadio = document.querySelector('.payment-method-radio:checked');
    if (firstRadio) {
        firstRadio.dispatchEvent(new Event('change'));
    }
}

/**
 * =============================================
 * SECTION : RECHERCHE DE COMPTES
 * =============================================
 */

/**
 * Gérer la saisie de recherche
 */
function handleSearchInput(e) {
    const query = e.target.value.trim();

    clearTimeout(searchTimeout);

    if (query.length < MIN_SEARCH_LENGTH) {
        showInitialState();
        return;
    }

    showSearchSpinner(true);

    searchTimeout = setTimeout(() => {
        searchAccounts(query);
    }, SEARCH_DEBOUNCE_MS);
}

/**
 * Rechercher des comptes via API
 */
async function searchAccounts(query) {
    try {
        const response = await fetch(`./quick-deposit-search?query=${encodeURIComponent(query)}`);
        const data = await response.json();

        showSearchSpinner(false);

        if (data.success && data.data.length > 0) {
            displaySearchResults(data.data);
        } else {
            showEmptyMessage();
        }
    } catch (error) {
        console.error('Erreur de recherche:', error);
        showSearchSpinner(false);
        showEmptyMessage();
        showNotification('Erreur lors de la recherche', 'error');
    }
}

/**
 * Afficher les résultats de recherche
 */
function displaySearchResults(accounts) {
    const container = document.getElementById('accountsList');
    container.innerHTML = '';

    accounts.forEach(account => {
        const card = createAccountCard(account);
        container.appendChild(card);
    });

    document.getElementById('initialState').classList.add('hidden');
    document.getElementById('searchResults').classList.remove('hidden');
    document.getElementById('emptyMessage').classList.add('hidden');
    document.getElementById('resultsCount').textContent = `${accounts.length} compte(s) trouvé(s)`;
}

/**
 * Créer une carte de compte
 */
function createAccountCard(account) {
    const div = document.createElement('div');

    const isSavings = account.account_type === 'savings';
    const typeConfig = isSavings ? {
        icon: 'fa-piggy-bank',
        color: 'cyan',
        label: 'Épargne'
    } : {
        icon: 'fa-users',
        color: 'purple',
        label: 'Tontine'
    };

    // Classes de base
    div.className = `border-2 border-gray-200 rounded-xl p-5 hover:border-${typeConfig.color}-500 hover:shadow-xl transition-all cursor-pointer transform hover:-translate-y-1`;

    // Informations additionnelles pour tontine
    let additionalInfo = '';
    if (!isSavings && account.tontine) {
        additionalInfo = renderTontineInfo(account.tontine);
    }

    // Bouton de dépôt
    const canDeposit = account.can_deposit !== false;
    const depositButton = canDeposit
        ? `<button onclick='selectAccount(${JSON.stringify(account).replace(/'/g, "&#39;")})'
                   class="flex-shrink-0 px-6 py-3 bg-gradient-to-r from-${typeConfig.color}-600 to-${typeConfig.color}-700 text-white rounded-lg hover:from-${typeConfig.color}-700 hover:to-${typeConfig.color}-800 transition-all shadow-md hover:shadow-xl font-bold">
               <i class="mr-2 fas fa-arrow-down"></i>Déposer
           </button>`
        : `<div class="flex-shrink-0 px-6 py-3 font-bold text-gray-600 bg-gray-300 rounded-lg cursor-not-allowed">
               <i class="mr-2 fas fa-ban"></i>Complet
           </div>`;

    div.innerHTML = `
        <div class="flex items-start justify-between gap-4">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-3 mb-3">
                    <span class="inline-flex items-center px-4 py-1.5 rounded-full text-sm font-bold bg-${typeConfig.color}-100 text-${typeConfig.color}-800 shadow-sm">
                        <i class="fas ${typeConfig.icon} mr-2"></i>${typeConfig.label}
                    </span>
                    <span class="px-3 py-1 font-mono text-sm font-bold text-gray-700 bg-gray-100 rounded-lg">${account.account_number}</span>
                </div>
                <p class="mb-2 text-lg font-bold text-gray-900 truncate">${account.client.name}</p>
                <div class="flex items-center gap-4 mb-3 text-sm text-gray-600">
                    <span class="flex items-center">
                        <i class="mr-2 text-gray-400 fas fa-id-badge"></i>${account.client.client_number}
                    </span>
                    <span class="flex items-center">
                        <i class="mr-2 text-gray-400 fas fa-phone"></i>${account.client.phone}
                    </span>
                </div>
                <div class="inline-flex items-center px-4 py-2 rounded-lg shadow-sm bg-gradient-to-r from-green-100 to-emerald-100">
                    <i class="mr-2 text-lg text-green-600 fas fa-wallet"></i>
                    <span class="text-xl font-black text-green-700">${formatCurrency(account.balance)}</span>
                </div>
                ${additionalInfo}
                ${!canDeposit ? `<div class="p-3 mt-3 border border-red-200 rounded-lg bg-red-50">
                    <p class="text-sm font-semibold text-red-700">
                        <i class="mr-1 fas fa-exclamation-circle"></i>${account.deposit_blocked_reason || 'Dépôt non autorisé'}
                    </p>
                </div>` : ''}
            </div>
            ${depositButton}
        </div>
    `;

    return div;
}

/**
 * Afficher les informations de tontine
 */
function renderTontineInfo(tontine) {
    if (!tontine.active_cycle) {
        return `
            <div class="p-3 mt-4 border border-purple-200 rounded-lg bg-purple-50">
                <p class="text-sm font-semibold text-purple-700">
                    <i class="mr-1 fas fa-info-circle"></i>${tontine.message || 'Cycle sera créé automatiquement'}
                </p>
            </div>
        `;
    }

    const cycle = tontine.active_cycle;

    return `
        <div class="pt-4 mt-4 border-t border-gray-200">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold tracking-wide text-gray-600 uppercase">Cycle #${cycle.cycle_number}</span>
                <span class="text-xs font-black text-purple-600">${cycle.progress_percent}%</span>
            </div>
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div class="p-3 text-center rounded-lg bg-gradient-to-br from-purple-50 to-pink-50">
                    <p class="mb-1 text-xs font-semibold text-gray-600">Collecté</p>
                    <p class="text-base font-black text-purple-600">${formatCurrency(cycle.collected_amount)}</p>
                </div>
                <div class="p-3 text-center rounded-lg bg-gradient-to-br from-orange-50 to-red-50">
                    <p class="mb-1 text-xs font-semibold text-gray-600">Restant</p>
                    <p class="text-base font-black text-orange-600">${formatCurrency(cycle.remaining_amount)}</p>
                </div>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5 shadow-inner">
                <div class="bg-gradient-to-r from-purple-600 to-purple-500 h-2.5 rounded-full transition-all duration-500 shadow-lg"
                     style="width: ${cycle.progress_percent}%"></div>
            </div>
        </div>
    `;
}

/**
 * =============================================
 * SECTION : SÉLECTION ET FORMULAIRE
 * =============================================
 */

/**
 * Sélectionner un compte
 */
function selectAccount(account) {
    selectedAccount = account;

    // Mettre à jour le formulaire
    document.getElementById('accountId').value = account.id;
    document.getElementById('depositForm').action = account.deposit_url;

    // Afficher les infos du compte
    displayAccountInfo(account);

    // Initialiser le montant
    initializeDepositAmount(account);

    // Afficher le formulaire
    document.getElementById('depositFormContainer').classList.remove('hidden');

    // Mettre à jour le récapitulatif
    updateRecapAndValidate();

    // Scroll vers le formulaire
    setTimeout(() => {
        document.getElementById('depositFormContainer').scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }, 100);
}

/**
 * Afficher les informations du compte sélectionné
 */
function displayAccountInfo(account) {
    const container = document.getElementById('accountInfoCard');

    if (account.account_type === 'savings') {
        container.innerHTML = renderSavingsAccountInfo(account);
    } else {
        container.innerHTML = renderTontineAccountInfo(account);
    }
}

/**
 * Rendu info compte épargne
 */
function renderSavingsAccountInfo(account) {
    return `
        <div class="overflow-hidden border-l-4 shadow-md bg-gradient-to-br from-cyan-50 via-blue-50 to-cyan-50 border-cyan-600 rounded-xl">
            <div class="p-6">
                <div class="flex items-start justify-between mb-4">
                    <h5 class="text-2xl font-bold text-gray-900">
                        <i class="mr-2 fas fa-piggy-bank text-cyan-600"></i>
                        Compte d'Épargne
                    </h5>
                    <button onclick="resetAll()" class="text-gray-400 transition-colors hover:text-gray-600">
                        <i class="text-2xl fas fa-times"></i>
                    </button>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="p-4 bg-white rounded-lg shadow-sm">
                        <p class="mb-2 text-sm font-semibold text-gray-600">Client</p>
                        <p class="text-lg font-bold text-gray-900">${account.client.name}</p>
                        <p class="text-sm text-gray-500">${account.client.client_number}</p>
                    </div>
                    <div class="p-4 bg-white rounded-lg shadow-sm">
                        <p class="mb-2 text-sm font-semibold text-gray-600">Numéro de Compte</p>
                        <p class="font-mono text-lg font-bold text-gray-900">${account.account_number}</p>
                    </div>
                    <div class="p-4 bg-white border-l-4 rounded-lg shadow-sm border-cyan-600">
                        <p class="mb-2 text-sm font-semibold text-gray-600">Solde Actuel</p>
                        <p class="text-2xl font-black text-cyan-600">${formatCurrency(account.balance)}</p>
                    </div>
                </div>
            </div>
        </div>
    `;
}

/**
 * Rendu info compte tontine
 */
function renderTontineAccountInfo(account) {
    const tontine = account.tontine;
    const cycle = tontine.active_cycle;

    return `
        <div class="overflow-hidden border-l-4 border-purple-600 shadow-md bg-gradient-to-br from-purple-50 via-pink-50 to-purple-50 rounded-xl">
            <div class="p-6">
                <div class="flex items-start justify-between mb-4">
                    <h5 class="text-2xl font-bold text-gray-900">
                        <i class="mr-2 text-purple-600 fas fa-users"></i>
                        Tontine ${cycle ? `- Cycle #${cycle.cycle_number}` : ''}
                    </h5>
                    <button onclick="resetAll()" class="text-gray-400 transition-colors hover:text-gray-600">
                        <i class="text-2xl fas fa-times"></i>
                    </button>
                </div>
                <div class="grid grid-cols-1 gap-4 mb-4 md:grid-cols-3">
                    <div class="p-4 bg-white rounded-lg shadow-sm">
                        <p class="mb-2 text-sm font-semibold text-gray-600">Client</p>
                        <p class="text-lg font-bold text-gray-900">${account.client.name}</p>
                        <p class="text-sm text-gray-500">${account.client.client_number}</p>
                    </div>
                    <div class="p-4 bg-white rounded-lg shadow-sm">
                        <p class="mb-2 text-sm font-semibold text-gray-600">Numéro de Compte</p>
                        <p class="font-mono text-lg font-bold text-gray-900">${account.account_number}</p>
                    </div>
                    <div class="p-4 bg-white border-l-4 border-purple-600 rounded-lg shadow-sm">
                        <p class="mb-2 text-sm font-semibold text-gray-600">Solde Actuel</p>
                        <p class="text-2xl font-black text-purple-600">${formatCurrency(account.balance)}</p>
                    </div>
                </div>
                ${cycle ? renderCycleProgress(cycle, tontine) : ''}
            </div>
        </div>
    `;
}

/**
 * Rendu progression cycle
 */
function renderCycleProgress(cycle, tontine) {
    return `
        <div class="p-5 bg-white rounded-lg shadow-sm">
            <div class="grid grid-cols-3 gap-4 mb-4">
                <div class="p-3 text-center rounded-lg bg-purple-50">
                    <p class="mb-1 text-xs font-semibold text-gray-600">Objectif Cycle</p>
                    <p class="text-lg font-black text-purple-900">${formatCurrency(cycle.target_amount)}</p>
                </div>
                <div class="p-3 text-center rounded-lg bg-green-50">
                    <p class="mb-1 text-xs font-semibold text-gray-600">Déjà Collecté</p>
                    <p class="text-lg font-black text-green-600">${formatCurrency(cycle.collected_amount)}</p>
                </div>
                <div class="p-3 text-center rounded-lg bg-orange-50">
                    <p class="mb-1 text-xs font-semibold text-gray-600">Reste à Payer</p>
                    <p class="text-lg font-black text-orange-600">${formatCurrency(cycle.remaining_amount)}</p>
                </div>
            </div>
            <div class="flex justify-between mb-2 text-xs text-gray-600">
                <span class="font-semibold">Progression Globale</span>
                <span class="font-bold text-purple-600">${tontine.total_progress}%</span>
            </div>
            <div class="w-full h-3 bg-gray-200 rounded-full shadow-inner">
                <div class="h-3 transition-all duration-500 rounded-full shadow-lg bg-gradient-to-r from-purple-600 via-purple-500 to-pink-500"
                     style="width: ${tontine.total_progress}%"></div>
            </div>
            <div class="flex justify-between mt-3 text-xs text-gray-600">
                <span>${formatCurrency(tontine.total_paid)}</span>
                <span class="font-bold">${formatCurrency(tontine.total_expected)}</span>
            </div>
        </div>
    `;
}

/**
 * Initialiser le montant de dépôt
 */
function initializeDepositAmount(account) {
    const amountInput = document.getElementById('amount');
    const hintEl = document.getElementById('amountHint');

    if (account.account_type === 'savings') {
        amountInput.value = account.savings.suggested_amount;
        amountInput.removeAttribute('max');
        hintEl.innerHTML = `
            <i class="mr-1 fas fa-info-circle text-cyan-600"></i>
            <span class="font-semibold text-cyan-700">Montant minimum : <strong>${formatCurrency(MIN_DEPOSIT_AMOUNT)}</strong></span>
        `;
    } else {
        const suggestedAmount = account.tontine.suggested_amount;
        const maxAmount = account.tontine.max_deposit_amount;

        amountInput.value = suggestedAmount;
        amountInput.setAttribute('max', maxAmount);

        hintEl.innerHTML = `
            <i class="mr-1 text-purple-600 fas fa-lightbulb"></i>
            <span class="font-semibold text-purple-700">Suggéré : <strong>${formatCurrency(suggestedAmount)}</strong></span>
            <span class="mx-2 text-gray-400">|</span>
            <span class="font-semibold text-orange-700">Maximum : <strong>${formatCurrency(maxAmount)}</strong></span>
        `;
    }
}

/**
 * =============================================
 * SECTION : RÉCAPITULATIF ET VALIDATION
 * =============================================
 */

/**
 * Mettre à jour le récapitulatif et valider
 */
function updateRecapAndValidate() {
    if (!selectedAccount) return;

    const amount = parseFloat(document.getElementById('amount').value) || 0;
    const isValid = validateDepositAmount(amount);

    if (isValid) {
        updateRecap(amount);
    }
}

/**
 * Valider le montant de dépôt
 */
function validateDepositAmount(amount) {
    const errorEl = document.getElementById('amountError');
    const submitBtn = document.getElementById('submitBtn');

    // Reset
    errorEl.classList.add('hidden');
    submitBtn.disabled = false;
    submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');

    // Montant minimum
    if (amount < MIN_DEPOSIT_AMOUNT) {
        showAmountError(`Le montant minimum est de ${formatCurrency(MIN_DEPOSIT_AMOUNT)}`);
        return false;
    }

    // Validation tontine
    if (selectedAccount.account_type === 'tontine' && selectedAccount.tontine) {
        const maxAmount = selectedAccount.tontine.max_deposit_amount;

        if (amount > maxAmount) {
            showAmountError(`Le montant ne peut pas dépasser ${formatCurrency(maxAmount)}`);
            return false;
        }

        if (selectedAccount.tontine.is_complete) {
            showAmountError('Cette tontine est complète, aucun dépôt supplémentaire n\'est autorisé');
            return false;
        }
    }

    return true;
}

/**
 * Afficher une erreur de montant
 */
function showAmountError(message) {
    const errorEl = document.getElementById('amountError');
    const submitBtn = document.getElementById('submitBtn');

    errorEl.textContent = message;
    errorEl.classList.remove('hidden');

    submitBtn.disabled = true;
    submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
}

/**
 * Mettre à jour le récapitulatif
 */
function updateRecap(amount) {
    const newBalance = selectedAccount.balance + amount;
    const typeIcon = selectedAccount.account_type === 'savings'
        ? '<i class="mr-1 fas fa-piggy-bank text-cyan-600"></i>'
        : '<i class="mr-1 text-purple-600 fas fa-users"></i>';
    const typeLabel = selectedAccount.account_type === 'savings' ? 'Épargne' : 'Tontine';

    let content = `
        <div class="flex items-center justify-between py-2 border-b border-blue-200">
            <span class="font-semibold text-gray-700">Client :</span>
            <span class="font-bold text-gray-900">${selectedAccount.client.name}</span>
        </div>
        <div class="flex items-center justify-between py-2 border-b border-blue-200">
            <span class="font-semibold text-gray-700">Type de compte :</span>
            <span class="font-bold text-gray-900">${typeIcon}${typeLabel}</span>
        </div>
    `;

    // Info tontine spécifique
    if (selectedAccount.account_type === 'tontine' && selectedAccount.tontine && selectedAccount.tontine.active_cycle) {
        content += renderRecapTontine(amount, selectedAccount.tontine);
    }

    content += `
        <div class="my-3 border-t-2 border-blue-300"></div>
        <div class="flex items-center justify-between py-2">
            <span class="font-semibold text-gray-700">Solde avant :</span>
            <span class="font-bold text-gray-900">${formatCurrency(selectedAccount.balance)}</span>
        </div>
        <div class="flex items-center justify-between py-2">
            <span class="font-semibold text-gray-700">Montant dépôt :</span>
            <span class="text-2xl font-bold text-blue-600">${formatCurrency(amount)}</span>
        </div>
    `;

    document.getElementById('recapContent').innerHTML = content;
}

/**
 * Récapitulatif spécifique tontine
 */
function renderRecapTontine(amount, tontine) {
    const cycle = tontine.active_cycle;
    if (!cycle) return '';

    const newCollected = cycle.collected_amount + amount;
    const newRemaining = Math.max(0, cycle.target_amount - newCollected);
    const newProgress = cycle.target_amount > 0 ? Math.round((newCollected / cycle.target_amount) * 100) : 0;
    const willComplete = newCollected >= cycle.target_amount;

    return `
        <div class="flex items-center justify-between py-2 border-b border-blue-200">
            <span class="font-semibold text-gray-700">Cycle actuel :</span>
            <span class="font-bold text-gray-900">#${cycle.cycle_number}</span>
        </div>
        <div class="pt-3 mt-3 border-t-2 border-purple-300">
            <p class="flex items-center mb-3 text-sm font-bold text-purple-900">
                <i class="mr-2 fas fa-chart-line"></i>Impact sur le cycle :
            </p>
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-gray-700">Nouveau collecté :</span>
                    <span class="font-bold text-purple-600">${formatCurrency(newCollected)}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-700">Restant après dépôt :</span>
                    <span class="font-bold text-orange-600">${formatCurrency(newRemaining)}</span>
                </div>
            </div>
            <div class="mt-3">
                <div class="flex justify-between mb-1 text-xs text-gray-600">
                    <span class="font-semibold">Progression</span>
                    <span class="font-black text-purple-600">${newProgress}%</span>
                </div>
                <div class="w-full h-3 bg-gray-300 rounded-full shadow-inner">
                    <div class="h-3 transition-all duration-500 rounded-full shadow-lg bg-gradient-to-r from-purple-600 to-purple-500"
                         style="width: ${newProgress}%"></div>
                </div>
            </div>
            ${willComplete ? `
                <div class="p-3 mt-3 border-2 border-green-300 rounded-lg bg-gradient-to-r from-green-100 to-emerald-100">
                    <p class="flex items-center text-sm font-bold text-green-800">
                        <i class="mr-2 text-lg fas fa-check-circle"></i>
                        Ce dépôt complètera le cycle #${cycle.cycle_number} !
                    </p>
                </div>
            ` : ''}
        </div>
    `;
}

/**
 * =============================================
 * SECTION : SOUMISSION DU FORMULAIRE
 * =============================================
 */

/**
 * Gérer la soumission du formulaire
 */
async function handleFormSubmit(e) {
    e.preventDefault();

    if (!selectedAccount) {
        showNotification('Veuillez sélectionner un compte', 'error');
        return;
    }

    const amount = parseFloat(document.getElementById('amount').value);
    if (!validateDepositAmount(amount)) {
        return;
    }

    // Afficher le loader
    showSubmitLoader(true);

    try {
        const formData = new FormData(e.target);

        const response = await fetch(selectedAccount.deposit_url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showSuccessModal(data.message);
            setTimeout(() => {
                resetAll();
            }, 3000);
        } else {
            showNotification(data.message || 'Erreur lors du dépôt', 'error');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showNotification('Erreur lors de la communication avec le serveur', 'error');
    } finally {
        showSubmitLoader(false);
    }
}

/**
 * =============================================
 * SECTION : MÉTHODES DE PAIEMENT
 * =============================================
 */

/**
 * Gérer le changement de méthode de paiement
 */
function handlePaymentMethodChange(e) {
    // Reset tous les labels
    document.querySelectorAll('.payment-method-label').forEach(label => {
        label.classList.remove('border-blue-500', 'bg-blue-50', 'ring-2', 'ring-blue-200');
        label.classList.add('border-gray-300');
        label.querySelector('.check-icon').classList.add('hidden');
    });

    // Activer le label sélectionné
    const label = e.target.closest('.payment-method-label');
    label.classList.remove('border-gray-300');
    label.classList.add('border-blue-500', 'bg-blue-50', 'ring-2', 'ring-blue-200');
    label.querySelector('.check-icon').classList.remove('hidden');

    // Afficher/masquer les opérateurs Mobile Money
    const operatorField = document.getElementById('mobile-money-operator-field');
    if (e.target.value === 'mobile_money') {
        operatorField.classList.remove('hidden');
    } else {
        operatorField.classList.add('hidden');
    }
}

/**
 * Gérer le changement d'opérateur
 */
function handleOperatorChange(e) {
    // Reset tous les labels
    document.querySelectorAll('.operator-label').forEach(label => {
        label.classList.remove('border-blue-500', 'bg-blue-50', 'ring-2', 'ring-blue-200');
        label.classList.add('border-gray-300');
        label.querySelector('.operator-check-icon').classList.add('hidden');
    });

    // Activer le label sélectionné
    const label = e.target.closest('.operator-label');
    label.classList.remove('border-gray-300');
    label.classList.add('border-blue-500', 'bg-blue-50', 'ring-2', 'ring-blue-200');
    label.querySelector('.operator-check-icon').classList.remove('hidden');
}

/**
 * =============================================
 * SECTION : UTILITAIRES UI
 * =============================================
 */

/**
 * Afficher/masquer le spinner de recherche
 */
function showSearchSpinner(show) {
    const spinner = document.getElementById('searchSpinner');
    if (show) {
        spinner.classList.remove('hidden');
    } else {
        spinner.classList.add('hidden');
    }
}

/**
 * Afficher l'état initial
 */
function showInitialState() {
    document.getElementById('initialState').classList.remove('hidden');
    document.getElementById('searchResults').classList.add('hidden');
    document.getElementById('emptyMessage').classList.add('hidden');
}

/**
 * Afficher le message vide
 */
function showEmptyMessage() {
    document.getElementById('initialState').classList.add('hidden');
    document.getElementById('searchResults').classList.add('hidden');
    document.getElementById('emptyMessage').classList.remove('hidden');
}

/**
 * Focus sur le champ de recherche
 */
function focusSearch() {
    document.getElementById('searchInput').focus();
}

/**
 * Afficher le loader de soumission
 */
function showSubmitLoader(show) {
    const btn = document.getElementById('submitBtn');

    if (show) {
        btn.disabled = true;
        btn.innerHTML = '<i class="mr-2 fas fa-spinner fa-spin"></i>Traitement en cours...';
        btn.classList.add('opacity-75', 'cursor-not-allowed');
    } else {
        btn.disabled = false;
        btn.innerHTML = '<i class="mr-2 fas fa-check-circle"></i>Enregistrer le Dépôt';
        btn.classList.remove('opacity-75', 'cursor-not-allowed');
    }
}

/**
 * Afficher une notification toast
 */
function showNotification(message, type = 'info') {
    // Créer la notification
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-2xl transform transition-all duration-300 max-w-md`;

    const colors = {
        success: 'bg-green-600 text-white',
        error: 'bg-red-600 text-white',
        warning: 'bg-yellow-600 text-white',
        info: 'bg-blue-600 text-white'
    };

    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };

    toast.classList.add(...colors[type].split(' '));

    toast.innerHTML = `
        <div class="flex items-center">
            <i class="fas ${icons[type]} text-2xl mr-3"></i>
            <p class="font-semibold">${message}</p>
        </div>
    `;

    document.body.appendChild(toast);

    // Animation d'entrée
    setTimeout(() => {
        toast.style.transform = 'translateX(0)';
    }, 10);

    // Retirer après 5 secondes
    setTimeout(() => {
        toast.style.transform = 'translateX(400px)';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

/**
 * Afficher la modal de succès
 */
function showSuccessModal(message) {
    const modal = document.getElementById('successModal');
    const messageEl = document.getElementById('successMessage');

    messageEl.innerHTML = message;
    modal.classList.remove('hidden');

    // Animation
    setTimeout(() => {
        modal.querySelector('.bg-white').classList.add('scale-100');
    }, 10);
}

/**
 * Fermer la modal de succès
 */
function closeSuccessModal() {
    const modal = document.getElementById('successModal');
    modal.classList.add('hidden');
}

/**
 * =============================================
 * SECTION : RESET ET NETTOYAGE
 * =============================================
 */

/**
 * Réinitialiser tout
 */
function resetAll() {
    // Reset variables
    selectedAccount = null;

    // Reset formulaire
    document.getElementById('depositForm').reset();
    document.getElementById('depositFormContainer').classList.add('hidden');

    // Reset recherche
    document.getElementById('searchInput').value = '';
    showInitialState();

    // Reset méthodes de paiement
    const firstRadio = document.querySelector('.payment-method-radio');
    if (firstRadio) {
        firstRadio.checked = true;
        firstRadio.dispatchEvent(new Event('change'));
    }

    // Scroll vers le haut
    window.scrollTo({ top: 0, behavior: 'smooth' });

    // Focus sur recherche
    setTimeout(() => focusSearch(), 300);
}

/**
 * =============================================
 * SECTION : FORMATAGE
 * =============================================
 */

/**
 * Formater un montant en devise
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'decimal',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount) + ' FCFA';
}

/**
 * =============================================
 * EXPORTS (si module)
 * =============================================
 */
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        selectAccount,
        resetAll,
        focusSearch,
        closeSuccessModal
    };
}
</script>
@endpush

@endsection
