@extends('layouts.app_admin')

@section('title', 'Modification du Dossier Adhérent - ' . $client->full_name)
@section('page-title', 'Protocole / Mise à Jour du Registre')

@section('content')
<div class="max-w-4xl mx-auto space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex items-center justify-between no-print">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.clients.show', $client->id) }}" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-xl flex items-center justify-center transition border border-slate-200 text-slate-600">
                <i class="fas fa-chevron-left text-xs"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Mise à Jour du Dossier</h2>
                <p class="text-slate-500 text-sm font-medium">Modification des attributs de l'entité <span class="font-mono text-blue-600 font-bold">{{ $client->client_number }}</span></p>
            </div>
        </div>
    </div>

    <!-- Alertes de Validation -->
    @if (request('focus') === 'kyc_validation')
        <div class="bank-card !border-blue-200 !bg-blue-50/50 p-6 shadow-xl animate-pulse">
            <div class="flex gap-4">
                <div class="w-12 h-12 bg-blue-600 text-white rounded-2xl flex items-center justify-center shrink-0 shadow-lg">
                    <i class="fas fa-shield-check text-xl"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-blue-900 uppercase tracking-widest leading-none">Protocole de Validation KYC Forcée</h3>
                    <p class="mt-2 text-[11px] font-bold text-blue-700 uppercase leading-relaxed">
                        Vous avez été redirigé ici pour valider ce dossier afin de permettre une demande de prêt.<br>
                        <span class="text-blue-900">REQUIS :</span> Date de naissance, Genre, Adresse, Ville, Type et N° de pièce d'identité.
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="bank-card !border-rose-200 !bg-rose-50/50 p-6">
            <div class="flex gap-3">
                <i class="fas fa-triangle-exclamation text-rose-500 mt-1"></i>
                <div>
                    <h3 class="text-sm font-black text-rose-900 uppercase tracking-widest leading-none">Anomalies Documentaires</h3>
                    <ul class="mt-3 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li class="text-[11px] font-bold text-rose-700 list-disc list-inside">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <!-- Formulaire de Mise à Jour -->
    <form action="{{ route('admin.clients.update', $client->id) }}" method="POST" enctype="multipart/form-data" class="space-y-8">
        @csrf
        @method('PUT')
        
        @if(request('focus') === 'kyc_validation')
            <input type="hidden" name="force_kyc_validation" value="1">
        @endif

        <!-- Segment : Identité Civile -->
        <div class="bank-card p-8">
            <div class="flex items-center gap-3 mb-8">
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs">
                    <i class="fas fa-user-pen"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Attributs d'Identité Civile</h3>
                    <p class="text-[10px] text-slate-400 font-bold uppercase mt-0.5">Mise à jour des données de base de l'adhérent</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Prénoms de l'Entité *</label>
                    <input type="text" name="first_name" value="{{ old('first_name', $client->first_name) }}" required class="bank-input">
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Nom Patronymique *</label>
                    <input type="text" name="last_name" value="{{ old('last_name', $client->last_name) }}" required class="bank-input">
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Canal Téléphonique *</label>
                    <input type="tel" name="phone" value="{{ old('phone', $client->phone) }}" required class="bank-input font-mono">
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Date de Naissance</label>
                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $client->date_of_birth?->format('Y-m-d')) }}" class="bank-input">
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Genre / Sexe</label>
                    <select name="gender" class="bank-input uppercase">
                        <option value="M" {{ old('gender', $client->gender) == 'M' ? 'selected' : '' }}>Masculin (M)</option>
                        <option value="F" {{ old('gender', $client->gender) == 'F' ? 'selected' : '' }}>Féminin (F)</option>
                        <option value="Other" {{ old('gender', $client->gender) == 'Other' ? 'selected' : '' }}>Autre</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Secteur d'Activité / Profession</label>
                    <input type="text" name="profession" value="{{ old('profession', $client->profession) }}" class="bank-input">
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Revenu Mensuel Estimé (XOF)</label>
                    <input type="number" name="monthly_income" value="{{ old('monthly_income', $client->monthly_income) }}" step="1" class="bank-input font-bold">
                </div>
            </div>
        </div>

        <!-- Segment : Localisation & Identification -->
        <div class="bank-card p-8">
            <div class="flex items-center gap-3 mb-8">
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">
                    <i class="fas fa-map-location-dot"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Infrastructure & Identification</h3>
                    <p class="text-[10px] text-slate-400 font-bold uppercase mt-0.5">Mise à jour de la résidence et des artefacts</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2 md:col-span-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Adresse Physique Détaillée *</label>
                    <input type="text" name="address" value="{{ old('address', $client->address) }}" required class="bank-input">
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Ville / Localisation</label>
                    <input type="text" name="city" value="{{ old('city', $client->city) }}" class="bank-input">
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Région Administrative</label>
                    <input type="text" name="region" value="{{ old('region', $client->region) }}" class="bank-input">
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Type de Document</label>
                    <select name="id_type" class="bank-input uppercase">
                        <option value="cni" {{ old('id_type', $client->id_type) == 'cni' ? 'selected' : '' }}>Carte Nationale d'Identité (CNI)</option>
                        <option value="passport" {{ old('id_type', $client->id_type) == 'passport' ? 'selected' : '' }}>Passeport International</option>
                        <option value="driving_license" {{ old('id_type', $client->id_type) == 'driving_license' ? 'selected' : '' }}>Permis de Conduire</option>
                        <option value="other" {{ old('id_type', $client->id_type) == 'other' ? 'selected' : '' }}>Autre Document Officiel</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Numéro de Série du Document</label>
                    <input type="text" name="id_number" value="{{ old('id_number', $client->id_number) }}" class="bank-input font-mono">
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Échéance de Validité</label>
                    <input type="date" name="id_expiry_date" value="{{ old('id_expiry_date', $client->id_expiry_date?->format('Y-m-d')) }}" class="bank-input">
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Mise à Jour Photo Profil</label>
                    <input type="file" name="profile_photo" accept="image/*" class="bank-input file:mr-4 file:py-1 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-black file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    @if($client->profile_photo_url)
                        <p class="text-[9px] text-emerald-600 font-bold uppercase mt-1">Artefact existant détecté</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Segment : Établissement & Gouvernance -->
        <div class="bank-card p-8 bg-slate-50/50">
            <div class="flex items-center gap-3 mb-8">
                <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-xs">
                    <i class="fas fa-shield-halved"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Dispositions de Gouvernance</h3>
                    <p class="text-[10px] text-slate-400 font-bold uppercase mt-0.5">Statut opérationnel de l'entité</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="p-4 bg-white rounded-xl border border-slate-200">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $client->is_active) ? 'checked' : '' }} class="w-5 h-5 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                        <div>
                            <span class="text-sm font-black text-slate-800 uppercase tracking-tight">Entité Opérationnelle</span>
                            <p class="text-[9px] text-slate-400 font-bold uppercase">Autoriser l'accès aux services bancaires</p>
                        </div>
                    </label>
                </div>


            </div>
        </div>

        <!-- Validation Finale -->
        <div class="flex items-center justify-end gap-4 pb-12">
            <a href="{{ route('admin.clients.show', $client->id) }}" class="btn-bank btn-bank-outline px-12">
                Abandonner les Modifications
            </a>
            <button type="submit" class="btn-bank btn-bank-primary px-12 py-3 text-sm">
                Valider la Mise à Jour du Registre
            </button>
        </div>
    </form>
</div>
@endsection
