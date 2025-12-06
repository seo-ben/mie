@extends('layouts.app_admin')

@section('title', 'Gestion des utilisateurs')

@section('page-title', 'Gestion des utilisateurs')

@section('content')
    <div class="max-w-7xl mx-auto">
        <!-- Header avec bouton de création -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-gray-800">Liste des utilisateurs</h2>
            <a href="{{ route('admin.users.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i> Nouvel utilisateur
            </a>
        </div>

        <!-- Filtres -->
        <form method="GET" action="{{ route('admin.users.index') }}" class="mb-6 bg-white p-6 rounded-lg shadow">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700">Rechercher</label>
                    <input type="text" name="search" id="search" value="{{ $search }}" placeholder="Nom, prénom ou email" class="mt-1 w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700">Rôle</label>
                    <select name="role" id="role" class="mt-1 w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="">Tous les rôles</option>
                        <option value="administrateur_reglementaire" {{ $role === 'administrateur_reglementaire' ? 'selected' : '' }}>Administrateur Réglementaire</option>
                        <option value="administrateur_systeme" {{ $role === 'administrateur_systeme' ? 'selected' : '' }}>Administrateur Système</option>
                        <option value="agent_terrain" {{ $role === 'agent_terrain' ? 'selected' : '' }}>Agent Terrain</option>
                    </select>
                </div>
                <div>
                    <label for="is_active" class="block text-sm font-medium text-gray-700">Statut</label>
                    <select name="is_active" id="is_active" class="mt-1 w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="">Tous les statuts</option>
                        <option value="1" {{ $is_active === true ? 'selected' : '' }}>Actif</option>
                        <option value="0" {{ $is_active === false ? 'selected' : '' }}>Inactif</option>
                    </select>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-filter mr-2"></i> Filtrer
                </button>
            </div>
        </form>

        <!-- Messages flash -->
        @if (session('success'))
            <div class="bg-green-100 text-green-800 p-4 rounded-lg mb-6">
                {{ session('success') }}
                @if (session('temporary_password'))
                    <p class="mt-2"><strong>Mot de passe temporaire :</strong> {{ session('temporary_password') }}</p>
                @endif
            </div>
        @endif
        @if (session('error'))
            <div class="bg-red-100 text-red-800 p-4 rounded-lg mb-6">
                {{ session('error') }}
            </div>
        @endif

        <!-- Tableau des utilisateurs -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rôle</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $user->id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $user->first_name }} {{ $user->last_name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $user->email }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ ucfirst($user->role) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="{{ $user->is_active ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $user->is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="{{ route('admin.users.show', $user) }}" class="text-blue-600 hover:underline">Voir</a>
                                <a href="{{ route('admin.users.edit', $user) }}" class="ml-4 text-blue-600 hover:underline">Modifier</a>
                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline-block" onsubmit="return confirm('Voulez-vous vraiment désactiver cet utilisateur ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ml-4 text-red-600 hover:underline">Désactiver</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">Aucun utilisateur trouvé.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @push('scripts')
        <script>
            // AJAX pour la recherche dynamique
            document.getElementById('search')?.addEventListener('input', function(e) {
                if (e.target.value.length >= 3 || e.target.value.length === 0) {
                    fetch('{{ route('admin.users.index') }}?' + new URLSearchParams({
                        search: e.target.value,
                        role: document.getElementById('role').value,
                        is_active: document.getElementById('is_active').value
                    }), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        const tbody = document.querySelector('tbody');
                        tbody.innerHTML = '';
                        if (data.data.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">Aucun utilisateur trouvé.</td></tr>';
                            return;
                        }
                        data.data.forEach(user => {
                            tbody.innerHTML += `
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">${user.id}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">${user.first_name} ${user.last_name}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">${user.email}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">${user.role.charAt(0).toUpperCase() + user.role.slice(1)}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="${user.is_active ? 'text-green-600' : 'text-red-600'}">
                                            ${user.is_active ? 'Actif' : 'Inactif'}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <a href="{{ route('admin.users.show', ':userId') }}".replace(':userId', user.id) class="text-blue-600 hover:underline">Voir</a>
                                        <a href="{{ route('admin.users.edit', ':userId') }}".replace(':userId', user.id) class="ml-4 text-blue-600 hover:underline">Modifier</a>
                                        <form action="{{ route('admin.users.destroy', ':userId') }}".replace(':userId', user.id) method="POST" class="inline-block" onsubmit="return confirm('Voulez-vous vraiment désactiver cet utilisateur ?');">
                                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                            <input type="hidden" name="_method" value="DELETE">
                                            <button type="submit" class="ml-4 text-red-600 hover:underline">Désactiver</button>
                                        </form>
                                    </td>
                                </tr>
                            `;
                        });
                    });
                }
            });
        </script>
    @endpush
@endsection
