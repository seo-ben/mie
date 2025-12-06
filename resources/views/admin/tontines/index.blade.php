@extends('layouts.app_admin')

@section('title', 'Gestion des Tontines')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- En-tête -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 mb-2">Gestion des Tontines</h1>
            <p class="text-gray-600">Suivi et gestion des comptes tontine</p>
        </div>
        <a href="{{ route('admin.tontines.report') }}" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
            <i class="fas fa-chart-bar mr-2"></i>Rapport Global
        </a>
    </div>

    <!-- Statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white border-l-4 border-purple-500 rounded-lg shadow-sm">
            <div class="p-6">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-600 text-sm mb-1">Total Tontines</p>
                        <h4 class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_tontines']) }}</h4>
                    </div>
                    <div class="text-purple-500">
                        <i class="fas fa-users text-3xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white border-l-4 border-green-500 rounded-lg shadow-sm">
            <div class="p-6">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-600 text-sm mb-1">Cycles Actifs</p>
                        <h4 class="text-2xl font-bold text-gray-900">{{ number_format($stats['active_cycles']) }}</h4>
                    </div>
                    <div class="text-green-500">
                        <i class="fas fa-sync text-3xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white border-l-4 border-blue-500 rounded-lg shadow-sm">
            <div class="p-6">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-600 text-sm mb-1">Montant Collecté</p>
                        <h4 class="text-xl font-bold text-gray-900">{{ number_format($stats['total_collected'], 0, ',', ' ') }} FCFA</h4>
                    </div>
                    <div class="text-blue-500">
                        <i class="fas fa-coins text-3xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white border-l-4 border-cyan-500 rounded-lg shadow-sm">
            <div class="p-6">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-600 text-sm mb-1">Débloqué</p>
                        <h4 class="text-xl font-bold text-gray-900">{{ number_format($stats['total_paid_out'], 0, ',', ' ') }} FCFA</h4>
                    </div>
                    <div class="text-cyan-500">
                        <i class="fas fa-hand-holding-usd text-3xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white border-l-4 border-yellow-500 rounded-lg shadow-sm">
            <div class="p-6">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-600 text-sm mb-1">En Attente</p>
                        <h4 class="text-2xl font-bold text-gray-900">{{ number_format($stats['pending_collections']) }}</h4>
                    </div>
                    <div class="text-yellow-500">
                        <i class="fas fa-hourglass-half text-3xl"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-6">
            <form method="GET" action="{{ route('admin.tontines.index') }}">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                    <div class="md:col-span-5">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
                        <input type="text" name="search"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                               placeholder="Numéro compte, nom client..."
                               value="{{ request('search') }}">
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fréquence</label>
                        <select name="payment_frequency" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="">Toutes</option>
                            <option value="daily" {{ request('payment_frequency') === 'daily' ? 'selected' : '' }}>
                                Journalière
                            </option>
                            <option value="weekly" {{ request('payment_frequency') === 'weekly' ? 'selected' : '' }}>
                                Hebdomadaire
                            </option>
                            <option value="monthly" {{ request('payment_frequency') === 'monthly' ? 'selected' : '' }}>
                                Mensuelle
                            </option>
                        </select>
                    </div>

                    <div class="md:col-span-4 flex items-end gap-2">
                        <button type="submit" class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                            <i class="fas fa-search mr-2"></i>Rechercher
                        </button>
                        <a href="{{ route('admin.tontines.index') }}" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                            <i class="fas fa-redo mr-2"></i>Réinitialiser
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des tontines -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h5 class="text-lg font-semibold text-gray-900">Comptes Tontine ({{ $tontines->total() }})</h5>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client / Compte</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant / Fréquence</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cycle Actuel</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progression</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Solde</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($tontines as $tontine)
                    @php
                        $activeCycle = $tontine->cycles->where('status', 'active')->first();
                        $progressPercent = $activeCycle && $activeCycle->target_amount > 0
                            ? round(($activeCycle->collected_amount / $activeCycle->target_amount) * 100, 2)
                            : 0;
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-semibold text-gray-900">
                                {{ $tontine->account->client->first_name }} {{ $tontine->account->client->last_name }}
                            </div>
                            <a href="{{ route('admin.accounts.show', $tontine->account_id) }}"
                               class="text-sm text-blue-600 hover:text-blue-800">
                                {{ $tontine->account->account_number }}
                            </a>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-purple-700">
                                {{ number_format($tontine->tontine_amount, 0, ',', ' ') }} FCFA
                            </div>
                            <div class="text-sm text-gray-500">
                                @switch($tontine->payment_frequency)
                                    @case('daily')
                                        <i class="fas fa-calendar-day mr-1"></i>Journalier
                                        @break
                                    @case('weekly')
                                        <i class="fas fa-calendar-week mr-1"></i>Hebdomadaire
                                        @break
                                    @case('monthly')
                                        <i class="fas fa-calendar mr-1"></i>Mensuel
                                        @break
                                @endswitch
                                • {{ $tontine->cycle_duration_months }} mois
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($activeCycle)
                                {{-- <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 mb-1">
                                    Cycle #{{ $activeCycle->cycle_number }}
                                </span> --}}
                                <div class="text-xs text-gray-500">
                                    {{ $activeCycle->start_date->format('d/m/Y') }} → {{ $activeCycle->end_date->format('d/m/Y') }}
                                </div>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                    Aucun cycle actif
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($activeCycle)
                                <div class="space-y-1">
                                    <div class="flex justify-between text-sm">
                                        <span class="font-medium text-gray-700">{{ number_format($activeCycle->collected_amount, 0, ',', ' ') }} FCFA</span>
                                        <span class="text-gray-500">/ {{ number_format($activeCycle->target_amount, 0, ',', ' ') }}</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-purple-600 h-2 rounded-full" style="width: {{ min($progressPercent, 100) }}%"></div>
                                    </div>
                                    <div class="text-xs text-gray-500">{{ $progressPercent }}% complété</div>
                                </div>
                            @else
                                <span class="text-sm text-gray-500">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap font-semibold text-gray-900">
                            {{ number_format($tontine->account->balance, 0, ',', ' ') }} FCFA
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex space-x-2">
                                <a href="{{ route('admin.tontines.show', $tontine->id) }}"
                                   class="px-3 py-1 border border-blue-600 text-blue-600 rounded hover:bg-blue-50"
                                   title="Détails">
                                    <i class="fas fa-eye"></i>
                                </a>

                                @if($activeCycle)
                                    <a href="{{ route('admin.tontines.contribute-form', $tontine->id) }}"
                                       class="px-3 py-1 border border-purple-600 text-purple-600 rounded hover:bg-purple-50"
                                       title="Cotiser">
                                        <i class="fas fa-plus"></i>
                                    </a>
                                @endif

                                <a href="{{ route('admin.tontines.contributions', $tontine->id) }}"
                                   class="px-3 py-1 border border-cyan-600 text-cyan-600 rounded hover:bg-cyan-50"
                                   title="Historique">
                                    <i class="fas fa-list"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <i class="fas fa-inbox text-5xl text-gray-400 mb-3"></i>
                            <p class="text-gray-500">Aucune tontine trouvée</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tontines->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $tontines->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
