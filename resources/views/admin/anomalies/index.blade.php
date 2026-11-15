@extends('layouts.app_admin')

@section('title', 'Audit & Régularisation des Flux - FSD YAYRA')
@section('page-title', 'Audit & Régularisation des Flux')

@section('content')
<div class="space-y-6">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900 tracking-tight">Audit des Flux & Régularisation</h2>
            <p class="text-xs text-slate-500 font-medium">Contrôle des anomalies de synchronisation et réalignement des comptes de tontine</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.transactions.index') }}" class="btn-bank btn-bank-outline !py-2 !px-3 text-xs">
                <i class="fas fa-arrow-left mr-1.5 text-[10px]"></i> Grand Livre
            </a>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Surveillance Active
            </span>
        </div>
    </div>

    <!-- KPIs Compacts (Style Grand Livre Financier) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bank-card p-4 border-l-4 border-amber-500 bg-amber-50/10">
            <div class="flex items-center justify-between">
                <span class="kpi-label text-amber-800 text-[10px]">Comptes Affectés</span>
                <span class="w-7 h-7 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center text-xs">
                    <i class="fas fa-users-viewfinder"></i>
                </span>
            </div>
            <div class="text-xl font-black text-amber-900 mt-1 font-numeric">
                {{ count($accountsWithAnomalies) }} <small class="text-xs font-semibold text-slate-500">compte(s) avec doublons</small>
            </div>
            <p class="text-[9px] font-medium text-slate-400 mt-1">{{ $totalDuplicateTxsCount ?? 0 }} écritures excédentaires détectées</p>
        </div>

        <div class="bank-card p-4 border-l-4 border-rose-500 bg-rose-50/10">
            <div class="flex items-center justify-between">
                <span class="kpi-label text-rose-800 text-[10px]">Volume Excédentaire Global</span>
                <span class="w-7 h-7 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center text-xs">
                    <i class="fas fa-money-bill-transfer"></i>
                </span>
            </div>
            <div class="text-xl font-black text-rose-600 mt-1 font-numeric">
                +{{ number_format($totalExcessVolume ?? 0, 0, ',', ' ') }} <small class="text-xs font-bold text-rose-400">XOF</small>
            </div>
            <p class="text-[9px] font-medium text-slate-400 mt-1">Cumul total des écritures en trop sur le réseau</p>
        </div>

        <div class="bank-card p-4 border-l-4 border-emerald-500 bg-emerald-50/10">
            <div class="flex items-center justify-between">
                <span class="kpi-label text-emerald-800 text-[10px]">Sécurité Anti-Doublon</span>
                <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center text-xs">
                    <i class="fas fa-shield-check"></i>
                </span>
            </div>
            <div class="text-xl font-black text-emerald-700 mt-1">
                Active <small class="text-xs font-semibold text-slate-500">(Idempotence renforcée)</small>
            </div>
            <p class="text-[9px] font-medium text-slate-400 mt-1">Empêche les réinjections futures lors des syncs</p>
        </div>
    </div>

    <!-- Navigation par Onglets -->
    @php
        $activeTab = !empty($searchQuery) ? 'manual' : 'duplicates';
    @endphp
    <div class="flex items-center gap-2 border-b border-slate-200">
        <button type="button" onclick="switchTab('duplicates')" id="tab-btn-duplicates"
                class="tab-btn px-4 py-2.5 text-xs font-bold border-b-2 transition flex items-center gap-2 {{ $activeTab === 'duplicates' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400 hover:text-slate-600' }}">
            <i class="fas fa-triangle-exclamation text-[11px]"></i>
            <span>Comptes avec Doublons</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-black {{ count($accountsWithAnomalies) > 0 ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-500' }}">
                {{ count($accountsWithAnomalies) }}
            </span>
        </button>

        <button type="button" onclick="switchTab('manual')" id="tab-btn-manual"
                class="tab-btn px-4 py-2.5 text-xs font-bold border-b-2 transition flex items-center gap-2 {{ $activeTab === 'manual' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400 hover:text-slate-600' }}">
            <i class="fas fa-sliders text-[11px]"></i>
            <span>Régulariser un Compte</span>
            @if($searchedAccount)
                <span class="w-2 h-2 rounded-full bg-blue-600"></span>
            @endif
        </button>
    </div>

    <!-- ONGLET 1 : COMPTES AVEC DOUBLONS (GROUPÉS PAR CLIENT) -->
    <div id="tab-content-duplicates" class="{{ $activeTab === 'duplicates' ? 'block' : 'hidden' }}">
        <div class="bank-card overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-2">
                    <span class="text-[11px] font-bold text-slate-700 uppercase tracking-wider">Comptes identifiés avec écritures en doublon</span>
                    <span class="text-[10px] text-slate-400">&bull; Nettoyage complet du compte en 1 clic</span>
                </div>
                <div class="text-[11px] font-medium text-slate-400">
                    {{ count($accountsWithAnomalies) }} compte(s) &bull; {{ $totalDuplicateTxsCount ?? 0 }} doublon(s)
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[10px] font-black uppercase text-slate-400 tracking-wider">
                            <th class="py-2.5 px-4">Titulaire</th>
                            <th class="py-2.5 px-4">Compte & Solde</th>
                            <th class="py-2.5 px-4">Détail des Doublons Détectés</th>
                            <th class="py-2.5 px-4 text-center">Écritures en trop</th>
                            <th class="py-2.5 px-4">Excédent Net</th>
                            <th class="py-2.5 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($accountsWithAnomalies as $accountId => $item)
                            <tr class="hover:bg-slate-50/80 transition">
                                <!-- Titulaire -->
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center font-bold text-xs flex-shrink-0">
                                            {{ strtoupper(substr($item['account']->client->first_name ?? 'C', 0, 1)) }}
                                        </div>
                                        <div>
                                            <a href="{{ route('admin.accounts.show', $item['account']->id) }}" class="font-bold text-slate-800 text-xs hover:text-blue-600 transition block">
                                                {{ $item['account']->client->first_name }} {{ $item['account']->client->last_name }}
                                            </a>
                                            <span class="text-[10px] text-slate-400 font-mono">{{ $item['account']->client->phone ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- N° Compte & Solde -->
                                <td class="py-3 px-4">
                                    <a href="{{ route('admin.accounts.show', $item['account']->id) }}" class="font-mono text-xs text-blue-600 font-bold hover:underline block">
                                        {{ $item['account']->account_number }}
                                    </a>
                                    <span class="text-[10px] text-slate-400">Solde: <strong class="text-slate-700 font-numeric">{{ number_format($item['account']->balance, 0, ',', ' ') }} XOF</strong></span>
                                </td>

                                <!-- Détail des Doublons (Montants & Répétitions) -->
                                <td class="py-3 px-4">
                                    <div class="flex flex-col gap-1.5">
                                        @foreach($item['clusters'] as $cluster)
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-50 text-amber-800 border border-amber-200 text-[10px] font-bold">
                                                    <i class="fas fa-repeat text-[8px] text-amber-500"></i>
                                                    {{ $cluster['total_count'] }}x {{ number_format($cluster['amount'], 0, ',', ' ') }} XOF
                                                </span>
                                                <span class="text-[9px] text-rose-600 font-bold">
                                                    ({{ $cluster['excess_count'] }} en trop = -{{ number_format($cluster['excess_amount'], 0, ',', ' ') }})
                                                </span>
                                                <div class="flex items-center gap-1">
                                                    @foreach($cluster['transactions'] as $tx)
                                                        <span class="px-1 py-0.2 rounded bg-slate-100 text-slate-500 text-[8px] font-mono">
                                                            {{ \Carbon\Carbon::parse($tx->created_at)->format('H:i') }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>

                                <!-- Total Doublons Compte -->
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 text-[10px] font-black">
                                        {{ $item['total_excess_count'] }} en trop
                                    </span>
                                </td>

                                <!-- Excédent Net Total -->
                                <td class="py-3 px-4">
                                    <span class="font-black text-rose-600 text-xs font-numeric block">
                                        +{{ number_format($item['total_excess_amount'], 0, ',', ' ') }} XOF
                                    </span>
                                    <span class="text-[9px] text-slate-400">À déduire du solde</span>
                                </td>

                                <!-- Action Résolution Complète -->
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <form method="POST" action="{{ route('admin.anomalies.resolve-account-duplicates') }}" class="inline form-resolve-account"
                                              data-client="{{ $item['account']->client->first_name }} {{ $item['account']->client->last_name }}"
                                              data-account="{{ $item['account']->account_number }}"
                                              data-count="{{ $item['total_excess_count'] }}"
                                              data-amount="{{ number_format($item['total_excess_amount'], 0, ',', ' ') }}">
                                            @csrf
                                            <input type="hidden" name="account_id" value="{{ $item['account']->id }}">
                                            <input type="hidden" name="transaction_ids" value="{{ implode(',', $item['all_duplicate_ids']) }}">

                                            <button type="button"
                                                    class="btn-trigger-clean-account px-2.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-[10px] font-bold uppercase transition inline-flex items-center gap-1 shadow-sm shadow-rose-600/20 cursor-pointer"
                                                    title="Supprimer tous les doublons de ce compte">
                                                <i class="fas fa-trash-can text-[9px]"></i>
                                                <span>Nettoyer (-{{ number_format($item['total_excess_amount'], 0, ',', ' ') }})</span>
                                            </button>
                                        </form>

                                        <a href="{{ route('admin.accounts.show', $item['account']->id) }}"
                                           class="w-7 h-7 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg flex items-center justify-center transition"
                                           title="Voir le compte">
                                            <i class="fas fa-eye text-[10px]"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center">
                                    <i class="fas fa-check-circle text-2xl text-emerald-500 mb-2 block"></i>
                                    <p class="text-xs font-bold text-slate-700">Aucun compte avec doublons suspect détecté</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5">Toutes les écritures sont cohérentes sur l'ensemble des comptes.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ONGLET 2 : RÉGULARISATION MANUELLE DE COMPTE -->
    <div id="tab-content-manual" class="{{ $activeTab === 'manual' ? 'block' : 'hidden' }} space-y-4">
        <!-- Barre de recherche sobre -->
        <div class="bank-card p-4">
            <form method="GET" action="{{ route('admin.anomalies.index') }}" class="flex items-center gap-2">
                <div class="flex-1 relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" name="q" value="{{ $searchQuery }}" placeholder="Rechercher par nom (ex: Dossouklo, Gbeblewou), téléphone ou N° de compte..."
                           class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium focus:outline-none focus:border-blue-500 focus:bg-white transition">
                </div>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                    <i class="fas fa-magnifying-glass text-[10px]"></i> Trouver
                </button>
                @if(!empty($searchQuery))
                    <a href="{{ route('admin.anomalies.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs font-medium transition">
                        Effacer
                    </a>
                @endif
            </form>
        </div>

        <!-- Fiche Compte & Formulaire de Régularisation -->
        @if($searchedAccount)
            @if(isset($accountsWithAnomalies[$searchedAccount->id]))
                @php
                    $searchAnom = $accountsWithAnomalies[$searchedAccount->id];
                @endphp
                <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-triangle-exclamation"></i>
                        </span>
                        <div>
                            <h4 class="text-xs font-black text-rose-900 uppercase tracking-tight">{{ $searchAnom['total_excess_count'] }} doublon(s) de synchronisation détecté(s)</h4>
                            <p class="text-[11px] text-rose-700">Volume excédentaire : <strong class="font-bold font-numeric text-rose-900">+{{ number_format($searchAnom['total_excess_amount'], 0, ',', ' ') }} XOF</strong></p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.anomalies.resolve-account-duplicates') }}" class="inline"
                          data-client="{{ $searchedAccount->client->first_name }} {{ $searchedAccount->client->last_name }}"
                          data-account="{{ $searchedAccount->account_number }}"
                          data-count="{{ $searchAnom['total_excess_count'] }}"
                          data-amount="{{ number_format($searchAnom['total_excess_amount'], 0, ',', ' ') }}">
                        @csrf
                        <input type="hidden" name="account_id" value="{{ $searchedAccount->id }}">
                        <input type="hidden" name="transaction_ids" value="{{ implode(',', $searchAnom['all_duplicate_ids']) }}">
                        <button type="button" class="btn-trigger-clean-account px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-bold uppercase transition flex items-center gap-1.5 shadow-sm cursor-pointer">
                            <i class="fas fa-trash-can text-[10px]"></i> Nettoyer Tous les Doublons (-{{ number_format($searchAnom['total_excess_amount'], 0, ',', ' ') }})
                        </button>
                    </form>
                </div>
            @endif

            <div class="bank-card p-5 border border-blue-200 bg-gradient-to-br from-white to-blue-50/20">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
                    <!-- Résumé du Compte (5 colonnes) -->
                    <div class="md:col-span-5 space-y-2 border-b md:border-b-0 md:border-r border-slate-200 pb-4 md:pb-0 md:pr-6">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold text-xs">
                                {{ strtoupper(substr($searchedAccount->client->first_name ?? 'C', 0, 1)) }}
                            </div>
                            <div>
                                <h4 class="text-xs font-black text-slate-900 leading-tight">
                                    {{ $searchedAccount->client->first_name }} {{ $searchedAccount->client->last_name }}
                                </h4>
                                <span class="font-mono text-[10px] text-blue-600 font-bold">{{ $searchedAccount->account_number }}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs pt-2">
                            <div class="p-2 bg-slate-50 rounded-lg border border-slate-100">
                                <span class="text-[9px] text-slate-400 font-bold uppercase block">Solde Actuel</span>
                                <span class="text-xs font-black text-slate-800 font-numeric">{{ number_format($searchedAccount->balance, 0, ',', ' ') }} XOF</span>
                            </div>
                            @if($searchedAccount->tontineAccount)
                                <div class="p-2 bg-purple-50/50 rounded-lg border border-purple-100">
                                    <span class="text-[9px] text-purple-700 font-bold uppercase block">Mise Journalière</span>
                                    <span class="text-xs font-black text-purple-900 font-numeric">{{ number_format($searchedAccount->tontineAccount->tontine_amount, 0, ',', ' ') }} XOF / j</span>
                                </div>
                                <div class="p-2 bg-slate-50 rounded-lg border border-slate-100">
                                    <span class="text-[9px] text-slate-400 font-bold uppercase block">Total Cotisé</span>
                                    <span class="text-xs font-black text-slate-800 font-numeric">{{ number_format($searchedAccount->tontineAccount->total_paid, 0, ',', ' ') }} XOF</span>
                                </div>
                                <div class="p-2 bg-slate-50 rounded-lg border border-slate-100">
                                    <span class="text-[9px] text-slate-400 font-bold uppercase block">Cycle Actuel</span>
                                    <span class="text-xs font-bold text-slate-700">Cycle n° {{ $searchedAccount->tontineAccount->current_cycle }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Formulaire de Saisie du Montant Réel (7 colonnes) -->
                    <div class="md:col-span-7">
                        <form method="POST" action="{{ route('admin.anomalies.regularize-balance') }}" id="form-regularize-account" class="space-y-3"
                              data-client="{{ $searchedAccount->client->first_name }} {{ $searchedAccount->client->last_name }}"
                              data-account="{{ $searchedAccount->account_number }}">
                            @csrf
                            <input type="hidden" name="account_id" value="{{ $searchedAccount->id }}">

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Montant Réel Cotisé (XOF) *</label>
                                    <input type="number" step="100" min="0" name="target_amount" id="regularize-target-amount" required placeholder="Ex: 55000, 44700..."
                                           class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-black text-slate-900 focus:outline-none focus:border-emerald-500">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Motif de Régularisation</label>
                                    <input type="text" name="reason" value="Régularisation des doublons de synchronisation"
                                           class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-medium text-slate-700 focus:outline-none focus:border-emerald-500">
                                </div>
                            </div>

                            <button type="submit"
                                    class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold uppercase transition flex items-center justify-center gap-1.5 shadow-sm cursor-pointer">
                                <i class="fas fa-check-double text-[10px]"></i> Valider & Réaligner la Tontine
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @elseif(!empty($searchQuery))
            <div class="bank-card p-8 text-center text-slate-400">
                <i class="fas fa-user-slash text-xl mb-1 block"></i>
                <p class="text-xs font-bold">Aucun compte trouvé pour "{{ $searchQuery }}"</p>
            </div>
        @else
            <div class="bank-card p-8 text-center text-slate-400">
                <i class="fas fa-search text-xl mb-1 block text-slate-300"></i>
                <p class="text-xs font-medium">Saisissez un nom de client ou un numéro de compte ci-dessus pour le régulariser.</p>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    function switchTab(tabName) {
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('border-blue-600', 'text-blue-600');
            btn.classList.add('border-transparent', 'text-slate-400');
        });

        const activeBtn = document.getElementById('tab-btn-' + tabName);
        if (activeBtn) {
            activeBtn.classList.add('border-blue-600', 'text-blue-600');
            activeBtn.classList.remove('border-transparent', 'text-slate-400');
        }

        const dupContent = document.getElementById('tab-content-duplicates');
        const manualContent = document.getElementById('tab-content-manual');
        if (dupContent) dupContent.classList.add('hidden');
        if (manualContent) manualContent.classList.add('hidden');

        const targetContent = document.getElementById('tab-content-' + tabName);
        if (targetContent) targetContent.classList.remove('hidden');
    }

    // Modal de confirmation SweetAlert2 pour la suppression complète des doublons d'un compte
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-trigger-clean-account, .btn-trigger-resolve');
        if (!btn) return;

        e.preventDefault();
        e.stopPropagation();

        const form = btn.closest('form');
        if (!form) return;

        const client = form.dataset.client || 'Client';
        const account = form.dataset.account || '';
        const count = form.dataset.count || '1';
        const amount = form.dataset.amount || '0';

        if (typeof Swal === 'undefined') {
            if (confirm(`Confirmer la suppression des ${count} doublon(s) pour ${client} (-${amount} XOF) ?`)) {
                form.submit();
            }
            return;
        }

        Swal.fire({
            title: 'Assainir ce Compte',
            html: `
                <div class="text-left text-xs text-slate-600 space-y-3 mt-3">
                    <p class="leading-relaxed">Vous confirmez la purge complète de <strong>${count} écriture(s) en doublon</strong> pour ce client :</p>
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <div class="font-bold text-slate-900 text-sm">${client}</div>
                        <div class="text-xs text-blue-600 font-mono font-bold mt-0.5">${account}</div>
                    </div>
                    <div class="p-3 bg-rose-50 rounded-xl border border-rose-200 flex items-center justify-between">
                        <span class="text-xs font-semibold text-rose-800">Déduction totale appliquée :</span>
                        <span class="text-sm font-black text-rose-600 font-mono">-${amount} XOF</span>
                    </div>
                    <p class="text-[11px] text-slate-400 italic">Tous les doublons de synchronisation de ce compte seront supprimés, et le carnet de tontine sera recalculé.</p>
                </div>
            `,
            icon: 'warning',
            iconColor: '#e11d48',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fas fa-trash-can mr-1.5"></i> Oui, Tout Assainir',
            cancelButtonText: 'Annuler',
            reverseButtons: true,
            focusCancel: true,
            customClass: {
                popup: 'rounded-2xl shadow-2xl border-0 p-6',
                title: 'text-base font-bold text-slate-900',
                confirmButton: 'rounded-xl text-xs font-bold px-4 py-2.5 shadow-sm',
                cancelButton: 'rounded-xl text-xs font-bold px-4 py-2.5'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    // Modal de confirmation SweetAlert2 pour la régularisation manuelle
    const regForm = document.getElementById('form-regularize-account');
    if (regForm) {
        regForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const client = this.dataset.client || 'Client';
            const account = this.dataset.account || '';
            const targetInput = document.getElementById('regularize-target-amount');
            const targetAmount = targetInput ? targetInput.value : '';

            if (!targetAmount) return;

            const formattedTarget = Number(targetAmount).toLocaleString('fr-FR');

            if (typeof Swal === 'undefined') {
                if (confirm(`Confirmer la régularisation de ${client} au montant de ${formattedTarget} XOF ?`)) {
                    regForm.submit();
                }
                return;
            }

            Swal.fire({
                title: 'Confirmer la Régularisation',
                html: `
                    <div class="text-left text-xs text-slate-600 space-y-3 mt-3">
                        <p class="leading-relaxed">Vous allez réaligner manuellement le compte :</p>
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                            <div class="font-bold text-slate-900 text-sm">${client}</div>
                            <div class="text-xs text-blue-600 font-mono font-bold mt-0.5">${account}</div>
                        </div>
                        <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200 flex items-center justify-between">
                            <span class="text-xs font-semibold text-emerald-800">Nouveau Total Cotisé :</span>
                            <span class="text-sm font-black text-emerald-700 font-mono">${formattedTarget} XOF</span>
                        </div>
                        <p class="text-[11px] text-slate-400 italic">Le solde et les 31 cases de tontine seront mis à jour avec ce montant exact.</p>
                    </div>
                `,
                icon: 'question',
                iconColor: '#059669',
                showCancelButton: true,
                confirmButtonColor: '#059669',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="fas fa-check-double mr-1.5"></i> Valider & Réaligner',
                cancelButtonText: 'Annuler',
                reverseButtons: true,
                focusCancel: true,
                customClass: {
                    popup: 'rounded-2xl shadow-2xl border-0 p-6',
                    title: 'text-base font-bold text-slate-900',
                    confirmButton: 'rounded-xl text-xs font-bold px-4 py-2.5 shadow-sm',
                    cancelButton: 'rounded-xl text-xs font-bold px-4 py-2.5'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    regForm.submit();
                }
            });
        });
    }
</script>
@endpush
@endsection
