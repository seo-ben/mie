@extends('layouts.app_admin')

@section('title', 'Gestion des Agences')

@section('content')
<div class="space-y-6">
    <!-- Header avec bouton d'action -->
    <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Gestion des Agences</h2>
            <p class="text-sm text-gray-600">Gérez vos agences et leurs informations</p>
        </div>
        <button onclick="openCreateModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg flex items-center gap-2 transition">
            <i class="fas fa-plus"></i>
            <span>Nouvelle Agence</span>
        </button>
    </div>

    <!-- Messages Flash -->
    @if(session('success'))
    <div class="flex items-center gap-3 p-4 border-l-4 border-green-500 rounded-lg bg-green-50">
        <i class="text-xl text-green-500 fas fa-check-circle"></i>
        <p class="text-green-700">{{ session('success') }}</p>
    </div>
    @endif

    @if(session('error'))
    <div class="flex items-center gap-3 p-4 border-l-4 border-red-500 rounded-lg bg-red-50">
        <i class="text-xl text-red-500 fas fa-exclamation-circle"></i>
        <p class="text-red-700">{{ session('error') }}</p>
    </div>
    @endif

    <!-- Filtres et Recherche -->
    <div class="p-4 bg-white shadow-sm rounded-xl">
        <form method="GET" action="{{ route('admin.agencies.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div class="md:col-span-2">
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher par nom, code ou ville..." class="w-full py-2 pl-10 pr-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <i class="absolute text-gray-400 fas fa-search left-3 top-3"></i>
                </div>
            </div>

            <select name="region" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="">Toutes les régions</option>
                <option value="Maritime" {{ request('region') == 'Maritime' ? 'selected' : '' }}>Maritime</option>
                <option value="Plateaux" {{ request('region') == 'Plateaux' ? 'selected' : '' }}>Plateaux</option>
                <option value="Centrale" {{ request('region') == 'Centrale' ? 'selected' : '' }}>Centrale</option>
                <option value="Kara" {{ request('region') == 'Kara' ? 'selected' : '' }}>Kara</option>
                <option value="Savanes" {{ request('region') == 'Savanes' ? 'selected' : '' }}>Savanes</option>
            </select>

            <select name="is_active" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="">Tous les statuts</option>
                <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Actif</option>
                <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactif</option>
            </select>

            <div class="flex gap-2 md:col-span-4">
                <button type="submit" class="px-6 py-2 text-white transition bg-blue-600 rounded-lg hover:bg-blue-700">
                    <i class="mr-2 fas fa-filter"></i>Filtrer
                </button>
                <a href="{{ route('admin.agencies.index') }}" class="px-6 py-2 text-gray-700 transition bg-gray-200 rounded-lg hover:bg-gray-300">
                    <i class="mr-2 fas fa-redo"></i>Réinitialiser
                </a>
            </div>
        </form>
    </div>

    <!-- Liste des Agences -->
    <div class="overflow-hidden bg-white shadow-sm rounded-xl">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-xs font-semibold text-left text-gray-600 uppercase">Code</th>
                        <th class="px-6 py-3 text-xs font-semibold text-left text-gray-600 uppercase">Nom</th>
                        <th class="px-6 py-3 text-xs font-semibold text-left text-gray-600 uppercase">Ville</th>
                        <th class="px-6 py-3 text-xs font-semibold text-left text-gray-600 uppercase">Région</th>
                        <th class="px-6 py-3 text-xs font-semibold text-left text-gray-600 uppercase">Manager</th>
                        <th class="px-6 py-3 text-xs font-semibold text-left text-gray-600 uppercase">Utilisateurs</th>
                        <th class="px-6 py-3 text-xs font-semibold text-left text-gray-600 uppercase">Statut</th>
                        <th class="px-6 py-3 text-xs font-semibold text-right text-gray-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($agencies as $agency)
                    <tr class="transition hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <span class="font-mono text-sm font-semibold text-gray-700">{{ $agency->code }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-10 h-10 bg-blue-100 rounded-lg">
                                    <i class="text-blue-600 fas fa-building"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800">{{ $agency->name }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ $agency->city }}</td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 text-xs font-medium text-purple-700 bg-purple-100 rounded-full">
                                {{ $agency->region }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if($agency->manager)
                                <div class="flex items-center gap-2">
                                    <div class="flex items-center justify-center w-8 h-8 text-xs font-semibold text-white rounded-full bg-gradient-to-br from-blue-400 to-purple-500">
                                        {{ strtoupper(substr($agency->manager->first_name, 0, 1) . substr($agency->manager->last_name, 0, 1)) }}
                                    </div>
                                    <span class="text-sm text-gray-700">{{ $agency->manager->full_name }}</span>
                                </div>
                            @else
                                <span class="text-sm italic text-gray-400">Non assigné</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs font-medium text-blue-700 bg-blue-100 rounded-full">
                                {{ $agency->users->count() }} utilisateur(s)
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if($agency->is_active)
                                <span class="flex items-center gap-1 px-3 py-1 text-xs font-medium text-green-700 bg-green-100 rounded-full w-fit">
                                    <i class="fas fa-check-circle"></i>
                                    Actif
                                </span>
                            @else
                                <span class="flex items-center gap-1 px-3 py-1 text-xs font-medium text-red-700 bg-red-100 rounded-full w-fit">
                                    <i class="fas fa-times-circle"></i>
                                    Inactif
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button onclick="viewAgency({{ $agency->id }})" class="p-2 text-blue-600 transition rounded-lg hover:bg-blue-50" title="Voir détails">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="openEditModal({{ $agency->id }})" class="p-2 text-green-600 transition rounded-lg hover:bg-green-50" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="confirmDelete({{ $agency->id }}, '{{ $agency->name }}')" class="p-2 text-red-600 transition rounded-lg hover:bg-red-50" title="Désactiver">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                            <i class="mb-4 text-4xl text-gray-300 fas fa-building"></i>
                            <p class="text-lg font-medium">Aucune agence trouvée</p>
                            <p class="text-sm">Créez votre première agence pour commencer</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $agencies->links() }}
        </div>
    </div>
</div>

<!-- Modal Création/Édition -->
<div id="agencyModal" class="fixed inset-0 z-50 flex items-center justify-center hidden p-4 bg-black bg-opacity-50">
    <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-6 border-b">
            <h3 id="modalTitle" class="text-xl font-bold text-gray-800">Nouvelle Agence</h3>
            <button onclick="closeModal()" class="text-gray-400 transition hover:text-gray-600">
                <i class="text-xl fas fa-times"></i>
            </button>
        </div>

        <form id="agencyForm" method="POST" class="p-6 space-y-4">
            @csrf
            <input type="hidden" id="methodField" name="_method" value="POST">

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="block mb-2 text-sm font-semibold text-gray-700">
                        Nom de l'agence <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block mb-2 text-sm font-semibold text-gray-700">
                        Code <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="code" id="code" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block mb-2 text-sm font-semibold text-gray-700">
                        Ville <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="city" id="city" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block mb-2 text-sm font-semibold text-gray-700">
                        Région <span class="text-red-500">*</span>
                    </label>
                    <select name="region" id="region" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">Sélectionner une région</option>
                        <option value="Maritime">Maritime</option>
                        <option value="Plateaux">Plateaux</option>
                        <option value="Centrale">Centrale</option>
                        <option value="Kara">Kara</option>
                        <option value="Savanes">Savanes</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block mb-2 text-sm font-semibold text-gray-700">
                        Manager <span class="text-xs text-gray-400">(Optionnel - Sélectionnez parmi les utilisateurs de l'agence)</span>
                    </label>
                    <select name="manager_id" id="manager_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">Aucun manager</option>
                    </select>
                    <p id="managerHint" class="hidden mt-1 text-xs text-gray-500">
                        <i class="fas fa-info-circle"></i> Les managers sont choisis parmi les utilisateurs affectés à cette agence
                    </p>
                </div>

                <div class="flex items-center md:col-span-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" id="is_active" value="1" checked class="w-5 h-5 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">
                        <span class="text-sm font-semibold text-gray-700">Agence active</span>
                    </label>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t">
                <button type="button" onclick="closeModal()" class="px-6 py-2 text-gray-700 transition border border-gray-300 rounded-lg hover:bg-gray-50">
                    Annuler
                </button>
                <button type="submit" class="px-6 py-2 text-white transition bg-blue-600 rounded-lg hover:bg-blue-700">
                    <i class="mr-2 fas fa-save"></i>
                    <span id="submitBtnText">Créer l'agence</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Visualisation -->
<div id="viewModal" class="fixed inset-0 z-50 flex items-center justify-center hidden p-4 bg-black bg-opacity-50">
    <div class="bg-white rounded-xl shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-6 border-b">
            <h3 class="text-xl font-bold text-gray-800">Détails de l'agence</h3>
            <button onclick="closeViewModal()" class="text-gray-400 transition hover:text-gray-600">
                <i class="text-xl fas fa-times"></i>
            </button>
        </div>

        <div id="viewModalContent" class="p-6">
            <div class="flex items-center justify-center py-8">
                <div class="w-12 h-12 border-b-2 border-blue-600 rounded-full animate-spin"></div>
            </div>
        </div>
    </div>
</div>

<script>
// Variables globales
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

// Ouvrir le modal de création
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Nouvelle Agence';
    document.getElementById('submitBtnText').textContent = 'Créer l\'agence';
    document.getElementById('agencyForm').action = "{{ route('admin.agencies.store') }}";
    document.getElementById('methodField').value = 'POST';
    document.getElementById('agencyForm').reset();
    document.getElementById('is_active').checked = true;

    // Désactiver le select manager pour la création
    const managerSelect = document.getElementById('manager_id');
    managerSelect.innerHTML = '<option value="">Assignez d\'abord des utilisateurs à cette agence</option>';
    managerSelect.disabled = true;

    document.getElementById('managerHint').classList.remove('hidden');
    document.getElementById('agencyModal').classList.remove('hidden');
}

// Ouvrir le modal d'édition
async function openEditModal(agencyId) {
    try {
        // Afficher le modal immédiatement avec un loader
        document.getElementById('modalTitle').textContent = 'Modifier l\'agence';
        document.getElementById('submitBtnText').textContent = 'Mettre à jour';
        document.getElementById('agencyModal').classList.remove('hidden');

        const managerSelect = document.getElementById('manager_id');
        managerSelect.innerHTML = '<option value="">Chargement...</option>';
        managerSelect.disabled = true;

        // Charger les données de l'agence
        const response = await fetch(`/admin/agencies/${agencyId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }

        const data = await response.json();

        if (!data.success || !data.data) {
            throw new Error('Format de réponse invalide');
        }

        const agency = data.data;

        // Remplir le formulaire
        document.getElementById('agencyForm').action = `/admin/agencies/${agencyId}`;
        document.getElementById('methodField').value = 'PUT';
        document.getElementById('name').value = agency.name || '';
        document.getElementById('code').value = agency.code || '';
        document.getElementById('city').value = agency.city || '';
        document.getElementById('region').value = agency.region || '';
        document.getElementById('is_active').checked = !!agency.is_active;

        // Charger les utilisateurs de cette agence pour le select manager
        await loadAgencyUsers(agencyId, agency.manager_id);

        document.getElementById('managerHint').classList.remove('hidden');

    } catch (error) {
        console.error('Erreur lors du chargement:', error);
        alert('Erreur lors du chargement des données: ' + error.message);
        closeModal();
    }
}

// Charger les utilisateurs d'une agence
async function loadAgencyUsers(agencyId, currentManagerId = null) {
    const managerSelect = document.getElementById('manager_id');

    try {
        const response = await fetch(`/admin/agencies/${agencyId}/users`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Erreur lors du chargement des utilisateurs');
        }

        // Réinitialiser le select
        managerSelect.innerHTML = '<option value="">Aucun manager</option>';
        managerSelect.disabled = false;

        if (data.data && data.data.length > 0) {
            data.data.forEach(user => {
                const option = document.createElement('option');
                option.value = user.id;

                // Afficher nom complet + rôle + email
                const displayText = `${user.first_name} ${user.last_name} - ${user.role}`;
                option.textContent = displayText;
                option.title = user.email; // Tooltip avec l'email

                // Sélectionner le manager actuel
                if (currentManagerId && user.id == currentManagerId) {
                    option.selected = true;
                }

                managerSelect.appendChild(option);
            });
        } else {
            managerSelect.innerHTML = '<option value="">Aucun utilisateur dans cette agence</option>';
            managerSelect.disabled = true;
        }

    } catch (error) {
        console.error('Erreur lors du chargement des utilisateurs:', error);
        managerSelect.innerHTML = '<option value="">Erreur de chargement</option>';
        managerSelect.disabled = true;

        // Afficher un message d'erreur discret
        const hint = document.getElementById('managerHint');
        hint.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Impossible de charger les utilisateurs';
        hint.classList.remove('hidden', 'text-gray-500');
        hint.classList.add('text-red-500');
    }
}

// Visualiser une agence
async function viewAgency(agencyId) {
    try {
        // Afficher le modal avec un loader
        document.getElementById('viewModal').classList.remove('hidden');
        document.getElementById('viewModalContent').innerHTML = `
            <div class="flex items-center justify-center py-12">
                <div class="w-12 h-12 border-b-2 border-blue-600 rounded-full animate-spin"></div>
            </div>
        `;

        const response = await fetch(`/admin/agencies/${agencyId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }

        const data = await response.json();

        if (!data.success || !data.data) {
            throw new Error('Format de réponse invalide');
        }

        const agency = data.data;

        // Construire la liste des utilisateurs
        let usersList = '';
        if (agency.users && agency.users.length > 0) {
            usersList = '<div class="mt-2 space-y-2">';
            agency.users.forEach(user => {
                const isManager = agency.manager_id == user.id;
                usersList += `
                    <div class="flex items-center gap-2 p-2 rounded bg-gray-50">
                        <div class="flex items-center justify-center w-8 h-8 text-xs font-semibold text-white rounded-full bg-gradient-to-br from-blue-400 to-purple-500">
                            ${user.first_name.charAt(0)}${user.last_name.charAt(0)}
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-800">${user.first_name} ${user.last_name}</p>
                            <p class="text-xs text-gray-500">${user.role}</p>
                        </div>
                        ${isManager ? '<span class="px-2 py-1 text-xs text-yellow-700 bg-yellow-100 rounded-full">Manager</span>' : ''}
                    </div>
                `;
            });
            usersList += '</div>';
        } else {
            usersList = '<p class="mt-2 text-sm italic text-gray-400">Aucun utilisateur</p>';
        }

        const content = `
            <div class="space-y-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="p-4 rounded-lg bg-gray-50">
                        <p class="mb-1 text-sm text-gray-600">Nom de l'agence</p>
                        <p class="font-semibold text-gray-800">${agency.name}</p>
                    </div>
                    <div class="p-4 rounded-lg bg-gray-50">
                        <p class="mb-1 text-sm text-gray-600">Code</p>
                        <p class="font-mono font-semibold text-gray-800">${agency.code}</p>
                    </div>
                    <div class="p-4 rounded-lg bg-gray-50">
                        <p class="mb-1 text-sm text-gray-600">Ville</p>
                        <p class="font-semibold text-gray-800">${agency.city}</p>
                    </div>
                    <div class="p-4 rounded-lg bg-gray-50">
                        <p class="mb-1 text-sm text-gray-600">Région</p>
                        <p class="font-semibold text-gray-800">${agency.region}</p>
                    </div>
                    <div class="p-4 rounded-lg bg-gray-50">
                        <p class="mb-1 text-sm text-gray-600">Manager</p>
                        <p class="font-semibold text-gray-800">${agency.manager ? agency.manager.full_name : '<span class="italic text-gray-400">Non assigné</span>'}</p>
                    </div>
                    <div class="p-4 rounded-lg bg-gray-50">
                        <p class="mb-1 text-sm text-gray-600">Statut</p>
                        <span class="inline-flex px-3 py-1 ${agency.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'} rounded-full text-xs font-medium">
                            ${agency.is_active ? 'Actif' : 'Inactif'}
                        </span>
                    </div>
                    <div class="p-4 rounded-lg bg-gray-50">
                        <p class="mb-1 text-sm text-gray-600">Date de création</p>
                        <p class="font-semibold text-gray-800">${new Date(agency.created_at).toLocaleDateString('fr-FR', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        })}</p>
                    </div>
                    <div class="p-4 rounded-lg bg-gray-50">
                        <p class="mb-1 text-sm text-gray-600">Dernière modification</p>
                        <p class="font-semibold text-gray-800">${new Date(agency.updated_at).toLocaleDateString('fr-FR', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        })}</p>
                    </div>
                </div>

                <div class="p-4 rounded-lg bg-gray-50">
                    <p class="mb-2 text-sm font-semibold text-gray-600">
                        Utilisateurs de l'agence (${agency.users.length})
                    </p>
                    ${usersList}
                </div>
            </div>
        `;

        document.getElementById('viewModalContent').innerHTML = content;

    } catch (error) {
        console.error('Erreur lors de la visualisation:', error);
        document.getElementById('viewModalContent').innerHTML = `
            <div class="py-8 text-center">
                <i class="mb-4 text-4xl text-red-500 fas fa-exclamation-triangle"></i>
                <p class="font-medium text-red-600">Erreur lors du chargement des données</p>
                <p class="mt-2 text-sm text-gray-500">${error.message}</p>
            </div>
        `;
    }
}

// Confirmer la suppression
function confirmDelete(agencyId, agencyName) {
    if (confirm(`⚠️ Êtes-vous sûr de vouloir désactiver l'agence "${agencyName}" ?\n\nCette action désactivera l'agence mais ne supprimera pas ses données.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/agencies/${agencyId}`;

        form.innerHTML = `
            <input type="hidden" name="_token" value="${csrfToken}">
            <input type="hidden" name="_method" value="DELETE">
        `;

        document.body.appendChild(form);
        form.submit();
    }
}

// Fermer le modal de création/édition
function closeModal() {
    document.getElementById('agencyModal').classList.add('hidden');
    document.getElementById('agencyForm').reset();
    document.getElementById('managerHint').classList.add('hidden');
}

// Fermer le modal de visualisation
function closeViewModal() {
    document.getElementById('viewModal').classList.add('hidden');
}

// Fermer les modals en cliquant en dehors
document.getElementById('agencyModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

document.getElementById('viewModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeViewModal();
    }
});

// Fermer les modals avec la touche Échap
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
        closeViewModal();
    }
});

// Masquer les messages flash après 5 secondes
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.bg-green-50, .bg-red-50');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});
</script>
@endsection
