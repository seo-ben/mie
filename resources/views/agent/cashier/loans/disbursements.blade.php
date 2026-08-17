@extends('layouts.cashier')

@section('title', 'Décaissement de Prêts')
@section('page-title', 'Décaissements')
@section('page-subtitle', 'Remise de fonds pour crédits approuvés')

@push('styles')
<style>
    .loan-payout-card {
        background: #1a2332;
        border: 1px solid rgba(0, 209, 178, 0.1);
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s;
        margin-bottom: 20px;
    }

    .loan-payout-card:hover {
        border-color: #00d1b2;
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }

    .card-accent {
        height: 4px;
        background: linear-gradient(90deg, #00d1b2, #009e86);
    }

    .loan-amount {
        font-size: 1.5rem;
        font-weight: 800;
        color: #00d1b2;
    }

    .client-avatar {
        width: 45px;
        height: 45px;
        background: rgba(0, 209, 178, 0.1);
        color: #00d1b2;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.2rem;
    }

    .btn-payout {
        background: #00d1b2;
        color: #0f1923;
        font-weight: 700;
        border: none;
        padding: 10px 20px;
        border-radius: 10px;
        transition: all 0.3s;
    }

    .btn-payout:hover {
        background: #00f2d1;
        transform: scale(1.05);
        color: #0f1923;
    }

    .status-badge {
        background: rgba(0, 209, 178, 0.1);
        color: #00d1b2;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }
</style>
@endpush

@section('content')

@if($loans->isEmpty())
<div class="text-center py-5">
    <div class="mb-4">
        <i class="fas fa-check-circle fa-4x opacity-25" style="color: #00d1b2;"></i>
    </div>
    <h4 class="text-white">Aucun décaissement en attente</h4>
    <p class="text-white-50">Tous les prêts approuvés ont déjà été décaissés pour votre agence.</p>
</div>
@else
<div class="row">
    @foreach($loans as $loan)
    <div class="col-md-6 col-lg-4">
        <div class="loan-payout-card">
            <div class="card-accent"></div>
            <div class="p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="client-avatar">
                        {{ substr($loan->client->first_name, 0, 1) }}{{ substr($loan->client->last_name, 0, 1) }}
                    </div>
                    <span class="status-badge">Approuvé</span>
                </div>

                <h6 class="text-white mb-1">{{ $loan->client->full_name }}</h6>
                <p class="text-white-50 small mb-3">
                    <i class="fas fa-barcode me-1"></i> Prêt #{{ $loan->loan_number }}<br>
                    <i class="fas fa-calendar-alt me-1"></i> Approuvé le {{ $loan->approved_at->format('d/m/Y') }}
                </p>

                <div class="mb-4">
                    <label class="text-white-50 small d-block">Montant à décaisser</label>
                    <span class="loan-amount">{{ number_format($loan->approved_amount, 0, ',', ' ') }} FCFA</span>
                </div>

                <button type="button" class="btn btn-payout w-100" 
                        onclick="openDisbursementModal('{{ addslashes($loan->client->full_name) }}', '{{ number_format($loan->approved_amount, 0, ',', ' ') }}', '{{ route('caissier.loans.disburse', $loan->id) }}')">
                    <i class="fas fa-hand-holding-usd me-2"></i> Décaisser maintenant
                </button>
            </div>
        </div>
    </div>
    @endforeach
</div>

<!-- Modal de Confirmation de Décaissement -->
<div class="modal fade" id="disbursementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="background: #1a2332; border-radius: 24px;">
            <div class="p-1" style="background: linear-gradient(90deg, #00d1b2, #009e86); border-radius: 24px 24px 0 0;"></div>
            <div class="modal-body p-5 text-center">
                <div class="mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; background: rgba(0, 209, 178, 0.1); border-radius: 12px; color: #00d1b2;">
                        <i class="fas fa-hand-holding-usd fa-3x"></i>
                    </div>
                </div>
                
                <h4 class="text-white fw-bold mb-3">Confirmation de Décaissement</h4>
                <p class="text-white-50 mb-4">
                    Vous êtes sur le point de procéder à la remise physique des fonds pour le client :<br>
                    <span class="text-white fw-bold fs-5" id="modalClientName"></span>
                </p>

                <div class="py-4 px-3 mb-4" style="background: rgba(0, 0, 0, 0.2); border-radius: 16px; border: 1px dashed rgba(0, 209, 178, 0.3);">
                    <p class="text-white-50 small mb-1">Montant à remettre en espèces</p>
                    <h2 class="text-white fw-black mb-0" style="color: #00d1b2 !important;"><span id="modalAmount"></span> <small class="fs-6">FCFA</small></h2>
                </div>

                <div class="alert alert-warning border-0 bg-warning bg-opacity-10 text-warning small mb-4 text-start" style="border-radius: 12px;">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Action irréversible :</strong> Assurez-vous d'avoir compté physiquement les fonds et vérifié l'identité du client avant de valider.
                </div>

                <form id="modalDisbursementForm" method="POST">
                    @csrf
                    <div class="d-grid gap-3">
                        <button type="submit" class="btn btn-lg py-3 fw-bold" style="background: #00d1b2; color: #0f1923; border-radius: 12px;">
                            <i class="fas fa-check-circle me-2"></i> Confirmer la Remise des Fonds
                        </button>
                        <button type="button" class="btn btn-link text-white-50 text-decoration-none fw-bold" data-bs-dismiss="modal">
                            Annuler l'opération
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
    function openDisbursementModal(clientName, amount, actionUrl) {
        document.getElementById('modalClientName').innerText = clientName;
        document.getElementById('modalAmount').innerText = amount;
        document.getElementById('modalDisbursementForm').action = actionUrl;
        
        let myModal = new bootstrap.Modal(document.getElementById('disbursementModal'));
        myModal.show();
    }

    @if(session('print_receipt'))
        // La gestion globale dans layout.cashier s'en occupe
    @endif
</script>
@endpush
