@extends('layouts.app_admin')

@section('title', 'Rapport de Paie')
@section('page-title', 'Audit Historique des Décaissements Personnel')

@section('content')
<div class="space-y-8">
    <!-- En-tête -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Archives de la Masse Salariale</h2>
            <p class="text-slate-500 text-sm font-medium">Audit des flux sortants du personnel sur la période du {{ $startDate->format('d/m/Y') }} au {{ $endDate->format('d/m/Y') }}</p>
        </div>
    </div>

    <!-- Filtres -->
    <div class="bank-card p-6">
        <form method="GET" action="{{ route('admin.payroll.report') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="space-y-1">
                <label class="text-[9px] font-black text-slate-400 uppercase">Date de Début</label>
                <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2 text-xs font-bold text-slate-600 outline-none focus:ring-1 focus:ring-blue-500">
            </div>
            <div class="space-y-1">
                <label class="text-[9px] font-black text-slate-400 uppercase">Date de Fin</label>
                <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2 text-xs font-bold text-slate-600 outline-none focus:ring-1 focus:ring-blue-500">
            </div>
            <div class="md:pt-5">
                <button type="submit" class="w-full btn-bank btn-bank-primary py-2 text-xs">
                    <i class="fas fa-filter mr-2"></i> Actualiser l'Audit
                </button>
            </div>
        </form>
    </div>

    <!-- Résumé par Type -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach($totals as $item)
        <div class="bank-card p-4 border-t-2 border-t-blue-500">
            <p class="text-[9px] font-black text-slate-400 uppercase">{{ match($item->payment_type) { 'salary' => 'Salaires', 'bonus' => 'Primes', 'advance' => 'Avances', default => $item->payment_type } }}</p>
            <p class="text-xl font-black text-slate-800 mt-1">{{ number_format($item->total, 0, ',', ' ') }} <small class="text-[10px]">XOF</small></p>
        </div>
        @endforeach
        <div class="bank-card p-4 bg-slate-900 border-t-2 border-t-emerald-500 text-white">
            <p class="text-[9px] font-black text-white/50 uppercase">Total Décaissements</p>
            <p class="text-xl font-black text-white mt-1">{{ number_format($totals->sum('total'), 0, ',', ' ') }} <small class="text-[10px] text-white/40">XOF</small></p>
        </div>
    </div>

    <!-- Liste des Paiements -->
    <div class="bank-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="bank-table">
                <thead>
                    <tr>
                        <th>Date & Référence</th>
                        <th>Bénéficiaire</th>
                        <th class="text-center">Type</th>
                        <th class="text-center">Canal</th>
                        <th class="text-right">Montant</th>
                        <th class="text-center">Caisse</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($payments as $payment)
                    <tr class="hover:bg-slate-50/50">
                        <td>
                            <p class="text-xs font-bold text-slate-800">{{ $payment->payment_date->format('d M Y') }}</p>
                            <p class="text-[9px] font-mono text-blue-600 font-bold uppercase">{{ $payment->transaction_reference }}</p>
                        </td>
                        <td>
                            <p class="text-xs font-bold text-slate-800 uppercase">{{ $payment->staff->full_name }}</p>
                            <p class="text-[9px] text-slate-400 uppercase font-bold">{{ str_replace('_', ' ', $payment->staff->role) }}</p>
                        </td>
                        <td class="text-center">
                            <span class="bank-badge py-0.5 px-2 text-[8px] bg-slate-100 text-slate-600 uppercase font-bold">
                                {{ match($payment->payment_type) { 'salary' => 'Salaire', 'bonus' => 'Prime', 'advance' => 'Avance', default => $payment->payment_type } }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="text-[8px] font-black uppercase text-slate-500">
                                {{ $payment->payment_method }}
                            </span>
                        </td>
                        <td class="text-right font-numeric font-black text-slate-900">
                            {{ number_format($payment->amount, 0, ',', ' ') }}
                        </td>
                        <td class="text-center">
                            @if($payment->cashier_session_id)
                                <span class="text-[9px] font-bold text-blue-600">SESS-{{ $payment->cashier_session_id }}</span>
                            @else
                                <span class="text-[9px] font-bold text-slate-300">N/A</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-20 text-center text-slate-400">Aucun enregistrement sur cette période</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/30">
            {{ $payments->links() }}
        </div>
    </div>
</div>
@endsection
