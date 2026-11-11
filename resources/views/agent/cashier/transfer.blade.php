@extends('layouts.cashier')

@section('title', 'Virement Inter-Comptes Client')
@section('page-title', 'Virement de Compte à Compte')
@section('page-subtitle', 'Transfert direct et instantané entre deux adhérents')

@section('content')
<div class="space-y-4" style="max-width: 960px; margin: 0 auto;">

    <!-- EN-TÊTE DE PAGE & ACTIONS -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1 fs-4">Virement Inter-Comptes</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 fs-8">
                    <li class="breadcrumb-item"><a href="{{ route('caissier.dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item active text-emerald-600 fw-semibold" aria-current="page">Virement Inter-Comptes</li>
                </ol>
            </nav>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('caissier.terminal') }}" class="btn btn-sm btn-light border fw-bold rounded-3 px-3 py-2 text-dark">
                <i class="fas fa-list-check me-1"></i> Journal des Écritures
            </a>
        </div>
    </div>

    <!-- CARTE PRINCIPALE DU FORMULAIRE DE VIREMENT -->
    <div class="white-card p-4">
        <div class="d-flex align-items-center gap-3 pb-3 mb-4 border-bottom">
            <div class="rounded-3 p-3 text-emerald-600 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: #ecfdf5; font-size: 1.25rem;">
                <i class="fas fa-arrow-right-arrow-left"></i>
            </div>
            <div>
                <h5 class="fw-bold text-dark mb-1">Transfert Immédiat de Compte à Compte</h5>
                <p class="text-muted fs-8 mb-0">Le débit du compte source et le crédit du compte bénéficiaire sont immédiats.</p>
            </div>
        </div>

        <form action="{{ route('caissier.transfer.process') }}" method="POST" id="transferForm">
            @csrf

            <div class="row g-4">
                <!-- COMPTE SOURCE (ÉMETTEUR) -->
                <div class="col-12 col-md-6">
                    <div class="p-3 rounded-3 border" style="background: #f8fafc;">
                        <label class="form-label fw-bold text-dark fs-8 d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-danger-subtle text-danger px-2 py-1 rounded-pill">1. Débit</span>
                            Compte Source (Émetteur)
                        </label>
                        <select name="source_account_id" id="sourceAccount" class="form-select rounded-3 border fw-bold" style="padding: 10px 14px; font-size: 0.88rem;" required onchange="updateTransferPreview()">
                            <option value="">Sélectionner le compte émetteur...</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" data-balance="{{ $acc->balance }}" data-client="{{ $acc->client->full_name }}" data-type="{{ strtoupper($acc->account_type) }}">
                                    {{ $acc->account_number }} — {{ $acc->client->full_name }} ({{ number_format($acc->balance, 0, ',', ' ') }} F)
                                </option>
                            @endforeach
                        </select>
                        <div class="mt-2 text-muted fs-8" id="sourceInfo">
                            <i class="fas fa-info-circle me-1"></i> Sélectionnez un compte pour vérifier le solde disponible.
                        </div>
                    </div>
                </div>

                <!-- COMPTE DESTINATION (BÉNÉFICIAIRE) -->
                <div class="col-12 col-md-6">
                    <div class="p-3 rounded-3 border" style="background: #f8fafc;">
                        <label class="form-label fw-bold text-dark fs-8 d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-success-subtle text-success px-2 py-1 rounded-pill">2. Crédit</span>
                            Compte Bénéficiaire (Destinataire)
                        </label>
                        <select name="destination_account_id" id="destAccount" class="form-select rounded-3 border fw-bold" style="padding: 10px 14px; font-size: 0.88rem;" required onchange="updateTransferPreview()">
                            <option value="">Sélectionner le compte bénéficiaire...</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" data-client="{{ $acc->client->full_name }}" data-type="{{ strtoupper($acc->account_type) }}">
                                    {{ $acc->account_number }} — {{ $acc->client->full_name }} ({{ strtoupper($acc->account_type) }})
                                </option>
                            @endforeach
                        </select>
                        <div class="mt-2 text-muted fs-8" id="destInfo">
                            <i class="fas fa-info-circle me-1"></i> Sélectionnez le destinataire des fonds.
                        </div>
                    </div>
                </div>

                <!-- MONTANT DU VIREMENT -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-bold text-dark fs-8 mb-1">Montant à Transférer (FCFA)</label>
                    <div class="input-group">
                        <input type="number" name="amount" id="transferAmount" class="form-control rounded-start-3 border fw-bold" style="padding: 10px 14px; font-size: 1.1rem;" placeholder="25 000" min="1" step="any" required oninput="updateTransferPreview()">
                        <span class="input-group-text bg-light fw-bold text-muted border-start-0 rounded-end-3">FCFA</span>
                    </div>
                    <small class="text-muted fs-8">Montant minimum : 100 FCFA. Sans frais administratifs au guichet.</small>
                </div>

                <!-- MOTIF DU VIREMENT -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-bold text-dark fs-8 mb-1">Motif / Justification du Virement</label>
                    <input type="text" name="description" class="form-control rounded-3 border" style="padding: 10px 14px; font-size: 0.88rem;" placeholder="Ex: Paiement facture, aide familiale, cotisation...">
                    <small class="text-muted fs-8">Sera imprimé sur les deux reçus comptables.</small>
                </div>
            </div>

            <!-- APERÇU EN DIRECT DU VIREMENT -->
            <div class="mt-4 p-3 rounded-3 border d-none" id="previewBox" style="background: #f0fdf4; border-color: #bbf7d0 !important;">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle p-2 bg-success text-white d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-success fs-8">Résumé du Virement Immédiat :</div>
                            <div class="fs-7 text-dark" id="previewText"></div>
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-success fs-7 px-3 py-2 rounded-pill font-numeric" id="previewAmount">0 FCFA</span>
                    </div>
                </div>
            </div>

            <!-- BOUTONS D'ACTION -->
            <div class="pt-4 border-top mt-4 d-flex justify-content-end gap-2">
                <a href="{{ route('caissier.dashboard') }}" class="btn btn-light border fw-bold text-secondary px-4 py-2 rounded-3 fs-7">
                    Annuler
                </a>
                <button type="submit" class="btn btn-success fw-bold px-4 py-2 rounded-3 fs-7 shadow-sm" id="btnSubmitTransfer">
                    <i class="fas fa-check-circle me-1"></i> Exécuter le Virement
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function updateTransferPreview() {
        const sourceSelect = document.getElementById('sourceAccount');
        const destSelect = document.getElementById('destAccount');
        const amountInput = document.getElementById('transferAmount');
        const previewBox = document.getElementById('previewBox');
        const previewText = document.getElementById('previewText');
        const previewAmount = document.getElementById('previewAmount');
        const sourceInfo = document.getElementById('sourceInfo');

        const sourceOpt = sourceSelect.options[sourceSelect.selectedIndex];
        const destOpt = destSelect.options[destSelect.selectedIndex];
        const amount = parseFloat(amountInput.value) || 0;

        if (sourceOpt && sourceOpt.value) {
            const balance = parseFloat(sourceOpt.getAttribute('data-balance')) || 0;
            sourceInfo.innerHTML = `<span class="fw-bold ${balance < amount ? 'text-danger' : 'text-success'}">Solde actuel : ${balance.toLocaleString('fr-FR')} FCFA</span>`;
            if (balance < amount) {
                sourceInfo.innerHTML += ` <span class="badge bg-danger ms-1">Fonds insuffisants</span>`;
            }
        } else {
            sourceInfo.innerHTML = `<i class="fas fa-info-circle me-1"></i> Sélectionnez un compte pour vérifier le solde disponible.`;
        }

        if (sourceOpt.value && destOpt.value && amount > 0) {
            previewBox.classList.remove('d-none');
            previewText.innerHTML = `Transfert de <strong>${sourceOpt.getAttribute('data-client')}</strong> vers <strong>${destOpt.getAttribute('data-client')}</strong>`;
            previewAmount.innerText = `${amount.toLocaleString('fr-FR')} FCFA`;
        } else {
            previewBox.classList.add('d-none');
        }
    }
</script>
@endsection
