@extends('layouts.app_admin')

@section('title', 'Dossier d\'Intelligence Adhérent - ' . $client->full_name)
@section('page-title', 'Protocole / Supervision de l\'Adhérent')

@section('content')
<div class="space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 no-print">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.clients.index') }}" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-xl flex items-center justify-center transition border border-slate-200 text-slate-600">
                <i class="fas fa-chevron-left text-xs"></i>
            </a>
            <div class="flex items-center gap-4">
                @if($client->profile_photo_url)
                    <img src="{{ asset('storage/' . $client->profile_photo_url) }}" class="w-14 h-14 rounded-xl object-cover border-2 border-white shadow-sm">
                @else
                    <div class="w-14 h-14 bg-slate-900 rounded-xl flex items-center justify-center text-white font-black text-lg">
                        {{ strtoupper(substr($client->first_name, 0, 1) . substr($client->last_name, 0, 1)) }}
                    </div>
                @endif
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $client->full_name }}</h2>
                    <p class="text-slate-500 text-sm font-medium">Référence : <span class="font-mono text-blue-600 font-bold uppercase">{{ $client->client_number }}</span></p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.clients.edit', $client->id) }}" class="btn-bank btn-bank-outline">
                <i class="fas fa-pen-nib mr-2 text-[10px]"></i> Modifier le Dossier
            </a>
            @if($client->is_active)
                <form action="{{ route('admin.clients.deactivate-accounts', $client->id) }}" method="POST" onsubmit="return confirm('Confirmer la révocation des accès pour cette entité ?');">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn-bank btn-bank-danger">
                        <i class="fas fa-shield-slash mr-2 text-[10px]"></i> Révoquer Accès
                    </button>
                </form>
            @else
                <form action="{{ route('admin.clients.activate-accounts', $client->id) }}" method="POST" onsubmit="return confirm('Confirmer la réactivation opérationnelle de cette entité ?');">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn-bank btn-bank-success">
                        <i class="fas fa-shield-check mr-2 text-[10px]"></i> Réactiver
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Matrice de Conformité KYC & Indice de Confiance -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bank-card p-6 border-trust relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-slate-50 rounded-full -mr-16 -mt-16"></div>
            <div class="flex flex-wrap items-center justify-between gap-6 relative z-10 text-nowrap">
                <div class="flex items-center gap-6">
                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-2xl
                        {{ $client->kyc_status === 'approved' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 
                           ($client->kyc_status === 'rejected' ? 'bg-rose-50 text-rose-600 border border-rose-100' : 'bg-amber-50 text-amber-600 border border-amber-100') }}">
                        <i class="fas {{ $client->kyc_status === 'approved' ? 'fa-shield-check' : ($client->kyc_status === 'rejected' ? 'fa-shield-xmark' : 'fa-shield-clock') }}"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Protocole de Vérification (KYC)</h3>
                        <div class="flex items-center gap-2 mt-1">
                            @if($client->kyc_status === 'approved')
                                <span class="bank-badge badge-success">Entité Vérifiée & Approuvée</span>
                            @elseif($client->kyc_status === 'rejected')
                                <span class="bank-badge badge-danger">Protocole Rejeté / Risque Détecté</span>
                            @else
                                <span class="bank-badge badge-warning">Audit de Sécurité en Attente</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-6 pt-6 border-t border-slate-100 flex items-center justify-between gap-4 relative z-10 text-nowrap">
                @if($client->kyc_status === 'pending')
                    <a href="{{ route('admin.clients.validate-kyc', $client->id) }}" class="btn-bank btn-bank-primary flex-1">
                        <i class="fas fa-gavel mr-2 text-[10px]"></i> Autoriser le KYC
                    </a>
                @endif
                <a href="{{ route('admin.loans.create', ['client_id' => $client->id]) }}" class="btn-bank flex-1 {{ $client->kyc_status === 'approved' ? 'btn-bank-primary' : 'opacity-50 cursor-not-allowed pointer-events-none' }}">
                    <i class="fas fa-hand-holding-dollar mr-2 text-[10px]"></i> Engager un Crédit
                </a>
            </div>
        </div>

        <!-- Indice de Confiance / Credit Scoring -->
        <div class="bank-card p-6 border-l-4 border-blue-600 relative overflow-hidden">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Indice de Confiance Institutionnelle</h3>
                    <p class="text-[10px] text-slate-400 font-bold uppercase mt-1">Évaluation algorithmique du profil de risque</p>
                </div>
                <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 border border-blue-100 italic font-black text-sm">
                    {{ number_format($client->credit_score ?? 0, 0) }}
                </div>
            </div>
            
            <div class="space-y-4 text-nowrap">
                <div class="flex justify-between items-center text-[10px] font-black uppercase tracking-tight">
                    <span class="text-slate-400 font-medium">Scoring de Crédibilité</span>
                    <span class="{{ ($client->credit_score ?? 0) >= 70 ? 'text-emerald-600' : (($client->credit_score ?? 0) >= 40 ? 'text-amber-600' : 'text-rose-600') }}">
                        @if(($client->credit_score ?? 0) >= 70) EXCELLENT @elseif(($client->credit_score ?? 0) >= 40) MODÉRÉ @else CRITIQUE @endif
                    </span>
                </div>
                <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden border border-slate-200">
                    <div class="h-full bg-gradient-to-r {{ ($client->credit_score ?? 0) >= 70 ? 'from-emerald-400 to-emerald-600' : (($client->credit_score ?? 0) >= 40 ? 'from-amber-400 to-amber-600' : 'from-rose-400 to-rose-600') }} transition-all duration-1000" style="width: {{ $client->credit_score ?? 0 }}%"></div>
                </div>
                <p class="text-[9px] font-bold text-slate-400 uppercase italic">
                    <i class="fas fa-circle-info text-blue-500 mr-1"></i>
                    Basé sur la ponctualité des flux, l'ancienneté du dossier et le volume des actifs sous gestion.
                </p>
            </div>
        </div>
    </div>

    <!-- Indicateurs de Portefeuille -->
    @php
        $totalBalance = $client->accounts->sum('balance');
        $activeAccounts = $client->accounts->where('status', 'active')->count();
        $savingsBalance = $client->accounts->where('account_type', 'savings')->sum('balance');
        $tontineBalance = $client->accounts->where('account_type', 'tontine')->sum('balance');
    @endphp
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bank-card p-6">
            <div class="flex items-center justify-between mb-4">
                <span class="kpi-label uppercase">Capitaux Totaux</span>
                <i class="fas fa-vault text-blue-500 text-xs"></i>
            </div>
            <div class="kpi-value truncate">{{ number_format($totalBalance, 0, ',', ' ') }} <small class="text-xs">XOF</small></div>
            <div class="mt-4 pt-4 border-t border-slate-100 flex justify-between text-[9px] font-bold uppercase">
                <span class="text-slate-400">comptes Actifs :</span>
                <span class="text-blue-600">{{ $activeAccounts }} / {{ $client->accounts->count() }}</span>
            </div>
        </div>

        <div class="bank-card p-6">
            <div class="flex items-center justify-between mb-4">
                <span class="kpi-label uppercase">compte Épargne</span>
                <i class="fas fa-piggy-bank text-emerald-500 text-xs"></i>
            </div>
            <div class="kpi-value truncate text-emerald-600">{{ number_format($savingsBalance, 0, ',', ' ') }} <small class="text-xs">XOF</small></div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">Disponibilités Liquides</p>
        </div>

        <div class="bank-card p-6">
            <div class="flex items-center justify-between mb-4">
                <span class="kpi-label uppercase">compte Tontine</span>
                <i class="fas fa-users-rays text-purple-500 text-xs"></i>
            </div>
            <div class="kpi-value truncate text-purple-600">{{ number_format($tontineBalance, 0, ',', ' ') }} <small class="text-xs">XOF</small></div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">Participation Collective</p>
        </div>

        <div class="bank-card p-6">
            <div class="flex items-center justify-between mb-4">
                <span class="kpi-label uppercase">Engagements</span>
                <i class="fas fa-file-invoice-dollar text-amber-500 text-xs"></i>
            </div>
            <div class="kpi-value truncate">{{ $client->loans->count() }} <small class="text-xs">Lignes</small></div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">Exposition au Crédit</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Section Détails -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Registre des Comptes (comptes de Capital) -->
            <div class="bank-card overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                    <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest">comptes de Capital Gérés</h3>
                    @if($client->kyc_status === 'approved')
                        <a href="{{ route('admin.accounts.create', $client->id) }}" class="text-[10px] font-black text-blue-600 uppercase hover:underline">
                            + Initialiser un compte
                        </a>
                    @endif
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($client->accounts as $account)
                        <div class="p-6 transition hover:bg-slate-50/50">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-xl {{ $account->account_type === 'savings' ? 'bg-emerald-50 text-emerald-600' : 'bg-purple-50 text-purple-600' }} flex items-center justify-center text-lg border border-current opacity-20">
                                        <i class="fas {{ $account->account_type === 'savings' ? 'fa-wallet' : 'fa-people-group' }}"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-slate-800">{{ $account->account_number }}</p>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-[9px] font-bold uppercase {{ $account->account_type === 'savings' ? 'text-emerald-600' : 'text-purple-600' }}">
                                                {{ $account->account_type === 'savings' ? 'Compte Épargne' : 'Compte Tontine' }}
                                            </span>
                                            <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                                            <span class="text-[9px] font-bold uppercase {{ $account->status === 'active' ? 'text-blue-600' : 'text-slate-400' }}">
                                                {{ $account->status === 'active' ? 'Opérationnel' : 'Suspendu' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-xl font-black text-slate-900">{{ number_format($account->balance, 0, ',', ' ') }} <small class="text-[10px] text-slate-400">XOF</small></p>
                                    <a href="{{ route('admin.accounts.show', $account->id) }}" class="text-[9px] font-bold text-blue-600 uppercase hover:underline mt-1 block">Audit des Flux</a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-12 text-center">
                            <i class="fas fa-folder-open text-3xl text-slate-200 mb-3"></i>
                            <p class="text-xs font-bold text-slate-400 uppercase">Aucun compte de capital initialisé</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Identité & Gouvernance -->
            <div class="bank-card p-8">
                <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest mb-6 flex items-center gap-2">
                    <i class="fas fa-id-card-clip text-blue-600"></i> Attributs d'Identité & Gouvernance
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-6">
                    <div class="space-y-1">
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Nom de l'Entité</p>
                        <p class="text-sm font-black text-slate-800">{{ $client->full_name }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Canal de Contact</p>
                        <p class="text-sm font-black text-slate-800">{{ $client->phone }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Protocole de Naissance</p>
                        <p class="text-sm font-black text-slate-800">{{ $client->date_of_birth?->format('d/m/Y') ?? 'Non Auditée' }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Secteur Professionnel</p>
                        <p class="text-sm font-black text-slate-800">{{ $client->profession ?? 'Non Déclaré' }}</p>
                    </div>
                    <div class="space-y-1 md:col-span-2">
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">compte Géographique de Résidence</p>
                        <p class="text-sm font-black text-slate-800">{{ $client->address }}, {{ $client->city }}, {{ $client->region }}</p>
                    </div>
                </div>

                <div class="mt-8 pt-8 border-t border-slate-100">
                    <h4 class="text-[10px] font-black text-slate-700 uppercase tracking-widest mb-4">Artefact d'Identification</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <p class="text-[8px] font-bold text-slate-400 uppercase mb-1">Type Document</p>
                            <p class="text-xs font-black text-slate-800 uppercase">{{ str_replace('_', ' ', $client->id_type) }}</p>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <p class="text-[8px] font-bold text-slate-400 uppercase mb-1">Numéro de Série</p>
                            <p class="text-xs font-mono font-black text-blue-600">{{ $client->id_number }}</p>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <p class="text-[8px] font-bold text-slate-400 uppercase mb-1">Expiration de Validité</p>
                            <p class="text-xs font-black text-slate-800">{{ $client->id_expiry_date?->format('d/m/Y') ?? 'Indéterminée' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Analytique / Sidebar -->
        <div class="space-y-8">
            <!-- Profil de Risque & Distribution -->
            @if($totalBalance > 0)
                <div class="bank-card p-6">
                    <h3 class="text-[10px] font-black text-slate-800 uppercase tracking-widest mb-6">Allocation des Flux</h3>
                    <div class="space-y-6">
                        @if($savingsBalance > 0)
                            <div>
                                <div class="flex justify-between text-[10px] font-black uppercase mb-2">
                                    <span class="text-emerald-600">Épargne Liquide</span>
                                    <span>{{ round(($savingsBalance / $totalBalance) * 100) }}%</span>
                                </div>
                                <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-500 rounded-full" style="width: {{ ($savingsBalance / $totalBalance) * 100 }}%"></div>
                                </div>
                            </div>
                        @endif
                        @if($tontineBalance > 0)
                            <div>
                                <div class="flex justify-between text-[10px] font-black uppercase mb-2">
                                    <span class="text-purple-600">Engagement Tontine</span>
                                    <span>{{ round(($tontineBalance / $totalBalance) * 100) }}%</span>
                                </div>
                                <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-purple-500 rounded-full" style="width: {{ ($tontineBalance / $totalBalance) * 100 }}%"></div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Historique de Gouvernance -->
            <div class="bank-card p-6">
                <h3 class="text-[10px] font-black text-slate-800 uppercase tracking-widest mb-6">Audit Système</h3>
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="w-1.5 h-1.5 rounded-full bg-blue-500 mt-1.5"></div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase leading-none">Capture Initiale</p>
                            <p class="text-[11px] font-black text-slate-800 mt-1">{{ $client->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 mt-1.5"></div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase leading-none">Dernière Synchronisation</p>
                            <p class="text-[11px] font-black text-slate-800 mt-1">{{ $client->updated_at->diffForHumans(null, true) }}</p>
                        </div>
                    </div>
                    @if($client->registeredBy)
                        <div class="flex items-start gap-3">
                            <div class="w-1.5 h-1.5 rounded-full bg-purple-500 mt-1.5"></div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase leading-none">Officier de Capture</p>
                                <p class="text-[11px] font-black text-slate-800 mt-1">{{ $client->registeredBy->name }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Actions Critiques -->
            <div class="bank-card p-6 bg-slate-900 text-white">
                <h3 class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-4">Commandes de Supervision</h3>
                <div class="grid grid-cols-1 gap-3">
                    <a href="{{ route('admin.clients.edit', $client->id) }}" class="flex items-center justify-between p-3 bg-white/5 border border-white/10 rounded-xl hover:bg-white/10 transition text-xs font-bold">
                        <span>Éditer les Paramètres</span>
                        <i class="fas fa-chevron-right text-[10px] opacity-30"></i>
                    </a>
                    @if($client->kyc_status === 'approved')
                        <a href="{{ route('admin.accounts.create', $client->id) }}" class="flex items-center justify-between p-3 bg-blue-600 rounded-xl hover:bg-blue-700 transition text-xs font-bold shadow-lg shadow-blue-500/20">
                            <span>Ajouter un compte de Capital</span>
                            <i class="fas fa-plus-circle text-[10px]"></i>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
