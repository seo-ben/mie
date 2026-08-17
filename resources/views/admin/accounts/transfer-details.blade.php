@extends('layouts.app_admin')

@section('title', 'Mémorandum de Migration - ' . $transaction->payment_reference)
@section('page-title', 'Trésorerie / Audit de Migration')

@section('content')
<div class="max-w-4xl mx-auto space-y-8 no-print">
    <!-- En-tête de Navigation -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.accounts.transfer.history') }}" class="btn-bank btn-bank-outline px-6">
            <i class="fas fa-chevron-left mr-2 text-[10px]"></i> Retour aux Registres
        </a>
        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="btn-bank btn-bank-outline px-6 italic">
                <i class="fas fa-print mr-2 text-[10px]"></i> Générer l'Artefact Impressible
            </button>
            <span class="px-4 py-2 bg-emerald-50 text-emerald-700 text-[10px] font-black rounded-full border border-emerald-100 uppercase tracking-widest shadow-sm">
                <i class="fas fa-certificate mr-1.5"></i> Migration Certifiée
            </span>
        </div>
    </div>

    <!-- Interface d'Audit Principale -->
    <div class="bank-card overflow-hidden shadow-2xl print-area">
        <!-- Bannière Institutionnelle -->
        <div class="px-10 py-8 bg-slate-900 text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-blue-500 opacity-5 rounded-full -mr-32 -mt-32 blur-3xl"></div>
            <div class="relative z-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <h2 class="text-[10px] font-black uppercase tracking-[0.4em] text-blue-400 mb-2">Mémorandum de Migration d'Actifs</h2>
                    <h3 class="text-3xl font-black tracking-tighter leading-none">{{ $transaction->payment_reference }}</h3>
                    <p class="text-[10px] font-mono font-bold text-white/40 mt-3 uppercase tracking-widest">Protocol Audit Index: {{ $transaction->transaction_reference }}</p>
                </div>
                <div class="text-right hidden md:block">
                    <p class="text-[10px] font-black text-white/30 uppercase tracking-[0.2em] mb-1">Horodatage de Certification</p>
                    <p class="text-sm font-black italic">{{ $transaction->transaction_date->format('d/m/Y') }} <span class="text-blue-400 mx-1">•</span> {{ $transaction->transaction_date->format('H:i:s') }}</p>
                </div>
            </div>
        </div>

        <div class="p-10 space-y-12">
            <!-- Visualisation du Flux d'Actifs -->
            <div class="relative">
                <div class="absolute inset-x-0 top-1/2 -translate-y-1/2 h-px bg-slate-100 hidden md:block border-dashed border-t"></div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-12 relative z-10">
                    <!-- Source -->
                    <div class="bg-white p-6 rounded-3xl border border-slate-100 text-center shadow-sm">
                        <div class="w-12 h-12 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-rose-100 shadow-inner">
                            <i class="fas fa-arrow-up-from-bracket"></i>
                        </div>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">compte Source / Émetteur</p>
                        @php
                            $sender = $transaction->transaction_type === 'transfer_out' ? $transaction : $relatedTransaction;
                        @endphp
                        @if($sender)
                            <h4 class="text-sm font-black text-slate-900 uppercase leading-tight">{{ $sender->account->client->full_name }}</h4>
                            <p class="text-[10px] font-mono font-bold text-blue-600 mt-1">{{ $sender->account->account_number }}</p>
                        @endif
                    </div>

                    <!-- Volume en Transit -->
                    <div class="flex flex-col items-center justify-center">
                        <div class="px-6 py-4 bg-slate-900 rounded-2xl text-white text-center shadow-xl mb-4">
                            <p class="text-[10px] font-black text-white/40 uppercase tracking-widest leading-none mb-2">Volume Migré</p>
                            <span class="text-2xl font-black font-numeric text-emerald-400">{{ number_format($transaction->amount, 0, ',', ' ') }}</span>
                            <span class="text-[10px] font-black text-white/40 ml-1">XOF</span>
                        </div>
                        @if($transaction->fee_amount > 0)
                            <p class="text-[9px] font-black text-rose-500 uppercase italic tracking-tighter">Taxe d'Audit : {{ number_format($transaction->fee_amount, 0, ',', ' ') }} XOF</p>
                        @endif
                    </div>

                    <!-- Destination -->
                    <div class="bg-white p-6 rounded-3xl border border-slate-100 text-center shadow-sm">
                        <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-emerald-100 shadow-inner">
                            <i class="fas fa-arrow-down-to-bracket"></i>
                        </div>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">compte Cible / Bénéficiaire</p>
                        @php
                            $receiver = $transaction->transaction_type === 'transfer_in' ? $transaction : $relatedTransaction;
                        @endphp
                        @if($receiver)
                            <h4 class="text-sm font-black text-slate-900 uppercase leading-tight">{{ $receiver->account->client->full_name }}</h4>
                            <p class="text-[10px] font-mono font-bold text-blue-600 mt-1">{{ $receiver->account->account_number }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Grille de Détails de l'Audit -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 pt-8 border-t border-slate-100">
                <!-- Spécifications Techniques -->
                <div class="space-y-6">
                    <h4 class="text-[10px] font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                        <i class="fas fa-microchip text-blue-500"></i> Spécifications du Protocole
                    </h4>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center text-[11px] font-bold">
                            <span class="text-slate-400 uppercase tracking-widest">Nature du Flux</span>
                            <span class="text-slate-900">Vecteur de Migration Interne</span>
                        </div>
                        <div class="flex justify-between items-center text-[11px] font-bold">
                            <span class="text-slate-400 uppercase tracking-widest">Statut Certification</span>
                            <span class="text-emerald-600 italic">IRRÉVOCABLE & SÉCURISÉ</span>
                        </div>
                        <div class="flex justify-between items-center text-[11px] font-bold">
                            <span class="text-slate-400 uppercase tracking-widest">Auditeur Responsable</span>
                            <span class="text-slate-900 uppercase">{{ $transaction->processedBy->name ?? 'Système Automatisé' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Analyse d'Incidence Financière -->
                <div class="space-y-6">
                    <h4 class="text-[10px] font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                        <i class="fas fa-calculator text-blue-500"></i> Incidence de Trésorerie
                    </h4>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center text-[11px] font-bold">
                            <span class="text-slate-400 uppercase tracking-widest">Valeur Nominale</span>
                            <span class="text-slate-900">{{ number_format($transaction->amount, 0, ',', ' ') }} XOF</span>
                        </div>
                        <div class="flex justify-between items-center text-[11px] font-bold">
                            <span class="text-slate-400 uppercase tracking-widest">Taxes Foncées Industrielles</span>
                            <span class="text-rose-500">+ {{ number_format($transaction->fee_amount, 0, ',', ' ') }} XOF</span>
                        </div>
                        <div class="pt-4 border-t border-slate-100 flex justify-between items-center">
                            @if($transaction->transaction_type === 'transfer_out')
                                <span class="text-slate-800 text-[11px] font-black uppercase tracking-widest">Exposition Totale Source</span>
                                <span class="text-lg font-black text-rose-600 font-numeric">{{ number_format($transaction->amount + $transaction->fee_amount, 0, ',', ' ') }} XOF</span>
                            @else
                                <span class="text-slate-800 text-[11px] font-black uppercase tracking-widest">Injection Nette Bénéficiaire</span>
                                <span class="text-lg font-black text-emerald-600 font-numeric">{{ number_format($transaction->amount, 0, ',', ' ') }} XOF</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes d'Audit -->
            @if($transaction->description)
            <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100 italic">
                <h4 class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Annotation de l'Auditeur</h4>
                <p class="text-[11px] font-bold text-slate-600 leading-relaxed">"{{ $transaction->description }}"</p>
            </div>
            @endif

            <!-- Preuve Cryptographique (Pied de page) -->
            <div class="flex flex-col md:flex-row items-center justify-between gap-6 pt-12 border-t border-slate-100 border-dashed">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-center text-slate-300">
                        <i class="fas fa-qrcode text-3xl"></i>
                    </div>
                    <div>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-tight">Vérification de l'Intégrité</p>
                        <p class="text-[10px] font-mono font-bold text-slate-500 mt-1 uppercase">X-MIGRATION-SIGNATURE-VALIDATED</p>
                    </div>
                </div>
                <div class="text-center md:text-right">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-tight mb-2 italic">Certifié par la Direction des Flux</p>
                    <div class="w-32 h-12 bg-slate-50/50 border border-slate-100 rounded-lg flex items-center justify-center italic text-[10px] font-black text-slate-200">SIGNATURE NUMÉRIQUE</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions Externes -->
    <div class="flex justify-end gap-4 pb-12">
        <a href="{{ route('admin.accounts.show', $transaction->account_id) }}" class="btn-bank btn-bank-outline px-8 py-3 text-xs font-black uppercase">
            Ré-Ouvrir le compte Détenteur
        </a>
    </div>
</div>

<!-- Styles spécifiques à l'impression -->
<style>
@media print {
    body { background: white !important; }
    .no-print { display: none !important; }
    .print-area { 
        position: fixed;
        inset: 0;
        margin: 0;
        box-shadow: none !important;
        border: none !important;
    }
    .bank-card { border: 1px solid #e2e8f0 !important; }
}
</style>

@if(session('print_receipt'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        window.open('{{ session('print_receipt') }}', '_blank');
    });
</script>
@endif
@endsection
