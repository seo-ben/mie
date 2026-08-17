@extends('layouts.cashier')

@section('title', 'Décaissement - Retraits')
@section('page-title', 'Décaissement')
@section('page-subtitle', 'Retrait de fonds au guichet')

@push('styles')
<style>
    .search-container {
        background: #1a2332;
        border: 1px solid rgba(239, 68, 68, 0.15);
        border-radius: 16px;
        padding: 25px;
        margin-bottom: 25px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    }

    .search-input-group {
        position: relative;
        display: flex;
        align-items: center;
    }

    .search-input-group input {
        background: #0f1923;
        border: 1px solid rgba(239, 68, 68, 0.3);
        color: white;
        border-radius: 12px;
        padding: 15px 50px 15px 45px;
        font-size: 1.05rem;
        width: 100%;
        transition: all 0.3s;
        font-weight: 500;
    }

    .search-input-group input:focus {
        border-color: #ef4444;
        box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
        outline: none;
    }

    .search-input-group .search-icon {
        position: absolute;
        left: 18px;
        color: #ef4444;
        font-size: 1.1rem;
    }

    .search-input-group .clear-btn {
        position: absolute;
        right: 15px;
        background: none;
        border: none;
        color: rgba(225, 232, 237, 0.4);
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
        background: rgba(239, 68, 68, 0.08);
        border: 1px solid rgba(239, 68, 68, 0.2);
        color: rgba(225, 232, 237, 0.7);
        border-radius: 20px;
        padding: 5px 14px;
        font-size: 0.78rem;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .shortcut-btn:hover {
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
        border-color: #ef4444;
    }

    .shortcut-btn i {
        font-size: 0.7rem;
    }

    .keyboard-hint {
        color: rgba(225, 232, 237, 0.3);
        font-size: 0.72rem;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .keyboard-hint kbd {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.2);
        padding: 1px 6px;
        border-radius: 4px;
        font-size: 0.7rem;
        color: #ef4444;
    }

    /* Résultats récents */
    .recent-section {
        margin-top: 15px;
        display: none;
    }

    .recent-header {
        color: rgba(225, 232, 237, 0.4);
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 8px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .recent-header button {
        background: none;
        border: none;
        color: rgba(225, 232, 237, 0.3);
        font-size: 0.7rem;
        cursor: pointer;
    }

    .recent-header button:hover {
        color: #ef4444;
    }

    .recent-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(239, 68, 68, 0.05);
        border: 1px solid rgba(239, 68, 68, 0.1);
        border-radius: 8px;
        padding: 6px 12px;
        margin: 0 6px 6px 0;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 0.8rem;
        color: rgba(225, 232, 237, 0.7);
    }

    .recent-item:hover {
        background: rgba(239, 68, 68, 0.12);
        border-color: #ef4444;
        color: white;
    }

    .recent-item .recent-name {
        font-weight: 500;
    }

    .recent-item .recent-num {
        color: rgba(225, 232, 237, 0.4);
        font-size: 0.72rem;
    }

    .results-container {
        display: none;
        margin-top: 20px;
    }

    .client-card {
        background: #1a2332;
        border: 1px solid rgba(239, 68, 68, 0.1);
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.15s;
        cursor: pointer;
        animation: slideIn 0.2s ease-out;
    }

    @keyframes slideIn {
        from { opacity: 0; transform: translateY(-5px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .client-card:hover {
        border-color: #ef4444;
        background: rgba(239, 68, 68, 0.05);
        transform: translateX(3px);
    }

    .client-card.selected {
        border-color: #ef4444;
        background: rgba(239, 68, 68, 0.1);
        box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2);
    }

    .client-info h6 { color: white; margin-bottom: 3px; font-weight: 600; }
    .client-info span { color: rgba(225, 232, 237, 0.5); font-size: 0.8rem; }

    .fast-indicator {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 0.65rem;
        color: #10b981;
        margin-left: 8px;
    }

    .fast-indicator i { font-size: 0.55rem; }

    .result-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .result-meta .result-time {
        font-size: 0.7rem;
        color: rgba(225, 232, 237, 0.3);
    }

    .quick-action-btn {
        background: linear-gradient(135deg, #ef4444, #b91c1c);
        border: none;
        color: white;
        border-radius: 8px;
        padding: 6px 14px;
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
    }

    .quick-action-btn:hover {
        transform: scale(1.05);
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
    }

    .withdrawal-modal-content {
        background: #1a2332;
        border: 1px solid rgba(239, 68, 68, 0.2);
        color: white;
    }

    .form-label { color: rgba(225, 232, 237, 0.6); font-size: 0.85rem; }

    .form-control, .form-select {
        background: #0f1923;
        border: 1px solid rgba(239, 68, 68, 0.2);
        color: white;
        border-radius: 8px;
        padding: 10px 12px;
    }

    .form-control:focus, .form-select:focus {
        background: #0f1923;
        color: white;
        border-color: #ef4444;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
    }

    .amount-input {
        font-size: 1.5rem;
        font-weight: 700;
        color: #ef4444;
        text-align: center;
    }

    .btn-withdrawal {
        background: linear-gradient(135deg, #ef4444, #b91c1c);
        border: none;
        color: white;
        font-weight: 600;
        padding: 12px;
        border-radius: 10px;
    }

    .balance-box {
        background: rgba(239, 68, 68, 0.05);
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 20px;
        border: 1px solid rgba(239, 68, 68, 0.1);
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        font-size: 0.85rem;
    }

    .info-item span:first-child { color: rgba(225, 232, 237, 0.5); }
    .info-item span:last-child { color: white; font-weight: 500; }

    .text-withdrawal { color: #ef4444; }

    /* Loading skeleton */
    .skeleton-card {
        background: #1a2332;
        border: 1px solid rgba(239, 68, 68, 0.05);
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 10px;
        animation: skeleton-pulse 1.5s infinite;
    }

    .skeleton-line {
        background: rgba(239, 68, 68, 0.08);
        border-radius: 4px;
        height: 14px;
        margin-bottom: 8px;
    }

    .skeleton-line.w-60 { width: 60%; }
    .skeleton-line.w-40 { width: 40%; }
    .skeleton-line.w-30 { width: 30%; height: 20px; }

    @keyframes skeleton-pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
</style>
@endpush

@section('content')

<div class="search-container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="text-white mb-0">
            <i class="fas fa-hand-holding-usd me-2" style="color: #ef4444;"></i>Rechercher un compte pour retrait
        </h5>
        <div class="keyboard-hint d-none d-md-flex">
            <kbd>/</kbd> pour rechercher &bull; <kbd>Échap</kbd> pour effacer
        </div>
    </div>

    <div class="search-input-group">
        <i class="fas fa-search search-icon"></i>
        <input type="text" id="accountSearch"
               placeholder="Nom du client, téléphone ou numéro de compte..."
               autocomplete="off"
               autofocus>
        <button class="clear-btn" id="clearSearch" title="Effacer">
            <i class="fas fa-times-circle"></i>
        </button>
    </div>

    <!-- Raccourcis -->
    <div class="search-shortcuts">
        <button class="shortcut-btn" onclick="quickSearch('ACC-')">
            <i class="fas fa-coins"></i> Tontine
        </button>
        <button class="shortcut-btn" onclick="quickSearch('SAV-')">
            <i class="fas fa-piggy-bank"></i> Épargne
        </button>
        {{-- <button class="shortcut-btn" onclick="quickSearch('EPR-')">
            <i class="fas fa-piggy-bank"></i> EPR
        </button> --}}
    </div>

    <!-- Récents -->
    <div id="recentSection" class="recent-section">
        <div class="recent-header">
            <span><i class="fas fa-clock me-1"></i> Récemment utilisés</span>
            <button onclick="clearRecentSearches()">Effacer</button>
        </div>
        <div id="recentList"></div>
    </div>

    <!-- Results -->
    <div id="searchResults" class="results-container">
        <div class="result-meta">
            <div class="d-flex align-items-center gap-2">
                <h6 class="text-white-50 mb-0">Comptes éligibles</h6>
                <span id="resultCount" class="badge bg-danger">0</span>
                <span id="fastModeIndicator" class="fast-indicator" style="display:none;">
                    <i class="fas fa-bolt"></i> Recherche rapide
                </span>
            </div>
            <span id="searchTime" class="result-time"></span>
        </div>
        <div id="resultsList"></div>
    </div>

    <!-- States -->
    <div id="searchPlaceholder" class="mt-4 text-center py-4 text-white-50">
        <i class="fas fa-user-minus fa-3x mb-3 d-block opacity-25"></i>
        <p>Entrez les informations du client pour initier le retrait...</p>
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

<!-- Modal Retrait -->
<div class="modal fade" id="withdrawalModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content withdrawal-modal-content">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title text-withdrawal">Confirmer le Retrait</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="withdrawalForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="balance-box">
                        <div class="info-item">
                            <span>Client</span>
                            <span id="modalClientName">-</span>
                        </div>
                        <div class="info-item">
                            <span>Compte</span>
                            <span id="modalAccountNumber">-</span>
                        </div>
                        <div class="info-item border-top border-secondary pt-2 mt-2">
                            <span>Solde Disponible</span>
                            <span id="modalAvailableBalance" class="fw-bold text-white fs-5">-</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Montant du retrait (FCFA)</label>
                        <input type="number" name="amount" id="withdrawalAmount" class="form-control amount-input" placeholder="0" required min="100">
                        <div id="amountHelp" class="form-text text-danger mt-1" style="display:none;">
                            <i class="fas fa-exclamation-triangle"></i> Le montant dépasse le solde disponible !
                        </div>
                    </div>

                    <div class="fee-summary mt-3 mb-3 p-3 rounded" style="background: rgba(0,0,0,0.2); border: 1px dashed rgba(239, 68, 68, 0.3); display:none;" id="feeContainer">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-white-50 small">Montant remis au client</span>
                            <span class="text-white small" id="summaryNet">-</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-white-50 small">Commission (<span id="feeRateText">Règle Professionnelle</span>)</span>
                            <span class="text-danger small" id="summaryFee">-</span>
                        </div>
                        <div class="d-flex justify-content-between mt-2 pt-2 border-top border-secondary">
                            <span class="text-white-50 font-weight-bold">Total débité du compte</span>
                            <span class="text-withdrawal fw-bold" id="summaryTotal">-</span>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Méthode de remise</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="cash">💵 Espèces (Main à main)</option>
                                <option value="mobile_money">📱 Mobile Money</option>
                                <option value="bank_transfer">🏛️ Virement</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Motif du retrait / Note</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Ex: Retrait personnel, Urgence..."></textarea>
                        </div>
                    </div>

                    <div class="mt-4 p-3 rounded bg-danger bg-opacity-10 border border-danger border-opacity-25">
                        <small class="text-danger d-flex align-items-center">
                            <i class="fas fa-info-circle me-2"></i>
                            Vérifiez l'identité du client avant de valider le décaissement.
                        </small>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" id="submitWithdrawal" class="btn btn-withdrawal px-4">Valider le Décaissement</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let searchTimer;
    let currentAvailableBalance = 0;
    let lastQuery = '';
    let searchCache = {};
    let selectedIndex = -1;

    const DEBOUNCE_FAST = 100;
    const DEBOUNCE_NORMAL = 200;
    const CACHE_DURATION = 30000;
    const RECENT_KEY = 'mie_retrait_recent';

    loadRecentSearches();

    // Raccourci clavier
    $(document).on('keydown', function(e) {
        if (e.key === '/' && !$('#accountSearch').is(':focus') && !$('.modal').hasClass('show')) {
            e.preventDefault();
            $('#accountSearch').focus();
        }
    });

    $('#accountSearch').on('keydown', function(e) {
        if (e.key === 'Escape') clearSearchField();
        if (e.key === 'ArrowDown') { e.preventDefault(); navigateResults(1); }
        if (e.key === 'ArrowUp') { e.preventDefault(); navigateResults(-1); }
        if (e.key === 'Enter' && selectedIndex >= 0) {
            e.preventDefault();
            $('.client-card').eq(selectedIndex).click();
        }
    });

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

    $('#accountSearch').on('input', function() {
        clearTimeout(searchTimer);
        const query = $(this).val().trim();

        $('#clearSearch').toggle(query.length > 0);

        if (query.length < 1) {
            $('#searchResults').hide();
            $('#searchPlaceholder').show();
            $('#recentSection').show();
            lastQuery = '';
            return;
        }

        $('#recentSection').hide();
        if (query === lastQuery) return;

        const cached = searchCache[query];
        if (cached && (Date.now() - cached.time < CACHE_DURATION)) {
            renderResults(cached.data, cached.fastMode, cached.searchTime);
            return;
        }

        const isNumberSearch = /^(TON|SAV|EPR|ACC|\d)/i.test(query);
        const debounceTime = isNumberSearch ? DEBOUNCE_FAST : DEBOUNCE_NORMAL;

        $('#searchPlaceholder').hide();
        $('#searchLoading').show();

        searchTimer = setTimeout(() => {
            const startTime = performance.now();
            lastQuery = query;

            $.ajax({
                url: "{{ route('caissier.retrait.search') }}",
                method: "GET",
                data: { query: query },
                success: function(response) {
                    const elapsed = Math.round(performance.now() - startTime);

                    searchCache[query] = {
                        data: response.data,
                        fastMode: response.fast_mode,
                        searchTime: elapsed,
                        time: Date.now()
                    };

                    renderResults(response.data, response.fast_mode, elapsed);
                },
                error: function() {
                    $('#searchLoading').hide();
                    $('#searchPlaceholder').html('<i class="fas fa-exclamation-triangle fa-3x mb-3 d-block text-warning opacity-50"></i><p>Erreur de connexion. Réessayez...</p>').show();
                }
            });
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
                const html = `
                    <div class="client-card" data-index="${index}" onclick="openWithdrawalModal(${JSON.stringify(account).replace(/"/g, '&quot;')})">
                        <div class="client-info">
                            <h6>${account.client_name} <span class="badge bg-secondary small" style="font-size:0.6rem;">${account.account_type}</span></h6>
                            <span><i class="fas fa-barcode me-1"></i>${account.account_number} • <i class="fas fa-phone me-1"></i>${account.client_phone || 'N/A'}</span>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="text-end">
                                <div class="text-danger fw-bold">${formatNumber(account.balance)} FCFA</div>
                                <div class="text-muted small">Disponible</div>
                            </div>
                            <button class="quick-action-btn" onclick="event.stopPropagation(); openWithdrawalModal(${JSON.stringify(account).replace(/"/g, '&quot;')})">
                                <i class="fas fa-arrow-up me-1"></i> Retrait
                            </button>
                        </div>
                    </div>
                `;
                $('#resultsList').append(html);
            });
        } else {
            $('#searchResults').hide();
            $('#searchPlaceholder').html('<i class="fas fa-search-minus fa-3x mb-3 d-block opacity-25"></i><p>Aucun compte solvable trouvé.</p>').show();
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

    let currentAccount = null;

    window.openWithdrawalModal = function(account) {
        currentAccount = account;
        currentAvailableBalance = parseFloat(account.balance);
        $('#modalClientName').text(account.client_name);
        $('#modalAccountNumber').text(account.account_number);
        $('#modalAvailableBalance').text(formatNumber(account.balance) + " FCFA");
        $('#withdrawalAmount').val('').removeClass('is-invalid');
        $('#amountHelp').hide();
        $('#feeContainer').hide();
        $('#submitWithdrawal').prop('disabled', false);

        $('#withdrawalForm').attr('action', account.withdrawal_url);
        $('#withdrawalModal').modal('show');

        setTimeout(() => { $('#withdrawalAmount').focus(); }, 400);

        saveToRecent(account);
    };

    $('#withdrawalAmount').on('input', function() {
        if (!currentAccount) return;
        
        const amount = parseFloat($(this).val()) || 0;
        const balance = currentAvailableBalance;
        let fee = 0;
        let rateLabel = "";
        
        if (currentAccount.account_type === 'tontine') {
            const mise = parseFloat(currentAccount.tontine_amount) || 0;
            const freq = currentAccount.payment_frequency;
            
            if (freq === 'daily') {
                const nbCommissions = Math.ceil(amount / (mise || 1) / 31);
                fee = nbCommissions * mise;
                rateLabel = "Règle 1/31";
            } else if (freq === 'weekly') {
                const nbCommissions = Math.ceil(amount / (mise || 1) / 52);
                fee = nbCommissions * mise;
                rateLabel = "Règle 1/52";
            } else {
                fee = mise;
                rateLabel = "Forfait 1 mise";
            }
        } else {
            // Épargne : 2%
            const rate = 2;
            fee = Math.round(amount * (rate / 100));
            rateLabel = rate + "%";
        }
        
        const total = amount + fee;
        $('#feeRateText').text(rateLabel);
        
        if (amount > 0) {
            $('#feeContainer').fadeIn(200);
            $('#summaryNet').text(formatNumber(amount) + " FCFA");
            $('#summaryFee').text(formatNumber(fee) + " FCFA");
            $('#summaryTotal').text(formatNumber(total) + " FCFA");
            
            if (total > balance) {
                $('#amountHelp').show().html('<i class="fas fa-exclamation-triangle"></i> Solde insuffisant (' + formatNumber(total) + ' FCFA requis)');
                $(this).addClass('is-invalid');
                $('#submitWithdrawal').prop('disabled', true);
            } else {
                $('#amountHelp').hide();
                $(this).removeClass('is-invalid');
                $('#submitWithdrawal').prop('disabled', false);
            }
        } else {
            $('#feeContainer').hide();
            $('#amountHelp').hide();
            $(this).removeClass('is-invalid');
            $('#submitWithdrawal').prop('disabled', false);
        }
    });

    // Gestion des récents
    function saveToRecent(account) {
        let recent = JSON.parse(localStorage.getItem(RECENT_KEY) || '[]');
        recent = recent.filter(r => r.account_number !== account.account_number);
        recent.unshift({
            client_name: account.client_name,
            account_number: account.account_number,
            account_type: account.account_type
        });
        recent = recent.slice(0, 8);
        localStorage.setItem(RECENT_KEY, JSON.stringify(recent));
    }

    function loadRecentSearches() {
        const recent = JSON.parse(localStorage.getItem(RECENT_KEY) || '[]');
        if (recent.length === 0) { $('#recentSection').hide(); return; }

        const list = $('#recentList');
        list.empty();
        recent.forEach(item => {
            const icon = item.account_type === 'tontine' ? 'fa-coins' : 'fa-piggy-bank';
            list.append(`
                <span class="recent-item" onclick="quickSearch('${item.account_number}')">
                    <i class="fas ${icon}" style="color: #ef4444; font-size: 0.7rem;"></i>
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

    // Nettoyer le cache
    setInterval(() => {
        const now = Date.now();
        Object.keys(searchCache).forEach(key => {
            if (now - searchCache[key].time > CACHE_DURATION) delete searchCache[key];
        });
    }, 60000);
});
</script>
@endpush
