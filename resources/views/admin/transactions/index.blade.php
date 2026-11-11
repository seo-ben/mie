@extends('layouts.app_admin')

@section('title', 'Grand Livre Institutionnel')
@section('page-title', 'Registre Central des Transactions')

@section('content')
<div class="space-y-8">
    <!-- En-tête d'Audit -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Grand Livre Financier</h2>
            <p class="text-slate-500 text-sm font-medium">Audit en temps réel des flux de capitaux et mouvements de trésorerie</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.transactions.analytics') }}" class="btn-bank btn-bank-outline">
                <i class="fas fa-chart-line mr-2 text-[10px]"></i> Analytique
            </a>
            <div class="relative group">
                <button class="btn-bank btn-bank-primary">
                    <i class="fas fa-download mr-2 text-[10px]"></i> Export d'Audit
                </button>
                <div class="absolute right-0 top-full mt-2 w-48 bg-white border border-slate-200 rounded-xl shadow-xl hidden group-hover:block z-50">
                    <a href="{{ route('admin.transactions.export', array_merge(request()->all(), ['format' => 'csv'])) }}" class="block px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 border-b border-slate-100">Registre CSV</a>
                    <a href="{{ route('admin.transactions.export', array_merge(request()->all(), ['format' => 'excel'])) }}" class="block px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 border-b border-slate-100">Grand Livre Excel</a>
                    <a href="{{ route('admin.transactions.export', array_merge(request()->all(), ['format' => 'pdf'])) }}" class="block px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">Rapport d'Audit PDF</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Matrice de Synthèse des Flux (Entrées / Sorties / Solde Net) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- TOTAL ENTRÉES -->
        <div class="bank-card p-5 border-l-4 border-emerald-500 bg-emerald-50/10">
            <div class="flex items-center justify-between">
                <span class="kpi-label text-emerald-800">Total Entrées (Inflows)</span>
                <span class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center text-xs">
                    <i class="fas fa-arrow-down"></i>
                </span>
            </div>
            <div class="kpi-value !text-xl mt-2 text-emerald-900 font-numeric">
                +{{ number_format($stats['total_inflows'], 0, ',', ' ') }} <small class="text-xs">XOF</small>
            </div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">Dépôts, Remboursements & Tontines</p>
        </div>

        <!-- TOTAL SORTIES -->
        <div class="bank-card p-5 border-l-4 border-rose-500 bg-rose-50/10">
            <div class="flex items-center justify-between">
                <span class="kpi-label text-rose-800">Total Sorties (Outflows)</span>
                <span class="w-8 h-8 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center text-xs">
                    <i class="fas fa-arrow-up"></i>
                </span>
            </div>
            <div class="kpi-value !text-xl mt-2 text-rose-900 font-numeric">
                -{{ number_format($stats['total_outflows'], 0, ',', ' ') }} <small class="text-xs">XOF</small>
            </div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">Retraits, Décaissements Prêts & Paiements</p>
        </div>

        <!-- FLUX NET (TRÉSORERIE DISPONIBLE DE LA PÉRIODE) -->
        @php
            $isPositive = $stats['net_cash_flow'] >= 0;
        @endphp
        <div class="bank-card p-5 border-l-4 {{ $isPositive ? 'border-blue-500 bg-blue-50/10' : 'border-amber-500 bg-amber-50/10' }}">
            <div class="flex items-center justify-between">
                <span class="kpi-label {{ $isPositive ? 'text-blue-800' : 'text-amber-800' }}">Flux Net de Trésorerie</span>
                <span class="w-8 h-8 rounded-lg {{ $isPositive ? 'bg-blue-100 text-blue-600' : 'bg-amber-100 text-amber-600' }} flex items-center justify-center text-xs">
                    <i class="fas fa-scale-balanced"></i>
                </span>
            </div>
            <div class="kpi-value !text-xl mt-2 {{ $isPositive ? 'text-blue-900' : 'text-amber-900' }} font-numeric">
                {{ $isPositive ? '+' : '' }}{{ number_format($stats['net_cash_flow'], 0, ',', ' ') }} <small class="text-xs">XOF</small>
            </div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">{{ $isPositive ? 'Excédent Net de Liquidité' : 'Déficit / Tirage Net' }}</p>
        </div>

        <!-- VÉLOCITÉ AUJOURD'HUI -->
        <div class="bank-card p-5 border-l-4 border-slate-700 bg-slate-50/50">
            <div class="flex items-center justify-between">
                <span class="kpi-label text-slate-800">Flux du Jour (En Direct)</span>
                <span class="w-8 h-8 rounded-lg bg-slate-200 text-slate-700 flex items-center justify-center text-xs">
                    <i class="fas fa-clock"></i>
                </span>
            </div>
            <div class="flex items-baseline gap-2 mt-2">
                <span class="text-xs font-black text-emerald-600 font-numeric">+{{ number_format($stats['today_inflows'], 0, ',', ' ') }}</span>
                <span class="text-slate-300">/</span>
                <span class="text-xs font-black text-rose-600 font-numeric">-{{ number_format($stats['today_outflows'], 0, ',', ' ') }}</span>
            </div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">{{ $stats['today_transactions'] }} opération(s) aujourd'hui</p>
        </div>
    </div>

    <!-- Contrôles d'Audit & Filtres -->
    <div class="bank-card p-6">
        <form method="GET" action="{{ route('admin.transactions.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <div class="md:col-span-3 relative">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Réf, N° Compte ou Adhérent..." class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-10 pr-4 py-2 text-xs font-bold text-slate-700 focus:ring-1 focus:ring-blue-500 outline-none transition">
                <i class="fas fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            </div>

            <!-- Filtre Direction (Entrée vs Sortie) -->
            <div class="md:col-span-2">
                <select name="flow_direction" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold text-slate-700 focus:ring-1 focus:ring-blue-500 outline-none transition">
                    <option value="">Tous les Flux (Entrées & Sorties)</option>
                    <option value="inflow" {{ request('flow_direction') === 'inflow' ? 'selected' : '' }}>🟢 Entrées Uniquement (+)</option>
                    <option value="outflow" {{ request('flow_direction') === 'outflow' ? 'selected' : '' }}>🔴 Sorties Uniquement (-)</option>
                </select>
            </div>

            <!-- Filtre Type précis de transaction -->
            <div class="md:col-span-2">
                <select name="transaction_type" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold text-slate-700 focus:ring-1 focus:ring-blue-500 outline-none transition">
                    <option value="">Tous les Types</option>
                    <optgroup label="🟢 Entrées d'Argent">
                        <option value="deposit" {{ request('transaction_type') === 'deposit' ? 'selected' : '' }}>Dépôt / Épargne</option>
                        <option value="tontine_deposit" {{ request('transaction_type') === 'tontine_deposit' ? 'selected' : '' }}>Cotisation Tontine</option>
                        <option value="loan_repayment" {{ request('transaction_type') === 'loan_repayment' ? 'selected' : '' }}>Remboursement Prêt</option>
                        <option value="transfer_in" {{ request('transaction_type') === 'transfer_in' ? 'selected' : '' }}>Virement Entrant / Dotation</option>
                    </optgroup>
                    <optgroup label="🔴 Sorties d'Argent">
                        <option value="withdrawal" {{ request('transaction_type') === 'withdrawal' ? 'selected' : '' }}>Retrait Espèces Guichet</option>
                        <option value="loan_disbursement" {{ request('transaction_type') === 'loan_disbursement' ? 'selected' : '' }}>Décaissement Prêt</option>
                        <option value="payout" {{ request('transaction_type') === 'payout' ? 'selected' : '' }}>Paiement / Remise Tontine</option>
                        <option value="transfer_out" {{ request('transaction_type') === 'transfer_out' ? 'selected' : '' }}>Virement Sortant</option>
                    </optgroup>
                </select>
            </div>

            <!-- Filtre Statut -->
            <div class="md:col-span-2">
                <select name="status" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold text-slate-700 focus:ring-1 focus:ring-blue-500 outline-none transition">
                    <option value="">Tous les Statuts</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Validé / Effectué</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En Revue / En Attente</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Signalé / Échoué</option>
                </select>
            </div>

            <div class="md:col-span-3 flex gap-2">
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="flex-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold text-slate-700 focus:ring-1 focus:ring-blue-500 outline-none transition" title="Date de début">
                <button type="submit" class="btn-bank btn-bank-primary px-4" title="Appliquer les filtres">
                    <i class="fas fa-filter text-xs"></i>
                </button>
                <a href="{{ route('admin.transactions.index') }}" class="btn-bank btn-bank-outline px-3" title="Réinitialiser">
                    <i class="fas fa-rotate text-xs"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Volet Principal d'Audit -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Liste du Registre -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bank-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="bank-table">
                        <thead>
                            <tr>
                                <th>Identité Transaction</th>
                                <th>Type & Direction de Flux</th>
                                <th>Montant (Mouvement)</th>
                                <th>Statut</th>
                                <th class="text-right">Audit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($transactions as $transaction)
                            @php
                                $inflowMap = [
                                    'deposit' => ['label' => 'Dépôt Espèces', 'icon' => 'fa-arrow-down', 'color' => 'emerald', 'sign' => '+', 'badge' => 'ENTRÉE'],
                                    'savings_deposit' => ['label' => 'Dépôt Épargne', 'icon' => 'fa-arrow-down', 'color' => 'emerald', 'sign' => '+', 'badge' => 'ENTRÉE'],
                                    'tontine_deposit' => ['label' => 'Cotisation Tontine', 'icon' => 'fa-rotate', 'color' => 'emerald', 'sign' => '+', 'badge' => 'ENTRÉE'],
                                    'tontine_contribution' => ['label' => 'Cotisation Tontine', 'icon' => 'fa-rotate', 'color' => 'emerald', 'sign' => '+', 'badge' => 'ENTRÉE'],
                                    'loan_repayment' => ['label' => 'Remboursement Prêt', 'icon' => 'fa-hand-holding-dollar', 'color' => 'emerald', 'sign' => '+', 'badge' => 'ENTRÉE'],
                                    'transfer_in' => ['label' => 'Dotation / Entrant', 'icon' => 'fa-arrow-right-to-bracket', 'color' => 'blue', 'sign' => '+', 'badge' => 'ENTRÉE'],
                                    'capital_contribution' => ['label' => 'Apport Capital', 'icon' => 'fa-vault', 'color' => 'emerald', 'sign' => '+', 'badge' => 'ENTRÉE'],
                                    'fee' => ['label' => 'Frais & Commission', 'icon' => 'fa-receipt', 'color' => 'emerald', 'sign' => '+', 'badge' => 'ENTRÉE'],
                                    'withdrawal' => ['label' => 'Retrait Guichet', 'icon' => 'fa-arrow-up', 'color' => 'rose', 'sign' => '-', 'badge' => 'SORTIE'],
                                    'loan_disbursement' => ['label' => 'Décaissement Prêt', 'icon' => 'fa-money-bill-transfer', 'color' => 'rose', 'sign' => '-', 'badge' => 'SORTIE'],
                                    'payout' => ['label' => 'Paiement Tontine', 'icon' => 'fa-money-bill-wave', 'color' => 'rose', 'sign' => '-', 'badge' => 'SORTIE'],
                                    'transfer_out' => ['label' => 'Virement Sortant', 'icon' => 'fa-arrow-right-from-bracket', 'color' => 'rose', 'sign' => '-', 'badge' => 'SORTIE'],
                                ];

                                $info = $inflowMap[$transaction->transaction_type] ?? [
                                    'label' => ucwords(str_replace('_', ' ', $transaction->transaction_type)),
                                    'icon' => 'fa-circle-dollar-to-slot',
                                    'color' => 'slate',
                                    'sign' => '',
                                    'badge' => 'FLUX'
                                ];
                                $isCredit = $info['sign'] === '+';
                            @endphp
                            <tr class="hover:bg-slate-50/50 transition-colors cursor-pointer text-slate-600" onclick="loadTransactionDetails({{ $transaction->id }})" id="tr-{{ $transaction->id }}">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-{{ $info['color'] }}-50 flex items-center justify-center text-{{ $info['color'] }}-600 border border-{{ $info['color'] }}-200 shadow-xs">
                                            <i class="fas {{ $info['icon'] }} text-xs"></i>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-slate-800 leading-tight">
                                                {{ $transaction->account?->client?->full_name ?? ($transaction->loan?->client?->full_name ?? 'Adhérent / Opération Interne') }}
                                            </p>
                                            <p class="text-[9px] font-mono font-bold text-slate-400 mt-0.5 uppercase tracking-tighter">{{ $transaction->transaction_reference }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="flex flex-col gap-1 items-start">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-wider bg-{{ $info['color'] }}-50 text-{{ $info['color'] }}-700 border border-{{ $info['color'] }}-200">
                                            {{ $info['badge'] }} &bull; {{ $info['label'] }}
                                        </span>
                                        <span class="text-[9px] text-slate-400 font-medium">{{ $transaction->transaction_date->format('d/m/Y H:i') }}</span>
                                    </div>
                                </td>
                                <td>
                                    <p class="text-xs font-bold font-numeric {{ $isCredit ? 'text-emerald-600' : 'text-rose-600' }}">
                                        {{ $info['sign'] }} {{ number_format($transaction->amount, 0, ',', ' ') }} <small class="text-[9px] font-semibold">XOF</small>
                                    </p>
                                </td>
                                <td>
                                    @switch($transaction->status)
                                        @case('completed')
                                            <span class="bank-badge badge-success !text-[8px]">Validé</span>
                                            @break
                                        @case('pending')
                                            <span class="bank-badge badge-warning !text-[8px]">En Revue</span>
                                            @break
                                        @case('failed')
                                        @case('rejected')
                                            <span class="bank-badge badge-danger !text-[8px]">Signalé</span>
                                            @break
                                    @endswitch
                                </td>
                                <td class="text-right">
                                    <button class="w-7 h-7 inline-flex items-center justify-center rounded bg-slate-100 text-slate-400 hover:text-blue-600 transition" title="Consulter l'audit">
                                        <i class="fas fa-eye text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="py-20 text-center">
                                    <i class="fas fa-file-invoice text-3xl text-slate-200 mb-4 block"></i>
                                    <p class="text-xs font-bold text-slate-400 uppercase">Aucune transaction trouvée dans l'audit actuel</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($transactions->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/30">
                    {{ $transactions->appends(request()->query())->links() }}
                </div>
                @endif
            </div>
        </div>

        <!-- Volet d'Investigation Détaillée -->
        <div class="lg:col-span-1">
            <div id="transaction-detail-panel" class="sticky top-24">
                <div class="bank-card p-12 text-center border-dashed border-2 border-slate-200 bg-slate-50/50">
                    <div class="w-16 h-16 rounded-full bg-white flex items-center justify-center text-slate-300 mx-auto mb-6 shadow-sm">
                        <i class="fas fa-fingerprint text-2xl"></i>
                    </div>
                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-widest mb-2">Volet d'Investigation</h4>
                    <p class="text-[10px] text-slate-400 font-medium">Sélectionnez une entrée du registre pour initier l'audit financier approfondi.</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function loadTransactionDetails(transactionId) {
        // Gestion de l'état esthétique
        document.querySelectorAll('tr[id^="tr-"]').forEach(row => row.classList.remove('bg-blue-50/50', 'border-l-2', 'border-blue-600'));
        const row = document.getElementById(`tr-${transactionId}`);
        if(row) row.classList.add('bg-blue-50/50', 'border-l-2', 'border-blue-600');

        // État de chargement professionnel
        document.getElementById('transaction-detail-panel').innerHTML = `
            <div class="bank-card p-20 text-center">
                <i class="fas fa-circle-notch fa-spin text-blue-600 text-2xl mb-4"></i>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Contrôle du Protocole...</p>
            </div>
        `;

        // Exécution via API Institutionnelle
        fetch(`/admin/transactions/${transactionId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('transaction-detail-panel').innerHTML = data.html;
            }
        })
        .catch(err => {
            document.getElementById('transaction-detail-panel').innerHTML = `
                <div class="bank-card p-12 bg-red-50 border-red-100 text-center">
                    <i class="fas fa-triangle-exclamation text-red-600 text-2xl mb-4"></i>
                    <p class="text-xs font-bold text-red-900">ÉCHEC DE DÉCHIFFREMENT</p>
                    <p class="text-[9px] text-red-500 mt-2">Vérifiez la connectivité système.</p>
                </div>
            `;
        });
    }

    @if($transactions->isNotEmpty() && !request()->has('search'))
        document.addEventListener('DOMContentLoaded', () => loadTransactionDetails({{ $transactions->first()->id }}));
    @endif
</script>
@endpush
@endsection