@extends('layouts.app_admin')

@section('title', 'Gestion des Caisses - MIE YAYRA')

@section('content')
<div class="space-y-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight uppercase">Trésorerie d'Agence</h2>
            <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest mt-1">Supervision des flux et des guichets</p>
        </div>
        
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.cashier.sessions.create') }}" class="px-6 py-3 bg-slate-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-800 transition shadow-xl shadow-slate-900/10 active:scale-95 flex items-center gap-2">
                <i class="fas fa-lock-open mr-1"></i> Ouvrir un Guichet
            </a>
        </div>
    </div>

    <!-- ÉTAT DES CAISSES OUVERTES -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($activeSessions as $session)
            @php
                // Calculer le solde temps réel de cette session
                $sessionTotals = DB::table('transactions')
                    ->where('cashier_session_id', $session->id)
                    ->where('status', 'completed')
                    ->select(
                        DB::raw('SUM(CASE WHEN transaction_type IN ("deposit", "transfer_in") THEN amount ELSE 0 END) as total_in'),
                        DB::raw('SUM(CASE WHEN transaction_type IN ("withdrawal", "payout", "transfer_out") THEN amount ELSE 0 END) as total_out')
                    )->first();
                $currentCash = $session->opening_balance + ($sessionTotals->total_in ?? 0) - ($sessionTotals->total_out ?? 0);
            @endphp
            <div class="bank-card group hover:border-slate-400 transition-all duration-300">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-600 font-bold border border-slate-200 uppercase">
                            {{ strtoupper(substr($session->user->first_name, 0, 1)) }}{{ strtoupper(substr($session->user->last_name, 0, 1)) }}
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-800 uppercase tracking-tight">{{ $session->user->full_name }}</h3>
                            <span class="text-[8px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded-full border border-emerald-100 uppercase tracking-widest italic">Guichet Ouvert</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-tighter mb-1">Solde à l'instant</p>
                        <p class="text-lg font-black text-slate-900 font-numeric leading-none">{{ number_format($currentCash, 0, ',', ' ') }} <small class="text-[8px] text-slate-400">CFA</small></p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 pt-4 border-t border-slate-100">
                    <button type="button" 
                        onclick="openTransferModal({{ $session->id }}, '{{ $session->user->full_name }}', 'in')"
                        class="px-3 py-2 bg-emerald-50 text-emerald-700 rounded-lg text-[8px] font-black uppercase tracking-widest hover:bg-emerald-100 transition border border-emerald-100 flex items-center justify-center gap-1.5">
                        <i class="fas fa-arrow-down-long"></i> Approvisionner
                    </button>
                    <button type="button"
                        onclick="openTransferModal({{ $session->id }}, '{{ $session->user->full_name }}', 'out')"
                        class="px-3 py-2 bg-rose-50 text-rose-700 rounded-lg text-[8px] font-black uppercase tracking-widest hover:bg-rose-100 transition border border-rose-100 flex items-center justify-center gap-1.5">
                        <i class="fas fa-arrow-up-long"></i> Décharger
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center bank-card bg-slate-50/50 border-dashed border-slate-200">
                <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center text-slate-300 mx-auto mb-3 border border-slate-200 shadow-inner">
                    <i class="fas fa-cash-register text-xl"></i>
                </div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] italic">Aucun guichet actif pour votre agence</p>
            </div>
        @endforelse
    </div>

    <!-- JOURNAL GLOBAL DES OPÉRATIONS (Audit) -->
    <div class="space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full bg-slate-900 animate-pulse"></span>
                Extrait du Grand Livre Institutionnel (Temps Réel)
            </h3>
            
            <form action="{{ route('admin.cashier.sessions.index') }}" method="GET" class="flex items-center gap-2">
                <div class="flex items-center bg-white border border-slate-200 rounded-xl px-3 py-1.5 shadow-sm">
                    <i class="fas fa-calendar-alt text-slate-400 text-[10px] mr-2"></i>
                    <input type="date" name="date_start" value="{{ request('date_start') }}" class="text-[10px] font-bold text-slate-600 uppercase border-none bg-transparent focus:ring-0 p-0">
                    <span class="mx-2 text-slate-300 text-[10px]">à</span>
                    <input type="date" name="date_end" value="{{ request('date_end') }}" class="text-[10px] font-bold text-slate-600 uppercase border-none bg-transparent focus:ring-0 p-0">
                </div>
                <button type="submit" class="p-2 bg-slate-900 text-white rounded-xl hover:bg-slate-800 transition shadow-lg shadow-slate-900/10">
                    <i class="fas fa-magnifying-glass text-[10px]"></i>
                </button>
            </form>
        </div>

        <div class="bank-card overflow-hidden !p-0">
            <div class="overflow-x-auto">
                <table class="bank-table !mb-0">
                    <thead>
                        <tr>
                            <th class="!px-8 py-4">Horodatage</th>
                            <th>Caissier</th>
                            <th>Nature du Flux</th>
                            <th>Tiers / Référence</th>
                            <th class="text-right">Montant</th>
                            <th class="text-right !px-8">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentTransactions as $transaction)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="!px-8">
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-black text-slate-800 uppercase tracking-tighter">{{ $transaction->transaction_date->translatedFormat('d F Y') }}</span>
                                        <span class="text-[8px] font-bold text-slate-400 uppercase leading-none">{{ $transaction->transaction_date->format('H:i') }} • {{ $transaction->transaction_reference }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-[10px] font-black text-slate-600 uppercase">{{ $transaction->processedBy->full_name ?? 'SYSTÈME' }}</span>
                                </td>
                                <td>
                                    @php
                                        $isIn = in_array($transaction->transaction_type, ['deposit', 'transfer_in', 'savings_deposit', 'tontine_deposit', 'loan_repayment']);
                                        $typeLabel = match($transaction->transaction_type) {
                                            'deposit', 'savings_deposit' => 'DÉPÔT ÉPARGNE',
                                            'tontine_deposit' => 'VERSEMENT TONTINE',
                                            'withdrawal', 'savings_withdrawal' => 'RETRAIT ESPÈCES',
                                            'loan_repayment' => 'REMBOURSEMENT PRÊT',
                                            'loan_disbursement' => 'DÉCAISSEMENT PRÊT',
                                            'transfer_in' => 'APPROVISIONNEMENT',
                                            'transfer_out' => 'VERSEMENT (DÉCHARGE)',
                                            default => strtoupper($transaction->transaction_type)
                                        };
                                        $typeColor = $isIn ? 'emerald' : 'rose';
                                        if (in_array($transaction->transaction_type, ['transfer_in', 'transfer_out'])) $typeColor = 'blue';
                                    @endphp
                                    <span class="px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-widest border border-{{ $typeColor }}-100 bg-{{ $typeColor }}-50 text-{{ $typeColor }}-600">
                                        {{ $typeLabel }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex flex-col">
                                        <span class="text-xs font-black text-slate-800 leading-tight uppercase">{{ $transaction->account->client->full_name ?? 'OPÉRATION INTERNE' }}</span>
                                        <span class="text-[9px] font-bold text-blue-600 tracking-tighter uppercase">{{ $transaction->account->account_number ?? $transaction->description }}</span>
                                    </div>
                                </td>
                                <td class="text-right font-numeric font-black text-xs {{ $isIn ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $isIn ? '+' : '-' }}{{ number_format($transaction->amount, 0, ',', ' ') }}
                                </td>
                                <td class="text-right !px-8">
                                    <a href="{{ route('admin.transactions.receipt', $transaction->id) }}" target="_blank" class="p-2 text-slate-400 hover:text-blue-600 transition">
                                        <i class="fas fa-print text-xs"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-24 text-center text-slate-300 text-[10px] font-black italic uppercase tracking-widest">Aucune donnée transactionnelle</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 bg-slate-50/50 border-t border-slate-100">
                {{ $recentTransactions->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Modal de Virement Treasury <-> Caisse -->
<div id="transferModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div onclick="closeTransferModal()" class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-3xl shadow-2xl sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
            <form action="{{ route('admin.cashier.sessions.transfer') }}" method="POST">
                @csrf
                <input type="hidden" name="cashier_session_id" id="modal_session_id">
                <input type="hidden" name="type" id="modal_type">
                
                <div class="px-8 pt-8 pb-6">
                    <div class="flex items-center justify-between mb-6">
                        <div id="modal_icon_container" class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl">
                            <i id="modal_icon"></i>
                        </div>
                        <button type="button" onclick="closeTransferModal()" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
                    </div>

                    <h3 class="text-lg font-black text-slate-900 uppercase tracking-tight" id="modal_title">Virement Trésorerie</h3>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1" id="modal_subtitle"></p>

                    <div class="mt-8 space-y-4">
                        <div class="space-y-2">
                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Montant du Virement (CFA)</label>
                            <input type="number" name="amount" required step="100" min="100" class="w-full bg-slate-50 border-slate-200 rounded-xl px-4 py-4 text-2xl font-black text-slate-900 focus:ring-slate-900 focus:border-slate-900 transition font-numeric">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Note descriptive</label>
                            <input type="text" name="notes" placeholder="Raison du virement..." class="w-full bg-slate-50 border-slate-200 rounded-xl px-4 py-3 text-sm font-bold text-slate-700">
                        </div>
                    </div>
                </div>

                <div class="px-8 py-6 bg-slate-50 flex flex-col gap-3 rounded-b-3xl">
                    <button type="submit" id="modal_submit_btn" class="w-full py-4 rounded-2xl text-xs font-black uppercase tracking-widest text-white transition-all active:scale-95 shadow-xl">
                        Valider l'Opération
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openTransferModal(sessionId, cashierName, type) {
        document.getElementById('modal_session_id').value = sessionId;
        document.getElementById('modal_type').value = type;
        
        const modal = document.getElementById('transferModal');
        const iconContainer = document.getElementById('modal_icon_container');
        const icon = document.getElementById('modal_icon');
        const title = document.getElementById('modal_title');
        const subtitle = document.getElementById('modal_subtitle');
        const submitBtn = document.getElementById('modal_submit_btn');

        if (type === 'in') {
            title.innerText = 'Approvisionnement';
            subtitle.innerText = 'Trésorerie Centrale ➔ ' + cashierName;
            iconContainer.className = 'w-12 h-12 rounded-2xl flex items-center justify-center text-xl bg-emerald-50 text-emerald-600 border border-emerald-100';
            icon.className = 'fas fa-arrow-down-long';
            submitBtn.className = 'w-full py-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl text-xs font-black uppercase tracking-widest text-white transition-all active:scale-95 shadow-xl shadow-emerald-500/20';
        } else {
            title.innerText = 'Versement (Décharge)';
            subtitle.innerText = cashierName + ' ➔ Trésorerie Centrale';
            iconContainer.className = 'w-12 h-12 rounded-2xl flex items-center justify-center text-xl bg-rose-50 text-rose-600 border border-rose-100';
            icon.className = 'fas fa-arrow-up-long';
            submitBtn.className = 'w-full py-4 bg-rose-600 hover:bg-rose-700 text-white rounded-2xl text-xs font-black uppercase tracking-widest text-white transition-all active:scale-95 shadow-xl shadow-rose-500/20';
        }

        modal.classList.remove('hidden');
    }

    function closeTransferModal() {
        document.getElementById('transferModal').classList.add('hidden');
    }
</script>
@endpush
@endsection
