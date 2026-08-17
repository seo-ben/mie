@extends('layouts.cashier')

@section('page-title', 'Session requise')

@section('content')
<div class="d-flex justify-content-center align-items-center" style="min-height: 60vh;">
    <div class="text-center p-5 shadow-lg rounded-4 bg-white" style="max-width: 500px; border-top: 5px solid #ff4757;">
        <div class="mb-4">
            <i class="fas fa-lock fa-4x text-danger"></i>
        </div>
        <h2 class="fw-bold text-dark mb-3">Guichet Fermé</h2>
        <p class="text-muted mb-4">
            Votre session de caisse n'est pas encore ouverte pour aujourd'hui. 
            Veuillez contacter votre administrateur pour l'ouverture de votre guichet et la mise à disposition de votre fonds de roulement.
        </p>
        <div class="d-grid">
            <a href="{{ route('caissier.dashboard') }}" class="btn btn-outline-secondary btn-lg">
                <i class="fas fa-sync-alt me-2"></i> Actualiser
            </a>
        </div>
        <p class="mt-4 text-xs text-uppercase tracking-widest text-secondary fw-bold" style="font-size: 0.7rem;">
            MIE YAYRA Microfinance – Système de Contrôle de Caisse
        </p>
    </div>
</div>
@endsection
