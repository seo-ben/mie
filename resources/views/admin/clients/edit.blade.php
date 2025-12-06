@extends('layouts.app_admin')

@section('content')
<div class="min-h-screen py-8 bg-gray-50">
    <div class="max-w-4xl px-4 mx-auto sm:px-6 lg:px-8">

        <!-- En-tête -->
        <div class="mb-8">
            <a href="{{ route('admin.clients.show', $client->id) }}" class="inline-flex items-center mb-4 text-sm text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Retour au profil
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Modifier le Client</h1>
            <p class="mt-2 text-sm text-gray-600">{{ $client->full_name }} ({{ $client->client_number }})</p>
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
        <form action="{{ route('admin.clients.update', $client->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

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
                        <input type="text" name="first_name" value="{{ old('first_name', $client->first_name) }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Nom *</label>
                        <input type="text" name="last_name" value="{{ old('last_name', $client->last_name) }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Téléphone *</label>
                        <input type="tel" name="phone" value="{{ old('phone', $client->phone) }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Date de naissance *</label>
                        <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $client->date_of_birth?->format('Y-m-d')) }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Genre *</label>
                        <select name="gender" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Sélectionner...</option>
                            <option value="M" {{ old('gender', $client->gender) == 'M' ? 'selected' : '' }}>Masculin</option>
                            <option value="F" {{ old('gender', $client->gender) == 'F' ? 'selected' : '' }}>Féminin</option>
                            <option value="Other" {{ old('gender', $client->gender) == 'Other' ? 'selected' : '' }}>Autre</option>
                        </select>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Profession</label>
                        <input type="text" name="profession" value="{{ old('profession', $client->profession) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Adresse</label>
                        <input type="text" name="address" value="{{ old('address', $client->address) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Ville *</label>
                        <input type="text" name="city" value="{{ old('city', $client->city) }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Région</label>
                        <input type="text" name="region" value="{{ old('region', $client->region) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Revenu mensuel (FCFA)</label>
                        <input type="number" name="monthly_income" value="{{ old('monthly_income', $client->monthly_income) }}" step="0.01"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
            </div>

            <!-- Pièce d'identité -->
            <div class="p-6 bg-white rounded-lg shadow">
                <h2 class="flex items-center mb-4 text-lg font-semibold text-gray-900">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>
                    </svg>
                    Pièce d'Identité
                </h2>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Type de pièce *</label>
                        <select name="id_type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Sélectionner...</option>
                            <option value="cni" {{ old('id_type', $client->id_type) == 'cni' ? 'selected' : '' }}>Carte d'identité nationale</option>
                            <option value="passport" {{ old('id_type', $client->id_type) == 'passport' ? 'selected' : '' }}>Passeport</option>
                            <option value="driving_license" {{ old('id_type', $client->id_type) == 'driving_license' ? 'selected' : '' }}>Permis de conduire</option>
                            <option value="other" {{ old('id_type', $client->id_type) == 'other' ? 'selected' : '' }}>Autre</option>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Numéro de pièce *</label>
                        <input type="text" name="id_number" value="{{ old('id_number', $client->id_number) }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Date d'expiration</label>
                        <input type="date" name="id_expiry_date" value="{{ old('id_expiry_date', $client->id_expiry_date?->format('Y-m-d')) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Nouvelle photo de profil</label>
                        <input type="file" name="profile_photo" accept="image/*"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @if($client->profile_photo_url)
                            <p class="mt-1 text-xs text-gray-500">Photo actuelle disponible</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Statut -->
            <div class="p-6 bg-white rounded-lg shadow">
                <h2 class="flex items-center mb-4 text-lg font-semibold text-gray-900">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Statut du Compte
                </h2>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $client->is_active) ? 'checked' : '' }}
                                   class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Compte actif</span>
                        </label>
                        <p class="mt-1 text-xs text-gray-500">Le client peut se connecter et effectuer des transactions</p>
                    </div>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="flex items-center justify-end space-x-4">
                <a href="{{ route('admin.clients.show', $client->id) }}"
                   class="px-6 py-2 text-gray-700 transition border border-gray-300 rounded-lg hover:bg-gray-50">
                    Annuler
                </a>
                <button type="submit"
                        class="px-6 py-2 font-medium text-white transition bg-blue-600 rounded-lg shadow hover:bg-blue-700">
                    Enregistrer les modifications
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
