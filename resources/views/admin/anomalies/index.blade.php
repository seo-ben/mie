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
    @php
        $totalExcess = collect($anomalies)->sum('excess_amount');
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bank-card p-4 border-l-4 border-amber-500 bg-amber-50/10">
            <div class="flex items-center justify-between">
                <span class="kpi-label text-amber-800 text-[10px]">Doublons Rapprochés</span>
                <span class="w-7 h-7 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center text-xs">
                    <i class="fas fa-clone"></i>
                </span>
            </div>
            <div class="text-xl font-black text-amber-900 mt-1 font-numeric">
                {{ count($anomalies) }} <small class="text-xs font-semibold text-slate-500">cas détectés</small>
            </div>
            <p class="text-[9px] font-medium text-slate-400 mt-1">Même compte & montant le même jour</p>
        </div>

        <div class="bank-card p-4 border-l-4 border-rose-500 bg-rose-50/10">
            <div class="flex items-center justify-between">
                <span class="kpi-label text-rose-800 text-[10px]">Volume Excédentaire</span>
                <span class="w-7 h-7 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center text-xs">
                    <i class="fas fa-money-bill-transfer"></i>
                </span>
            </div>
            <div class="text-xl font-black text-rose-600 mt-1 font-numeric">
                +{{ number_format($totalExcess, 0, ',', ' ') }} <small class="text-xs font-bold text-rose-400">XOF</small>
            </div>
            <p class="text-[9px] font-medium text-slate-400 mt-1">Cumul des écritures en trop</p>
        </div>

        <div class="bank-card p-4 border-l-4 border-emerald-500 bg-emerald-50/10">
            <div class="flex items-center justify-between">
                <span class="kpi-label text-emerald-800 text-[10px]">Sécurité Anti-Doublon</span>
                <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center text-xs">
                    <i class="fas fa-shield-check"></i>
                </span>
            </div>
            <div class="text-xl font-black text-emerald-700 mt-1">
                Active <small class="text-xs font-semibold text-slate-500">(30 min)</small>
            </div>
            <p class="text-[9px] font-medium text-slate-400 mt-1">Idempotence mobile & serveur renforcée</p>
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
            <span>Doublons Détectés</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-black {{ count($anomalies) > 0 ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-500' }}">
                {{ count($anomalies) }}
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

    <!-- ONGLET 1 : DOUBLONS DÉTECTÉS (TABLEAU COMPACT) -->
    <div id="tab-content-duplicates" class="{{ $activeTab === 'duplicates' ? 'block' : 'hidden' }}">
        <div class="bank-card overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-2">
                    <span class="text-[11px] font-bold text-slate-700 uppercase tracking-wider">Écritures suspectes identifiées</span>
                    <span class="text-[10px] text-slate-400">&bull; Confirmation requise avant toute suppression</span>
                </div>
                <div class="text-[11px] font-medium text-slate-400">
                    {{ count($anomalies) }} ligne(s)
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[10px] font-black uppercase text-slate-400 tracking-wider">
                            <th class="py-2.5 px-4">Titulaire</th>
                            <th class="py-2.5 px-4">Compte</th>
                            <th class="py-2.5 px-4">Date & Passages</th>
                            <th class="py-2.5 px-4">Montant Répété</th>
                            <th class="py-2.5 px-4">Répétitions</th>
                            <th class="py-2.5 px-4">Excédent Net</th>
                            <th class="py-2.5 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($anomalies as $index => $item)
                            <tr class="hover:bg-slate-50/80 transition">
                                <!-- Titulaire -->
                                <td class="py-2.5 px-4">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-[10px] flex-shrink-0">
                                            {{ strtoupper(substr($item['account']->client->first_name ?? 'C', 0, 1)) }}
                                        </div>
                                        <span class="font-bold text-slate-800 text-xs">
                                            {{ $item['account']->client->first_name }} {{ $item['account']->client->last_name }}
                                        </span>
                                    </div>
                                </td>

                                <!-- N° Compte -->
                                <td class="py-2.5 px-4">
                                    <span class="font-mono text-[11px] text-blue-600 font-bold">
                                        {{ $item['account']->account_number }}
                                    </span>
                                </td>

                                <!-- Date & Heures -->
                                <td class="py-2.5 px-4">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-slate-600 font-medium text-xs">
                                            {{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y') }}
                                        </span>
                                        <span class="text-slate-300">&bull;</span>
                                        <div class="flex items-center gap-1 flex-wrap">
                                            @foreach($item['transactions'] as $tx)
                                                <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-500 text-[9px] font-mono">
                                                    {{ \Carbon\Carbon::parse($tx->created_at)->format('H:i') }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                </td>

                                <!-- Montant Répété -->
                                <td class="py-2.5 px-4 font-bold text-slate-800">
                                    {{ number_format($item['amount'], 0, ',', ' ') }} <small class="text-[9px] text-slate-400">XOF</small>
                                </td>

                                <!-- Répétitions -->
                                <td class="py-2.5 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold">
                                        {{ $item['total_count'] }} fois
                                    </span>
                                </td>

                                <!-- Excédent Net -->
                                <td class="py-2.5 px-4">
                                    <span class="font-black text-rose-600 text-xs">
                                        +{{ number_format($item['excess_amount'], 0, ',', ' ') }} XOF
                                    </span>
                                    <span class="text-[9px] text-slate-400 ml-1">({{ $item['excess_count'] }} en trop)</span>
                                </td>

                                <!-- Action Résolution -->
                                <td class="py-2.5 px-4 text-right">
                                    <form method="POST" action="{{ route('admin.anomalies.resolve-duplicates') }}" class="inline"
                                          onsubmit="return confirm('Confirmer la suppression des {{ $item['excess_count'] }} doublon(s) (déduction de {{ number_format($item['excess_amount'], 0, ',', ' ') }} XOF) pour ce compte ?');">
                                        @csrf
                                        <input type="hidden" name="account_id" value="{{ $item['account']->id }}">
                                        <input type="hidden" name="amount" value="{{ $item['amount'] }}">
                                        <input type="hidden" name="date" value="{{ $item['date'] }}">
                                        <input type="hidden" name="transaction_ids" value="{{ $item['transactions']->pluck('id')->implode(',') }}">

                                        <button type="submit"
                                                class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-md text-[10px] font-bold transition inline-flex items-center gap-1 shadow-sm cursor-pointer">
                                            <i class="fas fa-trash-can text-[9px]"></i>
                                            <span>Corriger (-{{ number_format($item['excess_amount'], 0, ',', ' ') }})</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center">
                                    <i class="fas fa-check-circle text-2xl text-emerald-500 mb-2 block"></i>
                                    <p class="text-xs font-bold text-slate-700">Aucun doublon suspect détecté</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5">Les écritures de dépôts sont cohérentes.</p>
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
                        <form method="POST" action="{{ route('admin.anomalies.regularize-balance') }}" id="form-regularize-{{ $searchedAccount->id }}" class="space-y-3"
                              onsubmit="return confirm('Confirmer la régularisation de ce compte au montant saisi ?');">
                            @csrf
                            <input type="hidden" name="account_id" value="{{ $searchedAccount->id }}">

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Montant Réel Cotisé (XOF) *</label>
                                    <input type="number" step="100" min="0" name="target_amount" required placeholder="Ex: 55000, 44700..."
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
</script>
@endpush
@endsection
