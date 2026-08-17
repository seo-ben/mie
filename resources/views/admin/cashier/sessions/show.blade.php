@extends('layouts.app_admin')

@section('title', 'Audit Détail - Session #' . $session->id)

@section('content')
<div class="space-y-6">
    <!-- Barre de Navigation de Session (Audit Chronologique) -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-50 p-3 rounded-xl border border-slate-200 shadow-sm">
        @php
            $prevSession = \App\Models\CashierSession::where('user_id', $session->user_id)
                ->where('opened_at', '<', $session->opened_at)
                ->latest('opened_at')
                ->first();
            $nextSession = \App\Models\CashierSession::where('user_id', $session->user_id)
                ->where('opened_at', '>', $session->opened_at)
                ->oldest('opened_at')
                ->first();
        @endphp
        
        <div>
            @if($prevSession)
                <a href="{{ route('admin.cashier.sessions.show', $prevSession->id) }}" class="btn-bank btn-bank-outline !py-1 !px-3 !text-[10px] flex items-center gap-2">
                    <i class="fas fa-chevron-left"></i>
                    <span>Session Précédente (#{{ $prevSession->id }})</span>
                </a>
            @else
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-3 border-l-2 border-slate-200">Origine du Registre</span>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                <i class="fas fa-link text-blue-500"></i>
                Chaîne de Session
            </span>
        </div>

        <div>
            @if($nextSession)
                <a href="{{ route('admin.cashier.sessions.show', $nextSession->id) }}" class="btn-bank btn-bank-outline !py-1 !px-3 !text-[10px] flex items-center gap-2">
                    <span>Session Suivante (#{{ $nextSession->id }})</span>
                    <i class="fas fa-chevron-right"></i>
                </a>
            @else
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-3 border-r-2 border-slate-200">Dernière Période</span>
            @endif
        </div>
    </div>

    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center gap-5">
            <div class="w-14 h-14 bg-white border-2 border-slate-100 rounded-2xl flex items-center justify-center shadow-sm">
                <i class="fas fa-fingerprint text-blue-600 text-2xl"></i>
            </div>
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 text-[9px] font-bold uppercase tracking-widest text-slate-400">
                        <li class="breadcrumb-item"><a href="{{ route('admin.cashier.sessions.index') }}" class="text-blue-600">Registre Central</a></li>
                        <li class="breadcrumb-item active">Audit Session #{{ $session->id }}</li>
                    </ol>
                </nav>
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Session de {{ $session->user->full_name }}</h2>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.cashier.sessions.print', $session->id) }}" class="btn-bank btn-bank-outline !py-2.5" target="_blank">
                <i class="fas fa-print mr-2 text-[10px]"></i>
                <span>Soulignement Officiel</span>
            </a>
            @if($session->status === 'open' && $session->user_id === Auth::id())
                <button type="button" class="btn-bank btn-bank-danger !py-2.5" data-bs-toggle="modal" data-bs-target="#closeSessionModal">
                    <i class="fas fa-lock mr-2 text-[10px]"></i>
                    <span>Clôturer la Période</span>
                </button>
            @endif
        </div>
    </div>

    <!-- Matrice Financière -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bank-card p-5 border-l-4 border-blue-500">
            <span class="kpi-label">Report Initial</span>
            <div class="kpi-value !text-xl mt-1">{{ number_format($session->opening_balance, 0, ',', ' ') }} <small class="text-[10px]">CFA</small></div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">Solde de Report Validé</p>
        </div>
        <div class="bank-card p-5 border-l-4 border-emerald-500">
            <span class="kpi-label">Total Entrées (+)</span>
            <div class="kpi-value !text-xl mt-1 text-emerald-600">{{ number_format($session->total_deposits, 0, ',', ' ') }} <small class="text-[10px]">CFA</small></div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">Volume des Dépôts</p>
        </div>
        <div class="bank-card p-5 border-l-4 border-rose-500">
            <span class="kpi-label">Total Sorties (-)</span>
            <div class="kpi-value !text-xl mt-1 text-rose-600">{{ number_format($session->total_withdrawals, 0, ',', ' ') }} <small class="text-[10px]">CFA</small></div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">Volume des Retraits</p>
        </div>
        <div class="bank-card p-5 border-l-4 border-slate-900 bg-slate-900">
            <span class="kpi-label !text-slate-400">Solde Théorique</span>
            @php $theoretical = $session->opening_balance + $session->total_deposits - $session->total_withdrawals; @endphp
            <div class="kpi-value !text-xl mt-1 text-white">{{ number_format($theoretical, 0, ',', ' ') }} <small class="text-[10px]">CFA</small></div>
            <p class="text-[9px] font-bold text-slate-500 uppercase mt-2">Balance en Temps Réel</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Volet Audit & Logistique -->
        <div class="lg:col-span-4 space-y-6">
            <div class="bank-card p-0 overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-100">
                    <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest flex items-center gap-2">
                        <i class="fas fa-shield-halved text-blue-600"></i> Données du Registre
                    </h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center py-2 border-b border-slate-50">
                            <span class="text-[10px] font-bold text-slate-400 uppercase">Superviseur</span>
                            <span class="text-xs font-bold text-slate-800">{{ $session->user->full_name }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-slate-50">
                            <span class="text-[10px] font-bold text-slate-400 uppercase">Lieu de Service</span>
                            <span class="text-xs font-bold text-slate-600 tracking-tight">{{ $session->agency->name }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-slate-50">
                            <span class="text-[10px] font-bold text-slate-400 uppercase">Ouverture</span>
                            <span class="text-xs font-mono font-bold text-blue-600">{{ $session->opened_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-[10px] font-bold text-slate-400 uppercase">Statut</span>
                            @if($session->status === 'open')
                                <span class="bank-badge badge-success !text-[8px] px-2 py-0.5 animate-pulse">Session Active</span>
                            @else
                                <span class="bank-badge badge-secondary !text-[8px] px-2 py-0.5">Archive Clôturée</span>
                            @endif
                        </div>
                        
                        @if($session->closed_at)
                        <div class="pt-6 mt-2 border-t border-slate-100 space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] font-bold text-slate-400 uppercase">Solde Physique</span>
                                <span class="text-sm font-bold text-slate-900 font-numeric">{{ number_format($session->closing_balance, 0, ',', ' ') }} CFA</span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-slate-50 rounded-lg">
                                <span class="text-[10px] font-bold text-slate-400 uppercase italic">Écart de Caisse</span>
                                @php $diff = $session->closing_balance - $session->expected_closing_balance; @endphp
                                <span class="text-xs font-bold {{ $diff == 0 ? 'text-emerald-600' : ($diff > 0 ? 'text-blue-600' : 'text-rose-600') }}">
                                    {{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 0, ',', ' ') }} CFA
                                </span>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                
                @if($session->notes)
                <div class="px-6 py-5 bg-blue-50/30 border-t border-slate-100">
                    <p class="text-[9px] font-bold text-blue-700 uppercase mb-2 tracking-widest">Notes d'Audit & Observations</p>
                    <p class="text-[11px] font-medium text-slate-600 italic leading-relaxed">"{{ $session->notes }}"</p>
                </div>
                @endif
            </div>
            
            <a href="{{ route('admin.cashier.sessions.print', $session->id) }}" class="btn-bank btn-bank-outline w-full !py-4 shadow-sm" target="_blank">
                <i class="fas fa-file-pdf text-rose-500 mr-2 text-sm"></i>
                <span class="text-xs font-bold font-numeric tracking-widest uppercase">Générer Registre de Soulignement</span>
            </a>
        </div>

        <!-- Journal Chronologique des Flux -->
        <div class="lg:col-span-8 overflow-hidden">
            <div class="bank-card overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest flex items-center gap-2">
                        <i class="fas fa-list-ul text-blue-600"></i> Journal Chronologique des Opérations
                    </h3>
                    <span class="text-[9px] font-bold text-slate-400 bg-white px-2 py-1 rounded border border-slate-200">{{ $session->transactions->count() }} Entrées</span>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="bank-table">
                        <thead>
                            <tr>
                                <th class="!px-6">Timestamp</th>
                                <th>Référence / Tiers</th>
                                <th>Nature du Flux</th>
                                <th class="text-right !px-6">Montant</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50/50">
                            @forelse($session->transactions as $transaction)
                            <tr class="hover:bg-slate-50/30 transition-colors">
                                <td class="!px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-slate-800">{{ $transaction->created_at->format('H:i') }}</span>
                                        <span class="text-[9px] text-slate-400 font-bold uppercase tracking-tighter">{{ $transaction->created_at->format('d/m/Y') }}</span>
                                    </div>
                                </td>
                                <td class="py-4">
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-bold text-blue-600 mb-0.5 tracking-tight font-mono">REF#{{ $transaction->transaction_reference }}</span>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[9px] font-bold text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">{{ $transaction->account->account_number }}</span>
                                            <span class="text-[10px] font-bold text-slate-600 truncate max-w-[140px]">{{ $transaction->account->client->full_name }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4">
                                    @if($transaction->transaction_type === 'deposit')
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-2 h-2 rounded-full bg-emerald-500 shadow-sm shadow-emerald-200"></div>
                                            <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-tighter font-numeric">Encaissement (In)</span>
                                        </div>
                                    @elseif($transaction->transaction_type === 'withdrawal')
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-2 h-2 rounded-full bg-rose-500 shadow-sm shadow-rose-200"></div>
                                            <span class="text-[10px] font-bold text-rose-600 uppercase tracking-tighter font-numeric">Décaissement (Out)</span>
                                        </div>
                                    @else
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">{{ ucfirst($transaction->transaction_type) }}</span>
                                    @endif
                                </td>
                                <td class="!px-6 py-4 text-right">
                                    <span class="text-xs font-bold text-slate-900 font-numeric">{{ number_format($transaction->amount, 0, ',', ' ') }}</span>
                                    <span class="text-[9px] text-slate-400 font-bold ms-1 uppercase">CFA</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="py-24 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 bg-slate-50 border border-slate-100 rounded-full flex items-center justify-center mb-4 text-slate-200 shadow-inner">
                                            <i class="fas fa-ghost text-2xl"></i>
                                        </div>
                                        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-1">Aucun Mouvement Détecté</h4>
                                        <p class="text-[9px] text-slate-300 uppercase font-black tracking-tight italic">Cette période de caisse ne contient aucune transaction validée.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Fermeture Session Standard Bank Style -->
@if($session->status === 'open' && $session->user_id === Auth::id())
<div class="modal fade" id="closeSessionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-2xl border-0 shadow-2xl">
            <form action="{{ route('admin.cashier.sessions.close', $session->id) }}" method="POST">
                @csrf
                <div class="p-8 text-center bg-white rounded-2xl">
                    <div class="w-16 h-16 bg-rose-50 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-6 border border-rose-100 shadow-inner">
                        <i class="fas fa-lock text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 tracking-tight mb-2 uppercase">Clôture Définitive</h3>
                    <p class="text-xs text-slate-500 font-medium mb-6 px-4 leading-relaxed">
                        Vous êtes sur le point de clôturer le registre de la session <span class="text-rose-600 font-bold">#{{ $session->id }}</span>. Cette action est irréversible et certifie les soldes pour la période.
                    </p>
                    
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 flex items-center gap-4 mb-8">
                        <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded flex items-center justify-center shrink-0">
                            <i class="fas fa-calculator text-xs"></i>
                        </div>
                        <div class="text-left">
                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Solde Théorique Final</p>
                            <p class="text-sm font-bold text-slate-700 tracking-tight">{{ number_format($theoretical, 0, ',', ' ') }} FCFA</p>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <button type="button" class="btn-bank btn-bank-outline flex-1 py-3 uppercase text-[10px] font-bold" data-bs-dismiss="modal">Attendre</button>
                        <button type="submit" class="btn-bank btn-bank-danger flex-1 py-3 uppercase text-[10px] font-bold">
                            <i class="fas fa-check-double mr-2"></i> Valider & Fermer
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection
