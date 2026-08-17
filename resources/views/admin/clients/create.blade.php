@extends('layouts.app_admin')

@section('title', 'Enregistrement de Nouvelle Entité Adhérente')
@section('page-title', 'Protocole / Captation d\'Adhérent')

@section('content')
<div class="max-w-4xl mx-auto space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex items-center justify-between no-print">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.clients.index') }}" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-xl flex items-center justify-center transition border border-slate-200 text-slate-600">
                <i class="fas fa-chevron-left text-xs"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Captation d'Adhérent</h2>
                <p class="text-slate-500 text-sm font-medium">Initialisation d'une nouvelle entité dans le registre institutionnel</p>
            </div>
        </div>
    </div>

    <!-- Alertes de Validation -->
    @if ($errors->any())
        <div class="bank-card !border-rose-200 !bg-rose-50/50 p-6">
            <div class="flex gap-3">
                <i class="fas fa-triangle-exclamation text-rose-500 mt-1"></i>
                <div>
                    <h3 class="text-sm font-black text-rose-900 uppercase tracking-widest leading-none">Anomalies de Protocole</h3>
                    <ul class="mt-3 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li class="text-[11px] font-bold text-rose-700 list-disc list-inside">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <!-- Formulaire de Captation -->
    <form action="{{ route('admin.clients.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
        @csrf

        <!-- Segment : Identité Civile -->
        <div class="bank-card p-8">
            <div class="flex items-center gap-3 mb-8">
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Attributs d'Identité Civile</h3>
                    <p class="text-[10px] text-slate-400 font-bold uppercase mt-0.5">Données obligatoires pour l'audit KYC</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Prénoms de l'Entité *</label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" required class="bank-input" placeholder="Ex: Jean Paul">
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Nom Patronymique *</label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" required class="bank-input" placeholder="Ex: KOFFI">
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Canal Téléphonique *</label>
                    <input type="tel" name="phone" value="{{ old('phone') }}" required placeholder="+228 XX XX XX XX" class="bank-input font-mono">
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Courrier Électronique</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="bank-input" placeholder="entite@finance.tg">
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Date de Naissance</label>
                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" class="bank-input">
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Genre / Sexe</label>
                    <select name="gender" class="bank-input uppercase">
                        <option value="">Sélectionner...</option>
                        <option value="M" {{ old('gender') == 'M' ? 'selected' : '' }}>Masculin (M)</option>
                        <option value="F" {{ old('gender') == 'F' ? 'selected' : '' }}>Féminin (F)</option>
                        <option value="Other" {{ old('gender') == 'Other' ? 'selected' : '' }}>Autre</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Secteur d'Activité / Profession</label>
                    <input type="text" name="profession" value="{{ old('profession') }}" class="bank-input" placeholder="Ex: Commerçant, Fonctionnaire...">
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Revenu Mensuel Estimé (XOF)</label>
                    <input type="number" name="monthly_income" value="{{ old('monthly_income') }}" step="1" class="bank-input font-bold" placeholder="0">
                </div>


            </div>
        </div>

        <!-- Segment : Localisation Géographique -->
        <div class="bank-card p-8">
            <div class="flex items-center gap-3 mb-8">
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">
                    <i class="fas fa-location-dot"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Infrastructure de Résidence</h3>
                    <p class="text-[10px] text-slate-400 font-bold uppercase mt-0.5">Point d'ancrage géographique de l'adhérent</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2 md:col-span-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Adresse Physique Détaillée *</label>
                    <input type="text" name="address" value="{{ old('address') }}" required class="bank-input" placeholder="Quartier, Rue, N° de Porte...">
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Ville / Localisation</label>
                    <input type="text" name="city" value="{{ old('city') }}" class="bank-input" placeholder="Ex: Lomé">
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Région Administrative</label>
                    <input type="text" name="region" value="{{ old('region') }}" class="bank-input" placeholder="Ex: Maritime">
                </div>
            </div>
        </div>

        <!-- Segment : Artefacts d'Identification -->
        <div class="bank-card p-8">
            <div class="flex items-center gap-3 mb-8">
                <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-xs">
                    <i class="fas fa-id-card"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Artefacts d'Identification</h3>
                    <p class="text-[10px] text-slate-400 font-bold uppercase mt-0.5">Documents officiels pour la conformité bancaire</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Type de Document</label>
                    <select name="id_type" class="bank-input uppercase">
                        <option value="">Sélectionner...</option>
                        <option value="cni" {{ old('id_type') == 'cni' ? 'selected' : '' }}>Carte Nationale d'Identité (CNI)</option>
                        <option value="passport" {{ old('id_type') == 'passport' ? 'selected' : '' }}>Passeport International</option>
                        <option value="driving_license" {{ old('id_type') == 'driving_license' ? 'selected' : '' }}>Permis de Conduire</option>
                        <option value="other" {{ old('id_type') == 'other' ? 'selected' : '' }}>Autre Document Officiel</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Numéro de Série du Document</label>
                    <input type="text" name="id_number" value="{{ old('id_number') }}" class="bank-input font-mono" placeholder="S/N: XXXXXXXX">
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Échéance de Validité</label>
                    <input type="date" name="id_expiry_date" value="{{ old('id_expiry_date') }}" class="bank-input">
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Artefact de Profil (Photo)</label>
                    <div class="relative">
                        <input type="file" name="profile_photo" accept="image/*" class="bank-input file:mr-4 file:py-1 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-black file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                    <p class="text-[9px] text-slate-400 font-bold uppercase mt-1">Formats autorisés : JPG, PNG (Max 2 Mo)</p>
                </div>
            </div>
        </div>

        <!-- Segment : Parrainage & Réseau -->
        <div class="bank-card p-8">
            <div class="flex items-center gap-3 mb-8">
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-xs">
                    <i class="fas fa-sitemap"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Parrainage & Réseau</h3>
                    <p class="text-[10px] text-slate-400 font-bold uppercase mt-0.5">Origine de la captation de l'entité</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Identifiant du Parrain</label>
                    <input type="text" name="referred_by" value="{{ old('referred_by') }}" placeholder="Ex: CLT-XXXX" class="bank-input font-mono">
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Nature du Lien</label>
                    <input type="text" name="relationship" value="{{ old('relationship') }}" placeholder="Ex: Professionnel, Familial..." class="bank-input">
                </div>
            </div>
        </div>

        <!-- Segment : Gouvernance & Sécurité -->
        <div class="bank-card p-8">
            <div class="flex items-center gap-3 mb-8">
                <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-xs">
                    <i class="fas fa-key"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Gouvernance & Sécurité</h3>
                    <p class="text-[10px] text-slate-400 font-bold uppercase mt-0.5">Protocoles d'accès sécurisés</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @if(auth()->user()->role === 'super_admin')
                <div class="space-y-2 md:col-span-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">compte d'Affectation (Agence) *</label>
                    <select name="agency_id" required class="bank-input uppercase font-bold">
                        <option value="">Affecter à une agence...</option>
                        @foreach($agencies as $agency)
                            <option value="{{ $agency->id }}" {{ old('agency_id') == $agency->id ? 'selected' : '' }}>
                                {{ $agency->name }} — Division {{ $agency->city }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Clé d'Accès Initiale (Mot de passe)</label>
                    <input type="password" name="password" class="bank-input" placeholder="••••••••">
                    <p class="text-[9px] text-slate-400 font-bold uppercase mt-1">Laisse vide pour utiliser : <span class="text-blue-600">12@4</span></p>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Confirmation de la Clé</label>
                    <input type="password" name="password_confirmation" class="bank-input" placeholder="••••••••">
                </div>
            </div>
        </div>

        <!-- Validation Finale -->
        <div class="flex items-center justify-end gap-4 pb-12">
            <a href="{{ route('admin.clients.index') }}" class="btn-bank btn-bank-outline px-12">
                Abandonner le Protocole
            </a>
            <button type="submit" class="btn-bank btn-bank-primary px-12 py-3 text-sm">
                Enregistrer l'Entité dans le Registre
            </button>
        </div>
    </form>
</div>
@endsection
