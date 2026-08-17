@extends('layouts.cashier')

@section('title', 'Encaissement - Dépôts')
@section('page-title', 'Encaissement')
@section('page-subtitle', 'Recherche et dépôt sur compte')

@push('styles')
<style>
    .search-container {
        background: #1a2332;
        border: 1px solid rgba(0, 209, 178, 0.15);
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
        border: 1px solid rgba(0, 209, 178, 0.3);
        color: white;
        border-radius: 12px;
        padding: 15px 50px 15px 45px;
        font-size: 1.05rem;
        width: 100%;
        transition: all 0.3s;
        font-weight: 500;
    }

    .search-input-group input:focus {
        border-color: #00d1b2;
        box-shadow: 0 0 0 4px rgba(0, 209, 178, 0.15);
        outline: none;
    }

    .search-input-group .search-icon {
        position: absolute;
        left: 18px;
        color: #00d1b2;
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
        background: rgba(0, 209, 178, 0.08);
        border: 1px solid rgba(0, 209, 178, 0.2);
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
        background: rgba(0, 209, 178, 0.15);
        color: #00d1b2;
        border-color: #00d1b2;
    }

    .shortcut-btn i {
        font-size: 0.7rem;
    }

    .keyboard-hint {
        color: rgba(225, 232, 237, 0.3);
        font-size: 0.72rem;
        margin-top: 10px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .keyboard-hint kbd {
        background: rgba(0, 209, 178, 0.1);
        border: 1px solid rgba(0, 209, 178, 0.2);
        padding: 1px 6px;
        border-radius: 4px;
        font-size: 0.7rem;
        color: #00d1b2;
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
        background: rgba(0, 209, 178, 0.05);
        border: 1px solid rgba(0, 209, 178, 0.1);
        border-radius: 8px;
        padding: 6px 12px;
        margin: 0 6px 6px 0;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 0.8rem;
        color: rgba(225, 232, 237, 0.7);
    }

    .recent-item:hover {
        background: rgba(0, 209, 178, 0.12);
        border-color: #00d1b2;
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
        border: 1px solid rgba(0, 209, 178, 0.1);
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
        border-color: #00d1b2;
        background: rgba(0, 209, 178, 0.05);
        transform: translateX(3px);
    }

    .client-card.selected {
        border-color: #00d1b2;
        background: rgba(0, 209, 178, 0.1);
        box-shadow: 0 0 0 2px rgba(0, 209, 178, 0.2);
    }

    .client-info h6 { color: white; margin-bottom: 3px; font-weight: 600; }
    .client-info span { color: rgba(225, 232, 237, 0.5); font-size: 0.8rem; }

    .account-badge {
        font-size: 0.7rem;
        padding: 3px 8px;
        border-radius: 6px;
        margin-left: 8px;
        text-transform: uppercase;
        font-weight: 600;
    }

    .badge-savings { background: rgba(0, 180, 216, 0.15); color: #00b4d8; }
    .badge-tontine { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
    .badge-loan { background: rgba(168, 85, 247, 0.15); color: #a855f7; }

    .fast-indicator {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 0.65rem;
        color: #10b981;
        margin-left: 8px;
    }

    .fast-indicator i {
        font-size: 0.55rem;
    }

    /* Compteur de résultats */
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

    .deposit-modal-content {
        background: #1a2332;
        border: 1px solid rgba(0, 209, 178, 0.2);
        color: white;
    }

    .form-label { color: rgba(225, 232, 237, 0.6); font-size: 0.85rem; }

    .form-control, .form-select {
        background: #0f1923;
        border: 1px solid rgba(0, 209, 178, 0.2);
        color: white;
        border-radius: 8px;
        padding: 10px 12px;
    }

    .form-control:focus, .form-select:focus {
        background: #0f1923;
        color: white;
        border-color: #00d1b2;
        box-shadow: 0 0 0 3px rgba(0, 209, 178, 0.1);
    }

    .amount-input {
        font-size: 1.5rem;
        font-weight: 700;
        color: #00d1b2;
        text-align: center;
    }

    .btn-deposit {
        background: linear-gradient(135deg, #00d1b2, #00b4d8);
        border: none;
        color: white;
        font-weight: 600;
        padding: 12px;
        border-radius: 10px;
    }

    .info-box {
        background: rgba(0, 209, 178, 0.05);
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

    .info-item span:first-child { color: rgba(225, 232, 237, 0.5); }
    .info-item span:last-child { color: white; font-weight: 500; }

    /* Quick actions dans les résultats */
    .quick-action-btn {
        background: linear-gradient(135deg, #00d1b2, #00b4d8);
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
        box-shadow: 0 2px 8px rgba(0, 209, 178, 0.3);
    }

    /* Loading skeleton */
    .skeleton-card {
        background: #1a2332;
        border: 1px solid rgba(0, 209, 178, 0.05);
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 10px;
        animation: skeleton-pulse 1.5s infinite;
    }

    .skeleton-line {
        background: rgba(0, 209, 178, 0.08);
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
            <i class="fas fa-search-dollar me-2" style="color: #00d1b2;"></i>Rechercher un compte
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
            <span><i class="fas fa-clock me-1"></i> Récemment utilisés</span>
            <button onclick="clearRecentSearches()">Effacer</button>
        </div>
        <div id="recentList"></div>
    </div>

    <!-- Results -->
    <div id="searchResults" class="results-container">
        <div class="result-meta">
            <div class="d-flex align-items-center gap-2">
                <h6 class="text-white-50 mb-0">Résultats</h6>
                <span id="resultCount" class="badge bg-secondary">0</span>
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
    <div id="searchPlaceholder" class="mt-4 text-center py-4 text-white-50">
        <i class="fas fa-search-dollar fa-3x mb-3 d-block opacity-25"></i>
        <p>Commencez à saisir pour rechercher un client ou compte...</p>
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
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title">Effectuer un Dépôt</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="depositForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="info-box">
                        <div class="info-item">
                            <span>Client</span>
                            <span id="modalClientName">-</span>
                        </div>
                        <div class="info-item">
                            <span>Compte</span>
                            <span id="modalAccountNumber">-</span>
                        </div>
                        <div id="tontineInfo" style="display:none;">
                            <div class="info-item">
                                <span>Mise Tontine</span>
                                <span id="modalTontineAmount" class="text-warning">-</span>
                            </div>
                        </div>
                        <div class="info-item border-top border-secondary pt-2 mt-2">
                            <span>Solde actuel</span>
                            <span id="modalCurrentBalance">-</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Montant du dépôt (FCFA)</label>
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
                <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-deposit px-4">Confirmer le Dépôt</button>
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
    let lastQuery = '';
    let searchCache = {};
    let selectedIndex = -1;

    const DEBOUNCE_FAST = 100;    // Recherche par numéro
    const DEBOUNCE_NORMAL = 200;  // Recherche par nom
    const CACHE_DURATION = 30000; // Cache 30 secondes
    const RECENT_KEY = 'mie_depot_recent';

    // Charger les recherches récentes
    loadRecentSearches();

    // ⚡ Raccourci clavier "/" pour focus
    $(document).on('keydown', function(e) {
        if (e.key === '/' && !$('#accountSearch').is(':focus') && !$('.modal').hasClass('show')) {
            e.preventDefault();
            $('#accountSearch').focus();
        }
    });

    // Échap pour effacer
    $('#accountSearch').on('keydown', function(e) {
        if (e.key === 'Escape') {
            clearSearchField();
        }
        // Navigation clavier dans les résultats
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            navigateResults(1);
        }
        if (e.key === 'ArrowUp') {
            e.preventDefault();
            navigateResults(-1);
        }
        if (e.key === 'Enter' && selectedIndex >= 0) {
            e.preventDefault();
            $('.client-card').eq(selectedIndex).click();
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

    // Recherche principale
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

        $('#recentSection').hide();

        // Éviter de refaire la même recherche
        if (query === lastQuery) return;

        // Vérifier le cache
        const cached = searchCache[query];
        if (cached && (Date.now() - cached.time < CACHE_DURATION)) {
            renderResults(cached.data, cached.fastMode, cached.searchTime);
            return;
        }

        // Déterminer le délai de debounce selon le type de recherche
        const isNumberSearch = /^(TON|SAV|EPR|LN|PRE|CLI|ACC|\d)/i.test(query);
        const debounceTime = isNumberSearch ? DEBOUNCE_FAST : DEBOUNCE_NORMAL;

        $('#searchPlaceholder').hide();
        $('#searchLoading').show();

        searchTimer = setTimeout(() => {
            const startTime = performance.now();
            lastQuery = query;

            $.ajax({
                url: "{{ route('caissier.depot.search') }}",
                method: "GET",
                data: { query: query },
                success: function(response) {
                    const elapsed = Math.round(performance.now() - startTime);

                    // Mettre en cache
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
                            <span><i class="fas fa-barcode me-1"></i>${account.account_number} • <i class="fas fa-phone me-1"></i>${account.client_phone || 'N/A'}</span>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="text-end">
                                <div class="text-white fw-bold">${formatNumber(account.balance)} FCFA</div>
                                <div class="text-muted small">${account.account_type === 'loan' ? 'Reste dû' : 'Solde'}</div>
                            </div>
                            <div class="d-flex gap-2">
                                ${account.account_type === 'loan' ? `<a href="${account.schedule_url}" class="btn btn-sm btn-outline-light text-nowrap rounded-3"><i class="fas fa-file-contract"></i> Détail</a>` : ''}
                                <button class="quick-action-btn text-nowrap" onclick="event.stopPropagation(); openDepositModal(${JSON.stringify(account).replace(/"/g, '&quot;')})">
                                    <i class="fas fa-arrow-down me-1"></i> Dépôt
                                </button>
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

        if (account.account_type === 'tontine') {
            $('#tontineInfo').show();
            $('#modalTontineAmount').text(formatNumber(account.tontine_amount) + " FCFA / cycle");
            $('#depositAmount').val(account.tontine_amount);
        } else {
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
