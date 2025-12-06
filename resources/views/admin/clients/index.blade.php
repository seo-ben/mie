@extends('layouts.app_admin')

@section('title', 'Gestion des Clients - MIE YAYRA')
@section('page-title', 'Gestion des Clients')

@push('styles')
<style>
    .client-card {
        transition: all 0.3s ease;
    }

    .client-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
    }

    .filter-badge {
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .action-btn {
        transition: all 0.2s ease;
    }

    .action-btn:hover {
        transform: scale(1.05);
    }

    .search-box:focus-within {
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .table-row-hover:hover {
        background: linear-gradient(90deg, rgba(59, 130, 246, 0.02) 0%, transparent 100%);
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
    }

    .status-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        animation: pulse-dot 2s ease-in-out infinite;
    }

    @keyframes pulse-dot {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
</style>
@endpush

@section('content')
<div class="space-y-6">
    <!-- Header Section avec Stats -->
    <div class="grid gap-6 md:grid-cols-4">
        <!-- Total Clients -->
        <div class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-xl">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Clients</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($clients->total(), 0, ',', ' ') }}</p>
                    </div>
                    <div class="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-lg">
                        <i class="text-xl text-blue-600 fas fa-users"></i>
                    </div>
                </div>
                <div class="flex items-center mt-4 text-sm">
                    <i class="mr-1 text-green-500 fas fa-arrow-up"></i>
                    <span class="font-semibold text-green-600">12%</span>
                    <span class="ml-2 text-gray-500">vs mois dernier</span>
                </div>
            </div>
        </div>

        <!-- KYC En attente -->
        <div class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-xl">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">KYC En attente</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ $clients->where('kyc_status', 'pending')->count() }}</p>
                    </div>
                    <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-amber-100">
                        <i class="text-xl text-amber-600 fas fa-clock"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="{{ route('admin.clients.index', ['kyc_status' => 'pending']) }}" class="text-sm font-semibold text-amber-600 hover:text-amber-700">
                        Traiter maintenant →
                    </a>
                </div>
            </div>
        </div>

        <!-- Clients Actifs -->
        <div class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-xl">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Clients Actifs</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ $clients->where('kyc_status', 'approved')->count() }}</p>
                    </div>
                    <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-lg">
                        <i class="text-xl text-green-600 fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="flex items-center mt-4 text-sm">
                    <span class="text-gray-500">{{ number_format(($clients->where('kyc_status', 'approved')->count() / max($clients->total(), 1)) * 100, 1) }}% du total</span>
                </div>
            </div>
        </div>

        <!-- Nouveau Client Button -->
        <div class="overflow-hidden shadow-sm bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl">
            <a href="{{ route('admin.clients.create') }}" class="block h-full p-6 transition hover:from-blue-600 hover:to-blue-700">
                <div class="flex flex-col items-center justify-center h-full text-white">
                    <div class="flex items-center justify-center w-12 h-12 mb-3 bg-white rounded-lg bg-opacity-20">
                        <i class="text-2xl fas fa-plus"></i>
                    </div>
                    <p class="text-lg font-bold">Nouveau Client</p>
                    <p class="mt-1 text-sm text-blue-100">Créer un compte</p>
                </div>
            </a>
        </div>
    </div>

    <!-- Filtres & Recherche -->
    <div class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-xl">
        <div class="p-6">
            <form method="GET" action="{{ route('admin.clients.index') }}">
                <div class="grid gap-4 md:grid-cols-12">
                    <!-- Recherche -->
                    <div class="md:col-span-5">
                        <label class="block mb-2 text-sm font-semibold text-gray-700">
                            <i class="mr-1 text-gray-400 fas fa-search"></i>
                            Rechercher un client
                        </label>
                        <div class="relative transition rounded-lg search-box">
                            <input type="text"
                                   name="search"
                                   value="{{ request('search') }}"
                                   placeholder="Nom, téléphone, numéro client..."
                                   class="w-full px-4 py-2.5 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                            <i class="absolute text-gray-400 fas fa-search left-3 top-3.5 text-sm"></i>
                        </div>
                    </div>

                    <!-- Statut KYC -->
                    <div class="md:col-span-3">
                        <label class="block mb-2 text-sm font-semibold text-gray-700">
                            <i class="mr-1 text-gray-400 fas fa-filter"></i>
                            Statut KYC
                        </label>
                        <select name="kyc_status"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition bg-white">
                            <option value="">Tous les statuts</option>
                            <option value="pending" {{ request('kyc_status') == 'pending' ? 'selected' : '' }}>
                                🟡 En attente
                            </option>
                            <option value="approved" {{ request('kyc_status') == 'approved' ? 'selected' : '' }}>
                                🟢 Approuvé
                            </option>
                            <option value="rejected" {{ request('kyc_status') == 'rejected' ? 'selected' : '' }}>
                                🔴 Rejeté
                            </option>
                        </select>
                    </div>

                    <!-- Ville -->
                    <div class="md:col-span-2">
                        <label class="block mb-2 text-sm font-semibold text-gray-700">
                            <i class="mr-1 text-gray-400 fas fa-map-marker-alt"></i>
                            Ville
                        </label>
                        <select name="city"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition bg-white">
                            <option value="">Toutes</option>
                            <option value="Lomé" {{ request('city') == 'Lomé' ? 'selected' : '' }}>Lomé</option>
                            <option value="Kara" {{ request('city') == 'Kara' ? 'selected' : '' }}>Kara</option>
                            <option value="Sokodé" {{ request('city') == 'Sokodé' ? 'selected' : '' }}>Sokodé</option>
                        </select>
                    </div>

                    <!-- Boutons -->
                    <div class="flex items-end gap-2 md:col-span-2">
                        <button type="submit"
                                class="flex-1 px-4 py-2.5 text-white font-semibold bg-blue-600 rounded-lg hover:bg-blue-700 transition shadow-sm">
                            <i class="mr-1 fas fa-search"></i>
                            Filtrer
                        </button>
                        @if(request()->hasAny(['search', 'kyc_status', 'city']))
                        <a href="{{ route('admin.clients.index') }}"
                           class="px-4 py-2.5 text-gray-700 font-semibold bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                            <i class="fas fa-times"></i>
                        </a>
                        @endif
                    </div>
                </div>

                <!-- Filtres actifs -->
                @if(request()->hasAny(['search', 'kyc_status', 'city']))
                <div class="flex flex-wrap gap-2 mt-4">
                    <span class="text-sm font-semibold text-gray-600">Filtres actifs:</span>
                    @if(request('search'))
                    <span class="filter-badge inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold text-blue-700 bg-blue-100 rounded-full">
                        Recherche: "{{ request('search') }}"
                        <a href="{{ route('admin.clients.index', array_merge(request()->except('search'))) }}" class="hover:text-blue-900">
                            <i class="text-xs fas fa-times"></i>
                        </a>
                    </span>
                    @endif
                    @if(request('kyc_status'))
                    <span class="filter-badge inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold text-amber-700 bg-amber-100 rounded-full">
                        KYC: {{ ucfirst(request('kyc_status')) }}
                        <a href="{{ route('admin.clients.index', array_merge(request()->except('kyc_status'))) }}" class="hover:text-amber-900">
                            <i class="text-xs fas fa-times"></i>
                        </a>
                    </span>
                    @endif
                    @if(request('city'))
                    <span class="filter-badge inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold text-purple-700 bg-purple-100 rounded-full">
                        Ville: {{ request('city') }}
                        <a href="{{ route('admin.clients.index', array_merge(request()->except('city'))) }}" class="hover:text-purple-900">
                            <i class="text-xs fas fa-times"></i>
                        </a>
                    </span>
                    @endif
                </div>
                @endif
            </form>
        </div>
    </div>

    <!-- Tableau des clients -->
    <div class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-xl">
        <!-- Header du tableau -->
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Liste des Clients</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ $clients->total() }} client(s) trouvé(s)
                        @if($clients->currentPage() > 1)
                            - Page {{ $clients->currentPage() }} sur {{ $clients->lastPage() }}
                        @endif
                    </p>
                </div>
                <div class="flex gap-2">
                    <button class="px-4 py-2 text-sm font-semibold text-gray-700 transition bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        <i class="mr-1 fas fa-download"></i>
                        Exporter
                    </button>
                    <button class="px-4 py-2 text-sm font-semibold text-gray-700 transition bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        <i class="mr-1 fas fa-print"></i>
                        Imprimer
                    </button>
                </div>
            </div>
        </div>

        <!-- Tableau -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-xs font-bold tracking-wider text-left text-gray-600 uppercase">
                            Client
                        </th>
                        <th class="px-6 py-4 text-xs font-bold tracking-wider text-left text-gray-600 uppercase">
                            Contact
                        </th>
                        <th class="px-6 py-4 text-xs font-bold tracking-wider text-left text-gray-600 uppercase">
                            Comptes & Épargne
                        </th>
                        <th class="px-6 py-4 text-xs font-bold tracking-wider text-left text-gray-600 uppercase">
                            Statut KYC
                        </th>
                        <th class="px-6 py-4 text-xs font-bold tracking-wider text-left text-gray-600 uppercase">
                            Inscription
                        </th>
                        <th class="px-6 py-4 text-xs font-bold tracking-wider text-right text-gray-600 uppercase">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($clients as $client)
                        <tr class="transition table-row-hover">
                            <!-- Client Info -->
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        @if($client->profile_photo_url)
                                            <img class="object-cover w-12 h-12 border-2 border-gray-200 rounded-full"
                                                 src="{{ asset('storage/' . $client->profile_photo_url) }}"
                                                 alt="{{ $client->full_name }}">
                                        @else
                                            <div class="flex items-center justify-center w-12 h-12 font-bold text-white rounded-full shadow-sm bg-gradient-to-br from-blue-400 to-blue-600">
                                                <span class="text-base">
                                                    {{ strtoupper(substr($client->first_name, 0, 1)) }}{{ strtoupper(substr($client->last_name, 0, 1)) }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-bold text-gray-900">{{ $client->full_name }}</div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold text-blue-700 bg-blue-50 rounded">
                                                <i class="mr-1 text-xs fas fa-hashtag"></i>
                                                {{ $client->client_number }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Contact -->
                            <td class="px-6 py-4">
                                <div class="space-y-1">
                                    <div class="flex items-center text-sm text-gray-900">
                                        <i class="w-4 mr-2 text-gray-400 fas fa-phone"></i>
                                        {{ $client->phone }}
                                    </div>
                                    @if($client->city)
                                    <div class="flex items-center text-sm text-gray-500">
                                        <i class="w-4 mr-2 text-gray-400 fas fa-map-marker-alt"></i>
                                        {{ $client->city }}
                                    </div>
                                    @endif
                                </div>
                            </td>

                            <!-- Comptes -->
                            <td class="px-6 py-4">
                                <div class="space-y-1">
                                    <div class="text-sm font-semibold text-gray-900">
                                        {{ $client->accounts->count() }} compte(s)
                                    </div>
                                    <div class="text-sm font-bold text-blue-600">
                                        {{ number_format($client->total_savings + $client->total_tontine, 0, ',', ' ') }} FCFA
                                    </div>
                                    
                                </div>
                            </td>

                            <!-- Statut KYC -->
                            <td class="px-6 py-4">
                                @if($client->kyc_status === 'approved')
                                    <span class="status-badge px-3 py-1.5 text-xs font-bold text-green-700 bg-green-100 rounded-lg border border-green-200">
                                        <span class="bg-green-500 status-dot"></span>
                                        Approuvé
                                    </span>
                                @elseif($client->kyc_status === 'rejected')
                                    <span class="status-badge px-3 py-1.5 text-xs font-bold text-red-700 bg-red-100 rounded-lg border border-red-200">
                                        <span class="bg-red-500 status-dot"></span>
                                        Rejeté
                                    </span>
                                @else
                                    <span class="status-badge px-3 py-1.5 text-xs font-bold text-amber-700 bg-amber-100 rounded-lg border border-amber-200">
                                        <span class="status-dot bg-amber-500"></span>
                                        En attente
                                    </span>
                                @endif
                            </td>

                            <!-- Date -->
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">
                                    {{ $client->created_at->format('d/m/Y') }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $client->created_at->diffForHumans() }}
                                </div>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Voir -->
                                    <a href="{{ route('admin.clients.show', $client->id) }}"
                                       class="action-btn inline-flex items-center px-3 py-1.5 text-xs font-semibold text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 transition"
                                       title="Voir le profil">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    <!-- Éditer -->
                                    <a href="{{ route('admin.clients.edit', $client->id) }}"
                                       class="action-btn inline-flex items-center px-3 py-1.5 text-xs font-semibold text-indigo-700 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition"
                                       title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <!-- Dropdown Actions -->
                                    <div class="relative inline-block text-left">
                                        <button type="button"
                                                onclick="toggleActionMenu('action-{{ $client->id }}')"
                                                class="action-btn inline-flex items-center px-3 py-1.5 text-xs font-semibold text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>

                                        <div id="action-{{ $client->id }}"
                                             class="absolute right-0 z-10 hidden w-56 mt-2 origin-top-right bg-white border border-gray-200 rounded-lg shadow-lg">
                                            <div class="py-1">
                                                <a href="{{ route('admin.accounts.create', $client->id) }}"
                                                   class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                                    <i class="w-4 mr-3 text-blue-500 fas fa-wallet"></i>
                                                    Créer un compte
                                                </a>
                                                <a href="{{ route('admin.loans.create') }}"
                                                   class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                                    <i class="w-4 mr-3 text-green-500 fas fa-hand-holding-usd"></i>
                                                    Demande de prêt
                                                </a>
                                                @if($client->kyc_status === 'pending')
                                                <div class="border-t border-gray-100"></div>
                                                <a href="{{ route('admin.clients.validate-kyc', $client->id) }}"
                                                   class="flex items-center px-4 py-2 text-sm font-semibold text-green-700 hover:bg-green-50">
                                                    <i class="w-4 mr-3 fas fa-check-circle"></i>
                                                    Valider KYC
                                                </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="flex items-center justify-center w-16 h-16 mb-4 bg-gray-100 rounded-full">
                                        <i class="text-2xl text-gray-400 fas fa-users"></i>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-900">Aucun client trouvé</h3>
                                    <p class="mt-2 text-sm text-gray-500">
                                        @if(request()->hasAny(['search', 'kyc_status', 'city']))
                                            Essayez de modifier vos filtres de recherche
                                        @else
                                            Commencez par créer votre premier client
                                        @endif
                                    </p>
                                    <a href="{{ route('admin.clients.create') }}"
                                       class="inline-flex items-center px-6 py-3 mt-6 font-semibold text-white transition bg-blue-600 rounded-lg shadow-sm hover:bg-blue-700">
                                        <i class="mr-2 fas fa-plus"></i>
                                        Ajouter un client
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($clients->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        Affichage de <span class="font-semibold">{{ $clients->firstItem() }}</span>
                        à <span class="font-semibold">{{ $clients->lastItem() }}</span>
                        sur <span class="font-semibold">{{ $clients->total() }}</span> résultats
                    </div>
                    <div>
                        {{ $clients->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Toggle action menu dropdown
    function toggleActionMenu(menuId) {
        const menu = document.getElementById(menuId);
        const allMenus = document.querySelectorAll('[id^="action-"]');

        // Close all other menus
        allMenus.forEach(m => {
            if (m.id !== menuId) {
                m.classList.add('hidden');
            }
        });

        // Toggle current menu
        menu.classList.toggle('hidden');
    }

    // Close menus when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('[onclick^="toggleActionMenu"]')) {
            const allMenus = document.querySelectorAll('[id^="action-"]');
            allMenus.forEach(menu => menu.classList.add('hidden'));
        }
    });


</script>
@endpush
