@extends('layouts.cashier')

@section('title', 'Clôture de Caisse')
@section('page-title', 'Clôture de Session')
@section('page-subtitle', 'Récapitulatif de fin de journée')

@push('styles')
<style>
    .close-container {
        max-width: 800px;
        margin: 0 auto;
    }

    .summary-card {
        background: #1a2332;
        border: 1px solid rgba(0, 209, 178, 0.15);
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
    }

    .summary-title {
        color: white;
        font-weight: 700;
        text-align: center;
        margin-bottom: 30px;
        border-bottom: 1px solid rgba(0, 209, 178, 0.1);
        padding-bottom: 15px;
    }

    .summary-item {
        display: flex;
        justify-content: space-between;
        padding: 15px 0;
        border-bottom: 1px solid rgba(225, 232, 237, 0.05);
    }

    .summary-item .label { color: rgba(225, 232, 237, 0.6); font-size: 0.9rem; }
    .summary-item .value { color: white; font-weight: 600; font-size: 1.1rem; }

    .final-balance-box {
        background: rgba(0, 209, 178, 0.08);
        border-radius: 12px;
        padding: 20px;
        margin: 25px 0;
        text-align: center;
        border: 1px solid rgba(0, 209, 178, 0.2);
    }

    .final-balance-box h3 {
        color: #00d1b2;
        font-weight: 800;
        margin: 10px 0 0;
    }

    .btn-close-final {
        background: linear-gradient(135deg, #ef4444, #b91c1c);
        color: white;
        border: none;
        width: 100%;
        padding: 15px;
        border-radius: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: all 0.3s;
    }

    .btn-close-final:hover {
        transform: scale(1.02);
        box-shadow: 0 5px 20px rgba(239, 68, 68, 0.4);
        color: white;
    }

    .warning-box {
        background: rgba(245, 158, 11, 0.1);
        border: 1px solid rgba(245, 158, 11, 0.2);
        color: #f59e0b;
        padding: 15px;
        border-radius: 10px;
        font-size: 0.85rem;
        margin-bottom: 25px;
    }
</style>
@endpush

@section('content')

<div class="close-container">
    <div class="summary-card">
        <h4 class="summary-title">Récapitulatif de Clôture</h4>

        <div class="warning-box">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Attention: La clôture de session est irréversible. Toutes les opérations après cette confirmation seront enregistrées sur une nouvelle session.
        </div>

        {{-- <div class="summary-item">
            <span class="label">ID Session</span>
            <span class="value">#{{ $activeSession->id }}</span>
        </div> --}}
        <div class="summary-item">
            <span class="label">Ouverture</span>
            <span class="value">{{ $activeSession->opened_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="summary-item">
            <span class="label">Solde d'Ouverture</span>
            <span class="value">{{ number_format($activeSession->opening_balance, 0, ',', ' ') }} FCFA</span>
        </div>
        <div class="summary-item">
            <span class="label">Total Encaissement (+)</span>
            <span class="value text-success">+ {{ number_format($todayStats->total_in, 0, ',', ' ') }} FCFA</span>
        </div>
        <div class="summary-item">
            <span class="label">Total Décaissement (-)</span>
            <span class="value text-danger">- {{ number_format($todayStats->total_out, 0, ',', ' ') }} FCFA</span>
        </div>

        <div class="final-balance-box">
            <span class="label text-uppercase">Solde Théorique Final</span>
            <h3>{{ number_format(($activeSession->opening_balance + $todayStats->total_in - $todayStats->total_out), 0, ',', ' ') }} FCFA</h3>
        </div>

        <form action="{{ route('caissier.session.close', $activeSession->id) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="form-label text-white-50">Note ou observation de fin de journée</label>
                <textarea name="closing_note" class="form-control" rows="3" placeholder="Tout est conforme, écart de..."></textarea>
            </div>

            <button type="submit" class="btn-close-final">
                <i class="fas fa-lock me-2"></i> Confirmer la Clôture Définitive
            </button>
        </form>

        <div class="text-center mt-4">
            <a href="{{ route('caissier.dashboard') }}" class="text-white-50 text-decoration-none small">
                <i class="fas fa-arrow-left me-1"></i> Retour au tableau de bord
            </a>
        </div>
    </div>
</div>

@endsection
