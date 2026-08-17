@extends('layouts.agent')

@section('title', 'Nouveau Client')

@section('content')
<div class="py-4 container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <h2 class="mb-0 text-primary font-weight-bold">Inscription d'un Nouveau Client</h2>
                <a href="{{ route('agent.clients.index') }}" class="btn btn-outline-secondary rounded-pill">
                    <i class="fas fa-arrow-left me-2"></i>Retour
                </a>
            </div>
        </div>
    </div>

    <form action="{{ route('agent.clients.store') }}" method="POST" enctype="multipart/form-data" id="clientForm">
        @csrf

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Section Prioritaire : Informations de Base -->
                <div class="mb-4 shadow-lg border-0 card rounded-4">
                    <div class="py-3 card-header bg-primary text-white rounded-top-4">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-id-card-alt me-2"></i>Informations Obligatoires (Inscription Rapide)
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="first_name" class="form-label font-weight-bold">Prénom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg @error('first_name') is-invalid @enderror"
                                       id="first_name" name="first_name" value="{{ old('first_name') }}" required placeholder="Prénom du client">
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-6">
                                <label for="last_name" class="form-label font-weight-bold">Nom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg @error('last_name') is-invalid @enderror"
                                       id="last_name" name="last_name" value="{{ old('last_name') }}" required placeholder="Nom du client">
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-6">
                                <label for="phone" class="form-label font-weight-bold">Téléphone <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control form-control-lg @error('phone') is-invalid @enderror"
                                       id="phone" name="phone" value="{{ old('phone') }}"
                                       placeholder="+228 XX XX XX XX" required>
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-6">
                                <label for="address" class="form-label font-weight-bold">Adresse <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg @error('address') is-invalid @enderror"
                                       id="address" name="address" value="{{ old('address') }}" required placeholder="Quartier, Rue, N° de Porte...">
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="alert alert-soft-info mt-3 d-flex align-items-center">
                            <i class="fas fa-info-circle me-3 fa-2x"></i>
                            <div>
                                <p class="mb-0 small"><strong>Mot de passe par défaut :</strong> Le client pourra se connecter avec le mot de passe <strong>12@4</strong> après son enregistrement.</p>
                            </div>
                        </div>
                    </div>
                </div>

                @if(auth()->user()->role !== 'agent_terrain')
                <!-- Section Secondaire : Documents & KYC (Facultatif au début) -->
                <div class="mb-4 shadow border-0 card rounded-4">
                    <div class="py-3 card-header bg-light d-flex justify-content-between align-items-center cursor-pointer" 
                         data-bs-toggle="collapse" data-bs-target="#kycSection" aria-expanded="false">
                        <h6 class="m-0 font-weight-bold text-dark">
                            <i class="fas fa-file-invoice me-2"></i>Informations KYC & Documents (Facultatif à l'inscription)
                        </h6>
                        <i class="fas fa-chevron-down text-muted"></i>
                    </div>
                    <div id="kycSection" class="collapse">
                        <div class="card-body p-4">
                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <label for="date_of_birth" class="form-label">Date de Naissance</label>
                                    <input type="date" class="form-control @error('date_of_birth') is-invalid @enderror"
                                           id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth') }}"
                                           max="{{ date('Y-m-d', strtotime('-18 years')) }}">
                                </div>

                                <div class="mb-3 col-md-6">
                                    <label for="gender" class="form-label">Genre</label>
                                    <select class="form-select @error('gender') is-invalid @enderror" id="gender" name="gender">
                                        <option value="">Sélectionner...</option>
                                        <option value="M" {{ old('gender') == 'M' ? 'selected' : '' }}>Masculin</option>
                                        <option value="F" {{ old('gender') == 'F' ? 'selected' : '' }}>Féminin</option>
                                    </select>
                                </div>

                                <div class="mb-3 col-md-6">
                                    <label for="id_type" class="form-label">Type de Pièce</label>
                                    <select class="form-select @error('id_type') is-invalid @enderror" id="id_type" name="id_type">
                                        <option value="">Sélectionner...</option>
                                        <option value="cni" {{ old('id_type') == 'cni' ? 'selected' : '' }}>CNI</option>
                                        <option value="passport" {{ old('id_type') == 'passport' ? 'selected' : '' }}>Passeport</option>
                                        <option value="driving_license" {{ old('id_type') == 'driving_license' ? 'selected' : '' }}>Permis</option>
                                    </select>
                                </div>

                                <div class="mb-3 col-md-6">
                                    <label for="id_number" class="form-label">Numéro de Pièce</label>
                                    <input type="text" class="form-control @error('id_number') is-invalid @enderror"
                                           id="id_number" name="id_number" value="{{ old('id_number') }}">
                                </div>

                                <div class="mb-3 col-md-6">
                                    <label for="city" class="form-label">Ville</label>
                                    <input type="text" class="form-control @error('city') is-invalid @enderror"
                                           id="city" name="city" value="{{ old('city') }}">
                                </div>

                                <div class="mb-3 col-md-6">
                                    <label for="profile_photo" class="form-label">Photo de Profil</label>
                                    <input type="file" class="form-control @error('profile_photo') is-invalid @enderror"
                                           id="profile_photo" name="profile_photo" accept="image/*">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Actions -->
                <div class="mb-4 d-flex gap-3">
                    <button type="submit" class="btn btn-primary btn-lg flex-grow-1 rounded-pill py-3">
                        <i class="fas fa-check-circle me-2"></i>Enregistrer le Client
                    </button>
                    <a href="{{ route('agent.clients.index') }}" class="btn btn-light btn-lg rounded-pill py-3 px-4">
                        Annuler
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
    .alert-soft-info {
        background-color: #e7f3ff;
        color: #0c63e4;
        border: none;
        border-right: 4px solid #0c63e4;
    }
    .form-control-lg {
        padding: 0.8rem 1rem;
        font-size: 1rem;
    }
    .card-header.cursor-pointer:hover {
        background-color: #f8f9fa !important;
    }
</style>
@endsection
