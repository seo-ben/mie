@extends('layouts.app_admin')

@section('title', 'Gestion des Prêts')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- En-tête -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 mb-2">Gestion des Prêts</h1>
            <p class="text-gray-600">Suivi et validation des demandes de prêt</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.loans.report') }}" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                <i class="fas fa-chart-line mr-2"></i>Rapport Global
            </a>
            <a href="{{ route('admin.loans.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>Nouvelle Demande
            </a>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white border-l-4 border-blue-500 rounded-lg shadow-sm p-6">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-600 text-sm mb-1">Total Prêts</p>
                    <h4 class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_loans']) }}</h4>
                </div>
                <div class="text-blue-500">
                    <i class="fas fa-file-invoice-dollar text-3xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white border-l-4 border-yellow-500 rounded-lg shadow-sm p-6">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-600 text-sm mb-1">En Attente</p>
                    <h4 class="text-2xl font-bold text-gray-900">{{ number_format($stats['pending_loans']) }}</h4>
                </div>
                <div class="text-yellow-500">
                    <i class="fas fa-clock text-3xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white border-l-4 border-green-500 rounded-lg shadow-sm p-6">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-600 text-sm mb-1">Actifs</p>
                    <h4 class="text-2xl font-bold text-gray-900">{{ number_format($stats['active_loans']) }}</h4>
                </div>
                <div class="text-green-500">
                    <i class="fas fa-check-circle text-3xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white border-l-4 border-purple-500 rounded-lg shadow-sm p-6">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-600 text-sm mb-1">Portfolio Actif</p>
                    <h4 class="text-xl font-bold text-gray-900">{{ number_format($stats['active_portfolio'], 0, ',', ' ') }} FCFA</h4>
                </div>
                <div class="text-purple-500">
                    <i class="fas fa-wallet text-3xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-6">
            <form method="GET" action="{{ route('admin.loans.index') }}">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                    <div class="md:col-span-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
                        <input type="text" name="search"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="N° prêt, client..."
                               value="{{ request('search') }}">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                        <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Tous</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approuvé</option>
                            <option value="disbursed" {{ request('status') === 'disbursed' ? 'selected' : '' }}>Décaissé</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Soldé</option>
                            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejeté</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Risque</label>
                        <select name="risk_level" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Tous</option>
                            <option value="low" {{ request('risk_level') === 'low' ? 'selected' : '' }}>Faible</option>
                            <option value="medium" {{ request('risk_level') === 'medium' ? 'selected' : '' }}>Moyen</option>
                            <option value="high" {{ request('risk_level') === 'high' ? 'selected' : '' }}>Élevé</option>
                            <option value="very_high" {{ request('risk_level') === 'very_high' ? 'selected' : '' }}>Très élevé</option>
                        </select>
                    </div>

                    <div class="md:col-span-4 flex items-end gap-2">
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-search mr-2"></i>Rechercher
                        </button>
                        <a href="{{ route('admin.loans.index') }}" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                            <i class="fas fa-redo mr-2"></i>Réinitialiser
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des prêts -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h5 class="text-lg font-semibold text-gray-900">Prêts ({{ $loans->total() }})</h5>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prêt / Client</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durée</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Risque</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($loans as $loan)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-semibold text-blue-600">{{ $loan->loan_number }}</div>
                            <div class="text-sm text-gray-600">
                                {{ $loan->client->first_name }} {{ $loan->client->last_name }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900">
                                {{ number_format($loan->approved_amount ?? $loan->requested_amount, 0, ',', ' ') }} FCFA
                            </div>
                            <div class="text-xs text-gray-500">
                                Taux: {{ $loan->interest_rate }}%
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-medium text-gray-900">{{ $loan->duration_months }} mois</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusClasses = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'approved' => 'bg-green-100 text-green-800',
                                    'disbursed' => 'bg-blue-100 text-blue-800',
                                    'completed' => 'bg-gray-100 text-gray-800',
                                    'rejected' => 'bg-red-100 text-red-800',
                                ];
                                $statusLabels = [
                                    'pending' => 'En attente',
                                    'approved' => 'Approuvé',
                                    'disbursed' => 'Décaissé',
                                    'completed' => 'Soldé',
                                    'rejected' => 'Rejeté',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClasses[$loan->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $statusLabels[$loan->status] ?? $loan->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $riskClasses = [
                                    'low' => 'bg-green-100 text-green-800',
                                    'medium' => 'bg-yellow-100 text-yellow-800',
                                    'high' => 'bg-orange-100 text-orange-800',
                                    'very_high' => 'bg-red-100 text-red-800',
                                ];
                                $riskLabels = [
                                    'low' => 'Faible',
                                    'medium' => 'Moyen',
                                    'high' => 'Élevé',
                                    'very_high' => 'Très élevé',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $riskClasses[$loan->risk_level] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $riskLabels[$loan->risk_level] ?? $loan->risk_level }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $loan->application_date->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex space-x-2">
                                <a href="{{ route('admin.loans.show', $loan->id) }}"
                                   class="px-3 py-1 border border-blue-600 text-blue-600 rounded hover:bg-blue-50"
                                   title="Détails">
                                    <i class="fas fa-eye"></i>
                                </a>

                                @if($loan->status === 'pending')
                                    <a href="{{ route('admin.loans.analyze', $loan->id) }}"
                                       class="px-3 py-1 border border-purple-600 text-purple-600 rounded hover:bg-purple-50"
                                       title="Analyser">
                                        <i class="fas fa-chart-bar"></i>
                                    </a>
                                @endif

                                @if(in_array($loan->status, ['disbursed', 'active']))
                                    <a href="{{ route('admin.loans.schedule', $loan->id) }}"
                                       class="px-3 py-1 border border-green-600 text-green-600 rounded hover:bg-green-50"
                                       title="Échéancier">
                                        <i class="fas fa-calendar-alt"></i>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <i class="fas fa-inbox text-5xl text-gray-400 mb-3"></i>
                            <p class="text-gray-500">Aucun prêt trouvé</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($loans->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $loans->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
