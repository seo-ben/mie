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

    <!-- Matrice de Synthèse Fiscale -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bank-card p-5">
            <span class="kpi-label">Volume Audité</span>
            <div class="kpi-value !text-xl mt-1">{{ number_format($stats['total_transactions']) }}</div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">Cycles Totaux Traités</p>
        </div>
        <div class="bank-card p-5">
            <span class="kpi-label">Flux de Capitaux</span>
            <div class="kpi-value !text-xl mt-1">{{ number_format($stats['total_amount'], 0, ',', ' ') }} <small class="text-xs">XOF</small></div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">Mouvement Brut de Liquidité</p>
        </div>
        <div class="bank-card p-5">
            <span class="kpi-label">En Attente de Revue</span>
            <div class="kpi-value !text-xl mt-1 text-amber-600">{{ number_format($stats['pending_count']) }}</div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">En Attente de Vérification</p>
        </div>
        <div class="bank-card p-5">
            <span class="kpi-label">Vélocité Journalière</span>
            <div class="kpi-value !text-xl mt-1 text-blue-600">{{ number_format($stats['today_transactions']) }}</div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">Journée d'Activité en Cours</p>
        </div>
    </div>

    <!-- Contrôles d'Audit -->
    <div class="bank-card p-6">
        <form method="GET" action="{{ route('admin.transactions.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <div class="md:col-span-4 relative">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher par Référence, ID Membre ou Nom..." class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:ring-1 focus:ring-blue-500 outline-none transition font-bold text-slate-600">
                <i class="fas fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            </div>

            <div class="md:col-span-2">
                <select name="transaction_type" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold text-slate-600 focus:ring-1 focus:ring-blue-500 outline-none transition uppercase">
                    <option value="">Tous les Vecteurs</option>
                    <option value="deposit" {{ request('transaction_type') === 'deposit' ? 'selected' : '' }}>Injection de Capital</option>
                    <option value="withdrawal" {{ request('transaction_type') === 'withdrawal' ? 'selected' : '' }}>Extraction de Flux</option>
                    <option value="payout" {{ request('transaction_type') === 'payout' ? 'selected' : '' }}>Décaissement Crédit</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <select name="status" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold text-slate-600 focus:ring-1 focus:ring-blue-500 outline-none transition uppercase">
                    <option value="">Tous les Statuts</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Validé</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En Revue</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Signalé/Échoué</option>
                </select>
            </div>

            <div class="md:col-span-4 flex gap-2">
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="flex-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold text-slate-600 focus:ring-1 focus:ring-blue-500 outline-none transition">
                <button type="submit" class="btn-bank btn-bank-primary px-6">
                    <i class="fas fa-search text-[10px]"></i>
                </button>
                <a href="{{ route('admin.transactions.index') }}" class="btn-bank btn-bank-outline px-4">
                    <i class="fas fa-rotate"></i>
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
                                <th>Canal</th>
                                <th>Impact Fiscal</th>
                                <th>Vérification</th>
                                <th class="text-right">Audit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($transactions as $transaction)
                            <tr class="hover:bg-slate-50/50 transition-colors cursor-pointer text-slate-600" onclick="loadTransactionDetails({{ $transaction->id }})" id="tr-{{ $transaction->id }}">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded bg-slate-100 flex items-center justify-center text-slate-500 border border-slate-200">
                                            @if(in_array($transaction->transaction_type, ['deposit', 'tontine_contribution']))
                                                <i class="fas fa-arrow-down text-[10px] text-emerald-600"></i>
                                            @else
                                                <i class="fas fa-arrow-up text-[10px] text-rose-600"></i>
                                            @endif
                                        </div>
                                        <div>
                                            {{-- FIX: null-safe operators pour éviter l'erreur si account ou client est null --}}
                                            <p class="text-xs font-bold text-slate-800">{{ $transaction->account?->client?->full_name ?? 'Membre inconnu' }}</p>
                                            <p class="text-[9px] font-mono font-bold text-slate-400 mt-0.5 uppercase tracking-tighter">{{ $transaction->transaction_reference }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-bold text-slate-600 uppercase">{{ str_replace('_', ' ', $transaction->transaction_type) }}</span>
                                        <span class="text-[9px] text-slate-400 font-medium">{{ $transaction->transaction_date->format('d M Y, H:i') }}</span>
                                    </div>
                                </td>
                                <td>
                                    <p class="text-xs font-bold font-numeric {{ in_array($transaction->transaction_type, ['deposit', 'tontine_contribution']) ? 'text-emerald-600' : 'text-rose-600' }}">
                                        {{ in_array($transaction->transaction_type, ['deposit', 'tontine_contribution']) ? '+' : '-' }} {{ number_format($transaction->amount, 0, ',', ' ') }}
                                    </p>
                                </td>
                                <td>
                                    @switch($transaction->status)
                                        @case('completed')
                                            <span class="bank-badge badge-success !text-[8px]">Vérifié</span>
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
                                    <button class="w-7 h-7 flex items-center justify-center rounded bg-slate-100 text-slate-400 hover:text-blue-600 transition">
                                        <i class="fas fa-ellipsis-v text-[10px]"></i>
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