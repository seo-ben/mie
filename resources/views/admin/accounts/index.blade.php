@extends('layouts.app_admin')

@section('title', 'Registre des Comptes d\'Actifs Institutionnels')
@section('page-title', 'Protocole / Gestion du Grand Livre')

@section('content')
<div class="space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Comptes d'Actifs Financiers</h2>
            <p class="text-slate-500 text-sm font-medium">Surveillance des conteneurs de capitaux liquides et des comptes d'épargne mutuelle</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.accounts.transfer.form') }}" class="btn-bank btn-bank-primary shadow-lg shadow-blue-500/20">
                <i class="fas fa-exchange-alt mr-2 text-[10px]"></i> Faire un Transfert
            </a>
            <button class="btn-bank btn-bank-outline">
                <i class="fas fa-file-export mr-2 text-[10px]"></i> Export du Registre d'Actifs
            </button>
        </div>
    </div>

    <!-- Matrice Fiscale -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bank-card p-5 border-trust">
            <span class="kpi-label font-black uppercase">Compte des comptes</span>
            <div class="kpi-value !text-xl mt-1">{{ number_format($stats['total_accounts']) }}</div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">Portefeuilles Actifs Gérés</p>
        </div>
        <div class="bank-card p-5">
            <span class="kpi-label font-black uppercase">Protocoles Actifs</span>
            <div class="kpi-value !text-xl mt-1 text-emerald-600">{{ number_format($stats['active_accounts']) }}</div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">comptes opérationnels vérifiés</p>
        </div>
        <div class="bank-card p-5">
            <span class="kpi-label font-black uppercase">Supervision Requise</span>
            <div class="kpi-value !text-xl mt-1 text-amber-600">{{ number_format($stats['pending_accounts']) }}</div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">En attente de protocole d'activation</p>
        </div>
        <div class="bank-card p-5">
            <span class="kpi-label font-black uppercase">Exposition Brute Trésorerie</span>
            <div class="kpi-value !text-xl mt-1 text-blue-600">{{ number_format($stats['total_balance'], 0, ',', ' ') }} <small class="text-xs">XOF</small></div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">Poids total de la trésorerie agrégée</p>
        </div>
    </div>

    <!-- Contrôles d'Audit -->
    <div class="bank-card p-6">
        <form method="GET" action="{{ route('admin.accounts.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <div class="md:col-span-4 relative">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Recherche par N° de compte, Identité Adhérent ou Référence..." class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:ring-1 focus:ring-blue-500 outline-none transition uppercase font-bold text-slate-600">
                <i class="fas fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            </div>

            <div class="md:col-span-3">
                <select name="account_type" class="bank-input uppercase">
                    <option value="">Hiérarchie des Actifs</option>
                    <option value="savings" {{ request('account_type') === 'savings' ? 'selected' : '' }}>Épargne Institutionnelle</option>
                    <option value="tontine" {{ request('account_type') === 'tontine' ? 'selected' : '' }}>Épargne Mutuelle (Tontine)</option>
                </select>
            </div>

            <div class="md:col-span-3">
                <select name="status" class="bank-input uppercase">
                    <option value="">Statut de Vérification</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Vérifié (Actif)</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspension de Supervision</option>
                    <option value="pending_activation" {{ request('status') === 'pending_activation' ? 'selected' : '' }}>En attente de Protocole</option>
                </select>
            </div>

            <div class="md:col-span-2 flex gap-2">
                <button type="submit" class="btn-bank btn-bank-primary flex-1 shadow-lg shadow-blue-500/10">
                    <i class="fas fa-search text-[10px]"></i>
                </button>
                <a href="{{ route('admin.accounts.index') }}" class="btn-bank btn-bank-outline px-4">
                    <i class="fas fa-rotate"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Registre d'Actifs -->
    <div class="bank-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="bank-table">
                <thead>
                    <tr>
                        <th class="uppercase tracking-widest text-[10px]">Identité du compte</th>
                        <th class="uppercase tracking-widest text-[10px]">Adhérent Associé</th>
                        <th class="uppercase tracking-widest text-[10px]">Type Fiscal</th>
                        <th class="uppercase tracking-widest text-[10px]">Solde Fiscal</th>
                        <th class="text-center uppercase tracking-widest text-[10px]">Vérification</th>
                        <th class="text-right uppercase tracking-widest text-[10px]">Opérations</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($accounts as $account)
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 border border-slate-200 group-hover:bg-blue-50 group-hover:text-blue-600 group-hover:border-blue-100 transition-all">
                                    <i class="fas fa-vault text-[10px]"></i>
                                </div>
                                <a href="{{ route('admin.accounts.show', $account->id) }}" class="text-xs font-black text-slate-900 hover:text-blue-600 transition-colors">
                                    {{ $account->account_number }}
                                </a>
                            </div>
                        </td>
                        <td>
                            <div class="flex flex-col">
                                <p class="text-xs font-black text-slate-800">{{ $account->client->full_name }}</p>
                                <p class="text-[9px] font-mono font-bold text-slate-400 mt-0.5 uppercase tracking-tighter tracking-widest">{{ $account->client->client_number }}</p>
                            </div>
                        </td>
                        <td>
                            @if($account->account_type === 'savings')
                                <div class="flex items-center gap-1.5">
                                    <i class="fas fa-piggy-bank text-[10px] text-blue-600"></i>
                                    <span class="text-[9px] font-black text-blue-700 uppercase tracking-tight">compte d'Épargne</span>
                                </div>
                            @else
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-1.5">
                                        <i class="fas fa-rotate text-[10px] text-purple-600"></i>
                                        <span class="text-[9px] font-black text-purple-700 uppercase tracking-tight flex items-center gap-1">
                                            compte Tontine
                                            @if($account->tontineAccount && $account->tontineAccount->total_paid >= $account->tontineAccount->total_expected && $account->tontineAccount->total_expected > 0)
                                                <i class="fas fa-star text-amber-500 animate-pulse text-[10px]" title="Tontine Complète"></i>
                                            @elseif($account->tontineAccount && $account->tontineAccount->activeCycle && $account->tontineAccount->activeCycle->collected_amount >= $account->tontineAccount->activeCycle->target_amount)
                                                <i class="fas fa-certificate text-emerald-500 text-[10px]" title="Cycle Atteint"></i>
                                            @endif
                                        </span>
                                    </div>
                                    @if($account->tontineAccount)
                                        <p class="text-[9px] font-bold text-slate-400 mt-0.5">{{ number_format($account->tontineAccount->tontine_amount, 0, ',', ' ') }} XOF / {{ ucfirst(__($account->tontineAccount->payment_frequency)) }}</p>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td>
                            <p class="text-sm font-black text-slate-900 font-numeric tracking-tight">
                                {{ number_format($account->balance, 0, ',', ' ') }} <small class="text-[10px] text-slate-400 font-bold uppercase">XOF</small>
                            </p>
                        </td>
                        <td class="text-center">
                            @switch($account->status)
                                @case('active')
                                    <span class="bank-badge badge-success !text-[8px] uppercase tracking-widest font-black">Vérifié</span>
                                    @break
                                @case('suspended')
                                    <span class="bank-badge badge-danger !text-[8px] uppercase tracking-widest font-black">Suspendu</span>
                                    @break
                                @case('pending_activation')
                                    <span class="bank-badge badge-warning !text-[8px] uppercase tracking-widest font-black">Attente</span>
                                    @break
                                @default
                                    <span class="bank-badge badge-secondary !text-[8px] uppercase tracking-widest font-black">{{ $account->status }}</span>
                            @endswitch
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1 opacity-40 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('admin.accounts.deposit.form', $account->id) }}" class="p-2 text-slate-400 hover:text-emerald-600 transition" title="Injection de Capital">
                                    <i class="fas fa-circle-dollar-to-slot text-xs"></i>
                                </a>
                                <a href="{{ route('admin.accounts.show', $account->id) }}" class="p-2 text-slate-400 hover:text-blue-600 transition" title="Audit du Dossier">
                                    <i class="fas fa-eye text-xs"></i>
                                </a>
                                <a href="{{ route('admin.accounts.transactions', $account->id) }}" class="p-2 text-slate-400 hover:text-purple-600 transition" title="Journaux du compte">
                                    <i class="fas fa-list-ul text-xs"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-20 text-center">
                            <i class="fas fa-box-open text-3xl text-slate-200 mb-4"></i>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Aucun compte d'actifs détecté dans la fenêtre d'audit actuelle</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($accounts->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/30">
            {{ $accounts->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
