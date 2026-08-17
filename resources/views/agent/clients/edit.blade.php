@extends('layouts.agent')

@section('title', 'Modifier Client')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0">Modifier le Client</h2>
                    <p class="text-muted">{{ $client->first_name }} {{ $client->last_name }} - {{ $client->client_number }}</p>
                </div>
                <a href="{{ route('agent.clients.show', $client->id) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Retour
                </a>
            </div>

            @if($client->kyc_status === 'approved')
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Ce client a un KYC approuvé. La modification n'est plus possible.
                </div>
            @endif
        </div>
    </div>

    <form action="{{ route('agent.clients.update', $client->id) }}" method="POST" enctype="multipart/form-data" id="clientForm">
        @csrf
        @method('PUT')

        <div class="row">
            <!-- Informations personnelles -->
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-user me-2"></i>Informations Personnelles
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">Prénom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                                       id="first_name" name="first_name" value="{{ old('first_name', $client->first_name) }}" required>
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Nom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                                       id="last_name" name="last_name" value="{{ old('last_name', $client->last_name) }}" required>
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Téléphone <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control @error('phone') is-invalid @enderror"
                                       id="phone" name="phone" value="{{ old('phone', $client->phone) }}"
                                       placeholder="+228 XX XX XX XX" required>
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                       id="email" name="email" value="{{ old('email', $client->email) }}">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            @if(auth()->user()->role !== 'agent_terrain')
                            <div class="col-md-6 mb-3">
                                <label for="date_of_birth" class="form-label">Date de Naissance <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('date_of_birth') is-invalid @enderror"
                                       id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth', $client->date_of_birth) }}"
                                       max="{{ date('Y-m-d', strtotime('-18 years')) }}" required>
                                @error('date_of_birth')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Le client doit avoir au moins 18 ans</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="gender" class="form-label">Genre <span class="text-danger">*</span></label>
                                <select class="form-select @error('gender') is-invalid @enderror"
                                        id="gender" name="gender" required>
                                    <option value="">Sélectionner...</option>
                                    <option value="M" {{ old('gender', $client->gender) == 'M' ? 'selected' : '' }}>Masculin</option>
                                    <option value="F" {{ old('gender', $client->gender) == 'F' ? 'selected' : '' }}>Féminin</option>
                                    <option value="Other" {{ old('gender', $client->gender) == 'Other' ? 'selected' : '' }}>Autre</option>
                                </select>
                                @error('gender')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            @endif

                            <div class="col-md-6 mb-3">
                                <label for="profession" class="form-label">Profession</label>
                                <input type="text" class="form-control @error('profession') is-invalid @enderror"
                                       id="profession" name="profession" value="{{ old('profession', $client->profession) }}">
                                @error('profession')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            @if(auth()->user()->role !== 'agent_terrain')
                            <div class="col-md-6 mb-3">
                                <label for="monthly_income" class="form-label">Revenu Mensuel (FCFA)</label>
                                <input type="number" class="form-control @error('monthly_income') is-invalid @enderror"
                                       id="monthly_income" name="monthly_income" value="{{ old('monthly_income', $client->monthly_income) }}"
                                       step="0.01" min="0">
                                @error('monthly_income')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            @endif

                            <div class="col-12 mb-3">
                                <label for="address" class="form-label">Adresse</label>
                                <textarea class="form-control @error('address') is-invalid @enderror"
                                          id="address" name="address" rows="2">{{ old('address', $client->address) }}</textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">Ville <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('city') is-invalid @enderror"
                                       id="city" name="city" value="{{ old('city', $client->city) }}" required>
                                @error('city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="region" class="form-label">Région</label>
                                <input type="text" class="form-control @error('region') is-invalid @enderror"
                                       id="region" name="region" value="{{ old('region', $client->region) }}">
                                @error('region')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informations d'identification -->
                @if(auth()->user()->role !== 'agent_terrain')
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-id-card me-2"></i>Pièce d'Identité
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="id_type" class="form-label">Type de Pièce <span class="text-danger">*</span></label>
                                <select class="form-select @error('id_type') is-invalid @enderror"
                                        id="id_type" name="id_type" required>
                                    <option value="">Sélectionner...</option>
                                    <option value="cni" {{ old('id_type', $client->id_type) == 'cni' ? 'selected' : '' }}>Carte d'identité nationale</option>
                                    <option value="passport" {{ old('id_type', $client->id_type) == 'passport' ? 'selected' : '' }}>Passeport</option>
                                    <option value="driving_license" {{ old('id_type', $client->id_type) == 'driving_license' ? 'selected' : '' }}>Permis de conduire</option>
                                    <option value="other" {{ old('id_type', $client->id_type) == 'other' ? 'selected' : '' }}>Autre</option>
                                </select>
                                @error('id_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="id_number" class="form-label">Numéro de Pièce <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('id_number') is-invalid @enderror"
                                       id="id_number" name="id_number" value="{{ old('id_number', $client->id_number) }}" required>
                                @error('id_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="id_expiry_date" class="form-label">Date d'Expiration</label>
                                <input type="date" class="form-control @error('id_expiry_date') is-invalid @enderror"
                                       id="id_expiry_date" name="id_expiry_date" value="{{ old('id_expiry_date', $client->id_expiry_date) }}">
                                @error('id_expiry_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Parrainage (optionnel) -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-users me-2"></i>Parrainage (Optionnel)
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="referred_by" class="form-label">Numéro du Parrain</label>
                                <input type="text" class="form-control @error('referred_by') is-invalid @enderror"
                                       id="referred_by" name="referred_by" value="{{ old('referred_by', $client->referred_by) }}"
                                       placeholder="CLT-XXXX">
                                @error('referred_by')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="relationship" class="form-label">Relation avec le Parrain</label>
                                <input type="text" class="form-control @error('relationship') is-invalid @enderror"
                                       id="relationship" name="relationship" value="{{ old('relationship', $client->relationship) }}"
                                       placeholder="Ex: Ami, Famille, Collègue...">
                                @error('relationship')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Colonne de droite -->
            <div class="col-lg-4">
                <!-- Photo de profil -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-camera me-2"></i>Photo de Profil
                        </h6>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-3">
                            @if($client->profile_photo_url)
                                <img id="profilePreview" src="{{ Storage::url($client->profile_photo_url) }}"
                                     alt="Photo de profil"
                                     class="img-thumbnail rounded-circle"
                                     style="width: 200px; height: 200px; object-fit: cover;">
                            @else
                                <img id="profilePreview" src="{{ asset('images/default-avatar.png') }}"
                                     alt="Photo de profil"
                                     class="img-thumbnail rounded-circle"
                                     style="width: 200px; height: 200px; object-fit: cover;">
                            @endif
                        </div>
                        <input type="file" class="form-control @error('profile_photo') is-invalid @enderror"
                               id="profile_photo" name="profile_photo" accept="image/*">
                        @error('profile_photo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted d-block mt-2">Format: JPG, PNG. Max: 2MB</small>
                        <small class="text-muted">Laissez vide pour conserver la photo actuelle</small>
                    </div>
                </div>

                <!-- Modifier le mot de passe -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-lock me-2"></i>Modifier le Mot de Passe
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="password" class="form-label">Nouveau Mot de Passe</label>
                            <div class="input-group">
                                <input type="password" class="form-control @error('password') is-invalid @enderror"
                                       id="password" name="password">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Laissez vide pour ne pas changer</small>
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirmer Mot de Passe</label>
                            <input type="password" class="form-control"
                                   id="password_confirmation" name="password_confirmation">
                        </div>

                        <div class="alert alert-info">
                            <small>
                                <i class="fas fa-info-circle me-1"></i>
                                Ne remplissez ces champs que si vous voulez changer le mot de passe.
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary btn-lg w-100 mb-2">
                            <i class="fas fa-save me-2"></i>Enregistrer les Modifications
                        </button>
                        <a href="{{ route('agent.clients.show', $client->id) }}" class="btn btn-secondary btn-lg w-100">
                            <i class="fas fa-times me-2"></i>Annuler
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    // Prévisualisation de la photo
    document.getElementById('profile_photo').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profilePreview').src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });

    // Toggle password visibility
    document.getElementById('togglePassword').addEventListener('click', function() {
        const password = document.getElementById('password');
        const icon = this.querySelector('i');

        if (password.type === 'password') {
            password.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            password.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });

    // Validation du formulaire
    document.getElementById('clientForm').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const passwordConfirm = document.getElementById('password_confirmation').value;

        if (password && password !== passwordConfirm) {
            e.preventDefault();
            alert('Les mots de passe ne correspondent pas!');
            return false;
        }

        // Vérifier l'âge (18 ans minimum)
        const dob = new Date(document.getElementById('date_of_birth').value);
        const today = new Date();
        const age = today.getFullYear() - dob.getFullYear();

        if (age < 18) {
            e.preventDefault();
            alert('Le client doit avoir au moins 18 ans!');
            return false;
        }
    });
</script>
@endpush
