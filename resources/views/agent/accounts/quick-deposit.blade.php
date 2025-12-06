@extends('layouts.agent')

@section('title', 'Dépôt Rapide')

@section('content')
<div class="py-4 container-fluid">

    <!-- En-tête -->
    <div class="mb-4 row">
        <div class="col-md-8">
            <h2 class="mb-2">
                <i class="fas fa-bolt text-warning me-2"></i>
                Dépôt Rapide
            </h2>
            <p class="mb-0 text-muted">Recherchez un compte et effectuez un dépôt en quelques secondes</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('agent.accounts.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Retour aux comptes
            </a>
        </div>
    </div>

    <!-- Messages flash -->
    <div id="alertContainer"></div>

    <!-- Section de recherche -->
    <div class="mb-4 row">
        <div class="col-12">
            <div class="border-0 shadow-lg card">
                <div class="p-4 card-body">
                    <div class="row">
                        <div class="col-md-10">
                            <label for="searchInput" class="mb-2 form-label fw-bold">
                                <i class="fas fa-search text-primary me-2"></i>
                                Rechercher un compte
                            </label>
                            <input type="text"
                                   class="form-control form-control-lg"
                                   id="searchInput"
                                   placeholder="Entrez un nom, numéro de compte, numéro client ou téléphone..."
                                   autofocus>
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Tapez au moins 2 caractères pour commencer la recherche
                            </small>
                        </div>
                        <div class="col-md-2">
                            <label class="mb-2 form-label fw-bold">&nbsp;</label>
                            <button type="button" class="btn btn-primary btn-lg w-100" id="searchBtn">
                                <i class="fas fa-search me-2"></i>Rechercher
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loader -->
    <div id="searchLoader" class="py-5 text-center" style="display: none;">
        <div class="mb-3 spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Recherche...</span>
        </div>
        <h5 class="text-muted">Recherche en cours...</h5>
    </div>

    <!-- Résultats de recherche -->
    <div id="searchResults" style="display: none;">
        <div class="mb-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-list text-primary me-2"></i>
                Résultats de la recherche (<span id="resultCount">0</span>)
            </h5>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="clearSearch">
                <i class="fas fa-times me-1"></i>Effacer
            </button>
        </div>
        <div id="resultsContainer" class="row g-3"></div>
    </div>

    <!-- État initial (aucune recherche) -->
    <div id="emptyState" class="py-5 text-center">
        <div class="mb-4">
            <i class="opacity-25 fas fa-search fa-5x text-muted"></i>
        </div>
        <h4 class="mb-3 text-muted">Commencez par rechercher un compte</h4>
        <p class="text-muted">
            Utilisez la barre de recherche ci-dessus pour trouver le compte du client<br>
            sur lequel vous souhaitez effectuer un dépôt rapide.
        </p>
    </div>
</div>

<!-- Template pour les résultats de recherche -->
<template id="accountCardTemplate">
    <div class="col-md-6 col-lg-4">
        <div class="border-0 shadow-sm card h-100 account-card" data-account-id="">
            <div class="p-4 card-body">
                <!-- En-tête avec client -->
                <div class="mb-3 d-flex align-items-center">
                    <div class="avatar-circle bg-primary bg-opacity-10 text-primary me-3">
                        <span class="avatar-initials"></span>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-0 fw-bold client-name"></h6>
                        <small class="text-muted client-number"></small>
                    </div>
                </div>

                <!-- Numéro de compte -->
                <div class="mb-3">
                    <span class="badge bg-purple-soft text-purple account-number"></span>
                </div>

                <!-- Solde -->
                <div class="mb-3">
                    <small class="text-muted d-block">Solde actuel</small>
                    <h4 class="mb-0 text-primary fw-bold account-balance"></h4>
                </div>

                <!-- Progression cycle actif -->
                <div class="mb-3 cycle-info">
                    <div class="mb-1 d-flex justify-content-between align-items-center">
                        <small class="text-muted">Cycle <span class="cycle-number"></span></small>
                        <small class="fw-bold cycle-progress"></small>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-primary cycle-progress-bar"></div>
                    </div>
                    <div class="mt-1 d-flex justify-content-between">
                        <small class="text-success cycle-collected"></small>
                        <small class="text-muted cycle-target"></small>
                    </div>
                </div>

                <!-- Progression globale -->
                <div class="mb-3">
                    <div class="mb-1 d-flex justify-content-between align-items-center">
                        <small class="text-muted">Progression totale</small>
                        <small class="fw-bold total-progress"></small>
                    </div>
                    <div class="progress" style="height: 4px;">
                        <div class="progress-bar bg-gradient-primary total-progress-bar"></div>
                    </div>
                </div>

                <!-- Montant suggéré -->
                <div class="mb-3 border-0 alert alert-info suggested-amount-alert">
                    <small>
                        <i class="fas fa-lightbulb me-1"></i>
                        Montant suggéré : <strong class="suggested-amount"></strong> FCFA
                    </small>
                </div>

                <!-- Bouton d'action ou message de blocage -->
                <div class="action-container">
                    <button type="button" class="btn btn-success w-100 btn-lg deposit-btn">
                        <i class="fas fa-plus-circle me-2"></i>Effectuer un Dépôt
                    </button>
                    <div class="mb-0 border-0 alert alert-warning blocked-message" style="display: none;">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <small class="blocked-reason"></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<!-- Modal de dépôt rapide - Version robuste qui s'affiche même sans données -->
<div class="modal fade" id="quickDepositModal" tabindex="-1" aria-labelledby="quickDepositModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- En-tête du modal -->
            <div class="text-white modal-header bg-gradient-primary">
                <h5 class="modal-title" id="quickDepositModalLabel">
                    <i class="fas fa-bolt me-2"></i>
                    Dépôt Rapide
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <!-- Corps du modal -->
            <div class="p-4 modal-body">
                <!-- Informations du compte (TOUJOURS VISIBLE avec valeurs par défaut) -->
                <div class="mb-4 border alert alert-light">
                    <h6 class="mb-3 fw-bold text-primary">
                        <i class="fas fa-info-circle me-2"></i>
                        Informations du Compte
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="d-flex flex-column">
                                <small class="mb-1 text-muted">Client</small>
                                <strong id="modal_client_name" class="text-dark">Chargement...</strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex flex-column">
                                <small class="mb-1 text-muted">Numéro de Compte</small>
                                <span class="badge bg-purple-soft text-purple" id="modal_account_number" style="width: fit-content;">N/A</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex flex-column">
                                <small class="mb-1 text-muted">Solde Actuel</small>
                                <strong class="text-primary" id="modal_balance">0 FCFA</strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex flex-column">
                                <small class="mb-1 text-muted">Restant à Collecter</small>
                                <strong class="text-warning" id="modal_remaining">0 FCFA</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulaire de dépôt -->
                <form id="quickDepositForm" novalidate>
                    <!-- ID du compte (caché mais toujours présent) -->
                    <input type="hidden" id="modal_account_id" name="account_id" value="">

                    <!-- Montant du Dépôt -->
                    <div class="mb-4">
                        <label for="modal_amount" class="mb-2 form-label fw-bold">
                            <i class="fas fa-coins text-success me-2"></i>
                            Montant du Dépôt
                            <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-lg">
                            <input type="number"
                                   class="form-control"
                                   id="modal_amount"
                                   name="amount"
                                   min="100"
                                   step="100"
                                   placeholder="Entrez le montant (min. 100 FCFA)"
                                   required
                                   autocomplete="off">
                            <span class="bg-white input-group-text fw-bold">FCFA</span>
                        </div>
                        <div class="flex-wrap mt-2 d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Montant minimum : 100 FCFA
                            </small>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="applySuggestedAmount">
                                <i class="fas fa-magic me-1"></i>
                                Utiliser le montant suggéré : <span id="modal_suggested_amount">0</span> FCFA
                            </button>
                        </div>
                    </div>

                    <!-- Méthode de Paiement -->
                    <div class="mb-4">
                        <label class="mb-2 form-label fw-bold">
                            <i class="fas fa-credit-card text-primary me-2"></i>
                            Méthode de Paiement
                            <span class="text-danger">*</span>
                        </label>
                        <div class="row g-3">
                            <!-- Espèces -->
                            <div class="col-md-4 col-sm-6">
                                <div class="form-check payment-method-card h-100">
                                    <input class="form-check-input"
                                           type="radio"
                                           name="payment_method"
                                           id="modal_payment_cash"
                                           value="cash"
                                           checked
                                           required>
                                    <label class="form-check-label w-100 h-100 d-flex align-items-center justify-content-center" for="modal_payment_cash">
                                        <div class="p-3 text-center">
                                            <i class="mb-2 fas fa-money-bill-wave fa-2x text-success"></i>
                                            <div class="fw-bold">Espèces</div>
                                            <small class="text-muted">Paiement en liquide</small>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Mobile Money -->
                            <div class="col-md-4 col-sm-6">
                                <div class="form-check payment-method-card h-100">
                                    <input class="form-check-input"
                                           type="radio"
                                           name="payment_method"
                                           id="modal_payment_mobile"
                                           value="mobile_money">
                                    <label class="form-check-label w-100 h-100 d-flex align-items-center justify-content-center" for="modal_payment_mobile">
                                        <div class="p-3 text-center">
                                            <i class="mb-2 fas fa-mobile-alt fa-2x text-primary"></i>
                                            <div class="fw-bold">Mobile Money</div>
                                            <small class="text-muted">TMoney / Flooz</small>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Virement Bancaire -->
                            <div class="col-md-4 col-sm-12">
                                <div class="form-check payment-method-card h-100">
                                    <input class="form-check-input"
                                           type="radio"
                                           name="payment_method"
                                           id="modal_payment_bank"
                                           value="bank_transfer">
                                    <label class="form-check-label w-100 h-100 d-flex align-items-center justify-content-center" for="modal_payment_bank">
                                        <div class="p-3 text-center">
                                            <i class="mb-2 fas fa-university fa-2x text-info"></i>
                                            <div class="fw-bold">Virement</div>
                                            <small class="text-muted">Transfert bancaire</small>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Opérateur Mobile Money (conditionnelle) -->
                    <div class="mb-4" id="modal_mobile_money_section" style="display: none;">
                        <label class="mb-2 form-label fw-bold">
                            <i class="fas fa-sim-card text-primary me-2"></i>
                            Opérateur Mobile Money
                            <span class="text-danger">*</span>
                        </label>
                        <div class="row g-3">
                            <!-- TMoney -->
                            <div class="col-md-6">
                                <div class="form-check operator-card">
                                    <input class="form-check-input"
                                           type="radio"
                                           name="mobile_money_operator"
                                           id="modal_operator_tmoney"
                                           value="tmoney">
                                    <label class="form-check-label w-100" for="modal_operator_tmoney">
                                        <div class="p-3 d-flex align-items-center">
                                            <i class="fas fa-mobile-alt fa-2x text-warning me-3"></i>
                                            <div>
                                                <div class="mb-1 fw-bold">TMoney</div>
                                                <small class="text-muted">Togocom</small>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Flooz -->
                            <div class="col-md-6">
                                <div class="form-check operator-card">
                                    <input class="form-check-input"
                                           type="radio"
                                           name="mobile_money_operator"
                                           id="modal_operator_flooz"
                                           value="flooz">
                                    <label class="form-check-label w-100" for="modal_operator_flooz">
                                        <div class="p-3 d-flex align-items-center">
                                            <i class="fas fa-mobile-alt fa-2x text-danger me-3"></i>
                                            <div>
                                                <div class="mb-1 fw-bold">Flooz</div>
                                                <small class="text-muted">Moov Africa</small>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Référence de Paiement -->
                    <div class="mb-4">
                        <label for="modal_payment_reference" class="mb-2 form-label fw-bold">
                            <i class="fas fa-hashtag text-primary me-2"></i>
                            Référence de Paiement
                            <small class="text-muted fw-normal">(optionnel)</small>
                        </label>
                        <input type="text"
                               class="form-control"
                               id="modal_payment_reference"
                               name="payment_reference"
                               placeholder="Ex: REF-123456, TRANS-789"
                               maxlength="100"
                               autocomplete="off">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Numéro de transaction ou référence du paiement
                        </small>
                    </div>

                    <!-- Description / Notes -->
                    <div class="mb-3">
                        <label for="modal_description" class="mb-2 form-label fw-bold">
                            <i class="fas fa-comment text-primary me-2"></i>
                            Description / Notes
                            <small class="text-muted fw-normal">(optionnel)</small>
                        </label>
                        <textarea class="form-control"
                                  id="modal_description"
                                  name="description"
                                  rows="3"
                                  placeholder="Ajoutez une note ou un commentaire sur ce dépôt..."
                                  maxlength="500"
                                  autocomplete="off"></textarea>
                        <div class="mt-1 d-flex justify-content-between">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Informations complémentaires sur la transaction
                            </small>
                            <small class="text-muted" id="modal_charCount">0 / 500</small>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Pied du modal -->
            <div class="p-3 modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>
                    Annuler
                </button>
                <button type="submit" form="quickDepositForm" class="btn btn-success btn-lg" id="submitDepositBtn">
                    <i class="fas fa-check-circle me-2"></i>
                    Valider le Dépôt
                </button>
            </div>
        </div>
    </div>
</div>
<style>
    .avatar-circle {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1.1rem;
    }

    .account-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        cursor: pointer;
    }

    .account-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
    }

    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .bg-purple-soft {
        background-color: #f3e8ff;
    }

    .text-purple {
        color: #9333ea;
    }

    .payment-method-card,
    .operator-card {
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .payment-method-card:hover,
    .operator-card:hover {
        border-color: #667eea;
        box-shadow: 0 4px 6px rgba(102, 126, 234, 0.1);
    }

    .payment-method-card .form-check-input:checked ~ .form-check-label,
    .operator-card .form-check-input:checked ~ .form-check-label {
        border-color: #667eea;
        background-color: #f0f4ff;
    }

    .payment-method-card .form-check-input,
    .operator-card .form-check-input {
        position: absolute;
        opacity: 0;
    }

    .progress {
        border-radius: 10px;
        overflow: hidden;
    }


    /* Gradient personnalisé */
    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    /* Badge personnalisé violet */
    .bg-purple-soft {
        background-color: #f3e8ff;
        padding: 0.35rem 0.65rem;
        font-size: 0.875rem;
    }

    .text-purple {
        color: #9333ea;
    }

    /* Cards pour les méthodes de paiement */
    .payment-method-card,
    .operator-card {
        position: relative;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        transition: all 0.3s ease;
        cursor: pointer;
        background-color: #ffffff;
        overflow: hidden;
    }

    .payment-method-card:hover,
    .operator-card:hover {
        border-color: #667eea;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        transform: translateY(-2px);
    }

    /* État sélectionné */
    .payment-method-card .form-check-input:checked ~ .form-check-label,
    .operator-card .form-check-input:checked ~ .form-check-label {
        border-color: #667eea;
        background-color: #f0f4ff;
    }

    .payment-method-card .form-check-input:checked ~ .form-check-label::before,
    .operator-card .form-check-input:checked ~ .form-check-label::before {
        content: "✓";
        position: absolute;
        top: 8px;
        right: 8px;
        background-color: #667eea;
        color: white;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 14px;
    }

    /* Cacher les inputs radio natifs */
    .payment-method-card .form-check-input,
    .operator-card .form-check-input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    /* Labels occupent tout l'espace */
    .payment-method-card .form-check-label,
    .operator-card .form-check-label {
        cursor: pointer;
        margin: 0;
        position: relative;
    }

    /* Amélioration des champs de formulaire */
    .form-control:focus,
    .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.15);
    }

    /* Input groupe */
    .input-group-lg .input-group-text {
        font-weight: 600;
        color: #667eea;
        border-left: none;
    }

    .input-group-lg .form-control {
        border-right: none;
    }

    .input-group-lg .form-control:focus {
        border-right: none;
    }

    /* Boutons personnalisés */
    .btn-success {
        background-color: #10b981;
        border-color: #10b981;
    }

    .btn-success:hover {
        background-color: #059669;
        border-color: #059669;
    }

    /* Alert personnalisé */
    .alert-light {
        background-color: #f9fafb;
        border-color: #e5e7eb;
    }

    /* Animation pour le modal */
    .modal.fade .modal-dialog {
        transition: transform 0.3s ease-out;
    }

    .modal.show .modal-dialog {
        transform: none;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .modal-dialog {
            margin: 0.5rem;
        }

        .payment-method-card .p-3,
        .operator-card .p-3 {
            padding: 1rem !important;
        }

        .payment-method-card .fa-2x,
        .operator-card .fa-2x {
            font-size: 1.5rem !important;
        }
    }

    /* Amélioration de l'accessibilité */
    .form-control:focus,
    .form-select:focus,
    .form-check-input:focus {
        outline: 2px solid #667eea;
        outline-offset: 2px;
    }

    /* Spinner pour le bouton de soumission */
    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .fa-spin {
        animation: spin 1s linear infinite;
    }

</style>

@push('scripts')
<script>
// === SCRIPT DÉPÔT RAPIDE - VERSION CORRIGÉE ===
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Initialisation du script Dépôt Rapide...');

    // === RÉCUPÉRATION DES ÉLÉMENTS DOM ===
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const searchLoader = document.getElementById('searchLoader');
    const searchResults = document.getElementById('searchResults');
    const emptyState = document.getElementById('emptyState');
    const resultsContainer = document.getElementById('resultsContainer');
    const resultCount = document.getElementById('resultCount');
    const clearSearchBtn = document.getElementById('clearSearch');
    const alertContainer = document.getElementById('alertContainer');

    // Éléments du modal
    const quickDepositModalElement = document.getElementById('quickDepositModal');
    const quickDepositForm = document.getElementById('quickDepositForm');

    // Vérification critique des éléments
    if (!quickDepositModalElement) {
        console.error('❌ ERREUR CRITIQUE: Modal quickDepositModal introuvable!');
        return;
    }

    if (!quickDepositForm) {
        console.error('❌ ERREUR CRITIQUE: Formulaire quickDepositForm introuvable!');
        return;
    }

    const quickDepositModal = new bootstrap.Modal(quickDepositModalElement);

    // Variables globales
    let selectedAccount = null;
    let searchTimeout = null;

    // === FONCTION AMÉLIORÉE POUR RÉCUPÉRER LES ÉLÉMENTS DU MODAL ===
    function getModalElements() {
        // Attendre que le modal soit dans le DOM
        const selectors = {
            accountId: 'modal_account_id',
            clientName: 'modal_client_name',
            accountNumber: 'modal_account_number',
            balance: 'modal_balance',
            remaining: 'modal_remaining',
            suggestedAmount: 'modal_suggested_amount',
            amount: 'modal_amount'
        };

        const elements = {};
        const missing = [];

        // Utiliser getElementById directement (plus fiable que querySelector)
        for (const [key, id] of Object.entries(selectors)) {
            const element = document.getElementById(id);
            elements[key] = element;

            if (!element) {
                missing.push(id);
                console.warn(`⚠️  Élément manquant: ${id}`);
            } else {
                console.log(`✅ Élément trouvé: ${id}`);
            }
        }

        return {
            elements,
            missing,
            allFound: missing.length === 0
        };
    }

    // === REMPLISSAGE SÉCURISÉ DES DONNÉES DU MODAL ===
    function fillModalData(elements, account) {
        console.log('📝 Remplissage des données du modal...');

        const data = {
            id: account.id || '',
            clientName: account.client?.name || 'Client Inconnu',
            accountNumber: account.account_number || 'N/A',
            balance: account.balance || 0,
            totalRemaining: account.tontine?.total_remaining || 0,
            suggestedAmount: account.tontine?.suggested_amount || 0
        };

        // Remplissage sécurisé avec vérification
        const updates = [
            { el: elements.accountId, prop: 'value', val: data.id },
            { el: elements.clientName, prop: 'textContent', val: data.clientName },
            { el: elements.accountNumber, prop: 'textContent', val: data.accountNumber },
            { el: elements.balance, prop: 'textContent', val: formatMoney(data.balance) + ' FCFA' },
            { el: elements.remaining, prop: 'textContent', val: formatMoney(data.totalRemaining) + ' FCFA' },
            { el: elements.suggestedAmount, prop: 'textContent', val: formatMoney(data.suggestedAmount) },
            { el: elements.amount, prop: 'value', val: data.suggestedAmount }
        ];

        let successCount = 0;
        updates.forEach(({ el, prop, val }) => {
            if (el) {
                try {
                    el[prop] = val;
                    successCount++;
                } catch (error) {
                    console.error(`❌ Erreur lors de la mise à jour:`, error);
                }
            }
        });

        console.log(`✅ ${successCount}/${updates.length} éléments mis à jour`);
        return successCount === updates.length;
    }

    // === OUVERTURE DU MODAL AVEC RETRY ===
    function openDepositModal(account) {
        console.log('📂 Ouverture du modal pour:', account.client?.name);

        selectedAccount = account;

        // Fonction pour tenter d'ouvrir le modal
        function tryOpenModal(retryCount = 0) {
            const maxRetries = 3;

            // Récupérer les éléments
            const modalElements = getModalElements();

            if (modalElements.allFound) {
                // Tous les éléments sont présents
                console.log('✅ Tous les éléments du modal trouvés');

                // Remplir les données
                const fillSuccess = fillModalData(modalElements.elements, account);

                if (fillSuccess) {
                    // Ouvrir le modal
                    try {
                        quickDepositModal.show();
                        console.log('✅ Modal ouvert avec succès');
                    } catch (error) {
                        console.error('❌ Erreur ouverture modal:', error);
                        showAlert('Erreur lors de l\'ouverture du modal', 'danger');
                    }
                } else {
                    console.error('❌ Erreur lors du remplissage des données');
                    showAlert('Erreur lors du remplissage des données', 'danger');
                }
            } else {
                // Des éléments sont manquants
                console.warn(`⚠️  Éléments manquants (tentative ${retryCount + 1}/${maxRetries}):`, modalElements.missing);

                if (retryCount < maxRetries) {
                    // Réessayer après un délai
                    setTimeout(() => tryOpenModal(retryCount + 1), 100 * (retryCount + 1));
                } else {
                    // Échec après plusieurs tentatives
                    console.error('❌ Impossible de trouver tous les éléments du modal après', maxRetries, 'tentatives');
                    showAlert('Erreur technique: Impossible d\'ouvrir le modal. Veuillez recharger la page.', 'danger');

                    // Ouvrir quand même le modal pour diagnostic
                    quickDepositModal.show();
                }
            }
        }

        // Lancer la tentative d'ouverture
        tryOpenModal();
    }

    // === ÉVÉNEMENTS DE RECHERCHE ===

    // Recherche automatique après saisie
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            if (query.length >= 2) {
                searchTimeout = setTimeout(() => performSearch(query), 500);
            } else {
                showEmptyState();
            }
        });

        // Recherche avec Enter
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const query = this.value.trim();
                if (query.length >= 2) {
                    performSearch(query);
                } else {
                    showAlert('Veuillez entrer au moins 2 caractères', 'warning');
                }
            }
        });

        // Focus automatique
        searchInput.focus();
    }

    // Recherche au clic
    if (searchBtn) {
        searchBtn.addEventListener('click', function() {
            const query = searchInput.value.trim();
            if (query.length >= 2) {
                performSearch(query);
            } else {
                showAlert('Veuillez entrer au moins 2 caractères', 'warning');
            }
        });
    }

    // Effacer la recherche
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function() {
            searchInput.value = '';
            showEmptyState();
            searchInput.focus();
        });
    }

    // === FONCTION DE RECHERCHE ===
    async function performSearch(query) {
        try {
            showLoader();

            const response = await fetch(`./quick-deposit-search?query=${encodeURIComponent(query)}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                displayResults(data.data);
            } else {
                showAlert(data.message || 'Erreur lors de la recherche', 'danger');
                showEmptyState();
            }
        } catch (error) {
            console.error('❌ Erreur de recherche:', error);
            showAlert('Erreur de connexion au serveur', 'danger');
            showEmptyState();
        }
    }

    // === AFFICHAGE DES RÉSULTATS ===
    function displayResults(accounts) {
        if (!accounts || accounts.length === 0) {
            showAlert('Aucun compte trouvé', 'info');
            showEmptyState();
            return;
        }

        resultsContainer.innerHTML = '';
        if (resultCount) resultCount.textContent = accounts.length;

        accounts.forEach(account => {
            const card = createAccountCard(account);
            resultsContainer.appendChild(card);
        });

        hideLoader();
        if (emptyState) emptyState.style.display = 'none';
        if (searchResults) searchResults.style.display = 'block';
    }

    // === CRÉATION DES CARTES DE COMPTE ===
    function createAccountCard(account) {
        const template = document.getElementById('accountCardTemplate');
        if (!template) {
            console.error('❌ Template accountCardTemplate introuvable');
            return document.createElement('div');
        }

        const card = template.content.cloneNode(true);
        const cardElement = card.querySelector('.account-card');

        if (!cardElement) return card;

        // Stocker les données
        cardElement.dataset.accountId = account.id;
        cardElement.dataset.account = JSON.stringify(account);

        // Initiales
        const clientName = account.client?.name || 'Client';
        const initials = clientName.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);

        setElementText(card, '.avatar-initials', initials);
        setElementText(card, '.client-name', clientName);
        setElementText(card, '.client-number', account.client?.client_number || 'N/A');
        setElementText(card, '.account-number', account.account_number || 'N/A');
        setElementText(card, '.account-balance', formatMoney(account.balance || 0) + ' FCFA');

        // Cycle actif
        const cycleInfo = card.querySelector('.cycle-info');
        if (account.tontine?.active_cycle && cycleInfo) {
            const cycle = account.tontine.active_cycle;
            setElementText(card, '.cycle-number', '#' + (cycle.cycle_number || 1));
            setElementText(card, '.cycle-progress', (cycle.progress_percent || 0) + '%');
            setElementText(card, '.cycle-collected', formatMoney(cycle.collected_amount || 0) + ' FCFA');
            setElementText(card, '.cycle-target', formatMoney(cycle.target_amount || 0) + ' FCFA');

            const progressBar = card.querySelector('.cycle-progress-bar');
            if (progressBar) progressBar.style.width = (cycle.progress_percent || 0) + '%';
        } else if (cycleInfo) {
            cycleInfo.style.display = 'none';
        }

        // Progression totale
        setElementText(card, '.total-progress', (account.tontine?.total_progress || 0) + '%');
        const totalProgressBar = card.querySelector('.total-progress-bar');
        if (totalProgressBar) {
            totalProgressBar.style.width = (account.tontine?.total_progress || 0) + '%';
        }

        // Montant suggéré
        setElementText(card, '.suggested-amount', formatMoney(account.tontine?.suggested_amount || 0));

        // Gestion du bouton de dépôt
        const depositBtn = card.querySelector('.deposit-btn');
        const blockedMessage = card.querySelector('.blocked-message');
        const suggestedAmountAlert = card.querySelector('.suggested-amount-alert');

        if (!account.can_deposit) {
            if (depositBtn) depositBtn.style.display = 'none';
            if (blockedMessage) {
                blockedMessage.style.display = 'block';
                setElementText(card, '.blocked-reason', account.deposit_blocked_reason || 'Dépôt non autorisé');
            }
            if (suggestedAmountAlert) suggestedAmountAlert.style.display = 'none';
        } else if (depositBtn) {
            depositBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                openDepositModal(account);
            });
        }

        return card;
    }

    // === ÉVÉNEMENTS DU MODAL ===

    // Appliquer le montant suggéré
    const applySuggestedBtn = document.getElementById('applySuggestedAmount');
    if (applySuggestedBtn) {
        applySuggestedBtn.addEventListener('click', function() {
            if (selectedAccount) {
                const suggested = selectedAccount.tontine?.suggested_amount || 0;
                const amountInput = document.getElementById('modal_amount');
                if (amountInput) {
                    amountInput.value = suggested;
                    amountInput.focus();
                }
            }
        });
    }

    // Gestion Mobile Money
    const paymentMethodInputs = document.querySelectorAll('input[name="payment_method"]');
    const mobileMoneySection = document.getElementById('modal_mobile_money_section');

    paymentMethodInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (mobileMoneySection) {
                mobileMoneySection.style.display = this.value === 'mobile_money' ? 'block' : 'none';
                if (this.value !== 'mobile_money') {
                    document.querySelectorAll('input[name="mobile_money_operator"]')
                        .forEach(op => op.checked = false);
                }
            }
        });
    });

    // Compteur de caractères
    const modalDescription = document.getElementById('modal_description');
    const modalCharCount = document.getElementById('modal_charCount');

    if (modalDescription && modalCharCount) {
        modalDescription.addEventListener('input', function() {
            modalCharCount.textContent = this.value.length + ' / 500';
        });
    }

    // === SOUMISSION DU FORMULAIRE ===
    if (quickDepositForm) {
        quickDepositForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            if (!selectedAccount) {
                showAlert('Aucun compte sélectionné', 'warning');
                return;
            }

            const formData = new FormData(this);
            const amount = parseFloat(formData.get('amount'));
            const paymentMethod = formData.get('payment_method');

            // Validations
            if (!amount || isNaN(amount) || amount < 100) {
                showAlert('Le montant minimum est de 100 FCFA', 'warning');
                return;
            }

            const totalRemaining = selectedAccount.tontine?.total_remaining || 0;
            if (amount > totalRemaining) {
                const confirmed = confirm(
                    `Le montant dépasse le restant (${formatMoney(totalRemaining)} FCFA). ` +
                    `Il sera ajusté automatiquement. Continuer ?`
                );
                if (!confirmed) return;
            }

            if (paymentMethod === 'mobile_money' && !formData.get('mobile_money_operator')) {
                showAlert('Veuillez sélectionner un opérateur Mobile Money', 'warning');
                return;
            }

            if (!confirm(`Confirmer le dépôt de ${formatMoney(amount)} FCFA ?`)) {
                return;
            }

            // Soumission
            const submitBtn = document.getElementById('submitDepositBtn');
            try {
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Traitement...';
                }

                const response = await fetch(`/agent/accounts/${selectedAccount.id}/quick-deposit`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(Object.fromEntries(formData))
                });

                const data = await response.json();

                if (data.success) {
                    quickDepositModal.hide();
                    showAlert(data.message, 'success');

                    // Réactualiser la recherche
                    if (searchInput && searchInput.value.trim()) {
                        performSearch(searchInput.value.trim());
                    }

                    // Réinitialiser le formulaire
                    quickDepositForm.reset();
                } else {
                    showAlert(data.message || 'Erreur lors du dépôt', 'danger');
                }
            } catch (error) {
                console.error('❌ Erreur soumission:', error);
                showAlert('Erreur de connexion au serveur', 'danger');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Valider le Dépôt';
                }
            }
        });
    }

    // === ÉVÉNEMENT FERMETURE MODAL ===
    if (quickDepositModalElement) {
        quickDepositModalElement.addEventListener('hidden.bs.modal', function() {
            if (quickDepositForm) quickDepositForm.reset();
            if (mobileMoneySection) mobileMoneySection.style.display = 'none';
            if (modalCharCount) modalCharCount.textContent = '0 / 500';
            selectedAccount = null;
        });
    }

    // === FONCTIONS UTILITAIRES ===

    function setElementText(parent, selector, text) {
        const element = parent.querySelector(selector);
        if (element) element.textContent = text;
    }

    function showLoader() {
        if (searchLoader) searchLoader.style.display = 'block';
        if (searchResults) searchResults.style.display = 'none';
        if (emptyState) emptyState.style.display = 'none';
    }

    function hideLoader() {
        if (searchLoader) searchLoader.style.display = 'none';
    }

    function showEmptyState() {
        if (searchLoader) searchLoader.style.display = 'none';
        if (searchResults) searchResults.style.display = 'none';
        if (emptyState) emptyState.style.display = 'block';
    }

    function formatMoney(amount) {
        const numAmount = parseFloat(amount) || 0;
        return new Intl.NumberFormat('fr-FR', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(numAmount);
    }

    function showAlert(message, type = 'info') {
        if (!alertContainer) return;

        const icons = {
            success: 'fa-check-circle',
            danger: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        const titles = {
            success: 'Succès !',
            danger: 'Erreur !',
            warning: 'Attention !',
            info: 'Info'
        };

        const alertHTML = `
            <div class="alert alert-${type} alert-dismissible fade show shadow-sm" role="alert">
                <i class="fas ${icons[type]} me-2"></i>
                <strong>${titles[type]}</strong> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        alertContainer.innerHTML = alertHTML;
        window.scrollTo({ top: 0, behavior: 'smooth' });

        setTimeout(() => {
            const alert = alertContainer.querySelector('.alert');
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    }

    console.log('✅ Script Dépôt Rapide initialisé avec succès');
});
</script>
@endpush

@endsection
