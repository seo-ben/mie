@extends('layouts.app_admin')

@section('title', 'Journaux des Flux - ' . $account->account_number)
@section('page-title', 'Protocole / Audit des Flux')

@section('content')
<div class="space-y-8 no-print">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.accounts.show', $account->id) }}" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-xl flex items-center justify-center transition border border-slate-200 text-slate-600">
                <i class="fas fa-chevron-left text-xs"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Journaux des Flux de Capital</h2>
                <div class="flex items-center gap-3 mt-1">
                    <p class="text-slate-500 text-sm font-medium">Audit détaillé du compte <span class="font-mono text-blue-600 font-bold tracking-widest uppercase">{{ $account->account_number }}</span></p>
                    <span class="bank-badge {{ $account->account_type === 'savings' ? 'badge-info' : 'badge-warning' }} !text-[8px] uppercase font-black tracking-widest">
                        {{ $account->account_type_name }}
                    </span>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="btn-bank btn-bank-outline">
                <i class="fas fa-print mr-2 text-[10px]"></i> Certification Physique
            </button>
        </div>
    </div>

    <!-- Matrice de Filtrage d'Audit -->
    <div class="bank-card p-6 border-trust shadow-sm">
        <form method="GET" action="{{ route('admin.accounts.transactions', $account->id) }}" class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <div class="md:col-span-3">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5 block">Nature du Flux</label>
                <select name="type" class="bank-input text-xs font-bold uppercase">
                    <option value="">Toutes Natures</option>
                    <option value="deposit" {{ request('type') === 'deposit' ? 'selected' : '' }}>Injection (Dépôt)</option>
                    <option value="withdrawal" {{ request('type') === 'withdrawal' ? 'selected' : '' }}>Extraction (Retrait)</option>
                    <option value="transfer" {{ request('type') === 'transfer' ? 'selected' : '' }}>Migration (Transfert)</option>
                    <option value="fee" {{ request('type') === 'fee' ? 'selected' : '' }}>Taxe (Frais)</option>
                    <option value="interest" {{ request('type') === 'interest' ? 'selected' : '' }}>Rémunération (Intérêts)</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5 block">Validation</label>
                <select name="status" class="bank-input text-xs font-bold uppercase">
                    <option value="">Tous Statuts</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Traité</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En Attente</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Échec Audit</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Révoqué</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5 block">Fenêtre Initiale</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="bank-input text-xs font-bold">
            </div>

            <div class="md:col-span-2">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5 block">Fenêtre Finale</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="bank-input text-xs font-bold">
            </div>

            <div class="md:col-span-3 flex gap-2 pt-5">
                <button type="submit" class="btn-bank btn-bank-primary flex-1 shadow-lg shadow-blue-500/20">
                    <i class="fas fa-filter text-[10px]"></i>
                </button>
                <a href="{{ route('admin.accounts.transactions', $account->id) }}" class="btn-bank btn-bank-outline px-4 flex items-center justify-center">
                    <i class="fas fa-rotate"></i>
                </a>
            </div>
        </form>
    </div>

    @php
        $totalDeposits = $transactions->where('transaction_type', 'deposit')->where('status', 'completed')->sum('amount');
        $totalWithdrawals = $transactions->where('transaction_type', 'withdrawal')->where('status', 'completed')->sum('amount');
        $totalFees = $transactions->where('status', 'completed')->sum('fee_amount');
    @endphp

    <!-- KPIs de Trésorerie du Cycle -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bank-card p-6 border-l-4 border-emerald-500 bg-emerald-50/30">
            <span class="text-[10px] font-black text-emerald-800 uppercase tracking-widest block mb-2">Flux Entrants (Brut)</span>
            <div class="text-xl font-black text-emerald-900">{{ number_format($totalDeposits, 0, ',', ' ') }} <small class="text-[10px]">XOF</small></div>
            <p class="text-[9px] font-bold text-emerald-600/60 uppercase mt-2 italic">Injections de Capital Validées</p>
        </div>

        <div class="bank-card p-6 border-l-4 border-rose-500 bg-rose-50/30">
            <span class="text-[10px] font-black text-rose-800 uppercase tracking-widest block mb-2">Flux Sortants (Brut)</span>
            <div class="text-xl font-black text-rose-900">{{ number_format($totalWithdrawals, 0, ',', ' ') }} <small class="text-[10px]">XOF</small></div>
            <p class="text-[9px] font-bold text-rose-600/60 uppercase mt-2 italic">Extractions de Capital Validées</p>
        </div>

        <div class="bank-card p-6 border-l-4 border-amber-500 bg-amber-50/30">
            <span class="text-[10px] font-black text-amber-800 uppercase tracking-widest block mb-2">Prélèvements Fiscaux</span>
            <div class="text-xl font-black text-amber-900">{{ number_format($totalFees, 0, ',', ' ') }} <small class="text-[10px]">XOF</small></div>
            <p class="text-[9px] font-bold text-amber-600/60 uppercase mt-2 italic">Total des Taxes et Redevances</p>
        </div>

        <div class="bank-card p-6 border-l-4 border-blue-500 bg-blue-50/30 shadow-xl shadow-blue-500/5">
            <span class="text-[10px] font-black text-blue-800 uppercase tracking-widest block mb-2">Exposition Actuelle</span>
            <div class="text-xl font-black text-blue-900">{{ number_format($account->balance, 0, ',', ' ') }} <small class="text-[10px]">XOF</small></div>
            <p class="text-[9px] font-bold text-blue-600/60 uppercase mt-2 italic">Poids de Trésorerie au compte</p>
        </div>
    </div>

    <!-- Matrice des Transactions -->
    <div class="bank-card overflow-hidden">
        <div class="px-8 py-5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <h3 class="text-[10px] font-black text-slate-800 uppercase tracking-widest">Registre Permanent des Flux</h3>
            <span class="px-3 py-1 bg-slate-200 text-slate-600 text-[8px] font-black rounded-full uppercase">{{ $transactions->total() }} Itérations</span>
        </div>

        <div class="overflow-x-auto">
            <table class="bank-table">
                <thead>
                    <tr class="!bg-slate-50/20">
                        <th class="uppercase tracking-widest text-[9px]">Horodatage</th>
                        <th class="uppercase tracking-widest text-[9px]">Artefact Référence</th>
                        <th class="uppercase tracking-widest text-[9px]">Nature du Flux</th>
                        <th class="uppercase tracking-widest text-[9px] text-right">Volume Capital</th>
                        <th class="uppercase tracking-widest text-[9px] text-right">Frais d'Audit</th>
                        <th class="uppercase tracking-widest text-[9px] text-right">Post-Solde</th>
                        <th class="uppercase tracking-widest text-[9px]">Source/Média</th>
                        <th class="uppercase tracking-widest text-[9px]">Validation</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($transactions as $t)
                    <tr onclick="toggleDetails('{{ $t->id }}')" class="hover:bg-slate-50/50 transition-colors cursor-pointer group border-b border-slate-50">
                        <td class="text-[11px] font-bold text-slate-500 py-4 uppercase pl-4">
                            <i class="fas fa-chevron-right mr-2 text-[10px] text-slate-300 transition-transform duration-300" id="icon-{{ $t->id }}"></i>
                            {{ $t->transaction_date?->format('d/m/Y') }} <br>
                            <span class="text-[9px] text-slate-400 pl-4">{{ $t->transaction_date?->format('H:i') }}</span>
                        </td>
                        <td class="font-mono text-[10px] font-black text-slate-400 group-hover:text-blue-600 transition-colors">
                            {{ $t->transaction_reference ?? '—' }}
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                @switch($t->transaction_type)
                                    @case('deposit')
                                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                                        <span class="text-[9px] font-black text-emerald-700 uppercase tracking-tight">Injection</span>
                                        @break
                                    @case('withdrawal')
                                        <div class="w-1.5 h-1.5 rounded-full bg-rose-500"></div>
                                        <span class="text-[9px] font-black text-rose-700 uppercase tracking-tight">Extraction</span>
                                        @break
                                    @case('transfer')
                                        <div class="w-1.5 h-1.5 rounded-full bg-cyan-500"></div>
                                        <span class="text-[9px] font-black text-cyan-700 uppercase tracking-tight">Migration</span>
                                        @break
                                    @case('fee')
                                        <div class="w-1.5 h-1.5 rounded-full bg-amber-500"></div>
                                        <span class="text-[9px] font-black text-amber-700 uppercase tracking-tight">Redevance</span>
                                        @break
                                    @case('interest')
                                        <div class="w-1.5 h-1.5 rounded-full bg-blue-500"></div>
                                        <span class="text-[9px] font-black text-blue-700 uppercase tracking-tight">Rémunération</span>
                                        @break
                                    @default
                                        <span class="text-[9px] font-black text-slate-400 uppercase">{{ $t->transaction_type }}</span>
                                @endswitch
                            </div>
                        </td>
                        <td class="text-right">
                            <span class="text-[11px] font-black {{ in_array($t->transaction_type, ['deposit', 'interest']) ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ in_array($t->transaction_type, ['deposit', 'interest']) ? '+' : '-' }} {{ number_format($t->amount, 0, ',', ' ') }}
                            </span>
                        </td>
                        <td class="text-right text-[10px] font-bold text-slate-400">
                            -{{ number_format($t->fee_amount, 0, ',', ' ') }}
                        </td>
                        <td class="text-right text-[11px] font-black text-slate-900 bg-slate-50/50">
                            {{ number_format($t->balance_after, 0, ',', ' ') }}
                        </td>
                        <td class="text-[10px] font-bold text-slate-600 uppercase tracking-tighter">
                            @if($t->payment_method)
                                <i class="fas {{ $t->payment_method === 'cash' ? 'fa-wallet' : ($t->payment_method === 'mobile_money' ? 'fa-mobile-screen' : 'fa-building-columns') }} mr-1 opacity-30"></i>
                                {{ str_replace('_', ' ', $t->payment_method) }}
                            @else
                                <span class="text-slate-300">Néant</span>
                            @endif
                        </td>
                        <td>
                            <span class="bank-badge {{ $t->status === 'completed' ? 'badge-success' : 'badge-warning' }} !text-[7px] uppercase font-black tracking-widest">
                                {{ $t->status === 'completed' ? 'Traité' : ($t->status === 'pending' ? 'Attente' : 'Rejeté') }}
                            </span>
                        </td>
                    </tr>
                    <!-- Row Details Expansion -->
                    <tr id="details-{{ $t->id }}" class="hidden bg-slate-50/50 border-b border-slate-100 transition-all duration-300">
                        <td colspan="8" class="p-0">
                            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-8 bg-slate-50 border-l-4 border-slate-200 m-2 rounded-r-xl">
                                <div>
                                    <h4 class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 flex items-center gap-2">
                                        <i class="fas fa-user-shield text-slate-300"></i> Opérateur / Système
                                    </h4>
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded bg-white border border-slate-200 flex items-center justify-center text-slate-400 text-xs font-bold">
                                            {{ substr($t->processedBy->full_name ?? 'SYS', 0, 2) }}
                                        </div>
                                        <div>
                                            <p class="text-xs font-black text-slate-700">{{ $t->processedBy->full_name ?? 'Automate Système' }}</p>
                                            <p class="text-[8px] font-bold text-slate-400 uppercase tracking-tight">{{ $t->processedBy->role ?? 'N/A' }}</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div>
                                    <h4 class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 flex items-center gap-2">
                                        <i class="fas fa-clock text-slate-300"></i> Horodatage Précis
                                    </h4>
                                    <p class="text-xs font-mono font-black text-slate-600 bg-white inline-block px-2 py-1 rounded border border-slate-200">
                                        {{ $t->transaction_date?->format('d/m/Y H:i:s') }}
                                    </p>
                                </div>

                                <div>
                                    <h4 class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 flex items-center gap-2">
                                        <i class="fas fa-note-sticky text-slate-300"></i> Note de Gestion
                                    </h4>
                                    <p class="text-[10px] italic font-bold text-slate-500 bg-white p-3 rounded-lg border border-slate-200 leading-relaxed shadow-sm">
                                        "{{ $t->description ?? 'Aucune annotation consignée pour cet artefact.' }}"
                                    </p>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-20 text-center">
                            <i class="fas fa-inbox text-4xl text-slate-100 mb-4 block"></i>
                            <p class="text-[10px] font-black text-slate-300 uppercase tracking-[0.2em]">Aucun artefact de flux détecté dans la fenêtre d'audit</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
        <div class="px-8 py-5 border-t border-slate-100 bg-slate-50/30">
            {{ $transactions->links() }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function toggleDetails(id) {
    const row = document.getElementById('details-' + id);
    const icon = document.getElementById('icon-' + id);
    
    if (row.classList.contains('hidden')) {
        row.classList.remove('hidden');
        if(icon) icon.classList.add('rotate-90');
    } else {
        row.classList.add('hidden');
        if(icon) icon.classList.remove('rotate-90');
    }
}
</script>
@endpush

<!-- Print Layout -->
<div class="hidden print:block font-serif p-10 space-y-8">
    <div class="text-center border-b-2 border-slate-900 pb-6">
        <h1 class="text-3xl font-bold uppercase tracking-widest">Certification de Registre des Flux</h1>
        <p class="text-sm mt-2">compte Institutionnel : {{ $account->account_number }}</p>
        <p class="text-[10px] font-bold mt-4 italic">Document généré le {{ now()->format('d/m/Y H:i') }}</p>
    </div>
    
    <div class="grid grid-cols-2 gap-8 text-xs">
        <div>
            <p class="font-bold uppercase mb-2 border-b border-slate-300">Titulaire de Tutelle</p>
            <p>{{ $account->client->full_name }}</p>
            <p>ID : {{ $account->client->client_number }}</p>
        </div>
        <div class="text-right">
            <p class="font-bold uppercase mb-2 border-b border-slate-300">État de Trésorerie Final</p>
            <p class="text-lg font-bold">{{ number_format($account->balance, 0, ',', ' ') }} XOF</p>
        </div>
    </div>

    <!-- Mini Table and other print content can go here, but focusing on the main UI first -->
</div>

<style>
@media print {
    body * { visibility: hidden; }
    .print\:block, .print\:block * { visibility: visible; }
    .print\:block { position: absolute; left: 0; top: 0; width: 100%; border: none !important; }
}
</style>
@endsection
