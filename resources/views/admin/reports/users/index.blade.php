@extends('layouts.app_admin')

@section('title', 'Rapports des Utilisateurs')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Rapports des Utilisateurs</h2>
            <p class="text-sm text-gray-600">Analysez les performances et activités de tous les utilisateurs</p>
        </div>
        <div class="flex gap-2">
            <button onclick="openCompareModal()" class="flex items-center gap-2 px-4 py-2 text-white transition bg-purple-600 rounded-lg hover:bg-purple-700">
                <i class="fas fa-balance-scale"></i>
                <span>Comparer</span>
            </button>
            <button onclick="exportAllData()" class="flex items-center gap-2 px-4 py-2 text-white transition bg-green-600 rounded-lg hover:bg-green-700">
                <i class="fas fa-file-export"></i>
                <span>Exporter</span>
            </button>
        </div>
    </div>

    <!-- Statistiques Globales -->
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
        <div class="p-6 text-white shadow-lg bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-white/20">
                    <i class="text-2xl fas fa-users"></i>
                </div>
                <span class="px-3 py-1 text-sm rounded-full bg-white/20">Total</span>
            </div>
            <p class="text-3xl font-bold">{{ number_format($stats['total_users']) }}</p>
            <p class="mt-1 text-sm text-blue-100">Utilisateurs</p>
            <div class="flex items-center justify-between pt-3 mt-3 text-sm border-t border-white/20">
                <span>{{ $stats['active_users'] }} actifs</span>
                <span class="font-semibold">{{ $stats['total_users'] > 0 ? round(($stats['active_users'] / $stats['total_users']) * 100) : 0 }}%</span>
            </div>
        </div>

        <div class="p-6 text-white shadow-lg bg-gradient-to-br from-green-500 to-green-600 rounded-xl">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-white/20">
                    <i class="text-2xl fas fa-sign-in-alt"></i>
                </div>
                <span class="px-3 py-1 text-sm rounded-full bg-white/20">Aujourd'hui</span>
            </div>
            <p class="text-3xl font-bold">{{ number_format($stats['users_logged_today']) }}</p>
            <p class="mt-1 text-sm text-green-100">Connexions</p>
            <div class="pt-3 mt-3 text-sm border-t border-white/20">
                <span>{{ $stats['users_logged_week'] }} cette semaine</span>
            </div>
        </div>

        <div class="p-6 text-white shadow-lg bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-white/20">
                    <i class="text-2xl fas fa-user-friends"></i>
                </div>
                <span class="px-3 py-1 text-sm rounded-full bg-white/20">Clients</span>
            </div>
            <p class="text-3xl font-bold">{{ number_format($stats['total_clients']) }}</p>
            <p class="mt-1 text-sm text-purple-100">Total enregistrés</p>
            <div class="pt-3 mt-3 text-sm border-t border-white/20">
                <span>{{ number_format($stats['total_clients'] / max($stats['active_users'], 1), 1) }} / agent</span>
            </div>
        </div>

        <div class="p-6 text-white shadow-lg bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-white/20">
                    <i class="text-2xl fas fa-exchange-alt"></i>
                </div>
                <span class="px-3 py-1 text-sm rounded-full bg-white/20">Aujourd'hui</span>
            </div>
            <p class="text-3xl font-bold">{{ number_format($stats['total_transactions_today']) }}</p>
            <p class="mt-1 text-sm text-orange-100">Transactions</p>
            <div class="pt-3 mt-3 text-sm border-t border-white/20">
                <span>{{ number_format($stats['total_amount_today'], 0, ',', ' ') }} FCFA</span>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="p-6 bg-white shadow-sm rounded-xl">
        <form method="GET" action="{{ route('admin.reports.users.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-5">
                <div class="lg:col-span-2">
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        <i class="mr-1 fas fa-search"></i> Rechercher
                    </label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Nom, email, téléphone..."
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        <i class="mr-1 fas fa-user-tag"></i> Rôle
                    </label>
                    <select name="role" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">Tous les rôles</option>
                        <option value="super_admin" {{ request('role') == 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                        <option value="administrateur_reglementaire" {{ request('role') == 'administrateur_reglementaire' ? 'selected' : '' }}>Admin Réglementaire</option>
                        <option value="agent_collecteur" {{ request('role') == 'agent_collecteur' ? 'selected' : '' }}>Agent Collecteur</option>
                        <option value="support" {{ request('role') == 'support' ? 'selected' : '' }}>Support</option>
                    </select>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        <i class="mr-1 fas fa-building"></i> Agence
                    </label>
                    <select name="agency_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">Toutes les agences</option>
                        @foreach($agencies as $agency)
                            <option value="{{ $agency->id }}" {{ request('agency_id') == $agency->id ? 'selected' : '' }}>
                                {{ $agency->name }} ({{ $agency->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        <i class="mr-1 fas fa-toggle-on"></i> Statut
                    </label>
                    <select name="is_active" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">Tous</option>
                        <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Actif</option>
                        <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactif</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        <i class="mr-1 fas fa-clock"></i> Activité récente
                    </label>
                    <select name="activity_period" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">Toutes les périodes</option>
                        <option value="today" {{ request('activity_period') == 'today' ? 'selected' : '' }}>Aujourd'hui</option>
                        <option value="week" {{ request('activity_period') == 'week' ? 'selected' : '' }}>Cette semaine</option>
                        <option value="month" {{ request('activity_period') == 'month' ? 'selected' : '' }}>Ce mois</option>
                        <option value="3months" {{ request('activity_period') == '3months' ? 'selected' : '' }}>3 derniers mois</option>
                    </select>
                </div>

                <div class="flex items-end gap-2 md:col-span-3">
                    <button type="submit" class="flex-1 px-6 py-2 text-white transition bg-blue-600 rounded-lg hover:bg-blue-700">
                        <i class="mr-2 fas fa-filter"></i>Filtrer
                    </button>
                    <a href="{{ route('admin.reports.users.index') }}" class="px-6 py-2 text-gray-700 transition bg-gray-200 rounded-lg hover:bg-gray-300">
                        <i class="mr-2 fas fa-redo"></i>Réinitialiser
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Liste des Utilisateurs -->
    <div class="overflow-hidden bg-white shadow-sm rounded-xl">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left">
                            <input type="checkbox" id="selectAll" class="w-4 h-4 text-blue-600 rounded">
                        </th>
                        <th class="px-6 py-3 text-xs font-semibold text-left text-gray-600 uppercase">Utilisateur</th>
                        <th class="px-6 py-3 text-xs font-semibold text-left text-gray-600 uppercase">Agence</th>
                        <th class="px-6 py-3 text-xs font-semibold text-left text-gray-600 uppercase">Rôle</th>
                        <th class="px-6 py-3 text-xs font-semibold text-left text-gray-600 uppercase">Clients</th>
                        <th class="px-6 py-3 text-xs font-semibold text-left text-gray-600 uppercase">Dernière activité</th>
                        <th class="px-6 py-3 text-xs font-semibold text-left text-gray-600 uppercase">Statut</th>
                        <th class="px-6 py-3 text-xs font-semibold text-right text-gray-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($users as $user)
                    <tr class="transition hover:bg-gray-50" data-user-id="{{ $user->id }}">
                        <td class="px-6 py-4">
                            <input type="checkbox" class="w-4 h-4 text-blue-600 rounded user-checkbox" value="{{ $user->id }}">
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-10 h-10 font-semibold text-white rounded-full bg-gradient-to-br from-blue-400 to-purple-500">
                                    {{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800">{{ $user->full_name }}</p>
                                    <div class="flex items-center gap-2 text-xs text-gray-500">
                                        <span><i class="mr-1 fas fa-envelope"></i>{{ $user->email }}</span>
                                        @if($user->phone)
                                        <span><i class="mr-1 fas fa-phone"></i>{{ $user->phone }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($user->agency)
                                <div>
                                    <p class="font-medium text-gray-800">{{ $user->agency->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $user->agency->city }} - {{ $user->agency->code }}</p>
                                </div>
                            @else
                                <span class="text-sm italic text-gray-400">Non assigné</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                {{ $user->role === 'super_admin' ? 'bg-red-100 text-red-700' : '' }}
                                {{ $user->role === 'administrateur_reglementaire' ? 'bg-purple-100 text-purple-700' : '' }}
                                {{ $user->role === 'agent_collecteur' ? 'bg-blue-100 text-blue-700' : '' }}
                                {{ $user->role === 'support' ? 'bg-green-100 text-green-700' : '' }}">
                                {{ ucfirst(str_replace('_', ' ', $user->role)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 text-sm font-medium text-blue-700 bg-blue-100 rounded-full">
                                    {{ $user->clients_count }}
                                </span>
                                @if($user->active_clients_count > 0)
                                <span class="text-xs text-gray-500">
                                    ({{ $user->active_clients_count }} KYC OK)
                                </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($user->last_login)
                                <div>
                                    <p class="text-sm text-gray-800">{{ $user->last_login->format('d/m/Y') }}</p>
                                    <p class="text-xs text-gray-500">{{ $user->last_login->format('H:i') }}</p>
                                    <p class="text-xs text-gray-400">{{ $user->last_login->diffForHumans() }}</p>
                                </div>
                            @else
                                <span class="text-sm italic text-gray-400">Jamais</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($user->is_active)
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
                                <a href="{{ route('admin.reports.users.show', $user->id) }}"
                                   class="p-2 text-blue-600 transition rounded-lg hover:bg-blue-50"
                                   title="Voir le rapport">
                                    <i class="fas fa-chart-line"></i>
                                </a>
                                <a href="{{ route('admin.reports.users.export', $user->id) }}"
                                   class="p-2 text-green-600 transition rounded-lg hover:bg-green-50"
                                   title="Exporter les données">
                                    <i class="fas fa-download"></i>
                                </a>
                                @if($user->agency)
                                <a href="{{ route('admin.reports.agencies.show', $user->agency_id) }}"
                                   class="p-2 text-purple-600 transition rounded-lg hover:bg-purple-50"
                                   title="Voir l'agence">
                                    <i class="fas fa-building"></i>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                            <i class="mb-4 text-4xl text-gray-300 fas fa-users"></i>
                            <p class="text-lg font-medium">Aucun utilisateur trouvé</p>
                            <p class="text-sm">Essayez de modifier vos filtres</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $users->links() }}
        </div>
    </div>
</div>

<!-- Modal de Comparaison -->
<div id="compareModal" class="fixed inset-0 z-50 flex items-center justify-center hidden p-4 bg-black bg-opacity-50">
    <div class="w-full max-w-2xl bg-white shadow-xl rounded-xl">
        <div class="flex items-center justify-between p-6 border-b">
            <h3 class="text-xl font-bold text-gray-800">
                <i class="mr-2 text-purple-600 fas fa-balance-scale"></i>
                Comparer les Utilisateurs
            </h3>
            <button onclick="closeCompareModal()" class="text-gray-400 transition hover:text-gray-600">
                <i class="text-xl fas fa-times"></i>
            </button>
        </div>

        <form action="{{ route('admin.reports.users.compare') }}" method="POST" class="p-6">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block mb-2 text-sm font-semibold text-gray-700">
                        Sélectionnez 2 à 5 utilisateurs à comparer
                    </label>
                    <div id="selectedUsers" class="space-y-2 mb-4 min-h-[100px] border border-dashed border-gray-300 rounded-lg p-4">
                        <p class="text-sm text-center text-gray-400">Cochez des utilisateurs dans la liste pour les comparer</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Date début</label>
                        <input type="date" name="start_date" value="{{ now()->startOfMonth()->format('Y-m-d') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Date fin</label>
                        <input type="date" name="end_date" value="{{ now()->format('Y-m-d') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 mt-6 border-t">
                <button type="button" onclick="closeCompareModal()"
                        class="px-6 py-2 text-gray-700 transition border border-gray-300 rounded-lg hover:bg-gray-50">
                    Annuler
                </button>
                <button type="submit" id="compareBtn" disabled
                        class="px-6 py-2 text-white transition bg-purple-600 rounded-lg hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="mr-2 fas fa-balance-scale"></i>
                    Comparer
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const selectedUserIds = new Set();

// Sélection de tous les utilisateurs
document.getElementById('selectAll')?.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = this.checked;
        if (this.checked) {
            selectedUserIds.add(cb.value);
        } else {
            selectedUserIds.delete(cb.value);
        }
    });
    updateCompareButton();
});

// Sélection individuelle
document.querySelectorAll('.user-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        if (this.checked) {
            selectedUserIds.add(this.value);
        } else {
            selectedUserIds.delete(this.value);
        }
        updateCompareButton();
        updateSelectedUsersDisplay();
    });
});

// Ouvrir le modal de comparaison
function openCompareModal() {
    if (selectedUserIds.size < 2) {
        alert('Veuillez sélectionner au moins 2 utilisateurs');
        return;
    }
    if (selectedUserIds.size > 5) {
        alert('Vous ne pouvez comparer que 5 utilisateurs maximum');
        return;
    }
    updateSelectedUsersDisplay();
    document.getElementById('compareModal').classList.remove('hidden');
}

// Fermer le modal
function closeCompareModal() {
    document.getElementById('compareModal').classList.add('hidden');
}

// Mettre à jour l'affichage des utilisateurs sélectionnés
function updateSelectedUsersDisplay() {
    const container = document.getElementById('selectedUsers');
    container.innerHTML = '';

    if (selectedUserIds.size === 0) {
        container.innerHTML = '<p class="text-sm text-center text-gray-400">Cochez des utilisateurs dans la liste pour les comparer</p>';
        return;
    }

    selectedUserIds.forEach(userId => {
        const row = document.querySelector(`tr[data-user-id="${userId}"]`);
        if (row) {
            const userName = row.querySelector('.font-semibold').textContent;
            const userEmail = row.querySelector('.fa-envelope').parentElement.textContent.trim();

            const userCard = document.createElement('div');
            userCard.className = 'flex items-center justify-between p-3 bg-purple-50 border border-purple-200 rounded-lg';
            userCard.innerHTML = `
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-8 h-8 text-xs font-semibold text-white rounded-full bg-gradient-to-br from-purple-400 to-purple-600">
                        ${userName.split(' ').map(n => n[0]).join('')}
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-800">${userName}</p>
                        <p class="text-xs text-gray-500">${userEmail}</p>
                    </div>
                </div>
                <button type="button" onclick="removeUser('${userId}')" class="text-red-600 hover:text-red-700">
                    <i class="fas fa-times"></i>
                </button>
                <input type="hidden" name="user_ids[]" value="${userId}">
            `;
            container.appendChild(userCard);
        }
    });
}

// Retirer un utilisateur de la sélection
function removeUser(userId) {
    selectedUserIds.delete(userId);
    const checkbox = document.querySelector(`.user-checkbox[value="${userId}"]`);
    if (checkbox) checkbox.checked = false;
    updateSelectedUsersDisplay();
    updateCompareButton();
}

// Mettre à jour le bouton de comparaison
function updateCompareButton() {
    const compareBtn = document.getElementById('compareBtn');
    if (compareBtn) {
        compareBtn.disabled = selectedUserIds.size < 2 || selectedUserIds.size > 5;
    }
}

// Export de toutes les données
function exportAllData() {
    const url = new URL(window.location.href);
    url.searchParams.set('export', 'all');
    window.location.href = url.toString();
}

// Fermer le modal en cliquant en dehors
document.getElementById('compareModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeCompareModal();
    }
});

// Fermer avec la touche Échap
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCompareModal();
    }
});
</script>
@endsection
