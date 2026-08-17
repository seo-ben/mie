@extends('layouts.app_admin')

@section('title', 'Registres des Extractions de Fonds')
@section('page-title', 'Trésorerie / Historique des Sorties')

@section('content')
<div class="space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Archives des Extractions de Fonds</h2>
            <p class="text-slate-500 text-sm font-medium">Répertoire centralisé des liquidations d'actifs et retraits</p>
        </div>
        <div class="flex items-center gap-3">
             <span class="px-3 py-1 bg-rose-50 text-rose-700 text-[10px] font-extrabold rounded-full border border-rose-100 uppercase tracking-tighter shadow-sm">
                <i class="fas fa-shield-halved mr-1"></i> Audit de Sortie Actif
            </span>
        </div>
    </div>

    <!-- Matrice des Indicateurs de Liquidation -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-5">
        <div class="bank-card p-5 border-blue-100 flex flex-col justify-between">
            <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-3">Total Extractions</p>
            <div class="flex items-end justify-between">
                <span class="text-2xl font-black text-slate-900 leading-none">{{ number_format($stats['total_withdrawals']) }}</span>
                <i class="fas fa-hand-holding-dollar text-blue-200"></i>
            </div>
        </div>

        <div class="bank-card p-5 border-rose-100 bg-rose-50/20 flex flex-col justify-between">
            <p class="text-[8px] font-black text-rose-800/40 uppercase tracking-widest mb-3">Volume Brut</p>
            <div class="flex items-end justify-between">
                <span class="text-lg font-black text-rose-600 leading-none">{{ number_format($stats['total_amount'], 0, ',', ' ') }} <small class="text-[9px]">XOF</small></span>
                <i class="fas fa-arrow-right-from-bracket text-rose-300"></i>
            </div>
        </div>

        <div class="bank-card p-5 border-amber-100 bg-amber-50/20 flex flex-col justify-between">
            <p class="text-[8px] font-black text-amber-800/40 uppercase tracking-widest mb-3">Redevances d'Arbitrage</p>
            <div class="flex items-end justify-between">
                <span class="text-lg font-black text-amber-600 leading-none text-right">{{ number_format($stats['total_fees'], 0, ',', ' ') }} <small class="text-[9px]">XOF</small></span>
                <i class="fas fa-percentage text-amber-300"></i>
            </div>
        </div>

        <div class="bank-card p-5 border-emerald-100 bg-emerald-50/20 flex flex-col justify-between shadow-xl shadow-emerald-500/5">
            <p class="text-[8px] font-black text-emerald-800/40 uppercase tracking-widest mb-3">Liquidation Nette</p>
            <div class="flex items-end justify-between">
                <span class="text-lg font-black text-emerald-600 leading-none">{{ number_format($stats['total_net'], 0, ',', ' ') }} <small class="text-[9px]">XOF</small></span>
                <i class="fas fa-wallet text-emerald-300"></i>
            </div>
        </div>

        <div class="bank-card p-5 border-orange-100 bg-orange-50/20 flex flex-col justify-between">
            <p class="text-[8px] font-black text-orange-800/40 uppercase tracking-widest mb-3">Sorties (24H)</p>
            <div class="flex items-end justify-between">
                <div class="space-y-1">
                    <span class="text-2xl font-black text-orange-600 leading-none block">{{ number_format($stats['withdrawals_today']) }}</span>
                    <span class="text-[9px] font-bold text-orange-800 opacity-50">{{ number_format($stats['amount_today'], 0, ',', ' ') }} XOF</span>
                </div>
                <i class="fas fa-calendar-minus text-orange-300"></i>
            </div>
        </div>
    </div>

    <!-- Interface Filtres d'Audit de Sortie -->
    <div class="bank-card p-8">
        <form method="GET" action="{{ route('admin.withdrawals.history') }}" class="grid grid-cols-1 md:grid-cols-12 gap-6">
            <div class="md:col-span-4 space-y-2">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Référence / Nom / Bénéficiaire</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="TRX-RET-XXXX..." class="bank-input pl-10 text-[11px] font-bold">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 text-[10px]"></i>
                </div>
            </div>

            <div class="md:col-span-3 space-y-2">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Vecteur de Liquidation</label>
                <select name="payment_method" class="bank-input text-[11px] font-bold">
                    <option value="">Tous les Vecteurs</option>
                    <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>Espèces Physiques</option>
                    <option value="bank_transfer" {{ request('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Virements Internes</option>
                    <option value="mobile_money" {{ request('payment_method') === 'mobile_money' ? 'selected' : '' }}>Flux Numériques</option>
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

    <!-- Registre des Extractions (Tableau) -->
    <div class="bank-card overflow-hidden">
        <div class="px-8 py-5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <h5 class="text-[10px] font-black text-slate-800 uppercase tracking-widest">Flux d'Extraction Identifiés ({{ $withdrawals->total() }})</h5>
            <i class="fas fa-microchip text-slate-200"></i>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Timestamp Audit</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Référence Audit</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">compte Source / Titulaire</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Bénéficiaire Liquide</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Vecteur</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Volume Brut</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Arbitrage</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Net Liquidé</th>
                        <th class="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Opérateur</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 italic">
                    @forelse($withdrawals as $withdrawal)
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="px-8 py-5 whitespace-nowrap">
                            <span class="block text-[11px] font-black text-slate-700 leading-none">{{ $withdrawal->transaction_date->format('d/m/Y') }}</span>
                            <span class="block text-[9px] font-bold text-slate-400 mt-1 uppercase tracking-tighter">{{ $withdrawal->transaction_date->format('H:i') }}</span>
                        </td>
                        <td class="px-8 py-5 whitespace-nowrap">
                            <span class="block text-[10px] font-mono font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded border border-rose-100 shadow-sm">{{ $withdrawal->transaction_reference }}</span>
                            <span class="block text-[8px] font-bold text-slate-300 mt-1 uppercase tracking-tighter italic">ID : {{ $withdrawal->payment_reference }}</span>
                        </td>
                        <td class="px-8 py-5">
                            <div class="flex flex-col">
                                <a href="{{ route('admin.accounts.show', $withdrawal->account_id) }}" class="text-[10px] font-mono font-black text-blue-600 hover:underline leading-none mb-1">
                                    {{ $withdrawal->account->account_number }}
                                </a>
                                <span class="text-[11px] font-black text-slate-800 uppercase tracking-tighter">{{ $withdrawal->account->client->full_name }}</span>
                                <span class="text-[8px] font-bold text-slate-400 uppercase italic">{{ $withdrawal->account->client->client_number }}</span>
                            </div>
                        </td>
                        <td class="px-8 py-5">
                            <div class="flex flex-col">
                                <span class="text-[11px] font-black text-slate-900 uppercase leading-none mb-1">{{ $withdrawal->recipient_name }}</span>
                                @if($withdrawal->recipient_phone)
                                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter"><i class="fas fa-phone text-[8px] mr-1"></i>{{ $withdrawal->recipient_phone }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-8 py-5 whitespace-nowrap">
                            @switch($withdrawal->payment_method)
                                @case('cash')
                                    <span class="inline-flex items-center px-2 py-1 rounded-lg text-[8px] font-black bg-emerald-50 text-emerald-700 border border-emerald-100 uppercase tracking-widest shadow-sm">
                                        <i class="fas fa-money-bill-transfer mr-1.5"></i> Espèces
                                    </span>
                                    @break
                                @case('bank_transfer')
                                    <span class="inline-flex items-center px-2 py-1 rounded-lg text-[8px] font-black bg-blue-50 text-blue-700 border border-blue-100 uppercase tracking-widest shadow-sm">
                                        <i class="fas fa-building-columns mr-1.5"></i> Virement
                                    </span>
                                    @break
                                @case('mobile_money')
                                    <span class="inline-flex items-center px-2 py-1 rounded-lg text-[8px] font-black bg-purple-50 text-purple-700 border border-purple-100 uppercase tracking-widest shadow-sm">
                                        <i class="fas fa-mobile-screen mr-1.5"></i> Mobile
                                    </span>
                                    @break
                            @endswitch
                        </td>
                        <td class="px-8 py-5 text-right whitespace-nowrap">
                            <span class="text-[12px] font-black text-slate-900 font-numeric leading-none">{{ number_format($withdrawal->amount, 0, ',', ' ') }}</span>
                        </td>
                        <td class="px-8 py-5 text-right whitespace-nowrap">
                            <span class="text-[11px] font-black text-rose-500 font-numeric leading-none">{{ number_format($withdrawal->fee_amount, 0, ',', ' ') }}</span>
                        </td>
                        <td class="px-8 py-5 text-right whitespace-nowrap bg-emerald-50/20 shadow-inner">
                            <span class="text-[13px] font-black text-emerald-600 font-numeric leading-none">{{ number_format($withdrawal->net_amount, 0, ',', ' ') }}</span>
                            <span class="text-[8px] font-black text-slate-400 ml-1 uppercase">XOF</span>
                        </td>
                        <td class="px-8 py-5 whitespace-nowrap">
                            <span class="text-[10px] font-black text-slate-700 uppercase italic">{{ $withdrawal->processedBy->name ?? 'N/A' }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-8 py-20 text-center">
                            <div class="w-16 h-16 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-6 text-slate-300">
                                <i class="fas fa-inbox text-2xl"></i>
                            </div>
                            <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Registre d'Extraction Néant</h4>
                            <p class="text-[10px] font-bold text-slate-300 mt-2 italic">Aucune extraction d'actifs n'a été identifiée pour cette période.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($withdrawals->hasPages())
        <div class="px-8 py-5 bg-slate-50/50 border-t border-slate-100 text-[10px] font-black uppercase">
            {{ $withdrawals->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
