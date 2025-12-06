@extends('layouts.app_admin')
@section('title', 'Détails de l\'utilisateur')
@section('page-title', 'Détails de l\'utilisateur')
@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-gray-800">{{ $user->first_name }} {{ $user->last_name }}</h2>
                <div class="flex gap-4">
                    <a href="{{ route('admin.users.edit', $user) }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-edit mr-2"></i> Modifier
                    </a>
                    <a href="{{ route('admin.users.reset-password', $user) }}" class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700">
                        <i class="fas fa-key mr-2"></i> Réinitialiser le mot de passe
                    </a>
                    <a href="{{ route('admin.users.toggle-2fa', $user) }}" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
                        <i class="fas fa-shield-alt mr-2"></i> {{ $user->mfa_enabled ? 'Désactiver' : 'Activer' }} 2FA
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Informations</h3>
                    <dl class="space-y-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">ID</dt>
                            <dd class="text-gray-900">{{ $user->id }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Nom d'utilisateur</dt>
                            <dd class="text-gray-900">{{ $user->username }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Email</dt>
                            <dd class="text-gray-900">{{ $user->email }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Téléphone</dt>
                            <dd class="text-gray-900">{{ $user->phone }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Rôle</dt>
                            <dd class="text-gray-900">{{ ucfirst($user->role) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Agence</dt>
                            <dd class="text-gray-900">{{ $user->agency ? $user->agency->name : 'Aucune' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Statut</dt>
                            <dd class="text-gray-900 {{ $user->is_active ? 'text-green-600' : 'text-red-600' }}">
                                {{ $user->is_active ? 'Actif' : 'Inactif' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">2FA</dt>
                            <dd class="text-gray-900 {{ $user->mfa_enabled ? 'text-green-600' : 'text-red-600' }}">
                                {{ $user->mfa_enabled ? 'Activé' : 'Désactivé' }}
                            </dd>
                        </div>
                    </dl>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Clients associés</h3>
                    @if ($user->clients->isEmpty())
                        <p class="text-gray-500">Aucun client associé.</p>
                    @else
                        <ul class="space-y-2">
                            @foreach ($user->clients as $client)
                                <li class="text-gray-900">{{ $client->first_name }} {{ $client->last_name }} ({{ $client->email }})</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="mt-8">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Activités récentes</h3>
                @if ($recent_activities->isEmpty())
                    <p class="text-gray-500">Aucune activité récente.</p>
                @else
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Détails</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($recent_activities as $activity)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $activity->action }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $activity->created_at->format('d/m/Y H:i') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ json_encode($activity->additional_data) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
