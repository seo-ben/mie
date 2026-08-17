@extends('layouts.app_admin')

@section('title', 'Historique des Cotisations')
@section('page-title', 'Grand Livre / Flux Tontine')

@section('content')
<div class="space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 no-print">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.tontines.show', $tontine->id) }}" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-xl flex items-center justify-center transition border border-slate-200 text-slate-600">
                <i class="fas fa-chevron-left text-xs"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Registre Historique des Cotisations</h2>
                <div class="flex items-center gap-3">
                    <p class="text-slate-500 text-sm font-medium">{{ $tontine->account->client->first_name }} {{ $tontine->account->client->last_name }}</p>
                    <span class="text-slate-300">|</span>
                    <p class="text-slate-500 text-sm font-medium font-mono uppercase">{{ $tontine->account->account_number }}</p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.tontines.contribute-form', $tontine->id) }}" class="btn-bank btn-bank-primary">
                <i class="fas fa-plus mr-2 text-[10px]"></i>
                <span>Injecter une Cotisation</span>
            </a>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bank-card p-5 border-trust">
            <span class="kpi-label">Volume Total Cotisé</span>
            <div class="kpi-value !text-xl mt-1 text-purple-600">{{ number_format($stats['total_amount'], 0, ',', ' ') }} <small class="text-xs">XOF</small></div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">Cumul Historique</p>
        </div>
        <div class="bank-card p-5">
            <span class="kpi-label">Nombre de Flux</span>
            <div class="kpi-value !text-xl mt-1">{{ number_format($stats['total_count']) }}</div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">Transactions Validées</p>
        </div>
        <div class="bank-card p-5">
            <span class="kpi-label">Moyenne des Flux</span>
            <div class="kpi-value !text-xl mt-1 text-blue-600">{{ number_format($stats['average_amount'], 0, ',', ' ') }} <small class="text-xs">XOF</small></div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">Par Transaction</p>
        </div>
    </div>

    <!-- Filtres -->
    <div class="bank-card p-6">
        <form method="GET" action="{{ route('admin.tontines.contributions', $tontine->id) }}" class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <div class="md:col-span-3">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Cycle de Tontine</label>
                <select name="cycle_id" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold text-slate-600 focus:ring-1 focus:ring-blue-500 outline-none transition uppercase">
                    <option value="">Tous les cycles</option>
                    @foreach($cycles as $cycle)
                        <option value="{{ $cycle->id }}" {{ request('cycle_id') == $cycle->id ? 'selected' : '' }}>
                            Cycle #{{ $cycle->cycle_number }} ({{ $cycle->start_date->format('d/m/Y') }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-3">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Période Du</label>
                <input type="date"
                       name="date_from"
                       class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold text-slate-600 focus:ring-1 focus:ring-blue-500 outline-none transition uppercase"
                       value="{{ request('date_from') }}">
            </div>

            <div class="md:col-span-3">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Au</label>
                <input type="date"
                       name="date_to"
                       class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold text-slate-600 focus:ring-1 focus:ring-blue-500 outline-none transition uppercase"
                       value="{{ request('date_to') }}">
            </div>

            <div class="md:col-span-3 flex items-end gap-2">
                <button type="submit" class="btn-bank btn-bank-primary flex-1 h-[38px]">
                    <i class="fas fa-filter text-[10px] mr-2"></i>Filtrer
                </button>
                <a href="{{ route('admin.tontines.contributions', $tontine->id) }}" class="btn-bank btn-bank-outline h-[38px] px-4">
                    <i class="fas fa-rotate"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Liste des cotisations -->
    <div class="bank-card overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
            <h5 class="text-xs font-black text-slate-800 uppercase tracking-widest">Journal des Opérations ({{ $contributions->total() }})</h5>
        </div>
        <div class="overflow-x-auto">
            <table class="bank-table">
                <thead>
                    <tr>
                        <th>Date & Heure</th>
                        <th>Référence Transaction</th>
                        <th>Volume Financier</th>
                        <th>Canal</th>
                        <th>Référence Externe</th>
                        <th>Solde Résiduel</th>
                        <th>Opérateur</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($contributions as $contribution)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td>
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-slate-700">{{ $contribution->transaction_date->format('d/m/Y') }}</span>
                                <span class="text-[9px] font-bold text-slate-400">{{ $contribution->transaction_date->format('H:i') }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="font-mono text-xs font-bold text-slate-600 bg-slate-100 px-2 py-1 rounded">{{ $contribution->transaction_reference }}</span>
                        </td>
                        <td>
                            <span class="text-sm font-black text-purple-600 font-numeric">{{ number_format($contribution->amount, 0, ',', ' ') }} <small class="text-[9px]">XOF</small></span>
                        </td>
                        <td>
                            @switch($contribution->payment_method)
                                @case('cash')
                                    <span class="inline-flex items-center gap-1 text-[9px] font-black uppercase text-emerald-600 bg-emerald-50 px-2 py-1 rounded-full"><i class="fas fa-money-bill-wave"></i> Espèces</span>
                                    @break
                                @case('mobile_money')
                                    <span class="inline-flex items-center gap-1 text-[9px] font-black uppercase text-blue-600 bg-blue-50 px-2 py-1 rounded-full"><i class="fas fa-mobile-screen-button"></i> Mobile {{ $contribution->mobile_money_operator ? '('.strtoupper($contribution->mobile_money_operator).')' : '' }}</span>
                                    @break
                                @case('bank_transfer')
                                    <span class="inline-flex items-center gap-1 text-[9px] font-black uppercase text-purple-600 bg-purple-50 px-2 py-1 rounded-full"><i class="fas fa-building-columns"></i> Virement</span>
                                    @break
                                @default
                                    <span class="inline-flex items-center gap-1 text-[9px] font-black uppercase text-slate-600 bg-slate-100 px-2 py-1 rounded-full">{{ $contribution->payment_method }}</span>
                            @endswitch
                        </td>
                        <td>
                            @if($contribution->payment_reference)
                                <span class="font-mono text-[10px] font-bold text-slate-500 uppercase">{{ $contribution->payment_reference }}</span>
                            @else
                                <span class="text-slate-300 text-xs">-</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex flex-col text-right">
                                <span class="text-[9px] font-bold text-slate-400 uppercase">Avant: {{ number_format($contribution->balance_before, 0, ',', ' ') }}</span>
                                <span class="text-[10px] font-black text-emerald-600 uppercase">Après: {{ number_format($contribution->balance_after, 0, ',', ' ') }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="flex flex-col">
                                <span class="text-[10px] font-bold text-slate-700">{{ $contribution->processedBy->first_name ?? 'Système' }}</span>
                                @if($contribution->processed_at)
                                    <span class="text-[8px] font-bold text-slate-400 uppercase">{{ $contribution->processed_at->format('d/m/Y H:i') }}</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            @switch($contribution->status)
                                @case('completed')
                                    <span class="inline-flex items-center gap-1 text-[9px] font-black uppercase text-emerald-600 bg-emerald-50 px-2 py-1 rounded-full"><i class="fas fa-check-circle"></i> Validé</span>
                                    @break
                                @case('pending')
                                    <span class="inline-flex items-center gap-1 text-[9px] font-black uppercase text-amber-600 bg-amber-50 px-2 py-1 rounded-full"><i class="fas fa-clock"></i> En Attente</span>
                                    @break
                                @case('failed')
                                    <span class="inline-flex items-center gap-1 text-[9px] font-black uppercase text-rose-600 bg-rose-50 px-2 py-1 rounded-full"><i class="fas fa-times-circle"></i> Échec</span>
                                    @break
                                @default
                                    <span class="inline-flex items-center gap-1 text-[9px] font-black uppercase text-slate-600 bg-slate-100 px-2 py-1 rounded-full">{{ $contribution->status }}</span>
                            @endswitch
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center">
                            <i class="fas fa-search text-3xl text-slate-200 mb-3 block"></i>
                            <p class="text-xs font-bold text-slate-400 uppercase">Aucun flux ne correspond aux critères de recherche</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($contributions->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
            {{ $contributions->withQueryString()->links() }}
        </div>
        @endif
    </div>

    <!-- Résumé par cycle -->
    @if($cycles->isNotEmpty())
    <div class="bank-card p-6">
        <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-6 flex items-center gap-2">
            <i class="fas fa-layer-group text-purple-600"></i> Segmentation par Cycle
        </h5>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($cycles as $cycle)
                @php
                    $cycleContributions = $contributions->filter(function($c) use ($cycle) {
                        return $c->transaction_date >= $cycle->start_date && $c->transaction_date <= $cycle->end_date;
                    });
                    $cycleTotal = $cycleContributions->sum('amount');
                    $cycleCount = $cycleContributions->count();
                    $cycleProgress = $cycle->target_amount > 0 ? round(($cycle->collected_amount / $cycle->target_amount) * 100, 1) : 0;
                @endphp
                <div class="p-4 rounded-xl border border-slate-200 hover:border-purple-300 transition-all group {{ $cycle->status === 'active' ? 'bg-purple-50/30' : 'bg-white' }}">
                    <div class="flex items-center justify-between mb-3">
                        <h6 class="text-xs font-black text-slate-800 uppercase">Cycle #{{ $cycle->cycle_number }}</h6>
                        @switch($cycle->status)
                            @case('active')
                                <span class="text-[8px] font-black uppercase text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Actif</span>
                                @break
                            @case('completed')
                                <span class="text-[8px] font-black uppercase text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full">Clôturé</span>
                                @break
                        @endswitch
                    </div>
                    <div class="text-[10px] font-bold text-slate-500 uppercase mb-4 flex items-center gap-2">
                        <i class="fas fa-calendar-alt text-slate-300"></i>
                        {{ $cycle->start_date->format('d/m/Y') }} → {{ $cycle->end_date->format('d/m/Y') }}
                    </div>
                    
                    <div class="space-y-2 mb-4">
                        <div class="flex justify-between items-center bg-slate-50 p-2 rounded">
                            <span class="text-[9px] font-bold text-slate-500 uppercase">Collecté</span>
                            <span class="text-xs font-black text-purple-600">{{ number_format($cycleTotal, 0, ',', ' ') }} XOF</span>
                        </div>
                        <div class="flex justify-between items-center px-2">
                            <span class="text-[9px] font-bold text-slate-400 uppercase">Cible</span>
                            <span class="text-[10px] font-bold text-slate-600">{{ number_format($cycle->target_amount, 0, ',', ' ') }} XOF</span>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <div class="flex justify-between text-[8px] font-bold uppercase">
                            <span class="text-slate-400">Complétion</span>
                            <span class="text-purple-600">{{ $cycleProgress }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-purple-500 h-1.5 rounded-full" style="width: {{ min($cycleProgress, 100) }}%"></div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
