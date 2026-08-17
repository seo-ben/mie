@extends('layouts.app_admin')

@section('title', 'Registres des Migrations d\'Actifs')
@section('page-title', 'Trésorerie / Historique des Flux')

@section('content')
<div class="space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Archives des Migrations Inter-comptes</h2>
            <p class="text-slate-500 text-sm font-medium">Répertoire centralisé des flux d'actifs consolidés</p>
        </div>
        <a href="{{ route('admin.accounts.transfer.form') }}" class="btn-bank btn-bank-primary px-8 py-3">
            <i class="fas fa-plus mr-2 text-[10px]"></i> Nouvelle Migration
        </a>
    </div>

    <!-- Matrice de Performance des Flux -->
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-6">
        <div class="bank-card p-6 border-slate-100 flex flex-col justify-between">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none mb-4">Total Flux</p>
            <div class="flex items-end justify-between">
                <span class="text-2xl font-black text-slate-900 leading-none">{{ number_format($stats['total_transfers']) }}</span>
                <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400 border border-slate-100 italic font-black text-[10px]">#</div>
            </div>
        </div>

        <div class="bank-card p-6 border-rose-100 bg-rose-50/20 flex flex-col justify-between">
            <p class="text-[9px] font-black text-rose-800/40 uppercase tracking-widest leading-none mb-4">Migré (Sortant)</p>
            <div class="flex items-end justify-between">
                <span class="text-xl font-black text-rose-600 leading-none">{{ number_format($stats['total_amount_sent'], 0, ',', ' ') }} <small class="text-[10px]">XOF</small></span>
                <i class="fas fa-arrow-up-from-bracket text-rose-300"></i>
            </div>
        </div>

        <div class="bank-card p-6 border-emerald-100 bg-emerald-50/20 flex flex-col justify-between">
            <p class="text-[9px] font-black text-emerald-800/40 uppercase tracking-widest leading-none mb-4">Migré (Entrant)</p>
            <div class="flex items-end justify-between">
                <span class="text-xl font-black text-emerald-600 leading-none">{{ number_format($stats['total_amount_received'], 0, ',', ' ') }} <small class="text-[10px]">XOF</small></span>
                <i class="fas fa-arrow-down-to-bracket text-emerald-300"></i>
            </div>
        </div>

        <div class="bank-card p-6 border-purple-100 bg-purple-50/20 flex flex-col justify-between">
            <p class="text-[9px] font-black text-purple-800/40 uppercase tracking-widest leading-none mb-4">Taxes d'Audit</p>
            <div class="flex items-end justify-between">
                <span class="text-xl font-black text-purple-600 leading-none">{{ number_format($stats['total_fees_collected'], 0, ',', ' ') }} <small class="text-[10px]">XOF</small></span>
                <i class="fas fa-vault text-purple-300"></i>
            </div>
        </div>

        <div class="bank-card p-6 border-blue-100 bg-blue-50/20 flex flex-col justify-between shadow-xl shadow-blue-500/5">
            <p class="text-[9px] font-black text-blue-800/40 uppercase tracking-widest leading-none mb-4">Flux (24H)</p>
            <div class="flex items-end justify-between">
                <span class="text-2xl font-black text-blue-600 leading-none">{{ number_format($stats['transfers_today']) }}</span>
                <i class="fas fa-calendar-day text-blue-300"></i>
            </div>
        </div>
    </div>

    <!-- Interface Filtres d'Audit -->
    <div class="bank-card p-8">
        <form method="GET" action="{{ route('admin.accounts.transfer.history') }}" class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="space-y-2">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Référence / Titulaire</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="TRX-MIGN-XXXX..." class="bank-input pl-10 text-[11px] font-bold">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 text-[10px]"></i>
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Vecteur de Flux</label>
                <select name="type" class="bank-input text-[11px] font-bold">
                    <option value="">Tous les Vecteurs</option>
                    <option value="transfer_out" {{ request('type') == 'transfer_out' ? 'selected' : '' }}>Émissions Sortantes</option>
                    <option value="transfer_in" {{ request('type') == 'transfer_in' ? 'selected' : '' }}>Réceptions Entrantes</option>
                </select>
            </div>

            <div class="space-y-2">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Fenêtre (Début)</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="bank-input text-[11px] font-bold">
            </div>

            <div class="space-y-2">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Fenêtre (Fin)</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="bank-input text-[11px] font-bold">
            </div>

            <div class="flex items-center gap-3 pt-4 md:col-span-4 justify-end border-t border-slate-50 mt-2">
                <button type="submit" class="btn-bank btn-bank-primary !py-2.5 px-10 text-[10px] font-black uppercase">Filtrer le Registre</button>
                <a href="{{ route('admin.accounts.transfer.history') }}" class="btn-bank btn-bank-outline !py-2.5 px-8 text-[10px] font-black uppercase">Purge Filtres</a>
            </div>
        </form>
    </div>

    <!-- Registre des Flux (Tableau) -->
    <div class="bank-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Timestamp Audit</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Référence Flux</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Vecteur</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">compte Principal</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Contrepartie</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Volume</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Arbitrage</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 italic">
                    @forelse($transfers as $transfer)
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="px-8 py-5">
                            <span class="block text-[11px] font-black text-slate-700 leading-none">{{ $transfer->transaction_date->format('d/m/Y') }}</span>
                            <span class="block text-[9px] font-bold text-slate-400 mt-1 uppercase tracking-tighter">{{ $transfer->transaction_date->format('H:i:s') }}</span>
                        </td>
                        <td class="px-8 py-5">
                            <span class="text-[10px] font-mono font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded border border-blue-100 shadow-sm">{{ $transfer->payment_reference }}</span>
                        </td>
                        <td class="px-8 py-5">
                            @if($transfer->transaction_type === 'transfer_out')
                                <span class="flex items-center gap-1.5 text-[9px] font-black text-rose-600 uppercase">
                                    <i class="fas fa-circle-arrow-up text-[10px]"></i> Sortant
                                </span>
                            @else
                                <span class="flex items-center gap-1.5 text-[9px] font-black text-emerald-600 uppercase">
                                    <i class="fas fa-circle-arrow-down text-[10px]"></i> Entrant
                                </span>
                            @endif
                        </td>
                        <td class="px-8 py-5">
                            @if($transfer->account && $transfer->account->client)
                            <div class="space-y-1">
                                <p class="text-[11px] font-black text-slate-800 uppercase leading-none">{{ $transfer->account->client->full_name }}</p>
                                <p class="text-[9px] font-mono font-bold text-slate-400">{{ $transfer->account->account_number }}</p>
                            </div>
                            @elseif($transfer->account)
                            <div class="space-y-1">
                                <p class="text-[11px] font-black text-slate-800 uppercase leading-none">Client Inconnu</p>
                                <p class="text-[9px] font-mono font-bold text-slate-400">{{ $transfer->account->account_number }}</p>
                            </div>
                            @else
                            <span class="text-[10px] font-black text-slate-300 uppercase italic">Compte Inconnu</span>
                            @endif
                        </td>
                        <td class="px-8 py-5">
                            @if($transfer->relatedAccount && $transfer->relatedAccount->client)
                            <div class="space-y-1">
                                <p class="text-[11px] font-black text-slate-600 uppercase leading-none italic">{{ $transfer->relatedAccount->client->full_name }}</p>
                                <p class="text-[9px] font-mono font-bold text-slate-400/60 tracking-tighter">{{ $transfer->relatedAccount->account_number }}</p>
                            </div>
                            @elseif($transfer->relatedAccount)
                            <div class="space-y-1">
                                <p class="text-[11px] font-black text-slate-600 uppercase leading-none italic">Client Inconnu</p>
                                <p class="text-[9px] font-mono font-bold text-slate-400/60 tracking-tighter">{{ $transfer->relatedAccount->account_number }}</p>
                            </div>
                            @else
                            <span class="text-[10px] font-black text-slate-300 uppercase italic">N/A</span>
                            @endif
                        </td>
                        <td class="px-8 py-5 text-right">
                            <span class="text-[13px] font-black {{ $transfer->transaction_type === 'transfer_out' ? 'text-rose-600' : 'text-emerald-600' }}">
                                {{ $transfer->transaction_type === 'transfer_out' ? '-' : '+' }} {{ number_format($transfer->amount, 0, ',', ' ') }}
                            </span>
                            <span class="text-[8px] font-black text-slate-400 ml-1 uppercase">XOF</span>
                        </td>
                        <td class="px-8 py-5 text-right font-numeric text-[11px] font-black text-slate-900 shadow-inner bg-slate-50/30">
                            {{ number_format($transfer->fee_amount, 0, ',', ' ') }}
                        </td>
                        <td class="px-8 py-5 text-center">
                            <a href="{{ route('admin.accounts.transfer.details', $transfer->id) }}" class="btn-bank btn-bank-outline !py-1.5 !px-4 !text-[9px] uppercase font-black tracking-widest opacity-0 group-hover:opacity-100 transition-all">Audit</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-8 py-20 text-center">
                            <div class="w-16 h-16 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-6 text-slate-300">
                                <i class="fas fa-inbox text-2xl"></i>
                            </div>
                            <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Néant dans le Registre de Migration</h4>
                            <p class="text-[10px] font-bold text-slate-300 mt-2 italic px-8 max-w-xs mx-auto">Aucun flux n'a été identifié pour les critères d'audit fournis.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transfers->hasPages())
        <div class="px-8 py-5 bg-slate-50/50 border-t border-slate-100">
            {{ $transfers->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
