@extends('layouts.app_admin')

@section('title', 'Registres des Injections de Capital')
@section('page-title', 'Trésorerie / Historique des Apports')

@section('content')
<div class="space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Archives des Injections de Capital</h2>
            <p class="text-slate-500 text-sm font-medium">Répertoire centralisé des flux entrants (Épargne & Mutuelles)</p>
        </div>
        <div class="flex items-center gap-3">
             <span class="px-3 py-1 bg-emerald-50 text-emerald-700 text-[10px] font-extrabold rounded-full border border-emerald-100 uppercase tracking-tighter">
                <i class="fas fa-shield-halved mr-1"></i> Registre Audité
            </span>
        </div>
    </div>

    <!-- Matrice des Indicateurs de Flux -->
    <div class="grid grid-cols-1 md:grid-cols-6 gap-5">
        <div class="bank-card p-5 border-blue-100 flex flex-col justify-between">
            <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-3">Total Injections</p>
            <div class="flex items-end justify-between">
                <span class="text-2xl font-black text-slate-900 leading-none">{{ number_format($stats['total_deposits']) }}</span>
                <i class="fas fa-list-ol text-blue-200"></i>
            </div>
        </div>

        <div class="bank-card p-5 border-emerald-100 bg-emerald-50/20 flex flex-col justify-between">
            <p class="text-[8px] font-black text-emerald-800/40 uppercase tracking-widest mb-3">Volume Global</p>
            <div class="flex items-end justify-between">
                <span class="text-lg font-black text-emerald-600 leading-none">{{ number_format($stats['total_amount'], 0, ',', ' ') }} <small class="text-[9px]">XOF</small></span>
                <i class="fas fa-vault text-emerald-300"></i>
            </div>
        </div>

        <div class="bank-card p-5 border-cyan-100 bg-cyan-50/20 flex flex-col justify-between">
            <p class="text-[8px] font-black text-cyan-800/40 uppercase tracking-widest mb-3">comptes Épargne</p>
            <div class="flex items-end justify-between">
                <span class="text-2xl font-black text-cyan-600 leading-none">{{ number_format($stats['savings_deposits']) }}</span>
                <i class="fas fa-piggy-bank text-cyan-200"></i>
            </div>
        </div>

        <div class="bank-card p-5 border-purple-100 bg-purple-50/20 flex flex-col justify-between">
            <p class="text-[8px] font-black text-purple-800/40 uppercase tracking-widest mb-3">comptes Mutuels</p>
            <div class="flex items-end justify-between">
                <span class="text-2xl font-black text-purple-600 leading-none">{{ number_format($stats['tontine_contributions']) }}</span>
                <i class="fas fa-rotate text-purple-200"></i>
            </div>
        </div>

        <div class="bank-card p-5 border-orange-100 bg-orange-50/20 flex flex-col justify-between">
            <p class="text-[8px] font-black text-orange-800/40 uppercase tracking-widest mb-3">Flux (24H)</p>
            <div class="flex items-end justify-between">
                <span class="text-2xl font-black text-orange-600 leading-none">{{ number_format($stats['deposits_today']) }}</span>
                <i class="fas fa-calendar-check text-orange-200"></i>
            </div>
        </div>

        <div class="bank-card p-5 border-amber-100 bg-amber-50/20 flex flex-col justify-between shadow-xl shadow-amber-500/5">
            <p class="text-[8px] font-black text-amber-800/40 uppercase tracking-widest mb-3">Capital Injecté J</p>
            <div class="flex items-end justify-between">
                <span class="text-lg font-black text-amber-600 leading-none">{{ number_format($stats['amount_today'], 0, ',', ' ') }} <small class="text-[9px]">XOF</small></span>
                <i class="fas fa-chart-line text-amber-300"></i>
            </div>
        </div>
    </div>

    <!-- Interface des Filtres de Recherche Audit -->
    <div class="bank-card p-8">
        <form method="GET" action="{{ route('admin.deposits.history') }}" class="grid grid-cols-1 md:grid-cols-12 gap-6">
            <div class="md:col-span-3 space-y-2">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Référence / Titulaire</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="TRX-DEP-XXXX..." class="bank-input pl-10 text-[11px] font-bold">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 text-[10px]"></i>
                </div>
            </div>

            <div class="md:col-span-2 space-y-2">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Classe d'Actif</label>
                <select name="account_type" class="bank-input text-[11px] font-bold">
                    <option value="">Tous les Actifs</option>
                    <option value="savings" {{ request('account_type') === 'savings' ? 'selected' : '' }}>Épargne Institutional</option>
                    <option value="tontine" {{ request('account_type') === 'tontine' ? 'selected' : '' }}>Mutuelles de Crédit</option>
                </select>
            </div>

            <div class="md:col-span-2 space-y-2">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Vecteur de Règlement</label>
                <select name="payment_method" class="bank-input text-[11px] font-bold">
                    <option value="">Tous les Vecteurs</option>
                    <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>Espèces Physiques</option>
                    <option value="mobile_money" {{ request('payment_method') === 'mobile_money' ? 'selected' : '' }}>Flux Numériques</option>
                    <option value="bank_transfer" {{ request('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Virements Internes</option>
                </select>
            </div>

            <div class="md:col-span-2 space-y-2">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Fenêtre (Début)</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="bank-input text-[11px] font-bold">
            </div>

            <div class="md:col-span-2 space-y-2">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Fenêtre (Fin)</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="bank-input text-[11px] font-bold">
            </div>

            <div class="md:col-span-1 flex items-end">
                <button type="submit" class="btn-bank btn-bank-primary w-full !py-2.5">
                    <i class="fas fa-magnifying-glass text-[10px]"></i>
                </button>
            </div>
        </form>
    </div>

    <!-- Registre des Injections (Tableau) -->
    <div class="bank-card overflow-hidden">
        <div class="px-8 py-5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <h5 class="text-[10px] font-black text-slate-800 uppercase tracking-widest">Flux d'Injection Identifiés ({{ $deposits->total() }})</h5>
            <i class="fas fa-microchip text-slate-200"></i>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Timestamp Audit</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Référence Audit</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">compte Cible / Titulaire</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Classe</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Vecteur</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Volume Injecté</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Opérateur</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Certification</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 italic">
                    @forelse($deposits as $deposit)
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="px-8 py-5">
                            <span class="block text-[11px] font-black text-slate-700 leading-none">{{ $deposit->transaction_date->format('d/m/Y') }}</span>
                            <span class="block text-[9px] font-bold text-slate-400 mt-1 uppercase tracking-tighter">{{ $deposit->transaction_date->format('H:i') }}</span>
                        </td>
                        <td class="px-8 py-5 whitespace-nowrap">
                            <span class="block text-[10px] font-mono font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded border border-blue-100 shadow-sm">{{ $deposit->transaction_reference }}</span>
                            @if($deposit->payment_reference)
                                <span class="block text-[8px] font-bold text-slate-400 mt-1 uppercase tracking-tighter italic">Ref Externe : {{ $deposit->payment_reference }}</span>
                            @endif
                        </td>
                        <td class="px-8 py-5">
                            <div class="flex flex-col">
                                <a href="{{ route('admin.accounts.show', $deposit->account_id) }}" class="text-[10px] font-mono font-black text-blue-600 hover:underline leading-none mb-1">
                                    {{ $deposit->account->account_number }}
                                </a>
                                <span class="text-[11px] font-black text-slate-800 uppercase tracking-tighter">{{ $deposit->account->client->full_name }}</span>
                                <span class="text-[8px] font-bold text-slate-400 uppercase italic">{{ $deposit->account->client->client_number }}</span>
                            </div>
                        </td>
                        <td class="px-8 py-5">
                            @if($deposit->account->account_type === 'savings')
                                <span class="flex items-center gap-1.5 text-[9px] font-black text-cyan-600 uppercase">
                                    <i class="fas fa-vault text-[10px]"></i> Épargne
                                </span>
                            @else
                                <span class="flex items-center gap-1.5 text-[9px] font-black text-purple-600 uppercase">
                                    <i class="fas fa-rotate text-[10px]"></i> Mutuelle
                                </span>
                            @endif
                        </td>
                        <td class="px-8 py-5">
                            @switch($deposit->payment_method)
                                @case('cash')
                                    <span class="inline-flex items-center px-2 py-1 rounded-lg text-[8px] font-black bg-emerald-50 text-emerald-700 border border-emerald-100 uppercase tracking-widest">
                                        <i class="fas fa-money-bill-wave mr-1.5"></i> Espèces
                                    </span>
                                    @break
                                @case('bank_transfer')
                                    <span class="inline-flex items-center px-2 py-1 rounded-lg text-[8px] font-black bg-blue-50 text-blue-700 border border-blue-100 uppercase tracking-widest">
                                        <i class="fas fa-building-columns mr-1.5"></i> Virement
                                    </span>
                                    @break
                                @case('mobile_money')
                                    <span class="inline-flex items-center px-2 py-1 rounded-lg text-[8px] font-black bg-purple-50 text-purple-700 border border-purple-100 uppercase tracking-widest">
                                        <i class="fas fa-mobile-screen mr-1.5"></i>
                                        @if($deposit->mobile_money_operator === 'tmoney') TMoney
                                        @elseif($deposit->mobile_money_operator === 'flooz') Flooz
                                        @else Digital
                                        @endif
                                    </span>
                                    @break
                            @endswitch
                        </td>
                        <td class="px-8 py-5 text-right">
                            <span class="text-[13px] font-black text-emerald-600 font-numeric">
                                {{ number_format($deposit->amount, 0, ',', ' ') }}
                            </span>
                            <span class="text-[8px] font-black text-slate-400 ml-1 uppercase">XOF</span>
                        </td>
                        <td class="px-8 py-5 whitespace-nowrap">
                            <span class="text-[10px] font-black text-slate-700 uppercase italic">{{ $deposit->processedBy->name ?? 'N/A' }}</span>
                        </td>
                        <td class="px-8 py-5">
                            @if($deposit->status === 'completed')
                                <span class="flex items-center gap-1.5 text-[8px] font-black text-emerald-600 uppercase bg-emerald-50 px-2 py-1 rounded-full border border-emerald-100 shadow-inner">
                                    <i class="fas fa-shield-check"></i> Certifié
                                </span>
                            @else
                                <span class="flex items-center gap-1.5 text-[8px] font-black text-amber-600 uppercase bg-amber-50 px-2 py-1 rounded-full border border-amber-100 shadow-inner">
                                    <i class="fas fa-circle-notch fa-spin"></i> {{ ucfirst($deposit->status) }}
                                </span>
                            @endif 
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-8 py-20 text-center">
                            <div class="w-16 h-16 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-6 text-slate-300">
                                <i class="fas fa-inbox text-2xl"></i>
                            </div>
                            <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Registre d'Injection Néant</h4>
                            <p class="text-[10px] font-bold text-slate-300 mt-2 italic">Aucune injection de capital n'a été identifiée pour cette période.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($deposits->hasPages())
        <div class="px-8 py-5 bg-slate-50/50 border-t border-slate-100 text-[10px] font-black uppercase">
            {{ $deposits->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
