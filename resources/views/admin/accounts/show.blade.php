@extends('layouts.app_admin')

@section('title', 'Dossier d\'Audit du compte - ' . $account->account_number)
@section('page-title', 'Protocole / Détails du Compte')

@section('content')
<div class="space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 no-print">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.accounts.index') }}" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-xl flex items-center justify-center transition border border-slate-200 text-slate-600">
                <i class="fas fa-chevron-left text-xs"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Audit du compte d'Actifs</h2>
                <div class="flex items-center gap-3 mt-1">
                    <p class="text-slate-500 text-sm font-medium">Référence : <span class="font-mono text-blue-600 font-bold tracking-widest uppercase">{{ $account->account_number }}</span></p>
                    <span class="bank-badge {{ $account->account_type === 'savings' ? 'badge-info' : 'badge-warning' }} !text-[8px] uppercase font-black tracking-widest flex items-center gap-1">
                        {{ $account->account_type_name }}
                        @if($account->account_type === 'tontine' && $account->tontineAccount && $account->tontineAccount->total_paid >= $account->tontineAccount->total_expected && $account->tontineAccount->total_expected > 0)
                            <i class="fas fa-star text-amber-400 animate-pulse" title="Objectif Global Atteint"></i>
                        @elseif($account->account_type === 'tontine' && $account->tontineAccount && $account->tontineAccount->activeCycle && $account->tontineAccount->activeCycle->collected_amount >= $account->tontineAccount->activeCycle->target_amount)
                            <i class="fas fa-certificate text-emerald-400" title="Cycle Actuel Atteint"></i>
                        @endif
                    </span>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            @if($account->status === 'active')
                <button type="button" class="btn-bank btn-bank-danger" onclick="openSuspendModal()">
                    <i class="fas fa-ban mr-2 text-[10px]"></i> Suspendre le Protocole
                </button>
            @elseif($account->status === 'suspended')
                <form method="POST" action="{{ route('admin.accounts.reactivate', $account->id) }}" class="inline">
                    @csrf
                    <button type="submit" class="btn-bank btn-bank-success" onclick="return confirm('Confirmer la réactivation opérationnelle de ce compte ?')">
                        <i class="fas fa-check-double mr-2 text-[10px]"></i> Rétablir le Protocole
                    </button>
                </form>
            @endif
            <a href="{{ route('admin.accounts.transactions', $account->id) }}" class="btn-bank btn-bank-primary">
                <i class="fas fa-list-ul mr-2 text-[10px]"></i> Historique des Flux
            </a>
        </div>
    </div>

    <!-- Matrice de Statut & Trésorerie -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Colonne Latérale : Statut & Titulaire -->
        <div class="lg:col-span-4 space-y-8">
            <!-- État de la Trésorerie Premium -->
            <div class="bank-card p-0 overflow-hidden border-trust shadow-xl shadow-slate-200/50">
                <div class="px-8 py-10 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 relative overflow-hidden">
                    <!-- Décoration de fond -->
                    <div class="absolute top-0 right-0 w-40 h-40 bg-white/5 rounded-full -mr-20 -mt-20 blur-2xl"></div>
                    <div class="absolute bottom-0 left-0 w-32 h-32 bg-blue-500/10 rounded-full -ml-16 -mb-16 blur-2xl"></div>
                    
                    <div class="relative z-10">
                        <div class="flex justify-center mb-6">
                            @switch($account->status)
                                @case('active')
                                    <div class="flex items-center gap-2 px-4 py-1.5 bg-emerald-500/10 border border-emerald-500/20 rounded-full">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span class="text-[10px] font-black text-emerald-400 uppercase tracking-widest">Compte Opérationnel</span>
                                    </div>
                                    @break
                                @case('suspended')
                                    <div class="flex items-center gap-2 px-4 py-1.5 bg-rose-500/10 border border-rose-500/20 rounded-full">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        <span class="text-[10px] font-black text-rose-400 uppercase tracking-widest">Protocole Interrompu</span>
                                    </div>
                                    @break
                                @case('pending_activation')
                                    <div class="flex items-center gap-2 px-4 py-1.5 bg-amber-500/10 border border-amber-500/20 rounded-full">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-bounce"></span>
                                        <span class="text-[10px] font-black text-amber-400 uppercase tracking-widest">Attente d'Audit</span>
                                    </div>
                                    @break
                            @endswitch
                        </div>
                        
                        <div class="text-center">
                            <p class="text-[10px] font-black text-white/40 uppercase tracking-[0.2em] mb-2">Trésorerie Disponible</p>
                            <div class="flex items-baseline justify-center gap-2">
                                <span class="text-4xl font-black text-white italic tracking-tighter">{{ number_format($account->balance, 0, ',', ' ') }}</span>
                                <span class="text-xs font-black text-white/50 uppercase italic">XOF</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-8">
                    @if($account->status === 'active')
                        <div class="grid grid-cols-2 gap-4">
                            <a href="{{ route('admin.accounts.deposit.form', $account->id) }}" class="flex flex-col items-center justify-center p-5 bg-white border border-slate-100 rounded-3xl hover:border-emerald-200 hover:bg-emerald-50/30 transition-all group active:scale-95 shadow-sm hover:shadow-md">
                                <div class="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                                    <i class="fas fa-arrow-down"></i>
                                </div>
                                <span class="text-[10px] font-black text-slate-800 uppercase tracking-tight">Injection</span>
                            </a>
                            <a href="{{ $account->balance > 0 ? route('admin.accounts.withdrawal.form', $account->id) : '#' }}" 
                               class="flex flex-col items-center justify-center p-5 bg-white border border-slate-100 rounded-3xl transition-all group active:scale-95 shadow-sm 
                                    {{ $account->balance > 0 ? 'hover:border-rose-200 hover:bg-rose-50/30 hover:shadow-md' : 'opacity-50 cursor-not-allowed bg-slate-50' }}">
                                <div class="w-10 h-10 rounded-2xl {{ $account->balance > 0 ? 'bg-rose-100 text-rose-600' : 'bg-slate-200 text-slate-400' }} flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                                    <i class="fas fa-arrow-up"></i>
                                </div>
                                <span class="text-[10px] font-black {{ $account->balance > 0 ? 'text-slate-800' : 'text-slate-400' }} uppercase tracking-tight">Extraction</span>
                            </a>
                        </div>
                    @else
                        <div class="p-6 bg-slate-50 rounded-3xl border border-dashed border-slate-200 text-center">
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mb-3 mx-auto">
                                <i class="fas fa-lock"></i>
                            </div>
                            <p class="text-[11px] font-bold text-slate-500 leading-tight">OPÉRATIONS BLOQUÉES<br><span class="text-[9px] font-medium text-slate-400">Le protocole de sécurité est actif.</span></p>
                        </div>
                    @endif
                </div>
            </div>


            <!-- Signalétique du Titulaire -->
            <div class="bank-card overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="text-[10px] font-black text-slate-800 uppercase tracking-widest">Tutelle de l'Entité</h3>
                </div>
                <div class="p-6">
                    <div class="flex items-center gap-4 mb-6">
                        @if($account->client->profile_photo_url)
                            <img src="{{ Storage::url($account->client->profile_photo_url) }}" class="w-14 h-14 rounded-xl object-cover border-2 border-white shadow-sm">
                        @else
                            <div class="w-14 h-14 bg-slate-900 rounded-xl flex items-center justify-center text-white font-black text-lg">
                                {{ substr($account->client->first_name, 0, 1) }}{{ substr($account->client->last_name, 0, 1) }}
                            </div>
                        @endif
                        <div>
                            <a href="{{ route('admin.clients.show', $account->client_id) }}" class="text-sm font-black text-slate-900 hover:text-blue-600 transition truncate block max-w-[180px]">
                                {{ $account->client->full_name }}
                            </a>
                            <p class="text-[10px] font-mono font-bold text-blue-600 uppercase">{{ $account->client->client_number }}</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex justify-between items-center text-[10px]">
                            <span class="font-bold text-slate-400 uppercase tracking-tight">Signalétique</span>
                            <span class="font-black text-slate-800">{{ $account->client->phone }}</span>
                        </div>
                        <div class="flex justify-between items-center text-[10px]">
                            <span class="font-bold text-slate-400 uppercase tracking-tight">Conformité KYC</span>
                            <span class="bank-badge {{ $account->client->kyc_status === 'approved' ? 'badge-success' : 'badge-warning' }} !text-[8px] uppercase font-black">
                                {{ $account->client->kyc_status === 'approved' ? 'Validé' : 'En Audit' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paramètres de Gouvernance -->
            <div class="bank-card p-6 space-y-4">
                <h3 class="text-[10px] font-black text-slate-800 uppercase tracking-widest border-b border-slate-100 pb-2">Audit de Gouvernance</h3>
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="w-1.5 h-1.5 rounded-full bg-blue-500 mt-1.5"></div>
                        <div>
                            <p class="text-[9px] font-bold text-slate-400 uppercase leading-none">Capture Initiale</p>
                            <p class="text-[11px] font-black text-slate-800 mt-1">{{ $account->created_at->format('d/m/Y H:i') }}</p>
                            @if($account->createdBy)
                                <p class="text-[8px] font-bold text-slate-300 uppercase mt-0.5">Par : {{ $account->createdBy->name }}</p>
                            @endif
                        </div>
                    </div>
                    @if($account->activated_at)
                    <div class="flex items-start gap-3">
                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 mt-1.5"></div>
                        <div>
                            <p class="text-[9px] font-bold text-slate-400 uppercase leading-none">Déploiement Opérationnel</p>
                            <p class="text-[11px] font-black text-slate-800 mt-1">{{ $account->activated_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                    @endif
                    @if($account->last_transaction_at)
                    <div class="flex items-start gap-3">
                        <div class="w-1.5 h-1.5 rounded-full bg-purple-500 mt-1.5"></div>
                        <div>
                            <p class="text-[9px] font-bold text-slate-400 uppercase leading-none">Dernier Flux Détecté</p>
                            <p class="text-[11px] font-black text-slate-800 mt-1">{{ $account->last_transaction_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                    @endif
                </div>

                @if($account->status === 'suspended' && $account->suspension_reason)
                <div class="mt-6 p-4 bg-rose-50 rounded-xl border border-rose-100">
                    <p class="text-[10px] font-black text-rose-700 uppercase mb-2 flex items-center gap-2">
                        <i class="fas fa-triangle-exclamation"></i> Note de Suspension
                    </p>
                    <p class="text-[11px] font-bold text-rose-600 leading-relaxed italic">"{{ $account->suspension_reason }}"</p>
                    <p class="text-[8px] font-black text-rose-400 uppercase mt-2 italic">— Journalisé le {{ $account->suspended_at?->format('d/m/Y') }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Colonne Principale : Détails & Flux -->
        <div class="lg:col-span-8 space-y-8">
            <!-- Inclusion des Partiaux (Note : Ces partials sont supposés suivre le même style) -->
            @if($account->account_type === 'savings')
                @include('admin.accounts.partials.savings-details', ['account' => $account, 'stats' => $stats])
            @else
                @include('admin.accounts.partials.tontine-details', ['account' => $account, 'stats' => $stats])
            @endif

            <!-- Métriques de Performance de Flux Premium -->
            <div class="bank-card p-0 overflow-hidden shadow-xl shadow-slate-200/50">
                <div class="px-8 py-5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                    <h3 class="text-[10px] font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                        <i class="fas fa-chart-pie text-blue-600"></i> Métriques Analytiques des Flux
                    </h3>
                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tight italic">Consolidation Temps Réel</span>
                </div>
                <div class="p-8">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-0">
                        <!-- Injection -->
                        <div class="text-center px-4 group">
                            <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-3 group-hover:bg-emerald-600 group-hover:text-white transition-all duration-300">
                                <i class="fas fa-arrow-trend-up text-xs"></i>
                            </div>
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 group-hover:text-emerald-700 transition-colors">Vélocité Entrante</p>
                            <p class="text-xl font-black text-slate-900 leading-none">{{ number_format($stats['total_deposits'], 0, ',', ' ') }}</p>
                            <p class="text-[9px] font-bold text-emerald-500 uppercase mt-2 tracking-tighter">XOF (Somme)</p>
                        </div>
                        
                        <!-- Extraction -->
                        <div class="text-center px-4 group border-l border-slate-100">
                            <div class="w-10 h-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-3 group-hover:bg-rose-600 group-hover:text-white transition-all duration-300">
                                <i class="fas fa-arrow-trend-down text-xs"></i>
                            </div>
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 group-hover:text-rose-700 transition-colors">Vélocité Sortante</p>
                            <p class="text-xl font-black text-slate-900 leading-none">{{ number_format($stats['total_withdrawals'], 0, ',', ' ') }}</p>
                            <p class="text-[9px] font-bold text-rose-500 uppercase mt-2 tracking-tighter">XOF (Somme)</p>
                        </div>

                        <!-- Itérations -->
                        <div class="text-center px-4 group border-l border-slate-100">
                            <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-3 group-hover:bg-blue-600 group-hover:text-white transition-all duration-300">
                                <i class="fas fa-fingerprint text-xs"></i>
                            </div>
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 group-hover:text-blue-700 transition-colors">Volume de Cycles</p>
                            <p class="text-xl font-black text-slate-900 leading-none">{{ number_format($stats['transaction_count']) }}</p>
                            <p class="text-[9px] font-bold text-blue-500 uppercase mt-2 tracking-tighter">Itérations</p>
                        </div>

                        <!-- Horodatage -->
                        <div class="text-center px-4 group border-l border-slate-100">
                            <div class="w-10 h-10 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center mx-auto mb-3 group-hover:bg-purple-600 group-hover:text-white transition-all duration-300">
                                <i class="fas fa-clock-rotate-left text-xs"></i>
                            </div>
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 group-hover:text-purple-700 transition-colors">Activité Récente</p>
                            <p class="text-base font-black text-slate-900 leading-tight truncate">
                                @if($stats['last_transaction'])
                                    {{ $stats['last_transaction']->diffForHumans(null, true) }}
                                @else
                                    <span class="text-slate-200">Néant</span>
                                @endif
                            </p>
                            <p class="text-[9px] font-bold text-purple-500 uppercase mt-2 tracking-tighter">Horodatage</p>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Artefacts des Derniers Flux -->
            <div class="bank-card overflow-hidden shadow-2xl shadow-slate-900/5">
                <div class="px-8 py-5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                    <h3 class="text-[10px] font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                        <i class="fas fa-barcode text-blue-600"></i> Journaux de Flux Récents
                    </h3>
                    <a href="{{ route('admin.accounts.transactions', $account->id) }}" class="text-[10px] font-black text-blue-600 uppercase hover:underline tracking-tight">
                        Audit du Grand Livre Complet
                    </a>
                </div>
                <div class="overflow-x-auto">
                    @if($account->transactions->count() > 0)
                    <table class="bank-table">
                        <thead>
                            <tr class="!bg-slate-50/20">
                                <th class="uppercase tracking-widest text-[9px]">Horodatage</th>
                                <th class="uppercase tracking-widest text-[9px]">Nature du Flux</th>
                                <th class="uppercase tracking-widest text-[9px]">Volume (XOF)</th>
                                <th class="uppercase tracking-widest text-[9px]">Solde Post-Flux</th>
                                <th class="text-center uppercase tracking-widest text-[9px]">Validation</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($account->transactions as $transaction)
                            <tr class="hover:bg-slate-50/50 transition-colors group">
                                <td class="text-[11px] font-bold text-slate-500 uppercase py-4">
                                    {{ $transaction->transaction_date ? $transaction->transaction_date->format('d/m/Y H:i') : 'Indéterminé' }}
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        @switch($transaction->transaction_type)
                                            @case('deposit')
                                                <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                                                <span class="text-[9px] font-black text-emerald-700 uppercase tracking-tight">Injection (Dépôt)</span>
                                                @break
                                            @case('withdrawal')
                                                <div class="w-1.5 h-1.5 rounded-full bg-rose-500"></div>
                                                <span class="text-[9px] font-black text-rose-700 uppercase tracking-tight">Extraction (Retrait)</span>
                                                @break
                                            @case('transfer')
                                                <div class="w-1.5 h-1.5 rounded-full bg-cyan-500"></div>
                                                <span class="text-[9px] font-black text-cyan-700 uppercase tracking-tight">Migration (Transfert)</span>
                                                @break
                                            @case('fee')
                                                <div class="w-1.5 h-1.5 rounded-full bg-amber-500"></div>
                                                <span class="text-[9px] font-black text-amber-700 uppercase tracking-tight">Taxe (Frais Protocole)</span>
                                                @break
                                            @default
                                                <span class="text-[9px] font-black text-slate-400 uppercase">{{ $transaction->transaction_type }}</span>
                                        @endswitch
                                    </div>
                                </td>
                                <td>
                                    <p class="text-xs font-black text-slate-900">{{ number_format($transaction->amount, 0, ',', ' ') }} <small class="text-[8px] text-slate-400 uppercase">XOF</small></p>
                                </td>
                                <td>
                                    <p class="text-xs font-bold text-slate-400">{{ number_format($transaction->balance_after, 0, ',', ' ') }} <small class="text-[8px] uppercase">XOF</small></p>
                                </td>
                                <td class="text-center">
                                    <span class="bank-badge {{ $transaction->status === 'completed' ? 'badge-success' : 'badge-warning' }} !text-[7px] uppercase font-black tracking-widest shadow-sm">
                                        {{ $transaction->status === 'completed' ? 'Traité' : 'En Attente' }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <div class="py-20 text-center">
                        <i class="fas fa-inbox text-4xl text-slate-200 mb-4 block"></i>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest italic">Aucun flux journalisé sur ce compte pour le cycle actuel</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Suspension du Protocole Custom -->
<div id="suspendModal" class="fixed inset-0 z-[100] hidden overflow-y-auto">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeSuspendModal()"></div>

    <!-- Modal Content -->
    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md animate-in fade-in zoom-in duration-300">
            <form method="POST" action="{{ route('admin.accounts.suspend', $account->id) }}">
                @csrf
                <div class="px-8 py-6 bg-rose-600 text-white flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-black uppercase tracking-tight leading-none mb-1">Suspension du Protocole</h3>
                        <p class="text-[10px] font-bold text-white/70 uppercase tracking-widest">Action de Supervision Critique</p>
                    </div>
                    <button type="button" onclick="closeSuspendModal()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition">
                        <i class="fas fa-xmark text-sm"></i>
                    </button>
                </div>
                
                <div class="p-8 space-y-6">
                    <div class="p-4 bg-amber-50 rounded-2xl border border-amber-200 flex items-start gap-4">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-triangle-exclamation text-amber-500"></i>
                        </div>
                        <div class="text-[11px] font-bold text-amber-800 leading-relaxed uppercase tracking-tight">
                            Cette action interrompra immédiatement toute injection ou extraction de capital sur ce compte. Un audit complet sera requis pour la réactivation.
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Justification Administrative *</label>
                        <textarea name="reason" rows="4" required placeholder="Détaillez la raison documentaire de l'interruption..." class="bank-input resize-none focus:ring-rose-500 min-h-[120px]"></textarea>
                    </div>
                </div>

                <div class="p-8 bg-slate-50 border-t border-slate-100 flex gap-4">
                    <button type="button" onclick="closeSuspendModal()" class="btn-bank btn-bank-outline py-4 flex-1 text-xs uppercase font-black">Abandonner</button>
                    <button type="submit" class="btn-bank btn-bank-danger py-4 flex-1 text-xs uppercase font-black shadow-lg shadow-rose-500/20">Confirmer l'Interruption</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openSuspendModal() {
        const modal = document.getElementById('suspendModal');
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeSuspendModal() {
        const modal = document.getElementById('suspendModal');
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    // Fermer avec la touche Échap
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeSuspendModal();
        }
    });
</script>

@if(session('print_receipt'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        window.open('{{ session('print_receipt') }}', '_blank');
    });
</script>
@endif
@endsection
