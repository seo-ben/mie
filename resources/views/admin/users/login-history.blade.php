@extends('layouts.admin')
@section('title', 'Gérer 2FA')
@section('page-title', 'Gérer 2FA')
@section('content')
    <div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">{{ $user->first_name }} {{ $user->last_name }}</h2>
        <form method="POST" action="{{ route('users.toggle-2fa', $user) }}">
            @csrf
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700">
                    <input type="checkbox" name="enable" {{ $user->mfa_enabled ? 'checked' : '' }} class="mr-2">
                    Activer l'authentification à deux facteurs
                </label>
            </div>
            <div class="flex justify-end gap-4">
                <a href="{{ route('users.show', $user) }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">Annuler</a>
                <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
                    <i class="fas fa-shield-alt mr-2"></i> Mettre à jour
                </button>
            </div>
        </form>
    </div>
@endsection
