@extends('layouts.app_admin')

@section('title', 'Détails Tontine')
@section('page-title', 'Supervision / Portefeuille Tontine')

@section('content')
<div class="space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 no-print">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.tontines.index') }}" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-xl flex items-center justify-center transition border border-slate-200 text-slate-600">
                <i class="fas fa-chevron-left text-xs"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Détails du Portefeuille Tontine</h2>
                <div class="flex items-center gap-3">
                    <p class="text-slate-500 text-sm font-medium font-mono uppercase">{{ $tontine->account->account_number }}</p>
                    <span class="px-2 py-0.5 bg-purple-50 text-purple-700 border border-purple-100 text-[10px] font-bold rounded-full uppercase tracking-wide">
                        {{ $tontine->account->account_type_name }}
                    </span>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            @if($activeCycle)
                <a href="{{ route('admin.tontines.contribute-form', $tontine->id) }}" class="btn-bank btn-bank-primary">
                    <i class="fas fa-plus mr-2 text-[10px]"></i>
                    <span>Injecter une Cotisation</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Alerte Compte Suspendu -->
    @if($tontine->account->status === 'suspended')
        <div class="bank-card !border-rose-300 !bg-rose-50/50 p-6">
            <div class="flex items-start justify-between gap-4">
                <div class="flex gap-4">
                    <div class="w-12 h-12 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-ban text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-rose-900 uppercase tracking-tight mb-1">Compte Suspendu</h3>
                        <p class="text-xs text-rose-700 font-medium mb-2">Ce compte a été suspendu et ne peut pas recevoir de cotisations.</p>
                        @if($tontine->account->suspension_reason)
                            <p class="text-[10px] font-bold text-rose-600 bg-rose-100 px-3 py-1 rounded-lg inline-block">
                                <i class="fas fa-info-circle mr-1"></i>
                                Raison : {{ $tontine->account->suspension_reason }}
                            </p>
                        @endif
                        @if($tontine->account->suspended_at)
                            <p class="text-[9px] font-bold text-rose-500 mt-2">
                                Suspendu le {{ $tontine->account->suspended_at->format('d/m/Y à H:i') }}
                                @if($tontine->account->suspendedBy)
                                    par {{ $tontine->account->suspendedBy->first_name }} {{ $tontine->account->suspendedBy->last_name }}
                                @endif
                            </p>
                        @endif
                    </div>
                </div>
                <form action="{{ route('admin.accounts.reactivate', $tontine->account->id) }}" method="POST" 
                      onsubmit="return confirm('Êtes-vous sûr de vouloir réactiver ce compte ? Cette action permettra à nouveau les cotisations.');">
                    @csrf
                    <button type="submit" class="btn-bank btn-bank-primary whitespace-nowrap">
                        <i class="fas fa-unlock mr-2 text-[10px]"></i>
                        <span>Réactiver le Compte</span>
                    </button>
                </form>
            </div>
        </div>
    @endif

    <!-- Données du Titulaire -->
    <div class="bank-card p-6 border-trust">
        <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-6 flex items-center gap-2">
            <i class="fas fa-user-tie text-blue-600"></i> Données Stratégiques du Titulaire
        </h5>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 hover:border-blue-200 transition-colors group">
                <p class="text-[8px] font-black text-slate-400 uppercase mb-2">Identité de Tutelle</p>
                <a href="{{ route('admin.clients.show', $tontine->account->client_id) }}" class="text-sm font-black text-blue-600 group-hover:text-blue-800 flex items-center gap-2 transition-colors">
                    {{ $tontine->account->client->full_name }}
                    <i class="fas fa-external-link-alt text-[8px] opacity-0 group-hover:opacity-100 transition-opacity"></i>
                </a>
                <p class="text-[10px] font-mono text-slate-500 mt-1 uppercase">{{ $tontine->account->client->client_number }}</p>
            </div>
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                <p class="text-[8px] font-black text-slate-400 uppercase mb-2">Artefact de Capital</p>
                <p class="text-sm font-black text-slate-900 font-mono">{{ $tontine->account->account_number }}</p>
                <div class="flex items-center gap-2 mt-1">
                    @if($tontine->account->status === 'active')
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-[9px] font-bold text-emerald-600 uppercase">Actif Opérationnel</span>
                    @elseif($tontine->account->status === 'suspended')
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                        <span class="text-[9px] font-bold text-rose-600 uppercase">Compte Suspendu</span>
                    @elseif($tontine->account->status === 'pending_activation')
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        <span class="text-[9px] font-bold text-amber-600 uppercase">En Attente</span>
                    @else
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                        <span class="text-[9px] font-bold text-slate-600 uppercase">{{ $tontine->account->status_name }}</span>
                    @endif
                </div>
            </div>
            <div class="bg-slate-900 p-4 rounded-2xl shadow-xl shadow-slate-900/20 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-20 h-20 bg-white/5 rounded-full -mr-10 -mt-10 blur-xl"></div>
                <p class="text-[8px] font-black text-white/40 uppercase mb-2">Solde de Trésorerie</p>
                <p class="text-xl font-black text-white italic tracking-tight">{{ number_format($tontine->account->balance, 0, ',', ' ') }} <small class="text-xs text-white/50 uppercase not-italic ml-1">XOF</small></p>
            </div>
        </div>
    </div>

    <!-- Protocole de Cotisation & Discipline -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <div class="lg:col-span-3 bank-card p-6">
            <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-6 flex items-center gap-2">
                <i class="fas fa-gears text-purple-600"></i> Protocole de Cotisation Mutuelle
            </h5>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Flux Unitaire</p>
                    <p class="text-sm font-black text-slate-900 font-numeric">{{ number_format($tontine->tontine_amount, 0, ',', ' ') }} <small class="text-[10px]">XOF</small></p>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Rythme des Flux</p>
                    <p class="text-sm font-black text-slate-900 uppercase">
                        @switch($tontine->payment_frequency)
                            @case('daily') Journalier @break
                            @case('weekly') Hebdomadaire @break
                            @case('monthly') Mensuel @break
                        @endswitch
                    </p>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Horizon Temporel</p>
                    <p class="text-sm font-black text-slate-900">{{ $tontine->cycle_duration_months }} <small class="text-[10px]">MOIS</small></p>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Échéance Mensuelle</p>
                    <p class="text-sm font-black text-purple-600 font-numeric">{{ number_format($tontine->expected_monthly_payment, 0, ',', ' ') }} <small class="text-[10px]">XOF</small></p>
                </div>
            </div>
        </div>
        <div class="bank-card p-6 border-l-4 border-amber-500 bg-amber-50/10 flex flex-col justify-center items-center text-center">
            <h5 class="text-[10px] font-black text-amber-900/40 uppercase tracking-widest mb-4">Indice de Discipline</h5>
            <div class="relative">
                <svg class="w-20 h-20 transform -rotate-90">
                    <circle cx="40" cy="40" r="36" stroke="currentColor" stroke-width="8" fill="transparent" class="text-amber-200" />
                    <circle cx="40" cy="40" r="36" stroke="currentColor" stroke-width="8" fill="transparent" stroke-dasharray="226" stroke-dashoffset="4" class="text-amber-500" />
                </svg>
                <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-sm font-black text-amber-800">98.2<small class>%</small></div>
            </div>
            <p class="text-[8px] text-amber-600 font-bold uppercase mt-3 px-4">Basé sur la ponctualité des 12 derniers flux</p>
        </div>
    </div>

    <!-- Cycle actif -->
    @if($activeCycle)
    <div class="bank-card p-6 border-l-4 border-purple-600 bg-gradient-to-br from-purple-50/50 to-white">
        <div class="flex justify-between items-start mb-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center text-xl shadow-sm">
                    <i class="fas fa-arrows-spin"></i>
                </div>
                <div>
                    <h5 class="text-sm font-black text-slate-900 uppercase tracking-tight">Cycle Actif #{{ $activeCycle->cycle_number }}</h5>
                    <p class="text-[10px] font-bold text-slate-500 uppercase mt-0.5">Période : <span class="text-slate-700">{{ $activeCycle->start_date->format('d/m/Y') }}</span> → <span class="text-slate-700">{{ $activeCycle->end_date->format('d/m/Y') }}</span></p>
                </div>
            </div>
            @if($daysRemaining !== null)
                <span class="px-3 py-1.5 rounded-lg border {{ $daysRemaining > 7 ? 'bg-emerald-50 border-emerald-100 text-emerald-700' : 'bg-rose-50 border-rose-100 text-rose-700' }} text-[10px] font-black uppercase flex items-center gap-2">
                    <i class="fas fa-clock"></i>
                    {{ abs($daysRemaining) }} jour(s) {{ $daysRemaining < 0 ? 'restant(s)' : 'dépassé(s)' }}
                </span>
            @endif
        </div>

        @php
            $displayCycle = $selectedCycle;
            $remainingAmount = $displayCycle ? $displayCycle->target_amount - $displayCycle->collected_amount : 0;
            $progressPercent = $displayCycle && $displayCycle->target_amount > 0
                ? round(($displayCycle->collected_amount / $displayCycle->target_amount) * 100, 2)
                : 0;
        @endphp

        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm text-center">
                    <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Cible du Cycle #{{ $displayCycle->cycle_number ?? '?' }}</p>
                    <p class="text-xl font-black text-slate-900 font-numeric">{{ number_format($displayCycle->target_amount ?? 0, 0, ',', ' ') }} <small class="text-xs text-slate-400">XOF</small></p>
                </div>
                <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm text-center">
                    <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Capital Collecté</p>
                    <p class="text-xl font-black text-purple-600 font-numeric">{{ number_format($displayCycle->collected_amount ?? 0, 0, ',', ' ') }} <small class="text-xs text-purple-400">XOF</small></p>
                </div>
                <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm text-center">
                    <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Statut du Cycle</p>
                    <div class="mt-1">
                        @if($displayCycle && $displayCycle->status === 'active')
                            <span class="bank-badge badge-success !text-[10px]">Ouvert / Actif</span>
                        @elseif($displayCycle && $displayCycle->status === 'completed')
                            <span class="bank-badge badge-primary !text-[10px]">Complété / Clôturé</span>
                        @else
                            <span class="bank-badge badge-secondary !text-[10px]">Inexistant</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sélecteur de Cycle -->
            <div class="flex items-center gap-2 overflow-x-auto pb-4 no-scrollbar border-b border-slate-100">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest mr-2 shrink-0">Naviguer :</span>
                @foreach($cycles as $cycle)
                    <a href="{{ route('admin.tontines.show', ['tontine' => $tontine->id, 'cycle_number' => $cycle->cycle_number]) }}" 
                       class="px-4 py-1.5 rounded-lg text-[10px] font-black uppercase transition-all shrink-0
                       {{ ($selectedCycle && $selectedCycle->id === $cycle->id) 
                           ? 'bg-purple-600 text-white shadow-lg shadow-purple-600/20' 
                           : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                        Cycle #{{ $cycle->cycle_number }}
                    </a>
                @endforeach
            </div>

            <!-- Calendrier de Tontine (Grille Visuelle) -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h6 class="text-[10px] font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                        <i class="fas fa-calendar-alt text-purple-600"></i> Calendrier du Cycle #{{ $displayCycle->cycle_number ?? '?' }}
                    </h6>
                    <span class="text-[9px] font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full uppercase">
                        {{ $gridFilledSlots }} / {{ $gridTotalSlots }} {{ $tontine->payment_frequency === 'daily' ? 'Jours' : ($tontine->payment_frequency === 'weekly' ? 'Semaines' : 'Mois') }}
                    </span>
                </div>
                
                <div class="grid grid-cols-7 sm:grid-cols-11 md:grid-cols-15 lg:grid-cols-31 gap-2">
                    @for($i = 1; $i <= $gridTotalSlots; $i++)
                        <div class="aspect-square rounded-lg border-2 flex items-center justify-center relative group transition-all duration-300
                            {{ $i <= $gridFilledSlots 
                                ? 'bg-rose-500 border-rose-600 text-white shadow-lg shadow-rose-500/20' 
                                : 'bg-white border-slate-100 text-slate-200' }}">
                            <span class="text-[8px] font-black">{{ $i }}</span>
                            
                            @if($i <= $gridFilledSlots)
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="w-full h-[2px] bg-white/30 rotate-45 absolute"></div>
                                    <div class="w-full h-[2px] bg-white/30 -rotate-45 absolute"></div>
                                </div>
                            @endif
                            
                            <!-- Tooltip simple -->
                            <div class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 bg-slate-900 text-white text-[8px] py-1 px-2 rounded opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity whitespace-nowrap z-10">
                                {{ $tontine->payment_frequency === 'daily' ? 'Jour' : 'Semaine' }} {{ $i }} 
                                {{ $i <= $gridFilledSlots ? '(Cotisé)' : '(En attente)' }}
                            </div>
                        </div>
                    @endfor
                </div>
            </div>

            <div>
                <div class="flex justify-between text-[10px] font-black uppercase mb-2">
                    <span class="text-slate-500">Progression de Collecte</span>
                    <span class="text-purple-600">{{ $progressPercent }}%</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-500 to-purple-700 h-2.5 rounded-full transition-all duration-1000 shadow-[0_0_10px_rgba(168,85,247,0.4)]" style="width: {{ min($progressPercent, 100) }}%"></div>
                </div>
            </div>

            <!-- Actions de Cycle -->
            <div class="pt-6 border-t border-slate-100 flex flex-wrap gap-4">
                @if($hasPendingPayout)
                    <div class="w-full mb-2 bg-emerald-50 border border-emerald-100 rounded-xl p-4 flex items-center justify-between shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-600 border border-emerald-200">
                                <i class="fas fa-check text-lg"></i>
                            </div>
                            <div>
                                <p class="text-xs font-black text-emerald-800 uppercase tracking-tight">Cycle #{{ $pendingPayoutCycle->cycle_number }} Prêt au Déblocage</p>
                                <p class="text-[10px] font-bold text-emerald-600">Net à payer : <span class="font-black italic">{{ number_format($pendingPayoutCycle->payout_amount, 0, ',', ' ') }} XOF</span> (Après commission microfinance)</p>
                            </div>
                        </div>
                        <form action="{{ route('admin.tontines.payout', $tontine->id) }}" method="POST"
                              onsubmit="return confirm('Confirmez-vous le déblocage effectif de {{ number_format($pendingPayoutCycle->payout_amount, 0, ',', ' ') }} XOF pour ce client ?');">
                            @csrf
                            <button type="submit" class="btn-bank btn-bank-success !py-2">
                                <i class="fas fa-hand-holding-dollar mr-2"></i>Débloquer Capital
                            </button>
                        </form>
                    </div>
                @endif

                @if($activeCycle && $activeCycle->collected_amount > 0)
                    <form action="{{ route('admin.tontines.cycles.close', $activeCycle->id) }}" method="POST" 
                          onsubmit="return confirm('ALERTE COMMISSION : La clôture de ce cycle prélèvera obligatoirement {{ number_format($dailyCommission, 0, ',', ' ') }} XOF au profit de la microfinance, conformément à la règle du 1/31. Continuer ?');">
                        @csrf
                        <button type="submit" class="px-6 py-2.5 bg-amber-600 text-white rounded-xl hover:bg-amber-700 transition-all shadow-lg shadow-amber-600/20 text-[10px] font-black uppercase tracking-widest">
                            <i class="fas fa-stop-circle mr-2"></i>Clôture Anticipée (Prélèvement {{ number_format($dailyCommission, 0, ',', ' ') }})
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    @else
    <div class="bank-card p-8 border-l-4 border-amber-400 bg-amber-50/20">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center text-amber-600 text-xl">
                <i class="fas fa-pause"></i>
            </div>
            <div>
                <h4 class="text-sm font-black text-amber-800 uppercase tracking-tight">Aucun Cycle Actif</h4>
                <p class="text-xs text-amber-600 font-medium">Le protocole est actuellement en pause. Aucun flux ne peut être enregistré.</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Statistiques globales -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bank-card p-5">
            <span class="kpi-label">Cycles Totaux</span>
            <div class="kpi-value !text-2xl mt-1 text-slate-800">{{ $stats['total_cycles'] }}</div>
        </div>
        <div class="bank-card p-5">
            <span class="kpi-label">Cycles Clôturés</span>
            <div class="kpi-value !text-2xl mt-1 text-emerald-600">{{ $stats['completed_cycles'] }}</div>
        </div>
        <div class="bank-card p-5">
            <span class="kpi-label">Volume Cumulé</span>
            <div class="kpi-value !text-xl mt-1 text-purple-600">{{ number_format($stats['total_contributions'], 0, ',', ' ') }} <small class="text-xs">XOF</small></div>
        </div>
        <div class="bank-card p-5">
            <span class="kpi-label">Taux Réussite</span>
            <div class="kpi-value !text-2xl mt-1 text-blue-600">{{ $stats['completion_rate'] }}<small class="text-base">%</small></div>
        </div>
    </div>

    <!-- Dernières cotisations -->
    <div class="bank-card overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <h5 class="text-xs font-black text-slate-800 uppercase tracking-widest">Flux Récents</h5>
            <a href="{{ route('admin.tontines.contributions', $tontine->id) }}" class="text-[10px] font-black text-blue-600 uppercase hover:underline flex items-center gap-1">
                Audit Complet <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="bank-table">
                <thead>
                    <tr>
                        <th>Date & Heure</th>
                        <th>Référence Transaction</th>
                        <th>Volume Financier</th>
                        <th>Canal</th>
                        <th>Opérateur</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($tontine->account->transactions->take(10) as $transaction)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td>
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-slate-700">{{ $transaction->transaction_date->format('d/m/Y') }}</span>
                                <span class="text-[9px] font-bold text-slate-400">{{ $transaction->transaction_date->format('H:i') }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="font-mono text-xs font-bold text-slate-600 bg-slate-100 px-2 py-1 rounded">{{ $transaction->transaction_reference }}</span>
                        </td>
                        <td>
                            <span class="text-sm font-black text-purple-600 font-numeric">{{ number_format($transaction->amount, 0, ',', ' ') }} <small class="text-[9px]">XOF</small></span>
                        </td>
                        <td>
                            @switch($transaction->payment_method)
                                @case('cash')
                                    <span class="inline-flex items-center gap-1 text-[9px] font-black uppercase text-emerald-600 bg-emerald-50 px-2 py-1 rounded-full"><i class="fas fa-money-bill-wave"></i> Espèces</span>
                                    @break
                                @case('mobile_money')
                                    <span class="inline-flex items-center gap-1 text-[9px] font-black uppercase text-blue-600 bg-blue-50 px-2 py-1 rounded-full"><i class="fas fa-mobile-screen-button"></i> Mobile</span>
                                    @break
                                @case('bank_transfer')
                                    <span class="inline-flex items-center gap-1 text-[9px] font-black uppercase text-purple-600 bg-purple-50 px-2 py-1 rounded-full"><i class="fas fa-building-columns"></i> Virement</span>
                                    @break
                            @endswitch
                        </td>
                        <td>
                            <div class="flex flex-col">
                                <span class="text-[10px] font-bold text-slate-700">{{ $transaction->processedBy->first_name ?? 'Système' }}</span>
                                <span class="text-[8px] font-bold text-slate-400 uppercase">Au Guichet</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center">
                            <i class="fas fa-folder-open text-3xl text-slate-200 mb-3 block"></i>
                            <p class="text-xs font-bold text-slate-400 uppercase">Aucun flux enregistré récemment</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
