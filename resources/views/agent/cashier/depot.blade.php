@extends('layouts.cashier')

@section('title', 'Encaissement - Dépôts')
@section('page-title', 'Encaissement')
@section('page-subtitle', 'Recherche et dépôt sur compte')

@push('styles')
<style>
    .search-container {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .search-input-group {
        position: relative;
        display: flex;
        align-items: center;
    }

    .search-input-group input {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        color: #0f172a;
        border-radius: 12px;
        padding: 14px 48px 14px 44px;
        font-size: 1rem;
        width: 100%;
        transition: all 0.2s;
        font-weight: 500;
    }

    .search-input-group input:focus {
        background: #ffffff;
        border-color: #0d9488;
        box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.12);
        outline: none;
    }

    .search-input-group .search-icon {
        position: absolute;
        left: 16px;
        color: #0d9488;
        font-size: 1.1rem;
    }

    .search-input-group .clear-btn {
        position: absolute;
        right: 15px;
        background: none;
        border: none;
        color: #94a3b8;
        font-size: 1rem;
        cursor: pointer;
        display: none;
        padding: 5px;
        transition: color 0.2s;
    }

    .search-input-group .clear-btn:hover {
        color: #ef4444;
    }

    /* Raccourcis de recherche */
    .search-shortcuts {
        display: flex;
        gap: 8px;
        margin-top: 12px;
        flex-wrap: wrap;
    }

    .shortcut-btn {
        background: #f0fdfa;
        border: 1px solid #ccfbf1;
        color: #0f766e;
        border-radius: 20px;
        padding: 5px 14px;
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .shortcut-btn:hover {
        background: #0d9488;
        color: #ffffff;
        border-color: #0d9488;
    }

    .shortcut-btn i {
        font-size: 0.7rem;
    }

    .keyboard-hint {
        color: #94a3b8;
        font-size: 0.75rem;
        margin-top: 10px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .keyboard-hint kbd {
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.7rem;
        color: #475569;
        font-weight: 600;
    }

    /* Résultats récents */
    .recent-section {
        margin-top: 16px;
        display: none;
    }

    .recent-header {
        color: #64748b;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 700;
        margin-bottom: 8px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .recent-header button {
        background: none;
        border: none;
        color: #94a3b8;
        font-size: 0.72rem;
        cursor: pointer;
    }

    .recent-header button:hover {
        color: #ef4444;
    }

    .recent-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 6px 12px;
        margin: 0 6px 6px 0;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 0.8rem;
        color: #334155;
    }

    .recent-item:hover {
        background: #f0fdfa;
        border-color: #0d9488;
        color: #0f766e;
    }

    .recent-item .recent-name {
        font-weight: 600;
    }

    .recent-item .recent-num {
        color: #64748b;
        font-size: 0.72rem;
    }

    .results-container {
        display: none;
        margin-top: 20px;
    }

    .client-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.15s ease;
        cursor: pointer;
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    }

    .client-card:hover {
        border-color: #0d9488;
        background: #f0fdfa;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(13, 148, 136, 0.08);
    }

    .client-card.selected {
        border-color: #0d9488;
        background: #f0fdfa;
        box-shadow: 0 0 0 2px rgba(13, 148, 136, 0.2);
    }

    .client-info h6 { color: #0f172a; margin-bottom: 3px; font-weight: 700; }
    .client-info span { color: #64748b; font-size: 0.82rem; }

    .account-badge {
        font-size: 0.7rem;
        padding: 3px 8px;
        border-radius: 6px;
        margin-left: 8px;
        text-transform: uppercase;
        font-weight: 700;
    }

    .badge-savings { background: #ecfeff; color: #0e7490; border: 1px solid #a5f3fc; }
    .badge-tontine { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .badge-loan { background: #f3e8ff; color: #7e22ce; border: 1px solid #e9d5ff; }

    .fast-indicator {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 0.7rem;
        color: #059669;
        font-weight: 600;
        margin-left: 8px;
    }

    .fast-indicator i {
        font-size: 0.65rem;
    }

    /* Compteur de résultats */
    .result-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }

    .result-meta .result-time {
        font-size: 0.72rem;
        color: #94a3b8;
    }

    .deposit-modal-content {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        color: #0f172a;
    }

    .form-label { color: #334155; font-size: 0.85rem; font-weight: 600; }

    .form-control, .form-select {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        color: #0f172a;
        border-radius: 8px;
        padding: 10px 12px;
    }

    .form-control:focus, .form-select:focus {
        background: #ffffff;
        color: #0f172a;
        border-color: #0d9488;
        box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
    }

    .amount-input {
        font-size: 1.6rem;
        font-weight: 800;
        color: #0d9488;
        text-align: center;
        background: #f0fdfa;
        border-color: #99f6e4;
    }

    .btn-deposit {
        background: linear-gradient(135deg, #0d9488, #059669);
        border: none;
        color: white;
        font-weight: 600;
        padding: 10px 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(13, 148, 136, 0.2);
    }
    .btn-deposit:hover {
        background: linear-gradient(135deg, #0f766e, #047857);
        color: white;
    }

    .info-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 20px;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        font-size: 0.85rem;
    }

    .info-item span:first-child { color: #64748b; font-weight: 500; }
    .info-item span:last-child { color: #0f172a; font-weight: 700; }

    /* Quick actions dans les résultats */
    .quick-action-btn {
        background: linear-gradient(135deg, #0d9488, #059669);
        border: none;
        color: white;
        border-radius: 8px;
        padding: 7px 16px;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
    }

    .quick-action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(13, 148, 136, 0.25);
    }

    /* Loading skeleton */
    .skeleton-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 10px;
        animation: skeleton-pulse 1.5s infinite;
    }

    .skeleton-line {
        background: #e2e8f0;
        border-radius: 4px;
        height: 14px;
        margin-bottom: 8px;
    }

    .skeleton-line.w-60 { width: 60%; }
    .skeleton-line.w-40 { width: 40%; }
    .skeleton-line.w-30 { width: 30%; height: 20px; }

    @keyframes skeleton-pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.4; }
    }
</style>
@endpush

@section('content')

<div class="search-container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="text-slate-900 fw-bold mb-0">
            <i class="fas fa-search-dollar me-2 text-emerald-600"></i>Rechercher un compte
        </h5>
        <div class="keyboard-hint d-none d-md-flex">
            <kbd>/</kbd> pour rechercher &bull; <kbd>Échap</kbd> pour effacer
        </div>
    </div>

    <div class="search-input-group">
        <i class="fas fa-search search-icon"></i>
        <input type="text" id="accountSearch"
               placeholder="Tapez un nom, téléphone, N° compte (ACC-...) ou N° prêt..."
               autocomplete="off"
               autofocus>
        <button class="clear-btn" id="clearSearch" title="Effacer">
            <i class="fas fa-times-circle"></i>
        </button>
    </div>

    <!-- Raccourcis de recherche -->
    <div class="search-shortcuts">
        <button class="shortcut-btn" onclick="quickSearch('ACC-')">
            <i class="fas fa-coins"></i> Tontine
        </button>
        <button class="shortcut-btn" onclick="quickSearch('SAV-')">
            <i class="fas fa-piggy-bank"></i> Épargne
        </button>
        <button class="shortcut-btn" onclick="quickSearch('LOAN-')">
            <i class="fas fa-hand-holding-usd"></i> Prêts
        </button>
    </div>

    <!-- Recherches récentes -->
    <div id="recentSection" class="recent-section">
        <div class="recent-header">
            <span><i class="fas fa-clock me-1 text-slate-400"></i> Récemment utilisés</span>
            <button onclick="clearRecentSearches()">Effacer</button>
        </div>
        <div id="recentList"></div>
    </div>

    <!-- Results -->
    <div id="searchResults" class="results-container">
        <div class="result-meta">
            <div class="d-flex align-items-center gap-2">
                <h6 class="text-slate-700 fw-bold mb-0">Résultats</h6>
                <span id="resultCount" class="badge" style="background: #e2e8f0; color: #1e293b; font-size: 0.75rem; font-weight: 700; border: 1px solid #cbd5e1;">0</span>
                <span id="fastModeIndicator" class="fast-indicator" style="display:none;">
                    <i class="fas fa-bolt"></i> Recherche rapide
                </span>
            </div>
            <span id="searchTime" class="result-time"></span>
        </div>
        <div id="resultsList">
            <!-- Dynamic results here -->
        </div>
    </div>

    <!-- Empty and Loading states -->
    <div id="searchPlaceholder" class="mt-4 text-center py-4 text-slate-400">
        <i class="fas fa-search-dollar fa-3x mb-3 d-block text-slate-300"></i>
        <p class="mb-0 fw-medium">Commencez à saisir pour rechercher un client ou compte...</p>
    </div>

    <div id="searchLoading" class="mt-4" style="display:none;">
        <div class="skeleton-card">
            <div class="skeleton-line w-60"></div>
            <div class="skeleton-line w-40"></div>
        </div>
        <div class="skeleton-card">
            <div class="skeleton-line w-60"></div>
            <div class="skeleton-line w-40"></div>
        </div>
        <div class="skeleton-card">
            <div class="skeleton-line w-30"></div>
            <div class="skeleton-line w-40"></div>
        </div>
    </div>
</div>

<!-- Modal Dépôt -->
<div class="modal fade" id="depositModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content deposit-modal-content">
            <div class="modal-header border-bottom border-slate-100 px-4 py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <h5 class="modal-title font-bold text-slate-900 mb-0" id="depositModalTitle">Effectuer un Dépôt</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="depositForm" method="POST">
                @csrf
                <input type="hidden" name="type" id="depositAccountType" value="">
                <input type="hidden" name="source" value="depot">
                <div class="modal-body p-4">
                    <div class="info-box">
                        <div class="info-item">
                            <span>Client</span>
                            <span id="modalClientName" class="text-slate-900">-</span>
                        </div>
                        <div class="info-item">
                            <span id="modalAccountLabel">Compte</span>
                            <span id="modalAccountNumber" class="font-mono text-slate-800">-</span>
                        </div>
                        <div id="tontineInfo" style="display:none;">
                            <div class="info-item">
                                <span>Mise Tontine</span>
                                <span id="modalTontineAmount" class="text-amber-600 font-bold">-</span>
                            </div>
                        </div>
                        <div class="info-item border-top border-slate-200 pt-2 mt-2">
                            <span id="modalBalanceLabel">Solde actuel</span>
                            <span id="modalCurrentBalance" class="text-emerald-700 font-bold">-</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" id="modalAmountLabel">Montant du versement (FCFA)</label>
                        <input type="number" name="amount" id="depositAmount" class="form-control amount-input" placeholder="0" required min="100">
                    </div>

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Méthode de paiement</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="cash">💰 Espèces (Cash)</option>
                                <option value="mobile_money">📱 Mobile Money</option>
                                <option value="bank_transfer">🏛️ Virement Bancaire</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Commentaire (Optionnel)</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Ex: Versement mois de Mars..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-slate-100 px-4 py-3 bg-slate-50">
                    <button type="button" class="btn btn-outline-secondary rounded-lg" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-deposit px-4" id="btnConfirmDeposit">Confirmer le Dépôt</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function formatNumber(num) {
    if (num === null || num === undefined || num === '') return '0';
    const val = parseFloat(num);
    if (isNaN(val)) return '0';
    return val.toLocaleString('fr-FR', { maximumFractionDigits: 0 });
}

$(document).ready(function() {
    let searchTimer;
    let lastQuery = '';
    let searchCache = {};
    let selectedIndex = -1;

    const DEBOUNCE_FAST = 100;    // Recherche par numéro
    const DEBOUNCE_NORMAL = 200;  // Recherche par nom
    const CACHE_DURATION = 30000; // Cache 30 secondes
    const RECENT_KEY = 'mie_depot_recent';

    // Charger les recherches récentes
    loadRecentSearches();

    const urlParams = new URLSearchParams(window.location.search);
    const initialQuery = urlParams.get('search') || urlParams.get('q');
    if (initialQuery) {
        $('#accountSearch').val(initialQuery).trigger('input');
    }

    // ⚡ Raccourci clavier "/" pour focus
    $(document).on('keydown', function(e) {
        if (e.key === '/' && !$('#accountSearch').is(':focus') && !$('.modal').hasClass('show')) {
            e.preventDefault();
            $('#accountSearch').focus();
        }
    });

    // Raccourci clavier et navigation dans les résultats
    $('#accountSearch').on('keydown', function(e) {
        if (e.key === 'Escape') {
            clearSearchField();
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            navigateResults(1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            navigateResults(-1);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(searchTimer);
            if (selectedIndex >= 0) {
                $('.client-card').eq(selectedIndex).click();
            } else if ($('.client-card').length > 0) {
                $('.client-card').first().click();
            } else {
                executeSearch($(this).val().trim());
            }
        }
    });

    // Bouton effacer
    $('#clearSearch').on('click', clearSearchField);

    function clearSearchField() {
        $('#accountSearch').val('').focus();
        $('#clearSearch').hide();
        $('#searchResults').hide();
        $('#searchPlaceholder').show();
        lastQuery = '';
        selectedIndex = -1;
        loadRecentSearches();
    }

    // Exécution directe de la recherche
    function executeSearch(query) {
        if (!query || query.length < 1) {
            $('#searchResults').hide();
            $('#searchPlaceholder').show();
            $('#recentSection').show();
            lastQuery = '';
            return;
        }

        $('#recentSection').hide();
        $('#searchPlaceholder').hide();
        $('#searchLoading').show();

        const startTime = performance.now();
        lastQuery = query;

        $.ajax({
            url: "{{ route('caissier.depot.search') }}",
            method: "GET",
            data: { query: query },
            success: function(response) {
                const elapsed = Math.round(performance.now() - startTime);
                renderResults(response.data, response.fast_mode, elapsed);
            },
            error: function() {
                $('#searchLoading').hide();
                $('#searchPlaceholder').html('<i class="fas fa-exclamation-triangle fa-3x mb-3 d-block text-warning opacity-50"></i><p>Erreur de connexion. Réessayez...</p>').show();
            }
        });
    }

    // Recherche automatique à la frappe (Debounce 150ms)
    $('#accountSearch').on('input', function() {
        clearTimeout(searchTimer);
        const query = $(this).val().trim();

        // Toggle clear button
        $('#clearSearch').toggle(query.length > 0);

        if (query.length < 1) {
            $('#searchResults').hide();
            $('#searchPlaceholder').show();
            $('#recentSection').show();
            lastQuery = '';
            return;
        }

        const isNumberSearch = /^(TON|SAV|EPR|LN|PRE|CLI|ACC|\d)/i.test(query);
        const debounceTime = isNumberSearch ? 80 : 150;

        searchTimer = setTimeout(() => {
            executeSearch(query);
        }, debounceTime);
    });

    function renderResults(data, fastMode, elapsed) {
        $('#searchLoading').hide();
        $('#resultsList').empty();
        selectedIndex = -1;

        if (data && data.length > 0) {
            $('#resultCount').text(data.length);
            $('#searchTime').text(elapsed + 'ms');
            $('#fastModeIndicator').toggle(!!fastMode);
            $('#searchResults').show();

            data.forEach((account, index) => {
                let badgeClass, badgeLabel;
                if (account.account_type === 'loan') {
                    badgeClass = 'badge-loan';
                    badgeLabel = 'Prêt';
                } else if (account.account_type === 'tontine') {
                    badgeClass = 'badge-tontine';
                    badgeLabel = 'Tontine';
                } else {
                    badgeClass = 'badge-savings';
                    badgeLabel = 'Épargne';
                }

                const html = `
                    <div class="client-card" data-index="${index}" onclick="openDepositModal(${JSON.stringify(account).replace(/"/g, '&quot;')})">
                        <div class="client-info">
                            <h6>${account.client_name} <span class="account-badge ${badgeClass}">${badgeLabel}</span></h6>
                            <span><i class="fas fa-barcode me-1 text-slate-400"></i>${account.account_number} • <i class="fas fa-phone me-1 text-slate-400"></i>${account.client_phone || 'N/A'}</span>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="text-end">
                                <div class="text-slate-900 fw-bold fs-6">${formatNumber(account.balance)} FCFA</div>
                                <div class="text-slate-500 small">${account.account_type === 'loan' ? 'Reste dû' : 'Solde disponible'}</div>
                            </div>
                            <div class="d-flex gap-2">
                                ${account.account_type === 'loan' ? `
                                    <a href="/caissier/loans/${account.id}" class="btn btn-sm btn-outline-secondary text-nowrap rounded-3"><i class="fas fa-file-contract"></i> Échéancier</a>
                                    <button class="btn btn-sm btn-success text-nowrap rounded-3 fw-bold" onclick="event.stopPropagation(); openDepositModal(${JSON.stringify(account).replace(/"/g, '&quot;')})">
                                        <i class="fas fa-coins me-1"></i> Rembourser
                                    </button>
                                ` : `
                                    <button class="quick-action-btn text-nowrap" onclick="event.stopPropagation(); openDepositModal(${JSON.stringify(account).replace(/"/g, '&quot;')})">
                                        <i class="fas fa-arrow-down me-1"></i> Dépôt
                                    </button>
                                `}
                            </div>
                        </div>
                    </div>
                `;
                $('#resultsList').append(html);
            });
        } else {
            $('#searchResults').hide();
            $('#searchPlaceholder').html('<i class="fas fa-search fa-3x mb-3 d-block opacity-25"></i><p>Aucun compte trouvé pour cette recherche.</p>').show();
        }
    }

    function navigateResults(direction) {
        const cards = $('.client-card');
        if (cards.length === 0) return;

        cards.removeClass('selected');
        selectedIndex += direction;

        if (selectedIndex < 0) selectedIndex = cards.length - 1;
        if (selectedIndex >= cards.length) selectedIndex = 0;

        const card = cards.eq(selectedIndex);
        card.addClass('selected');
        card[0].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }

    window.openDepositModal = function(account) {
        $('#modalClientName').text(account.client_name);
        $('#modalAccountNumber').text(account.account_number);
        $('#modalCurrentBalance').text(formatNumber(account.balance) + " FCFA");

        if (account.account_type === 'loan') {
            $('#depositAccountType').val('loan');
            $('#depositModalTitle').html('<i class="fas fa-coins text-warning me-2"></i>Remboursement de Prêt');
            $('#modalAccountLabel').text('Dossier Prêt');
            $('#modalBalanceLabel').text('Reste à Rembourser');
            $('#modalAmountLabel').text('Montant du remboursement (FCFA)');
            $('#btnConfirmDeposit').html('<i class="fas fa-check-circle me-1"></i> Valider le Remboursement');
            $('#tontineInfo').hide();
            $('#depositAmount').val('');
        } else if (account.account_type === 'tontine') {
            $('#depositAccountType').val('tontine');
            $('#depositModalTitle').html('<i class="fas fa-piggy-bank text-emerald-600 me-2"></i>Versement Tontine');
            $('#modalAccountLabel').text('Compte Tontine');
            $('#modalBalanceLabel').text('Solde actuel');
            $('#modalAmountLabel').text('Montant de la mise (FCFA)');
            $('#btnConfirmDeposit').html('<i class="fas fa-check-circle me-1"></i> Confirmer la Cotisation');
            $('#tontineInfo').show();
            $('#modalTontineAmount').text(formatNumber(account.tontine_amount) + " FCFA / cycle");
            $('#depositAmount').val(account.tontine_amount);
        } else {
            $('#depositAccountType').val('savings');
            $('#depositModalTitle').html('<i class="fas fa-wallet text-emerald-600 me-2"></i>Dépôt sur Compte Épargne');
            $('#modalAccountLabel').text('Compte');
            $('#modalBalanceLabel').text('Solde disponible');
            $('#modalAmountLabel').text('Montant du dépôt (FCFA)');
            $('#btnConfirmDeposit').html('<i class="fas fa-check-circle me-1"></i> Confirmer le Dépôt');
            $('#tontineInfo').hide();
            $('#depositAmount').val('');
        }

        $('#depositForm').attr('action', account.deposit_url);
        $('#depositModal').modal('show');

        // Focus sur le montant
        setTimeout(() => { $('#depositAmount').focus().select(); }, 400);

        // Sauvegarder dans les récents
        saveToRecent(account);
    };

    // Gestion des recherches récentes
    function saveToRecent(account) {
        let recent = JSON.parse(localStorage.getItem(RECENT_KEY) || '[]');
        // Éviter les doublons
        recent = recent.filter(r => r.account_number !== account.account_number);
        recent.unshift({
            client_name: account.client_name,
            account_number: account.account_number,
            account_type: account.account_type
        });
        // Garder max 8
        recent = recent.slice(0, 8);
        localStorage.setItem(RECENT_KEY, JSON.stringify(recent));
    }

    function loadRecentSearches() {
        const recent = JSON.parse(localStorage.getItem(RECENT_KEY) || '[]');
        if (recent.length === 0) {
            $('#recentSection').hide();
            return;
        }

        const list = $('#recentList');
        list.empty();

        recent.forEach(item => {
            const icon = item.account_type === 'tontine' ? 'fa-coins' :
                         item.account_type === 'loan' ? 'fa-hand-holding-usd' : 'fa-piggy-bank';
            list.append(`
                <span class="recent-item" onclick="quickSearch('${item.account_number}')">
                    <i class="fas ${icon}" style="color: #00d1b2; font-size: 0.7rem;"></i>
                    <span class="recent-name">${item.client_name}</span>
                    <span class="recent-num">${item.account_number}</span>
                </span>
            `);
        });

        if ($('#accountSearch').val().trim() === '') {
            $('#recentSection').show();
        }
    }

    window.clearRecentSearches = function() {
        localStorage.removeItem(RECENT_KEY);
        $('#recentSection').hide();
    };

    window.quickSearch = function(term) {
        $('#accountSearch').val(term).trigger('input').focus();
    };

    // Nettoyer le cache toutes les 60 secondes
    setInterval(() => {
        const now = Date.now();
        Object.keys(searchCache).forEach(key => {
            if (now - searchCache[key].time > CACHE_DURATION) {
                delete searchCache[key];
            }
        });
    }, 60000);
});
</script>
@endpush
