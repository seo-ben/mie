@extends('layouts.app_admin')

@section('content')
<div class="min-h-screen py-8 bg-gray-50">
    <div class="max-w-4xl px-4 mx-auto sm:px-6 lg:px-8">

        <!-- En-tête -->
        <div class="mb-8">
            <a href="{{ route('admin.clients.index') }}" class="inline-flex items-center mb-4 text-sm text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Retour à la liste
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Nouveau Client</h1>
            <p class="mt-2 text-sm text-gray-600">Enregistrez un nouveau client dans le système</p>
        </div>

        <!-- Messages d'erreur -->
        @if ($errors->any())
            <div class="p-4 mb-6 border-l-4 border-red-500 rounded bg-red-50">
                <div class="flex">
                    <svg class="w-5 h-5 mr-2 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <h3 class="font-medium text-red-800">Erreurs de validation</h3>
                        <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <!-- Formulaire -->
        <form action="{{ route('admin.clients.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- Informations personnelles -->
            <div class="p-6 bg-white rounded-lg shadow">
                <h2 class="flex items-center mb-4 text-lg font-semibold text-gray-900">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Informations Personnelles
                </h2>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Prénom *</label>
                        <input type="text" name="first_name" value="{{ old('first_name') }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Nom *</label>
                        <input type="text" name="last_name" value="{{ old('last_name') }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Téléphone *</label>
                        <input type="tel" name="phone" value="{{ old('phone') }}" required
                               placeholder="+228 XX XX XX XX"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Date de naissance *</label>
                        <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Genre *</label>
                        <select name="gender" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Sélectionner...</option>
                            <option value="M" {{ old('gender') == 'M' ? 'selected' : '' }}>Masculin</option>
                            <option value="F" {{ old('gender') == 'F' ? 'selected' : '' }}>Féminin</option>
                            <option value="Other" {{ old('gender') == 'Other' ? 'selected' : '' }}>Autre</option>
                        </select>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Profession</label>
                        <input type="text" name="profession" value="{{ old('profession') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Adresse</label>
                        <input type="text" name="address" value="{{ old('address') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Ville *</label>
                        <input type="text" name="city" value="{{ old('city') }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Région</label>
                        <input type="text" name="region" value="{{ old('region') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Revenu mensuel (FCFA)</label>
                        <input type="number" name="monthly_income" value="{{ old('monthly_income') }}" step="0.01"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
            </div>

            <!-- Pièce d'identité -->
            <div class="p-6 bg-white rounded-lg shadow">
                <h2 class="flex items-center mb-4 text-lg font-semibold text-gray-900">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0"/>
                    </svg>
                    Pièce d'Identité
                </h2>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Type de pièce *</label>
                        <select name="id_type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Sélectionner...</option>
                            <option value="cni" {{ old('id_type') == 'cni' ? 'selected' : '' }}>Carte d'identité nationale</option>
                            <option value="passport" {{ old('id_type') == 'passport' ? 'selected' : '' }}>Passeport</option>
                            <option value="driving_license" {{ old('id_type') == 'driving_license' ? 'selected' : '' }}>Permis de conduire</option>
                            <option value="other" {{ old('id_type') == 'other' ? 'selected' : '' }}>Autre</option>
                        </select>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Numéro de pièce *</label>
                        <input type="text" name="id_number" value="{{ old('id_number') }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Date d'expiration</label>
                        <input type="date" name="id_expiry_date" value="{{ old('id_expiry_date') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Photo de profil</label>
                        <input type="file" name="profile_photo" accept="image/*"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="mt-1 text-xs text-gray-500">Format: JPG, PNG (max 2MB)</p>
                    </div>
                </div>
            </div>

            <!-- Parrainage (optionnel) -->
            <div class="p-6 bg-white rounded-lg shadow">
                <h2 class="flex items-center mb-4 text-lg font-semibold text-gray-900">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Parrainage (Optionnel)
                </h2>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Numéro du parrain</label>
                        <input type="text" name="referred_by" value="{{ old('referred_by') }}"
                               placeholder="CLT-XXXX"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Relation avec le parrain</label>
                        <input type="text" name="relationship" value="{{ old('relationship') }}"
                               placeholder="Ex: Ami, Famille, Collègue..."
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
            </div>

            <!-- Agence (pour super admin) -->
            @if(auth()->user()->role === 'super_admin')
            <div class="p-6 bg-white rounded-lg shadow">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">Agence</h2>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">Agence *</label>
                    <select name="agency_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">Sélectionner une agence...</option>
                        @foreach($agencies as $agency)
                            <option value="{{ $agency->id }}" {{ old('agency_id') == $agency->id ? 'selected' : '' }}>
                                {{ $agency->name }} - {{ $agency->city }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            @endif

            <!-- Mot de passe -->
            <div class="p-6 bg-white rounded-lg shadow">
                <h2 class="flex items-center mb-4 text-lg font-semibold text-gray-900">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Sécurité
                </h2>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Mot de passe *</label>
                        <input type="password" name="password" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="mt-1 text-xs text-gray-500">Minimum 8 caractères</p>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Confirmer le mot de passe *</label>
                        <input type="password" name="password_confirmation" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="flex items-center justify-end space-x-4">
                <a href="{{ route('admin.clients.index') }}"
                   class="px-6 py-2 text-gray-700 transition border border-gray-300 rounded-lg hover:bg-gray-50">
                    Annuler
                </a>
                <button type="submit"
                        class="px-6 py-2 font-medium text-white transition bg-blue-600 rounded-lg shadow hover:bg-blue-700">
                    Enregistrer le client
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
