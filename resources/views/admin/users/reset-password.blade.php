@extends('layouts.admin')
@section('title', 'Réinitialiser le mot de passe')
@section('page-title', 'Réinitialiser le mot de passe')
@section('content')
    <div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">{{ $user->first_name }} {{ $user->last_name }}</h2>
        <form method="POST" action="{{ route('admin.users.reset-password', $user) }}">
            @csrf
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700">
                    <input type="checkbox" name="send_notification" class="mr-2">
                    Envoyer une notification à l'utilisateur
                </label>
            </div>
            <div class="flex justify-end gap-4">
                <a href="{{ route('admin.users.show', $user) }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">Annuler</a>
                <button type="submit" class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700">
                    <i class="fas fa-key mr-2"></i> Réinitialiser
                </button>
            </div>
        </form>
    </div>
@endsection
