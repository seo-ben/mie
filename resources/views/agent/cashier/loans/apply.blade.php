@extends('layouts.cashier')

@section('title', 'Montage Demande de Prêt')
@section('page-title', 'Montage de Dossier de Crédit')
@section('page-subtitle', 'Demande de prêt, cautionnaires solidaires et grille d\'audit au guichet')

@push('styles')
<style>
    .guarantor-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 16px;
        transition: all 0.2s;
    }
    .guarantor-card:hover {
        border-color: #cbd5e1;
        background: #ffffff;
    }
    .check-item-box {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px 14px;
        transition: all 0.2s;
    }
    .check-item-box:hover {
        border-color: #10b981;
        background: #f0fdf4;
    }
    .advisor-alert-item {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
        border-radius: 10px;
        padding: 8px 12px;
        font-size: 0.8rem;
        display: flex;
        align-items: flex-start;
        gap: 8px;
        margin-bottom: 6px;
    }
    .advisor-warning-item {
        background: #fffbeb;
        border: 1px solid #fde68a;
        color: #92400e;
        border-radius: 10px;
        padding: 8px 12px;
        font-size: 0.8rem;
        display: flex;
        align-items: flex-start;
        gap: 8px;
        margin-bottom: 6px;
    }
</style>
@endpush

@section('content')
<div class="space-y-4" style="max-width: 1200px; margin: 0 auto;">

    <!-- EN-TÊTE DE PAGE & BREADCRUMBS -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1 fs-4">Nouveau Dossier de Crédit</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 fs-8">
                    <li class="breadcrumb-item"><a href="{{ route('caissier.dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('caissier.loans.index') }}" class="text-decoration-none text-muted">Prêts</a></li>
                    <li class="breadcrumb-item active text-emerald-600 fw-semibold" aria-current="page">Montage Dossier</li>
                </ol>
            </nav>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('caissier.loans.index') }}" class="btn btn-sm btn-light border fw-bold rounded-3 px-3 py-2 text-dark">
                <i class="fas fa-arrow-left me-1"></i> Portefeuille des Prêts
            </a>
        </div>
    </div>

    <form action="{{ route('caissier.loans.apply') }}" method="POST" id="loanApplyForm">
        @csrf

        <div class="row g-4">
            <!-- COLONNE GAUCHE : DOSSIER EMPRUNTEUR, CAUTIONS & CONTRÔLES -->
            <div class="col-12 col-lg-8">
                
                <!-- 1. IDENTITÉ DU PRÊT & CLIENT -->
                <div class="white-card p-4 mb-4">
                    <div class="d-flex align-items-center gap-3 pb-3 mb-4 border-bottom">
                        <div class="rounded-3 p-3 text-emerald-600 d-flex align-items-center justify-center" style="width: 44px; height: 44px; background: #ecfdf5; font-size: 1.2rem;">
                            <i class="fas fa-file-contract"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-0 fs-6">1. Emprunteur & Modalités Financières</h5>
                            <small class="text-muted fs-8">Sélection de l'adhérent et conditions d'octroi</small>
                        </div>
                    </div>

                    <!-- CHOIX DU CLIENT -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark fs-8 mb-1">Sélectionner l'Adhérent (Client) *</label>
                        <select name="client_id" id="clientSelect" class="form-select rounded-3 border fw-bold" style="padding: 10px 14px; font-size: 0.88rem;" required onchange="onClientChanged()">
                            <option value="">Sélectionner un client...</option>
                            @foreach($clients as $c)
                                <option value="{{ $c->id }}" data-phone="{{ $c->phone }}" {{ (isset($preselectedClient) && $preselectedClient->id === $c->id) ? 'selected' : '' }}>
                                    {{ $c->full_name }} ({{ $c->client_number }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- PANNEAU CONSEILLER CRÉDIT & ANALYSE DE RISQUE EN DIRECT -->
                    <div class="rounded-4 mb-4 border d-none overflow-hidden shadow-xs" id="advisorPanel">
                        <!-- Entête du Diagnostic -->
                        <div class="px-3.5 py-2.5 d-flex justify-content-between align-items-center flex-wrap gap-2" id="advisorHeader" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                            <div class="d-flex align-items-center gap-2">
                                <div class="w-7 h-7 rounded-circle d-flex align-items-center justify-center fs-8" id="advisorIconWrapper" style="background: #ecfdf5; color: #059669; width: 30px; height: 30px;">
                                    <i class="fas fa-user-tie" id="advisorIcon"></i>
                                </div>
                                <div>
                                    <span class="fs-9 text-uppercase tracking-wider fw-bold text-muted d-block lh-1">Conseiller Financier & Risque</span>
                                    <span class="fw-black fs-7 text-dark" id="advisorTitle">Analyse du profil adhérent...</span>
                                </div>
                            </div>
                            <div>
                                <span class="badge rounded-pill px-3 py-1.5 fs-8 fw-black" id="advisorBadge"></span>
                            </div>
                        </div>

                        <div class="p-3.5 bg-white">
                            <!-- 3 KPIs Financiers de l'adhérent -->
                            <div class="row g-2 mb-3">
                                <div class="col-4">
                                    <div class="p-2.5 rounded-3 bg-slate-50 border border-slate-100 text-center h-100">
                                        <span class="fs-9 text-muted text-uppercase fw-bold d-block mb-0.5">Capital Cotisé</span>
                                        <div class="fs-7 fw-black text-dark font-monospace" id="advisorCapital">0 F</div>
                                        <small class="fs-9 text-slate-400 d-block" id="advisorSavingsDetail">(Épargne + Tontine)</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-2.5 rounded-3 bg-slate-50 border border-slate-100 text-center h-100">
                                        <span class="fs-9 text-muted text-uppercase fw-bold d-block mb-0.5">Capacité / Mois</span>
                                        <div class="fs-7 fw-black text-emerald-700 font-monospace" id="advisorFlow">0 F</div>
                                        <small class="fs-9 text-slate-400 d-block">Flux constatés</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-2.5 rounded-3 bg-slate-50 border border-slate-100 text-center h-100">
                                        <span class="fs-9 text-muted text-uppercase fw-bold d-block mb-0.5">Taux d'Effort</span>
                                        <div class="fs-7 fw-black font-monospace" id="advisorEffortRatio">0%</div>
                                        <small class="fs-9 text-slate-400 d-block" id="advisorEffortLabel">Mensualité / Flux</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Alertes de Risque spécifiques -->
                            <div id="advisorAlertsContainer" class="mb-3 d-none">
                                <!-- Liste dynamique des alertes -->
                            </div>

                            <!-- Diagnostic Textuel du Conseiller -->
                            <div class="p-3 rounded-3 mb-3 fs-8" id="advisorAnalysisBox" style="background: #f8fafc; border-left: 4px solid #94a3b8;">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="fas fa-comment-dollar mt-0.5 text-muted fs-7" id="advisorAnalysisIcon"></i>
                                    <div class="min-w-0">
                                        <strong class="d-block text-dark mb-0.5" id="advisorAnalysisHeading">Avis du Conseiller :</strong>
                                        <span class="text-slate-700" id="advisorAnalysisText"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- RECOMMANDATION ACTIVE : QUEL PRÊT LUI FAIRE ? -->
                            <div class="p-3 rounded-3 border border-emerald-200 bg-emerald-50/40 d-flex flex-wrap align-items-center justify-content-between gap-2.5" id="advisorRecommendationBox">
                                <div class="min-w-0">
                                    <div class="d-flex align-items-center gap-1.5 text-emerald-800 fs-9 text-uppercase fw-bold mb-0.5">
                                        <i class="fas fa-lightbulb text-emerald-600"></i> Montant Conseillé pour cet adhérent :
                                    </div>
                                    <div class="fs-6 fw-black text-dark font-monospace">
                                        <span id="advisorRecommendedAmount" class="text-emerald-700">0 F</span>
                                        <span class="fs-8 fw-semibold text-slate-500">
                                            (Mensualité : <span id="advisorRecommendedMonthly">0 F</span> / mois)
                                        </span>
                                    </div>
                                    <small class="fs-9 text-muted d-block" id="advisorCeilingText">Plafond absolu avec 3 cautions : 0 F</small>
                                </div>
                                
                                <button type="button" class="btn btn-sm btn-success fw-bold px-3 py-1.5 rounded-3 fs-8 shadow-xs" id="btnApplyRecommendation" onclick="applyRecommendedAmount()">
                                    <i class="fas fa-wand-magic-sparkles me-1"></i>Appliquer ce montant
                                </button>
                            </div>

                        </div>
                    </div>

                    <!-- MONTANT & DURÉE & FRAIS DOSSIER -->
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-bold text-dark fs-8 mb-1">Montant Souhaité (FCFA) *</label>
                            <div class="input-group">
                                <input type="number" name="requested_amount" id="loanAmount" class="form-control rounded-start-3 border fw-bold" style="padding: 10px 14px; font-size: 1.1rem;" value="200000" min="1000" step="any" required oninput="recalcSimulation()">
                                <span class="input-group-text bg-light fw-bold text-muted border-start-0 rounded-end-3">FCFA</span>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label fw-bold text-success fs-8 mb-1">Frais de Dossier Perçus (FCFA)</label>
                            <div class="input-group">
                                <input type="number" name="application_fee" id="applicationFee" class="form-control rounded-start-3 border fw-bold text-success" style="padding: 10px 14px; font-size: 1.05rem;" value="{{ old('application_fee', 0) }}" min="0" step="any" placeholder="Ex: 5000">
                                <span class="input-group-text bg-light fw-bold text-muted border-start-0 rounded-end-3">FCFA</span>
                            </div>
                            <small class="text-muted fs-9">Montant pris pour le montage du dossier.</small>
                        </div>

                        <div class="col-6 col-md-2">
                            <label class="form-label fw-bold text-dark fs-8 mb-1">Durée (Mois) *</label>
                            <select name="duration_months" id="loanDuration" class="form-select rounded-3 border fw-bold" style="padding: 10px 14px; font-size: 0.88rem;" required onchange="recalcSimulation()">
                                <option value="3">3 Mois</option>
                                <option value="6" selected>6 Mois</option>
                                <option value="9">9 Mois</option>
                                <option value="12">12 Mois (1 An)</option>
                                <option value="18">18 Mois</option>
                                <option value="24">24 Mois (2 Ans)</option>
                            </select>
                        </div>

                        <div class="col-6 col-md-2">
                            <label class="form-label fw-bold text-dark fs-8 mb-1">Taux (%)</label>
                            <div class="input-group">
                                <input type="number" name="interest_rate" id="loanRate" value="17.0" class="form-control rounded-start-3 border fw-bold" style="padding: 10px 14px; font-size: 0.88rem;" step="0.5" min="1" max="50" oninput="recalcSimulation()">
                                <span class="input-group-text bg-light fw-bold text-muted border-start-0 rounded-end-3">%</span>
                            </div>
                        </div>
                    </div>

                    <!-- OBJET DU PRÊT -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark fs-8 mb-1">Objet / Activité Financée *</label>
                        <input type="text" name="purpose" class="form-control rounded-3 border" style="padding: 10px 14px; font-size: 0.88rem;" placeholder="Achat stock marchandises, fonds de roulement atelier..." required value="Fonds de roulement commercial">
                    </div>

                    <!-- GARANTIES GLOBALES -->
                    <div>
                        <label class="form-label fw-bold text-dark fs-8 mb-1">Garanties Matérielles / Gages Complémentaires</label>
                        <textarea name="collateral_description" rows="2" class="form-control rounded-3 border" style="padding: 10px 14px; font-size: 0.88rem;" placeholder="Nantissement de stock, gages matériels, caution morale...">Caution solidaire des 3 garants & solde tontine</textarea>
                    </div>
                </div>

                <!-- 2. SECTION DES 3 CAUTIONNAIRES SOLIDAIRES -->
                <div class="white-card p-4 mb-4">
                    <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 p-3 text-primary d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: #eff6ff; font-size: 1.2rem;">
                                <i class="fas fa-users-viewfinder"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0 fs-6">2. Cautionnaires Solidaires (Jusqu'à 3 Cautions)</h5>
                                <small class="text-muted fs-8">Garanties personnelles et engagements de solidarité</small>
                            </div>
                        </div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 fs-8">
                            <i class="fas fa-shield-halved me-1"></i> 3 Cautions Requises
                        </span>
                    </div>

                    <!-- ONGLETS DES 3 CAUTIONS -->
                    <ul class="nav nav-pills mb-3" id="guarantorsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active rounded-pill px-3 py-1.5 fs-8 fw-bold" id="g1-tab" data-bs-toggle="pill" data-bs-target="#g1-content" type="button" role="tab">
                                <i class="fas fa-user-check me-1"></i> Cautionnaire 1 <span class="badge bg-danger text-white rounded-pill ms-1 fs-9">Obligatoire</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill px-3 py-1.5 fs-8 fw-bold" id="g2-tab" data-bs-toggle="pill" data-bs-target="#g2-content" type="button" role="tab">
                                <i class="fas fa-user-plus me-1"></i> Cautionnaire 2 <span class="badge bg-secondary text-white rounded-pill ms-1 fs-9">Requis</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill px-3 py-1.5 fs-8 fw-bold" id="g3-tab" data-bs-toggle="pill" data-bs-target="#g3-content" type="button" role="tab">
                                <i class="fas fa-user-shield me-1"></i> Cautionnaire 3 <span class="badge bg-secondary text-white rounded-pill ms-1 fs-9">Requis</span>
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="guarantorsTabContent">
                        @for($i = 1; $i <= 3; $i++)
                        <div class="tab-pane fade {{ $i === 1 ? 'show active' : '' }}" id="g{{ $i }}-content" role="tabpanel">
                            <div class="guarantor-card">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold text-dark mb-0 fs-7">
                                        <i class="fas fa-address-card text-primary me-1.5"></i> Fiche d'Engagement — Cautionnaire {{ $i }}
                                    </h6>
                                    <div class="form-check form-switch fs-8">
                                        <input class="form-check-input" type="checkbox" name="guarantors[{{ $i }}][is_verified]" value="1" id="gVerified{{ $i }}" checked>
                                        <label class="form-check-label fw-bold text-success" for="gVerified{{ $i }}">Identité & Accord Validés</label>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-bold text-dark fs-8 mb-1">Nom & Prénoms du Cautionnaire {{ $i === 1 ? '*' : '' }}</label>
                                        <input type="text" name="guarantors[{{ $i }}][full_name]" class="form-control rounded-3 border" style="padding: 8px 12px; font-size: 0.88rem;" placeholder="Ex: Mensah Koffi Edoh" {{ $i === 1 ? 'required' : '' }}>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-bold text-dark fs-8 mb-1">Téléphone de Contact {{ $i === 1 ? '*' : '' }}</label>
                                        <input type="text" name="guarantors[{{ $i }}][phone]" class="form-control rounded-3 border font-monospace" style="padding: 8px 12px; font-size: 0.88rem;" placeholder="Ex: 90123456" {{ $i === 1 ? 'required' : '' }}>
                                    </div>

                                    <div class="col-12 col-md-4">
                                        <label class="form-label fw-bold text-dark fs-8 mb-1">N° Pièce (CNI / Passeport)</label>
                                        <input type="text" name="guarantors[{{ $i }}][id_card_number]" class="form-control rounded-3 border font-monospace" style="padding: 8px 12px; font-size: 0.88rem;" placeholder="TG-LOM-1990-...">
                                    </div>

                                    <div class="col-12 col-md-4">
                                        <label class="form-label fw-bold text-dark fs-8 mb-1">Lien de Parenté / Relation</label>
                                        <select name="guarantors[{{ $i }}][relationship]" class="form-select rounded-3 border" style="padding: 8px 12px; font-size: 0.88rem;">
                                            <option value="Frère / Sœur">Frère / Sœur</option>
                                            <option value="Conjoint(e)">Conjoint(e)</option>
                                            <option value="Parent (Père / Mère)">Parent (Père / Mère)</option>
                                            <option value="Collègue de travail">Collègue de travail</option>
                                            <option value="Partenaire d'affaires">Partenaire d'affaires</option>
                                            <option value="Autre caution morale">Autre caution morale</option>
                                        </select>
                                    </div>

                                    <div class="col-12 col-md-4">
                                        <label class="form-label fw-bold text-dark fs-8 mb-1">Profession / Activité</label>
                                        <input type="text" name="guarantors[{{ $i }}][profession]" class="form-control rounded-3 border" style="padding: 8px 12px; font-size: 0.88rem;" placeholder="Commerçant, artisan, fonctionnaire...">
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-bold text-dark fs-8 mb-1">Adresse / Quartier de Résidence</label>
                                        <input type="text" name="guarantors[{{ $i }}][address]" class="form-control rounded-3 border" style="padding: 8px 12px; font-size: 0.88rem;" placeholder="Ex: Agoè Assiyéyé, Lomé">
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-bold text-dark fs-8 mb-1">Valeur Estimée de l'Engagement (FCFA)</label>
                                        <input type="number" name="guarantors[{{ $i }}][collateral_value]" class="form-control rounded-3 border" style="padding: 8px 12px; font-size: 0.88rem;" placeholder="Ex: 200000" min="0" step="any">
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-bold text-dark fs-8 mb-1">Nature de la Garantie / Gage apporté</label>
                                        <input type="text" name="guarantors[{{ $i }}][collateral_description]" class="form-control rounded-3 border" style="padding: 8px 12px; font-size: 0.88rem;" placeholder="Ex: Nantissement matériel d'atelier, stock boutique, engagement solidaire sur salaire">
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endfor
                    </div>
                </div>

                <!-- 3. GRILLE DE VÉRIFICATIONS D'AUDIT (CASES À COCHER) -->
                <div class="white-card p-4">
                    <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 p-3 text-success d-flex align-items-center justify-center" style="width: 44px; height: 44px; background: #ecfdf5; font-size: 1.2rem;">
                                <i class="fas fa-list-check"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0 fs-6">3. Contrôles & Grille des Vérifications Préalables</h5>
                                <small class="text-muted fs-8">Points de conformité obligatoire à auditer avant instruction</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="fs-8 fw-bold text-muted">Contrôles cochés :</span>
                            <span class="badge bg-emerald-600 text-white rounded-pill px-3 py-1 fs-8 fw-bold" id="checkedCountBadge">9 / 9</span>
                        </div>
                    </div>

                    <div class="space-y-3">
                        @php
                            $items = \App\Models\Loan::VERIFICATION_ITEMS;
                            $categories = [
                                'Identité & Localisation' => 'text-primary',
                                'Activité & Solvabilité' => 'text-success',
                                'Cautions & Cautionnaires' => 'text-purple-600',
                                'Conformité & Juridique' => 'text-dark',
                            ];
                        @endphp

                        @foreach($categories as $cat => $colorClass)
                        <div class="mb-3">
                            <div class="fs-8 font-bold text-uppercase tracking-wider mb-2 {{ $colorClass }}">
                                <i class="fas fa-tag me-1"></i> {{ $cat }}
                            </div>
                            <div class="space-y-2">
                                @foreach($items as $k => $item)
                                    @if($item['category'] === $cat)
                                    <div class="check-item-box">
                                        <div class="form-check d-flex align-items-center gap-2">
                                            <input class="form-check-input verif-checkbox me-2" type="checkbox" name="verifications[{{ $k }}]" value="1" id="verif_{{ $k }}" checked onchange="updateVerifCount()">
                                            <label class="form-check-label fs-8 fw-semibold text-dark cursor-pointer d-flex align-items-center justify-content-between w-100" for="verif_{{ $k }}">
                                                <span><i class="fas {{ $item['icon'] }} me-2 text-muted"></i> {{ $item['label'] }}</span>
                                                <span class="badge bg-slate-100 text-slate-600 fs-9 rounded-pill">Vérifié</span>
                                            </label>
                                        </div>
                                    </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="pt-4 border-top d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('caissier.loans.index') }}" class="btn btn-light border fw-bold text-secondary px-4 py-2.5 rounded-3 fs-7">Annuler</a>
                        <button type="submit" class="btn btn-success fw-bold px-4 py-2.5 rounded-3 fs-7 shadow-sm">
                            <i class="fas fa-check-circle me-1.5"></i> Valider & Enregistrer le Dossier Complet
                        </button>
                    </div>
                </div>

            </div>

            <!-- COLONNE DROITE : SIMULATEUR D'ÉCHÉANCIER -->
            <div class="col-12 col-lg-4">
                <div class="white-card p-4 position-sticky" style="top: 20px;">
                    <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                        <div class="rounded-3 p-2 bg-success-subtle text-success d-flex align-items-center justify-center" style="width: 36px; height: 36px;">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-0 fs-7">Simulateur d'Échéancier</h6>
                            <small class="text-muted fs-8">Amortissement et mensualité</small>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="p-3 rounded-3 mb-3" style="background: #f0fdf4; border: 1px solid #dcfce7;">
                            <span class="stat-title text-success">Mensualité Estimée</span>
                            <div class="stat-value text-success fs-3 font-numeric mt-1" id="simMonthly">0 <small class="fs-8 text-muted">FCFA / mois</small></div>
                            <small class="text-muted fs-8">Montant constant à rembourser chaque mois</small>
                        </div>

                        <div class="p-3 bg-light rounded-3 border mb-3">
                            <div class="d-flex justify-content-between py-2 border-bottom fs-8">
                                <span class="text-muted">Capital sollicité :</span>
                                <strong class="text-dark font-numeric" id="simCapital">0 FCFA</strong>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom fs-8">
                                <span class="text-muted">Total des Intérêts :</span>
                                <strong class="text-danger font-numeric" id="simInterest">0 FCFA</strong>
                            </div>
                            <div class="d-flex justify-content-between py-2 fs-7">
                                <span class="fw-bold text-dark">Total à Rembourser :</span>
                                <strong class="text-dark fw-black fs-6 font-numeric" id="simTotalDue">0 FCFA</strong>
                            </div>
                        </div>

                        <div class="p-3 rounded-3 bg-white border text-muted fs-8">
                            <i class="fas fa-shield-halved text-success me-1"></i> 
                            Le taux d'intérêt institutionnel appliqué par défaut est de <strong>17% par an</strong>. L'accord de 3 cautionnaires solidaires est audité avant décaissement.
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

@push('scripts')
<script>
    let currentAnalysis = null;
    let debounceTimer = null;

    function recalcSimulation() {
        const amount = parseFloat(document.getElementById('loanAmount').value) || 0;
        const months = parseInt(document.getElementById('loanDuration').value) || 6;
        const rate = parseFloat(document.getElementById('loanRate').value) || 17.0;

        if (amount > 0) {
            const interest = amount * (rate / 100) * (months / 12);
            const totalDue = amount + interest;
            const monthly = totalDue / months;

            document.getElementById('simCapital').innerText = Math.round(amount).toLocaleString('fr-FR') + ' FCFA';
            document.getElementById('simInterest').innerText = Math.round(interest).toLocaleString('fr-FR') + ' FCFA';
            document.getElementById('simTotalDue').innerText = Math.round(totalDue).toLocaleString('fr-FR') + ' FCFA';
            document.getElementById('simMonthly').innerText = Math.round(monthly).toLocaleString('fr-FR') + ' FCFA / mois';
        } else {
            document.getElementById('simCapital').innerText = '0 FCFA';
            document.getElementById('simInterest').innerText = '0 FCFA';
            document.getElementById('simTotalDue').innerText = '0 FCFA';
            document.getElementById('simMonthly').innerText = '0 FCFA / mois';
        }

        // Déclencher l'analyse du conseiller de crédit avec debouncing
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            fetchAdvisorAnalysis();
        }, 250);
    }

    function applyRecommendedAmount() {
        if (!currentAnalysis || !currentAnalysis.recommended_amount) return;
        document.getElementById('loanAmount').value = currentAnalysis.recommended_amount;
        recalcSimulation();
    }

    async function fetchAdvisorAnalysis() {
        const clientId = document.getElementById('clientSelect').value;
        const panel = document.getElementById('advisorPanel');
        if (!clientId) {
            panel.classList.add('d-none');
            return;
        }

        const amount = parseFloat(document.getElementById('loanAmount').value) || 200000;
        const months = parseInt(document.getElementById('loanDuration').value) || 6;
        const rate = parseFloat(document.getElementById('loanRate').value) || 17.0;

        try {
            const params = new URLSearchParams({
                requested_amount: amount,
                duration_months: months,
                interest_rate: rate
            });

            const resp = await fetch(`/caissier/loans/check-eligibility/${clientId}?${params.toString()}`);
            const data = await resp.json();
            currentAnalysis = data;

            panel.classList.remove('d-none');

            // 1. Mise à jour de l'entête & badge
            const badge = document.getElementById('advisorBadge');
            const title = document.getElementById('advisorTitle');
            const header = document.getElementById('advisorHeader');
            const iconWrapper = document.getElementById('advisorIconWrapper');
            const analysisBox = document.getElementById('advisorAnalysisBox');
            const effortRatioEl = document.getElementById('advisorEffortRatio');

            badge.innerText = data.badge_title;

            if (data.risk_level === 'high') {
                header.style.background = '#fef2f2';
                header.style.borderBottomColor = '#fecaca';
                iconWrapper.style.background = '#fee2e2';
                iconWrapper.style.color = '#dc2626';
                badge.className = 'badge bg-danger rounded-pill px-3 py-1.5 fs-8 fw-bold';
                title.innerText = 'Attention : Risque Élevé de Défaut';
                analysisBox.style.borderLeftColor = '#dc2626';
                analysisBox.style.background = '#fff5f5';
                effortRatioEl.className = 'fs-7 fw-black text-danger font-monospace';
            } else if (data.risk_level === 'moderate') {
                header.style.background = '#fffbeb';
                header.style.borderBottomColor = '#fde68a';
                iconWrapper.style.background = '#fef3c7';
                iconWrapper.style.color = '#d97706';
                badge.className = 'badge bg-warning text-dark rounded-pill px-3 py-1.5 fs-8 fw-bold';
                title.innerText = 'Vigilance : Risque Modéré sous Conditions';
                analysisBox.style.borderLeftColor = '#d97706';
                analysisBox.style.background = '#fffdf5';
                effortRatioEl.className = 'fs-7 fw-black text-warning-emphasis font-monospace';
            } else {
                header.style.background = '#f0fdf4';
                header.style.borderBottomColor = '#bbf7d0';
                iconWrapper.style.background = '#dcfce7';
                iconWrapper.style.color = '#16a34a';
                badge.className = 'badge bg-success rounded-pill px-3 py-1.5 fs-8 fw-bold';
                title.innerText = 'Profil Sain : Demande Équilibrée';
                analysisBox.style.borderLeftColor = '#16a34a';
                analysisBox.style.background = '#f7fdf9';
                effortRatioEl.className = 'fs-7 fw-black text-success font-monospace';
            }

            // 2. Chiffres clés de l'adhérent
            document.getElementById('advisorCapital').innerText = Number(data.total_capital || 0).toLocaleString('fr-FR') + ' F';
            document.getElementById('advisorSavingsDetail').innerText = `(Épargne: ${(data.savings_total || 0).toLocaleString('fr-FR')} F | Tontine: ${(data.tontine_total || 0).toLocaleString('fr-FR')} F)`;
            document.getElementById('advisorFlow').innerText = Number(data.estimated_monthly_flow || 0).toLocaleString('fr-FR') + ' F';
            effortRatioEl.innerText = (data.effort_ratio || 0) + '%';

            // 3. Alertes spécifiques
            const alertsContainer = document.getElementById('advisorAlertsContainer');
            alertsContainer.innerHTML = '';
            if (data.alerts && data.alerts.length > 0) {
                alertsContainer.classList.remove('d-none');
                data.alerts.forEach(alertText => {
                    const item = document.createElement('div');
                    item.className = (data.risk_level === 'high') ? 'advisor-alert-item' : 'advisor-warning-item';
                    item.innerHTML = `<i class="fas fa-triangle-exclamation mt-0.5"></i><span>${alertText}</span>`;
                    alertsContainer.appendChild(item);
                });
            } else {
                alertsContainer.classList.add('d-none');
            }

            // 4. Texte d'analyse du conseiller
            document.getElementById('advisorAnalysisText').innerText = data.analysis_text || '';

            // 5. Recommandation chiffrée
            document.getElementById('advisorRecommendedAmount').innerText = Number(data.recommended_amount || 0).toLocaleString('fr-FR') + ' FCFA';
            document.getElementById('advisorRecommendedMonthly').innerText = Number(data.recommended_monthly || 0).toLocaleString('fr-FR') + ' F';
            document.getElementById('advisorCeilingText').innerText = `Plafond absolu avec 3 cautions : ${Number(data.max_safe_ceiling || 0).toLocaleString('fr-FR')} FCFA`;

            // Bouton appliquer
            const btnApply = document.getElementById('btnApplyRecommendation');
            if (Math.round(amount) === Math.round(data.recommended_amount)) {
                btnApply.className = 'btn btn-sm btn-outline-success fw-bold px-3 py-1.5 rounded-3 fs-8 shadow-xs';
                btnApply.innerHTML = '<i class="fas fa-check me-1"></i>Montant optimal appliqué';
            } else {
                btnApply.className = 'btn btn-sm btn-success fw-bold px-3 py-1.5 rounded-3 fs-8 shadow-xs';
                btnApply.innerHTML = '<i class="fas fa-wand-magic-sparkles me-1"></i>Appliquer ce montant';
            }

        } catch (e) {
            console.error("Erreur conseiller crédit:", e);
        }
    }

    function onClientChanged() {
        recalcSimulation();
    }

    function updateVerifCount() {
        const checkboxes = document.querySelectorAll('.verif-checkbox');
        const total = checkboxes.length;
        let checked = 0;
        checkboxes.forEach(c => { if(c.checked) checked++; });
        
        const badge = document.getElementById('checkedCountBadge');
        if (badge) {
            badge.innerText = checked + ' / ' + total;
            if (checked === total) {
                badge.className = "badge bg-emerald-600 text-white rounded-pill px-3 py-1 fs-8 fw-bold";
            } else {
                badge.className = "badge bg-warning text-dark rounded-pill px-3 py-1 fs-8 fw-bold";
            }
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        recalcSimulation();
        updateVerifCount();
        if (document.getElementById('clientSelect').value) {
            onClientChanged();
        }
    });
</script>
@endpush
@endsection
