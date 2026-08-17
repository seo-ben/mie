@extends('layouts.app_admin')

@section('title', 'Registre des Crédits')
@section('page-title', 'Supervision du Portefeuille Crédit')

@section('content')
<div class="space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Registre du Portefeuille de Crédits</h2>
            <p class="text-slate-500 text-sm font-medium">Surveillance et validation fiscale du marché de crédit actif</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.loans.report') }}" class="btn-bank btn-bank-outline">
                <i class="fas fa-chart-line mr-2 text-[10px]"></i> Rapport de Portefeuille
            </a>
            <a href="{{ route('admin.loans.create') }}" class="btn-bank btn-bank-primary">
                <i class="fas fa-plus mr-2 text-[10px]"></i> Nouvelle Entrée de Crédit
            </a>
        </div>
    </div>

    <!-- Matrice d'Exposition aux Risques -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bank-card p-5 border-trust">
            <span class="kpi-label">Contrats Actifs</span>
            <div class="kpi-value !text-xl mt-1">{{ number_format($stats['total_loans']) }}</div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">Cumul du Registre</p>
        </div>
        <div class="bank-card p-5">
            <span class="kpi-label">En Attente de Validation</span>
            <div class="kpi-value !text-xl mt-1 text-amber-600">{{ number_format($stats['pending_loans']) }}</div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">Évaluation des Risques en Cours</p>
        </div>
        <div class="bank-card p-5">
            <span class="kpi-label">Crédits en Cycle</span>
            <div class="kpi-value !text-xl mt-1 text-emerald-600">{{ number_format($stats['active_loans']) }}</div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">Fonds Actuellement Décaissés</p>
        </div>
        <div class="bank-card p-5 border-l-4 border-rose-600 bg-rose-50/10">
            <div class="flex items-center justify-between">
                <span class="kpi-label text-rose-800">Exposition au Risque</span>
                <span class="text-[10px] font-black text-rose-600 uppercase">PAR 30/90</span>
            </div>
            <div class="flex items-end justify-between mt-1">
                <div class="kpi-value !text-xl text-rose-900">{{ number_format($stats['active_portfolio'], 0, ',', ' ') }} <small class="text-xs">XOF</small></div>
                <div class="text-right">
                    <p class="text-[10px] font-bold text-rose-500">P30: {{ number_format($stats['par_30'] ?? 0, 1) }}%</p>
                    <p class="text-[10px] font-bold text-rose-700">P90: {{ number_format($stats['par_90'] ?? 0, 1) }}%</p>
                </div>
            </div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">Poids Global & Alerte Recouvrement</p>
        </div>
    </div>

    <!-- Filtres de Supervision -->
    <div class="bank-card p-6">
        <form method="GET" action="{{ route('admin.loans.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <div class="md:col-span-4 relative">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher par N° Prêt, Identité Client ou Référence..." class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:ring-1 focus:ring-blue-500 outline-none transition uppercase">
                <i class="fas fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            </div>

            <div class="md:col-span-2">
                <select name="status" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold text-slate-600 focus:ring-1 focus:ring-blue-500 outline-none transition uppercase">
                    <option value="">Tous les Statuts</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En Attente</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approuvé</option>
                    <option value="disbursed" {{ request('status') === 'disbursed' ? 'selected' : '' }}>Décaissé</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Soldé</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejeté</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <select name="risk_level" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold text-slate-600 focus:ring-1 focus:ring-blue-500 outline-none transition uppercase">
                    <option value="">Niveaux de Risque</option>
                    <option value="low" {{ request('risk_level') === 'low' ? 'selected' : '' }}>Faible Risque</option>
                    <option value="medium" {{ request('risk_level') === 'medium' ? 'selected' : '' }}>Modéré</option>
                    <option value="high" {{ request('risk_level') === 'high' ? 'selected' : '' }}>Élevé</option>
                    <option value="very_high" {{ request('risk_level') === 'very_high' ? 'selected' : '' }}>Critique</option>
                </select>
            </div>

            <div class="md:col-span-4 flex gap-2">
                <button type="submit" class="btn-bank btn-bank-primary flex-1">
                    <i class="fas fa-filter mr-2 text-[10px]"></i> Auditer le Registre
                </button>
                <a href="{{ route('admin.loans.index') }}" class="btn-bank btn-bank-outline px-4">
                    <i class="fas fa-rotate"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Volet du Registre des Crédits -->
    <div class="bank-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="bank-table">
                <thead>
                    <tr>
                        <th>Identité Crédit / Adhérent</th>
                        <th>Allocation Fiscale</th>
                        <th class="text-center">Cycle de Vie</th>
                        <th class="text-right">Reste à Payer</th>
                        <th class="text-center">Statut d'Audit</th>
                        <th class="text-center">Vecteur de Risque</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($loans as $loan)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded bg-slate-100 flex items-center justify-center text-slate-500 border border-slate-200">
                                    <i class="fas fa-file-invoice-dollar text-sm"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-800 leading-tight">{{ $loan->client->full_name }}</p>
                                    <p class="text-[9px] font-mono font-bold text-blue-600 uppercase tracking-tighter mt-0.5">{{ $loan->loan_number }}</p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="flex flex-col">
                                <p class="text-sm font-extrabold text-slate-800 font-numeric">{{ number_format($loan->approved_amount ?? $loan->requested_amount, 0, ',', ' ') }} <small class="text-[10px] text-slate-400">XOF</small></p>
                                <p class="text-[9px] font-bold text-slate-400 uppercase">Taux : {{ $loan->interest_rate }}%</p>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="text-xs font-bold text-slate-600">{{ $loan->duration_months }} Mois</span>
                        </td>
                        <td class="text-right">
                            @if(in_array($loan->status, ['approved', 'disbursed', 'active', 'completed']))
                                <div class="flex flex-col items-end">
                                    <span class="text-sm font-bold font-numeric border-b border-dashed {{ $loan->remaining_amount > 0 ? 'text-amber-600 border-amber-200' : 'text-emerald-600 border-emerald-200' }} pb-0.5">
                                        {{ number_format($loan->remaining_amount, 0, ',', ' ') }}
                                    </span>
                                    <span class="text-[9px] font-bold text-slate-400 uppercase mt-1">XOF</span>
                                </div>
                            @else
                                <span class="text-xs text-slate-400 font-bold">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @php
                                $statusLabels = [
                                    'pending' => ['label' => 'En Attente', 'class' => 'badge-warning'],
                                    'approved' => ['label' => 'Autorisé', 'class' => 'badge-success'],
                                    'disbursed' => ['label' => 'Actif', 'class' => 'badge-info'],
                                    'active' => ['label' => 'En Cours', 'class' => 'badge-info'],
                                    'completed' => ['label' => 'Soldé', 'class' => 'badge-secondary'],
                                    'rejected' => ['label' => 'Refusé', 'class' => 'badge-danger'],
                                ];
                                $s = $statusLabels[$loan->status] ?? ['label' => $loan->status, 'class' => 'badge-secondary'];
                            @endphp
                            <span class="bank-badge {{ $s['class'] }}">{{ $s['label'] }}</span>
                        </td>
                        <td class="text-center">
                            @php
                                $riskLabels = [
                                    'low' => ['label' => 'Faible Risque', 'class' => 'text-emerald-600 bg-emerald-50'],
                                    'medium' => ['label' => 'Modéré', 'class' => 'text-amber-600 bg-amber-50'],
                                    'high' => ['label' => 'Élevé', 'class' => 'text-orange-600 bg-orange-50'],
                                    'very_high' => ['label' => 'Haut Risque', 'class' => 'text-rose-600 bg-rose-50'],
                                ];
                                $r = $riskLabels[$loan->risk_level] ?? ['label' => 'Non Noté', 'class' => 'text-slate-400 bg-slate-50'];
                            @endphp
                            <span class="text-[9px] font-extrabold uppercase px-2 py-1 rounded-full {{ $r['class'] }}">
                                {{ $r['label'] }}
                            </span>
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.loans.show', $loan->id) }}" class="p-2 text-slate-400 hover:text-blue-600 transition" title="Audit Dossier">
                                    <i class="fas fa-folder-open text-xs"></i>
                                </a>
                                @if($loan->status === 'pending')
                                    <a href="{{ route('admin.loans.analyze', $loan->id) }}" class="p-2 text-slate-400 hover:text-purple-600 transition" title="Analyse de Risque">
                                        <i class="fas fa-microscope text-xs"></i>
                                    </a>
                                @endif
                                @if(in_array($loan->status, ['disbursed', 'active']))
                                    <a href="{{ route('admin.loans.schedule', $loan->id) }}" class="p-2 text-slate-400 hover:text-emerald-600 transition" title="Échéancier de Remboursement">
                                        <i class="fas fa-calendar-check text-xs"></i>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-20 text-center">
                            <i class="fas fa-vault text-3xl text-slate-200 mb-4 block"></i>
                            <p class="text-xs font-bold text-slate-400 uppercase">Le registre des crédits est actuellement vide</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($loans->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/30">
            {{ $loans->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
