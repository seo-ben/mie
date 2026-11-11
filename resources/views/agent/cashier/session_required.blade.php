@extends('layouts.cashier')

@section('title', 'Statut de Session Guichet')
@section('page-title', 'Contrôle d\'Accès Guichet')
@section('page-subtitle', 'Vérification des habilitations et démarrage de la session de caisse')

@section('content')
@php
    $user = auth()->user();
    $isAuthorized = $user->can_open_cashier_session || in_array($user->role, ['administrateur_systeme', 'administrateur_reglementaire']);
@endphp

<div class="d-flex justify-content-center align-items-center py-4" style="min-height: 75vh;">
    <div class="card border border-slate-200 shadow-sm rounded-2xl bg-white overflow-hidden" style="max-width: 540px; width: 100%;">
        
        @if($isAuthorized)
            <!-- Header Institutionnel - Autorisée -->
            <div class="card-header bg-emerald-50 border-bottom border-emerald-100 p-4 text-center">
                <div class="w-16 h-16 rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-bold text-2xl mx-auto mb-3 shadow-md">
                    <i class="fas fa-cash-register"></i>
                </div>
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-800 mb-2">
                    <i class="fas fa-shield-check"></i> Autorisation Active (Habilitation Admin)
                </div>
                <h4 class="text-slate-900 font-extrabold mb-1">Ouverture de Session Caisse</h4>
                <p class="text-xs text-slate-500 mb-0 font-medium">
                    {{ $user->full_name }} &bull; {{ $user->agency->name ?? 'Agence Centrale' }}
                </p>
            </div>

            <div class="card-body p-4 p-md-5">
                @if(session('error'))
                    <div class="alert alert-danger border-0 rounded-xl mb-4 d-flex align-items-center gap-2">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div class="small fw-bold">{{ session('error') }}</div>
                    </div>
                @endif

                <p class="text-sm text-slate-600 text-center mb-4">
                    Votre caisse est prête pour aujourd'hui ({{ now()->translatedFormat('d F Y') }}). Veuillez saisir votre fond de départ physique pour démarrer votre guichet.
                </p>

                <form action="{{ route('caissier.session.open') }}" method="POST">
                    @csrf

                    <!-- Fond d'ouverture -->
                    <div class="mb-4">
                        <label class="form-label text-slate-700 font-bold small mb-2 d-flex justify-content-between">
                            <span>Fond de caisse initial (Espèces)</span>
                            <span class="text-slate-400 font-normal">Saisir 0 si démarrage à vide</span>
                        </label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-slate-50 border-slate-300 text-slate-400">
                                <i class="fas fa-coins"></i>
                            </span>
                            <input type="number" 
                                   name="opening_balance" 
                                   id="opening_balance" 
                                   class="form-control border-slate-300 font-bold text-slate-800 text-lg" 
                                   placeholder="0" 
                                   value="{{ old('opening_balance', 0) }}" 
                                   min="0" 
                                   step="any" 
                                   required 
                                   autofocus>
                            <span class="input-group-text bg-slate-50 border-slate-300 font-bold text-slate-600">FCFA</span>
                        </div>
                        <small class="text-slate-400 mt-1 d-block text-xs">
                            Espèces physiques disponibles dans votre tiroir-caisse au début du service.
                        </small>
                    </div>

                    <!-- Notes / Observations -->
                    <div class="mb-4">
                        <label class="form-label text-slate-700 font-bold small mb-2">
                            Observation d'ouverture <span class="text-slate-400 font-normal">(Optionnel)</span>
                        </label>
                        <textarea name="notes" 
                                  class="form-control border-slate-300 rounded-lg text-sm" 
                                  rows="2" 
                                  placeholder="Ex: Fond de caisse physique recompté...">{{ old('notes') }}</textarea>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-emerald text-white py-3 rounded-xl font-bold shadow-sm d-flex align-items-center justify-content-center gap-2" style="background-color: #059669 !important; border-color: #047857 !important; color: #ffffff !important;">
                            <i class="fas fa-play-circle text-lg"></i>
                            <span>Démarrer et Ouvrir mon Guichet</span>
                        </button>
                        <a href="{{ route('caissier.dashboard') }}" class="btn btn-outline-secondary py-2 rounded-xl text-xs font-semibold text-slate-600">
                            <i class="fas fa-sync-alt me-1"></i> Actualiser le statut
                        </a>
                    </div>
                </form>
            </div>
        @else
            <!-- Header Institutionnel - Verrouillée -->
            <div class="card-header bg-slate-100 border-bottom border-slate-200 p-4 text-center">
                <div class="w-16 h-16 rounded-2xl bg-amber-500 text-white flex items-center justify-center font-bold text-2xl mx-auto mb-3 shadow-md">
                    <i class="fas fa-lock"></i>
                </div>
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-100 text-amber-800 mb-2">
                    <i class="fas fa-shield-halved"></i> Ouverture Restreinte
                </div>
                <h4 class="text-slate-900 font-extrabold mb-1">Guichet Caisse Verrouillé</h4>
                <p class="text-xs text-slate-500 mb-0 font-medium">
                    {{ $user->full_name }} &bull; {{ $user->agency->name ?? 'Agence Centrale' }}
                </p>
            </div>

            <div class="card-body p-4 p-md-5">
                @if(session('error'))
                    <div class="alert alert-danger border-0 rounded-xl mb-4 d-flex align-items-center gap-2">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div class="small fw-bold">{{ session('error') }}</div>
                    </div>
                @endif

                <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-slate-700 mb-4">
                    <div class="d-flex items-start gap-3">
                        <i class="fas fa-circle-info text-amber-600 text-lg mt-0.5"></i>
                        <div>
                            <h6 class="font-extrabold text-amber-900 mb-1">Autorisation Requise par l'Administration</h6>
                            <p class="text-xs text-amber-800 mb-0 leading-relaxed">
                                Conformément aux règles de contrôle interne et d'audit de <strong>FSD YAYRA Microfinance</strong>, vous ne pouvez pas ouvrir votre session de caisse de façon autonome tant que l'Administrateur ne vous en a pas accordé l'habilitation.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="space-y-3 mb-4 text-xs text-slate-600">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-check text-emerald-600"></i>
                        <span>Soit un <strong>Administrateur</strong> ouvre et approvisionne votre caisse depuis le panneau administratif.</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-check text-emerald-600"></i>
                        <span>Soit l'<strong>Administrateur</strong> active l'option <em>« Autorisation d'Ouverture Autonome de Caisse »</em> sur votre profil.</span>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <a href="{{ route('caissier.dashboard') }}" class="btn btn-dark py-3 rounded-xl font-bold shadow-sm d-flex align-items-center justify-center gap-2">
                        <i class="fas fa-sync-alt text-base"></i>
                        <span>Vérifier le Statut / Actualiser</span>
                    </a>
                </div>
            </div>
        @endif

        <div class="card-footer bg-slate-50 border-top border-slate-100 py-3 text-center">
            <span class="text-xs text-slate-400 font-medium">
                <i class="fas fa-shield-alt text-slate-400 me-1"></i> FSD YAYRA Microfinance &bull; Guichet Caisse Sécurisé
            </span>
        </div>
    </div>
</div>
@endsection
