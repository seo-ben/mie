@extends('layouts.app_admin')

@section('title', 'Échéancier de Remboursement - ' . $loan->loan_number)
@section('page-title', 'Crédits / Échéancier de Paiement')

@section('content')
<div class="space-y-8 no-print">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.loans.show', $loan->id) }}" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-xl flex items-center justify-center transition border border-slate-200 text-slate-600">
                <i class="fas fa-chevron-left text-xs"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Protocole de Remboursement</h2>
                <div class="flex items-center gap-3 mt-1">
                    <p class="text-slate-500 text-sm font-medium">Référence Prêt : <span class="font-mono text-blue-600 font-bold tracking-widest uppercase">{{ $loan->loan_number }}</span></p>
                    <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                    <p class="text-slate-500 text-sm font-bold uppercase">{{ $loan->client->full_name }}</p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="btn-bank btn-bank-outline">
                <i class="fas fa-print mr-2 text-[10px]"></i> Version Imprimable
            </button>
            <a href="{{ route('admin.loans.show', $loan->id) }}" class="btn-bank btn-bank-primary">
                <i class="fas fa-file-contract mr-2 text-[10px]"></i> Dossier du Prêt
            </a>
        </div>
    </div>

    <!-- Synthèse Financière du Prêt -->
    <div class="bank-card p-6 border-trust relative overflow-hidden">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-8 relative z-10">
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Capital Engagé</p>
                <p class="text-xl font-black text-slate-900">{{ number_format($loan->approved_amount, 0, ',', ' ') }} <small class="text-xs">XOF</small></p>
            </div>
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Taux Appliqué</p>
                <p class="text-xl font-black text-slate-900">{{ $loan->interest_rate }}%</p>
            </div>
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Horizon Temporel</p>
                <p class="text-xl font-black text-slate-900">{{ $loan->duration_months }} <small class="text-xs">Mois</small></p>
            </div>
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Mensualité Fixe</p>
                <p class="text-xl font-black text-blue-600">{{ number_format($loan->monthly_payment, 0, ',', ' ') }} <small class="text-xs">XOF</small></p>
            </div>
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Total Recouvrement</p>
                <p class="text-xl font-black text-emerald-700">{{ number_format($loan->total_amount_due, 0, ',', ' ') }} <small class="text-xs">XOF</small></p>
            </div>
        </div>
    </div>

    <!-- Séquenceur de Progression -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bank-card p-6">
            <h3 class="text-[10px] font-black text-slate-800 uppercase tracking-widest mb-6 flex items-center justify-between">
                <span>Avancement du Recouvrement</span>
                @php
                    $totalPaid = $loan->payments->where('status', 'paid')->sum('paid_amount');
                    $totalRemaining = $loan->total_amount_due - $totalPaid;
                    $progressPercent = $loan->total_amount_due > 0 ? ($totalPaid / $loan->total_amount_due) * 100 : 0;
                @endphp
                <span class="text-emerald-600">{{ round($progressPercent, 1) }}% Complété</span>
            </h3>

            <div class="relative h-4 bg-slate-100 rounded-full overflow-hidden mb-4">
                <div class="absolute h-full bg-emerald-500 rounded-full transition-all duration-1000 ease-out" style="width: {{ min($progressPercent, 100) }}%"></div>
            </div>

            <div class="flex justify-between items-center text-xs font-bold uppercase">
                <div>
                    <span class="text-slate-400 block text-[9px]">Volume Recouvré</span>
                    <span class="text-emerald-700">{{ number_format($totalPaid, 0, ',', ' ') }} XOF</span>
                </div>
                <div class="text-right">
                    <span class="text-slate-400 block text-[9px]">Reste à Courir</span>
                    <span class="text-slate-700">{{ number_format($totalRemaining, 0, ',', ' ') }} XOF</span>
                </div>
            </div>
        </div>

        <div class="bank-card p-0 overflow-hidden flex flex-col">
            @php
                $paidCount = $loan->payments->where('status', 'paid')->count();
                $pendingCount = $loan->payments->where('status', 'pending')->count();
                $overdueCount = $loan->payments->where('status', 'overdue')->count();
            @endphp
            <div class="flex-1 bg-emerald-50/50 p-4 border-b border-emerald-100 flex items-center justify-between">
                <span class="text-[9px] font-black text-emerald-800 uppercase tracking-widest">Soldés</span>
                <span class="text-sm font-black text-emerald-600 bg-white px-3 py-1 rounded-lg border border-emerald-100 shadow-sm">{{ $paidCount }}</span>
            </div>
            <div class="flex-1 bg-amber-50/50 p-4 border-b border-amber-100 flex items-center justify-between">
                <span class="text-[9px] font-black text-amber-800 uppercase tracking-widest">En Attente</span>
                <span class="text-sm font-black text-amber-600 bg-white px-3 py-1 rounded-lg border border-amber-100 shadow-sm">{{ $pendingCount }}</span>
            </div>
            <div class="flex-1 bg-rose-50/50 p-4 flex items-center justify-between">
                <span class="text-[9px] font-black text-rose-800 uppercase tracking-widest">En Souffrance</span>
                <span class="text-sm font-black text-rose-600 bg-white px-3 py-1 rounded-lg border border-rose-100 shadow-sm">{{ $overdueCount }}</span>
            </div>
        </div>
    </div>

    @if($overdueCount > 0)
    <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl flex items-start gap-4 animate-pulse">
        <i class="fas fa-triangle-exclamation text-rose-500 mt-1"></i>
        <div>
            <h4 class="text-sm font-black text-rose-800 uppercase tracking-tight">Alerte de Conformité</h4>
            <p class="text-xs font-bold text-rose-700 mt-1">
                Ce dossier présente {{ $overdueCount }} échéance(s) en souffrance. Le protocole de recouvrement contentieux doit être activé.
            </p>
        </div>
    </div>
    @endif

    <!-- Matrice d'Échéancier -->
    <div class="bank-card overflow-hidden">
        <div class="px-8 py-5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <h3 class="text-[10px] font-black text-slate-800 uppercase tracking-widest">Tableau d'Amortissement</h3>
            <span class="px-3 py-1 bg-slate-200 text-slate-600 text-[8px] font-black rounded-full uppercase">{{ $loan->payments->count() }} Périodes</span>
        </div>

        <div class="overflow-x-auto">
            <table class="bank-table">
                <thead>
                    <tr class="!bg-slate-50/20">
                        <th class="uppercase tracking-widest text-[9px] w-16 text-center">Cycle</th>
                        <th class="uppercase tracking-widest text-[9px]">Échéance</th>
                        <th class="uppercase tracking-widest text-[9px] text-right">Montant Dû</th>
                        <th class="uppercase tracking-widest text-[9px] text-right">Principal</th>
                        <th class="uppercase tracking-widest text-[9px] text-right">Intérêts</th>
                        <th class="uppercase tracking-widest text-[9px] text-right">Réalisé</th>
                        <th class="uppercase tracking-widest text-[9px]">Date Ops.</th>
                        <th class="uppercase tracking-widest text-[9px] text-right">Pénalités</th>
                        <th class="uppercase tracking-widest text-[9px] text-center">Statut</th>
                        <th class="uppercase tracking-widest text-[9px] text-right">Contrôle</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($loan->payments->sortBy('payment_number') as $payment)
                    <tr class="hover:bg-slate-50/50 transition-colors group {{ $payment->status === 'overdue' ? 'bg-rose-50/30' : '' }}">
                        <td class="text-center py-4">
                            <span class="w-6 h-6 rounded-lg flex items-center justify-center text-[10px] font-black mx-auto
                                {{ $payment->status === 'paid' ? 'bg-emerald-100 text-emerald-700' : 
                                   ($payment->status === 'overdue' ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-600') }}">
                                {{ $payment->payment_number }}
                            </span>
                        </td>
                        <td>
                            <div class="text-[11px] font-bold text-slate-700">{{ $payment->due_date->format('d/m/Y') }}</div>
                            @if($payment->status === 'pending' && $payment->due_date->isPast())
                                <span class="text-[8px] font-black text-rose-500 uppercase flex items-center gap-1 mt-0.5">
                                    <i class="fas fa-clock"></i> J+{{ $payment->due_date->diffInDays(now()) }}
                                </span>
                            @endif
                        </td>
                        <td class="text-right">
                            <span class="text-xs font-black text-slate-900">{{ number_format($payment->expected_amount, 0, ',', ' ') }}</span>
                            <span class="text-[9px] text-slate-400 font-bold">XOF</span>
                        </td>
                        <td class="text-right text-[11px] font-medium text-slate-500">
                            {{ number_format($payment->principal_amount, 0, ',', ' ') }}
                        </td>
                        <td class="text-right text-[11px] font-medium text-slate-500">
                            {{ number_format($payment->interest_amount, 0, ',', ' ') }}
                        </td>
                        <td class="text-right">
                            @if($payment->paid_amount)
                                <span class="text-xs font-black text-emerald-600">{{ number_format($payment->paid_amount, 0, ',', ' ') }}</span>
                                <span class="text-[9px] text-emerald-400 font-bold">XOF</span>
                            @else
                                <span class="text-slate-300 font-bold">—</span>
                            @endif
                        </td>
                        <td class="text-[10px] font-bold">
                            @if($payment->paid_date)
                                <span class="text-emerald-700">{{ $payment->paid_date->format('d/m/Y') }}</span>
                            @else
                                <span class="text-slate-300 italic">Non traité</span>
                            @endif
                        </td>
                        <td class="text-right">
                            @if($payment->penalty_amount > 0)
                                <span class="text-[10px] font-black text-rose-600">+{{ number_format($payment->penalty_amount, 0, ',', ' ') }}</span>
                            @else
                                <span class="text-slate-300 font-bold">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @php
                                $statusStyles = [
                                    'pending' => 'bg-amber-100 text-amber-700',
                                    'paid' => 'bg-emerald-100 text-emerald-700',
                                    'overdue' => 'bg-rose-100 text-rose-700',
                                    'partial' => 'bg-blue-100 text-blue-700',
                                ];
                                $label = match($payment->status) {
                                    'pending' => 'Attente',
                                    'paid' => 'Soldé',
                                    'overdue' => 'Retard',
                                    'partial' => 'Partiel',
                                    default => 'Inconnu'
                                };
                            @endphp
                            <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-tight {{ $statusStyles[$payment->status] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ $label }}
                            </span>
                        </td>
                        <td class="text-right pr-6">
                            @if(in_array($payment->status, ['pending', 'overdue', 'partial']))
                                <button onclick="openPaymentModal({{ $payment->id }}, {{ $payment->expected_amount - ($payment->paid_amount ?? 0) }})"
                                        class="btn-bank btn-bank-primary !py-1 !px-3 !text-[9px] !h-auto">
                                    <i class="fas fa-coins mr-1"></i> Encaisser
                                </button>
                            @elseif($payment->status === 'paid')
                                <span class="text-emerald-500 text-[10px]">
                                    <i class="fas fa-check-double"></i>
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="py-20 text-center">
                            <i class="fas fa-file-invoice text-4xl text-slate-200 mb-4 block"></i>
                            <p class="text-[10px] font-black text-slate-300 uppercase tracking-[0.2em]">Protocole d'amortissement non initialisé</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($loan->payments->count() > 0)
                <tfoot class="bg-slate-50 border-t-2 border-slate-200">
                    <tr>
                        <td colspan="2" class="px-6 py-4 text-right text-[10px] font-black text-slate-500 uppercase tracking-widest">Cumul Global :</td>
                        <td class="px-4 py-4 text-right text-xs font-black text-slate-900">{{ number_format($loan->payments->sum('expected_amount'), 0, ',', ' ') }} <small>XOF</small></td>
                        <td class="px-4 py-4 text-right text-xs font-bold text-slate-500">{{ number_format($loan->payments->sum('principal_amount'), 0, ',', ' ') }}</td>
                        <td class="px-4 py-4 text-right text-xs font-bold text-slate-500">{{ number_format($loan->payments->sum('interest_amount'), 0, ',', ' ') }}</td>
                        <td class="px-4 py-4 text-right text-xs font-black text-emerald-600">{{ number_format($loan->payments->sum('paid_amount'), 0, ',', ' ') }} <small>XOF</small></td>
                        <td class="px-4 py-4"></td>
                        <td class="px-4 py-4 text-right text-xs font-black text-rose-600">{{ number_format($loan->payments->sum('penalty_amount'), 0, ',', ' ') }} <small>XOF</small></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

<!-- Modal Paiement Pro -->
<div id="paymentModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
    <div class="bank-card w-full max-w-md animate-scale-in overflow-hidden shadow-2xl">
        <div class="px-6 py-4 bg-blue-600 text-white flex items-center justify-between">
            <h3 class="text-xs font-black uppercase tracking-widest flex items-center gap-2">
                <i class="fas fa-cash-register"></i> Guichet de Recouvrement
            </h3>
            <button onclick="closePaymentModal()" class="text-white/70 hover:text-white transition">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.loans.record-payment', $loan->id) }}" id="paymentForm">
            @csrf
            <input type="hidden" name="payment_id" id="payment_id">

            <div class="p-6 space-y-6">
                <!-- Montant Attendu -->
                <div class="p-4 bg-slate-50 border-l-4 border-blue-500 rounded-r-xl">
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Solde Exigible</p>
                    <p class="text-2xl font-black text-slate-800" id="expected_amount_display">0 XOF</p>
                </div>

                <!-- Saisie -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2">Montant Perçu <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <input type="number" name="paid_amount" id="paid_amount" required
                                class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-4 pr-12 py-3 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-blue-500 outline-none"
                                placeholder="0" min="0">
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[9px] font-black text-slate-400">XOF</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2">Canal de Règlement <span class="text-rose-500">*</span></label>
                        <select name="payment_method" required
                                class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-xs font-bold text-slate-700 uppercase focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="cash">Espèces (Caisse Physique)</option>
                            <option value="mobile_money">Mobile Money (Numérique)</option>
                            <option value="bank_transfer">Virement Bancaire (Interbancaire)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2">Référence (Optionnel)</label>
                        <input type="text" name="payment_reference"
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500 outline-none uppercase"
                               placeholder="REF-TRANSACTION-000">
                    </div>

                    <div>
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2">Annotation</label>
                        <textarea name="payment_notes" rows="2"
                                  class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-xs font-medium text-slate-700 focus:ring-2 focus:ring-blue-500 outline-none resize-none"
                                  placeholder="Note d'observation éventuelle..."></textarea>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex gap-3">
                <button type="button" onclick="closePaymentModal()"
                        class="flex-1 btn-bank btn-bank-outline py-3 text-xs uppercase font-black">
                    Annuler
                </button>
                <button type="submit"
                        class="flex-1 btn-bank btn-bank-primary py-3 text-xs uppercase font-black shadow-lg shadow-blue-500/20">
                    Confirmer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Layout Impression -->
<div class="hidden print:block font-serif p-8">
    <div class="text-center border-b-2 border-black pb-8 mb-8">
        <h1 class="text-3xl font-bold uppercase tracking-widest mb-2">Tableau d'Amortissement</h1>
        <p class="text-sm">Document émis le {{ now()->format('d/m/Y à H:i') }}</p>
    </div>
    
    <div class="grid grid-cols-2 gap-8 mb-8 text-sm">
        <div>
            <p class="font-bold uppercase border-b border-black mb-2">Emprunteur</p>
            <p>{{ $loan->client->full_name }}</p>
            <p>ID: {{ $loan->client->client_number }}</p>
        </div>
        <div class="text-right">
            <p class="font-bold uppercase border-b border-black mb-2 inline-block">Détails Prêt</p>
            <p>Réf: {{ $loan->loan_number }}</p>
            <p>Montant: {{ number_format($loan->approved_amount, 0, ',', ' ') }} XOF</p>
        </div>
    </div>
    
    <!-- Table content for print would be duplicated or CSS customized, handled by media query above -->
</div>

@push('scripts')
<script>
function openPaymentModal(paymentId, expectedAmount) {
    document.getElementById('payment_id').value = paymentId;
    document.getElementById('paid_amount').value = expectedAmount;
    document.getElementById('expected_amount_display').textContent = new Intl.NumberFormat('fr-FR').format(expectedAmount) + ' XOF';
    
    const modal = document.getElementById('paymentModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closePaymentModal() {
    const modal = document.getElementById('paymentModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.getElementById('paymentForm').reset();
}

// Close on outside click
document.getElementById('paymentModal').addEventListener('click', function(e) {
    if (e.target === this) closePaymentModal();
});
</script>
@endpush

<style>
@media print {
    body * { visibility: hidden; }
    .print\:block, .print\:block * { visibility: visible; }
    .print\:block { position: absolute; left: 0; top: 0; width: 100%; }
    .no-print { display: none !important; }
}
</style>
@endsection
