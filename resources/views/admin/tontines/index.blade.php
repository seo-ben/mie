@extends('layouts.app_admin')

@section('title', 'Registre des Tontines')
@section('page-title', 'Supervision / Cycles d\'Épargne Mutuelle')

@section('content')
<div class="space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Registre de Gestion des Tontines</h2>
            <p class="text-slate-500 text-sm font-medium">Surveillance des cycles d'épargne mutuelle et dynamique des pools de liquidité</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.tontines.visual-audit') }}" class="btn-bank btn-bank-outline">
                <i class="fas fa-magnifying-glass mr-2 text-[10px]"></i> Audit Visuel
            </a>
            <a href="{{ route('admin.tontines.report') }}" class="btn-bank btn-bank-outline">
                <i class="fas fa-chart-line mr-2 text-[10px]"></i> Analytique
            </a>
            <a href="#" class="btn-bank btn-bank-primary">
                <i class="fas fa-plus mr-2 text-[10px]"></i> Nouveau
            </a>
        </div>
    </div>

    <!-- Matrice d'Épargne -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bank-card p-5 border-trust">
            <span class="kpi-label">Adhérents Actifs</span>
            <div class="kpi-value !text-xl mt-1">{{ number_format($stats['total_tontines']) }}</div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">Membres Uniques Vérifiés</p>
        </div>
        <div class="bank-card p-5">
            <span class="kpi-label">Cycles en Cours</span>
            <div class="kpi-value !text-xl mt-1 text-emerald-600">{{ number_format($stats['active_cycles']) }}</div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">Pools Opérationnels Parallèles</p>
        </div>
        <div class="bank-card p-5">
            <span class="kpi-label">Collecte Brute</span>
            <div class="kpi-value !text-xl mt-1 text-blue-600">{{ number_format($stats['total_collected'], 0, ',', ' ') }} <small class="text-xs">XOF</small></div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">Liquidité Cumulée des Pools</p>
        </div>
        <div class="bank-card p-5">
            <span class="kpi-label">Sorties Totales</span>
            <div class="kpi-value !text-xl mt-1 text-purple-600">{{ number_format($stats['total_paid_out'], 0, ',', ' ') }} <small class="text-xs">XOF</small></div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">Volume des Règlements</p>
        </div>
        <div class="bank-card p-5">
            <span class="kpi-label">En Attente de Revue</span>
            <div class="kpi-value !text-xl mt-1 text-amber-600">{{ number_format($stats['pending_collections']) }}</div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">En Attente de Vérification</p>
        </div>
    </div>

    <!-- Contrôles d'Audit -->
    <div class="bank-card p-6">
        <form method="GET" action="{{ route('admin.tontines.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <div class="md:col-span-5 relative">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Recherche par ID Membre, Nom ou N° Nœud..." class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:ring-1 focus:ring-blue-500 outline-none transition uppercase">
                <i class="fas fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            </div>

            <div class="md:col-span-3">
                <select name="payment_frequency" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold text-slate-600 focus:ring-1 focus:ring-blue-500 outline-none transition uppercase">
                    <option value="">Fréquence de Collecte</option>
                    <option value="daily" {{ request('payment_frequency') === 'daily' ? 'selected' : '' }}>Cadence Journalière</option>
                    <option value="weekly" {{ request('payment_frequency') === 'weekly' ? 'selected' : '' }}>Fenêtre Hebdomadaire</option>
                    <option value="monthly" {{ request('payment_frequency') === 'monthly' ? 'selected' : '' }}>Règlement Mensuel</option>
                </select>
            </div>

            <div class="md:col-span-4 flex gap-2">
                <button type="submit" class="btn-bank btn-bank-primary flex-1">
                    <i class="fas fa-search text-[10px]"></i> Auditer
                </button>
                <a href="{{ route('admin.tontines.index') }}" class="btn-bank btn-bank-outline px-4">
                    <i class="fas fa-rotate"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Volet du Registre d'Épargne -->
    <div class="bank-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="bank-table">
                <thead>
                    <tr>
                        <th>Identité Membre</th>
                        <th>Configuration de Cycle</th>
                        <th>Dynamique Interne</th>
                        <th>Progression Fiscale</th>
                        <th>Actif Total</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($tontines as $tontine)
                    @php
                        $activeCycle = $tontine->cycles->where('status', 'active')->first();
                        $progressPercent = $activeCycle && $activeCycle->target_amount > 0
                            ? round(($activeCycle->collected_amount / $activeCycle->target_amount) * 100, 1)
                            : 0;
                        
                        $freqLabels = [
                            'daily' => 'Journalière',
                            'weekly' => 'Hebdomadaire',
                            'monthly' => 'Mensuelle'
                        ];
                    @endphp
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded bg-slate-100 flex items-center justify-center text-slate-500 border border-slate-200">
                                    <i class="fas fa-rotate text-[10px]"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-800 leading-tight">{{ $tontine->account->client->full_name }}</p>
                                    <a href="{{ route('admin.accounts.show', $tontine->account_id) }}" class="text-[9px] font-mono font-bold text-blue-600 uppercase tracking-tighter mt-0.5 hover:underline">
                                        {{ $tontine->account->account_number }}
                                    </a>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="flex flex-col">
                                <p class="text-[11px] font-extrabold text-slate-800 font-numeric">{{ number_format($tontine->tontine_amount, 0, ',', ' ') }} <small class="text-[9px] text-slate-400">XOF</small></p>
                                <p class="text-[9px] font-bold text-slate-400 uppercase">Cadence {{ $freqLabels[$tontine->payment_frequency] ?? ucfirst($tontine->payment_frequency) }}</p>
                            </div>
                        </td>
                        <td>
                            @if($activeCycle)
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-bold text-emerald-600 uppercase">Cycle #{{ $activeCycle->cycle_number }} Actif</span>
                                    <span class="text-[9px] text-slate-400 font-medium">{{ $activeCycle->start_date->format('d M') }} → {{ $activeCycle->end_date->format('d M') }}</span>
                                </div>
                            @else
                                <span class="bank-badge badge-secondary !text-[8px]">Inerte</span>
                            @endif
                        </td>
                        <td class="min-w-[150px]">
                            @if($activeCycle)
                                <div class="space-y-1.5 p-1">
                                    <div class="flex justify-between items-center text-[9px] font-bold uppercase tracking-tighter">
                                        <span class="text-slate-500">{{ number_format($activeCycle->collected_amount, 0, ',', ' ') }}</span>
                                        <span class="text-slate-400">Cible : {{ number_format($activeCycle->target_amount, 0, ',', ' ') }}</span>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-1 overflow-hidden">
                                        <div class="bg-purple-600 h-1 rounded-full transition-all duration-500" style="width: {{ min($progressPercent, 100) }}%"></div>
                                    </div>
                                    <p class="text-[8px] font-extrabold text-purple-600 text-right">{{ $progressPercent }}% Complet</p>
                                </div>
                            @else
                                <span class="text-[10px] text-slate-300 font-medium italic">Protocole en attente</span>
                            @endif
                        </td>
                        <td>
                            <p class="text-[11px] font-extrabold text-slate-800 font-numeric">
                                {{ number_format($tontine->account->balance, 0, ',', ' ') }} <small class="text-[9px] text-slate-400">XOF</small>
                            </p>
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.tontines.show', $tontine->id) }}" class="p-2 text-slate-400 hover:text-blue-600 transition" title="Auditer le Dossier">
                                    <i class="fas fa-folder-tree text-xs"></i>
                                </a>
                                @if($activeCycle)
                                    <a href="{{ route('admin.tontines.contribute-form', $tontine->id) }}" class="p-2 text-slate-400 hover:text-purple-600 transition" title="Injecter des Fonds">
                                        <i class="fas fa-hand-holding-dollar text-xs"></i>
                                    </a>
                                @endif
                                <a href="{{ route('admin.tontines.contributions', $tontine->id) }}" class="p-2 text-slate-400 hover:text-emerald-600 transition" title="Historique du Grand Livre">
                                    <i class="fas fa-clock-rotate-left text-xs"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-20 text-center">
                            <i class="fas fa-circle-nodes text-3xl text-slate-200 mb-4 block"></i>
                            <p class="text-xs font-bold text-slate-400 uppercase">Le registre universel des tontines est actuellement vide</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tontines->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/30">
            {{ $tontines->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
