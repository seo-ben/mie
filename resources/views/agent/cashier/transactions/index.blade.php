@extends('layouts.cashier')

@section('title', 'Journal des Transactions')
@section('page-title', 'Journal des Flux Guichet')
@section('page-subtitle', 'Audit exhaustif des opérations, reçus et annulations')

@section('content')
<div class="container-fluid px-0">
    <!-- Filtres -->
    <div class="card border border-slate-200 shadow-sm rounded-xl bg-white mb-4">
        <div class="card-header bg-slate-50 border-bottom border-slate-100 p-3 px-4">
            <h6 class="mb-0 text-slate-800 font-bold"><i class="fas fa-filter me-2 text-emerald-600"></i>Critères de Recherche</h6>
        </div>
        <div class="card-body p-4">
            <form method="GET" action="{{ route('caissier.transactions.index') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label text-slate-600 font-bold small mb-1">Date Début</label>
                        <input type="date" class="form-control rounded-lg border-slate-300" name="date_start" value="{{ request('date_start') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-slate-600 font-bold small mb-1">Date Fin</label>
                        <input type="date" class="form-control rounded-lg border-slate-300" name="date_end" value="{{ request('date_end') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-slate-600 font-bold small mb-1">Type d'opération</label>
                        <select class="form-select rounded-lg border-slate-300" name="type">
                            <option value="">Tous les flux</option>
                            <option value="deposit" {{ request('type') == 'deposit' ? 'selected' : '' }}>Dépôts / Encaissements</option>
                            <option value="withdrawal" {{ request('type') == 'withdrawal' ? 'selected' : '' }}>Retraits / Décaissements</option>
                            <option value="transfer" {{ request('type') == 'transfer' ? 'selected' : '' }}>Virements Internes</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-slate-600 font-bold small mb-1">Recherche libre</label>
                        <div class="d-flex gap-2">
                            <input type="text" class="form-control rounded-lg border-slate-300" name="search" placeholder="N° Réf, Compte, Client..." value="{{ request('search') }}">
                            <button type="submit" class="btn btn-slate-900 text-white px-3 rounded-lg font-bold shadow-sm" style="background:#0f172a;">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tableau -->
    <div class="card border border-slate-200 shadow-sm rounded-xl bg-white overflow-hidden">
        <div class="card-header bg-white border-bottom border-slate-100 p-4 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 text-slate-800 font-bold">
                <i class="fas fa-receipt me-2 text-emerald-600"></i>Transactions Récentes ({{ $transactions->total() }})
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-slate-50 text-slate-500 text-xs font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-4">Horodatage</th>
                            <th class="py-3 px-4">Référence</th>
                            <th class="py-3 px-4">Compte & Client</th>
                            <th class="py-3 px-4">Type</th>
                            <th class="py-3 px-4 text-end">Montant</th>
                            <th class="py-3 px-4">Agent</th>
                            <th class="py-3 px-4 text-center">Statut</th>
                            <th class="py-3 px-4 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($transactions as $transaction)
                        <tr>
                            <td class="px-4 py-3 text-xs text-slate-600">
                                <div class="font-bold text-slate-800">{{ $transaction->created_at->format('d/m/Y') }}</div>
                                <div class="text-slate-400">{{ $transaction->created_at->format('H:i:s') }}</div>
                            </td>
                            <td class="px-4 py-3 font-mono font-bold text-slate-900 text-xs">
                                {{ $transaction->transaction_reference ?? $transaction->transaction_number }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-mono font-bold text-emerald-700 text-xs">{{ $transaction->account->account_number ?? 'N/A' }}</div>
                                <div class="text-xs text-slate-600 font-semibold">
                                    {{ $transaction->account->client->first_name ?? '' }} {{ $transaction->account->client->last_name ?? '' }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @if(in_array($transaction->transaction_type, ['deposit', 'savings_deposit', 'tontine_deposit']))
                                    <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fas fa-arrow-down me-1"></i> Dépôt
                                    </span>
                                @elseif(in_array($transaction->transaction_type, ['withdrawal', 'payout']))
                                    <span class="badge bg-rose-50 text-rose-700 border border-rose-200">
                                        <i class="fas fa-arrow-up me-1"></i> Retrait
                                    </span>
                                @elseif($transaction->transaction_type == 'transfer')
                                    <span class="badge bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        <i class="fas fa-exchange-alt me-1"></i> Virement
                                    </span>
                                @elseif($transaction->transaction_type == 'fee')
                                    <span class="badge bg-amber-50 text-amber-700 border border-amber-200">
                                        <i class="fas fa-tag me-1"></i> Frais
                                    </span>
                                @else
                                    <span class="badge bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ $transaction->transaction_type }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-end font-mono font-bold text-sm">
                                @if(in_array($transaction->transaction_type, ['withdrawal', 'payout', 'fee']))
                                    <span class="text-rose-600">- {{ number_format($transaction->amount, 0, ',', ' ') }} F</span>
                                @else
                                    <span class="text-emerald-700">+ {{ number_format($transaction->amount, 0, ',', ' ') }} F</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600">
                                {{ $transaction->processedBy->first_name ?? 'Système' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($transaction->status == 'completed')
                                    <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200">Succès</span>
                                @elseif($transaction->status == 'reversed' || $transaction->status == 'cancelled')
                                    <span class="badge bg-rose-50 text-rose-700 border border-rose-200">Annulé</span>
                                @else
                                    <span class="badge bg-amber-50 text-amber-700 border border-amber-200">{{ $transaction->status }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('caissier.receipt.print', $transaction->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary rounded-lg" title="Imprimer reçu">
                                        <i class="fas fa-print"></i>
                                    </a>
                                    @if($transaction->status == 'completed' && $transaction->created_at->isToday())
                                        <button class="btn btn-sm btn-outline-danger rounded-lg" title="Annuler (Superviseur)" onclick="openReversalModal({{ $transaction->id }}, '{{ $transaction->transaction_reference ?? $transaction->transaction_number }}', {{ $transaction->amount }})">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-slate-400">
                                <i class="fas fa-search-dollar fa-3x mb-3 text-slate-300"></i>
                                <h6 class="text-slate-600 font-bold">Aucune transaction trouvée</h6>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($transactions instanceof \Illuminate\Pagination\LengthAwarePaginator && $transactions->hasPages())
                <div class="p-3 border-top border-slate-100 bg-slate-50 d-flex justify-content-end">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection