@extends('layouts.cashier')

@section('title', 'Clôture de Caisse')
@section('page-title', 'Clôture de Session')
@section('page-subtitle', 'Récapitulatif et validation de fin de journée')

@push('styles')
<style>
    .close-container {
        max-width: 760px;
        margin: 0 auto;
    }

    .summary-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 32px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .summary-title {
        color: #0f172a;
        font-weight: 800;
        text-align: center;
        margin-bottom: 24px;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 16px;
    }

    .summary-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .summary-item .label { color: #64748b; font-size: 0.9rem; font-weight: 500; }
    .summary-item .value { color: #0f172a; font-weight: 700; font-size: 1.05rem; }

    .final-balance-box {
        background: #f0fdf4;
        border-radius: 12px;
        padding: 24px;
        margin: 25px 0;
        text-align: center;
        border: 1px solid #bbf7d0;
    }

    .final-balance-box h3 {
        color: #15803d;
        font-weight: 800;
        font-size: 2rem;
        margin: 8px 0 0;
    }

    .btn-close-final {
        background: linear-gradient(135deg, #e11d48, #be123c);
        color: white;
        border: none;
        width: 100%;
        padding: 14px;
        border-radius: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: all 0.2s;
    }

    .btn-close-final:hover {
        background: linear-gradient(135deg, #be123c, #9f1239);
        box-shadow: 0 4px 12px rgba(225, 29, 72, 0.25);
        color: white;
    }

    .warning-box {
        background: #fffbeb;
        border: 1px solid #fde68a;
        color: #b45309;
        padding: 16px;
        border-radius: 10px;
        font-size: 0.88rem;
        font-weight: 500;
        margin-bottom: 25px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }
</style>
@endpush

@section('content')

<div class="close-container">
    <div class="summary-card">
        <h4 class="summary-title">Récapitulatif de Clôture</h4>

        <div class="warning-box">
            <i class="fas fa-exclamation-triangle mt-1 text-amber-600 fs-5"></i>
            <div>
                <strong>Attention opérationnelle :</strong> La clôture de session est irréversible. Un billetage physique sera requis et toutes les opérations ultérieures seront enregistrées sur une nouvelle session.
            </div>
        </div>

        <div class="summary-item">
            <span class="label">Date & Heure d'ouverture</span>
            <span class="value">{{ $activeSession->opened_at ? $activeSession->opened_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i') }}</span>
        </div>
        <div class="summary-item">
            <span class="label">Solde d'Ouverture</span>
            <span class="value">{{ number_format($activeSession->opening_balance ?? 0, 0, ',', ' ') }} FCFA</span>
        </div>
        <div class="summary-item">
            <span class="label">Total Encaissements (+)</span>
            <span class="value text-emerald-600">+ {{ number_format($todayStats->total_in ?? 0, 0, ',', ' ') }} FCFA</span>
        </div>
        <div class="summary-item">
            <span class="label">Total Décaissements (-)</span>
            <span class="value text-rose-600">- {{ number_format($todayStats->total_out ?? 0, 0, ',', ' ') }} FCFA</span>
        </div>

        <div class="final-balance-box">
            <span class="label text-xs uppercase tracking-wider text-emerald-800 font-bold">Solde Théorique de Clôture</span>
            <h3>{{ number_format((($activeSession->opening_balance ?? 0) + ($todayStats->total_in ?? 0) - ($todayStats->total_out ?? 0)), 0, ',', ' ') }} FCFA</h3>
        </div>

        <form action="{{ route('caissier.session.close', $activeSession->id ?? 1) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="form-label text-slate-700 font-bold small">Note ou observation de fin de journée (Optionnel)</label>
                <textarea name="closing_note" class="form-control rounded-lg border-slate-300" rows="3" placeholder="Ex: Caisse parfaitement équilibrée, aucun écart constaté..."></textarea>
            </div>

            <button type="button" class="btn-close-final"
                    data-confirm="Confirmez-vous la fermeture définitive de votre session de caisse ? Cette action verrouille les écritures du jour et transfère le bilan pour arrêté comptable."
                    data-confirm-title="Clôture Définitive de Caisse"
                    data-confirm-subtitle="Arrêté journalier de session"
                    data-confirm-btn="Oui, Clôturer la Caisse"
                    data-confirm-class="btn-danger"
                    data-confirm-icon="fa-lock"
                    data-confirm-icon-bg="#fee2e2"
                    data-confirm-icon-color="#dc2626">
                <i class="fas fa-lock me-2"></i> Valider la Clôture Définitive
            </button>
        </form>

        <div class="text-center mt-4">
            <a href="{{ route('caissier.dashboard') }}" class="text-slate-500 hover:text-slate-800 text-decoration-none small font-semibold">
                <i class="fas fa-arrow-left me-1"></i> Retour au tableau de bord
            </a>
        </div>
    </div>
</div>

@endsection