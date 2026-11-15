@extends('layouts.app_admin')

@section('title', 'Audit & Contrôle des Doublons - FSD YAYRA')
@section('page-title', 'Contrôle des Flux & Régularisation')

@section('content')
<div class="space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center border border-rose-100 shadow-sm">
                    <i class="fas fa-shield-virus text-base"></i>
                </div>
                Centre de Contrôle & Résolution des Doublons
            </h2>
            <p class="text-xs text-slate-500 font-medium mt-1">
                Détection automatique des synchronisations répétées et outil de réalignement immédiat des comptes et cycles.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-100 text-[10px] font-black uppercase tracking-wider">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Surveillance Active
            </span>
        </div>
    </div>

    <!-- KPIs de Synthèse -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bank-card p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Doublons Détectés</span>
                <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xs">
                    <i class="fas fa-clone"></i>
                </span>
            </div>
            <p class="text-3xl font-black text-slate-900">{{ count($anomalies) }}</p>
            <p class="text-[10px] text-slate-400 font-medium mt-1">Cas rapprochés identifiés</p>
        </div>

        @php
            $totalExcess = collect($anomalies)->sum('excess_amount');
        @endphp
        <div class="bank-card p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Volume Excédentaire</span>
                <span class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xs">
                    <i class="fas fa-money-bill-transfer"></i>
                </span>
            </div>
            <p class="text-3xl font-black text-rose-600">{{ number_format($totalExcess, 0, ',', ' ') }} <small class="text-xs">XOF</small></p>
            <p class="text-[10px] text-slate-400 font-medium mt-1">Cumul des écritures excédentaires</p>
        </div>

        <div class="bank-card p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Sécurité Opérationnelle</span>
                <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xs">
                    <i class="fas fa-lock"></i>
                </span>
            </div>
            <p class="text-sm font-black text-slate-800 mt-2">Validation Requise</p>
            <p class="text-[10px] text-slate-400 font-medium mt-1">Aucune suppression sans votre accord</p>
        </div>
    </div>

    <!-- SECTION 1 : RECHERCHE & RÉGULARISATION EXPRESS -->
    <div class="bank-card p-6">
        <div class="border-b border-slate-100 pb-4 mb-6">
            <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                <i class="fas fa-magnifying-glass text-blue-600"></i>
                Régularisation Express d'un Compte
            </h3>
            <p class="text-[10px] text-slate-400 font-medium mt-0.5">
                Recherchez un compte pour forcer son montant cotisé exact et réaligner ses 31 cases de tontine.
            </p>
        </div>

        <form method="GET" action="{{ route('admin.anomalies.index') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="q" value="{{ $searchQuery }}" placeholder="Rechercher par nom (ex: Dossouklo, Gbeblewou), téléphone ou N° de compte..."
                       class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:outline-none focus:border-blue-500 focus:bg-white transition">
            </div>
            <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-black uppercase transition flex items-center justify-center gap-2 shadow-sm">
                <i class="fas fa-search"></i> Inspecter le Compte
            </button>
            @if(!empty($searchQuery))
                <a href="{{ route('admin.anomalies.index') }}" class="px-4 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition text-center">
                    Effacer
                </a>
            @endif
        </form>

        <!-- Résultats de recherche de compte -->
        @if($searchedAccount)
            <div class="mt-6 p-6 rounded-2xl bg-gradient-to-br from-slate-50 to-blue-50/30 border border-blue-100">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Fiche Titulaire & Compte -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-blue-600 text-white flex items-center justify-center font-black text-sm">
                                {{ strtoupper(substr($searchedAccount->client->first_name ?? 'C', 0, 1) . substr($searchedAccount->client->last_name ?? 'L', 0, 1)) }}
                            </div>
                            <div>
                                <h4 class="text-sm font-black text-slate-900">{{ $searchedAccount->client->first_name }} {{ $searchedAccount->client->last_name }}</h4>
                                <p class="text-[10px] text-slate-400 font-bold font-mono">{{ $searchedAccount->account_number }} &bull; {{ $searchedAccount->client->phone ?? 'N/A' }}</p>
                            </div>
                        </div>

                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between py-1 border-b border-slate-200/50">
                                <span class="text-slate-500">Type de Compte :</span>
                                <span class="font-bold text-slate-800 uppercase">{{ $searchedAccount->account_type_name }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-200/50">
                                <span class="text-slate-500">Solde Actuel :</span>
                                <span class="font-black text-blue-600">{{ number_format($searchedAccount->balance, 0, ',', ' ') }} XOF</span>
                            </div>
                            @if($searchedAccount->tontineAccount)
                                <div class="flex justify-between py-1 border-b border-slate-200/50">
                                    <span class="text-slate-500">Mise Journalière :</span>
                                    <span class="font-bold text-slate-800">{{ number_format($searchedAccount->tontineAccount->tontine_amount, 0, ',', ' ') }} XOF / j</span>
                                </div>
                                <div class="flex justify-between py-1 border-b border-slate-200/50">
                                    <span class="text-slate-500">Total Cotisé Enregistré :</span>
                                    <span class="font-black text-purple-600">{{ number_format($searchedAccount->tontineAccount->total_paid, 0, ',', ' ') }} XOF</span>
                                </div>
                                <div class="flex justify-between py-1">
                                    <span class="text-slate-500">Cycle Actuel :</span>
                                    <span class="font-bold text-purple-700">Cycle n° {{ $searchedAccount->tontineAccount->current_cycle }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Formulaire de Régularisation -->
                    <div class="lg:col-span-2 bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                        <h4 class="text-xs font-black text-slate-800 uppercase tracking-widest mb-1 flex items-center gap-2">
                            <i class="fas fa-pen-to-square text-emerald-600"></i>
                            Régulariser le Solde & les Cycles
                        </h4>
                        <p class="text-[10px] text-slate-400 mb-4">
                            Entrez le montant réel cotisé par le client. Le système ajustera automatiquement les transactions, le solde et les cycles de tontine.
                        </p>

                        <form method="POST" action="{{ route('admin.anomalies.regularize-balance') }}" id="form-regularize-{{ $searchedAccount->id }}">
                            @csrf
                            <input type="hidden" name="account_id" value="{{ $searchedAccount->id }}">

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Montant Réel Cotisé (XOF) *</label>
                                    <input type="number" step="100" min="0" name="target_amount" required placeholder="Ex: 55000, 44700..."
                                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm font-black text-slate-900 focus:outline-none focus:border-blue-500 focus:bg-white">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Motif de l'Audit</label>
                                    <input type="text" name="reason" value="Correction doublons de synchronisation"
                                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-medium text-slate-700 focus:outline-none focus:border-blue-500 focus:bg-white">
                                </div>
                            </div>

                            <button type="button" onclick="confirmRegularize({{ $searchedAccount->id }}, '{{ $searchedAccount->client->first_name }} {{ $searchedAccount->client->last_name }}')"
                                    class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-black uppercase transition flex items-center justify-center gap-2 shadow-sm">
                                <i class="fas fa-check-double"></i> Appliquer & Réaligner la Tontine
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @elseif(!empty($searchQuery))
            <div class="mt-6 p-8 text-center bg-slate-50 rounded-2xl border border-slate-100">
                <i class="fas fa-user-slash text-2xl text-slate-300 mb-2 block"></i>
                <p class="text-xs font-bold text-slate-500 uppercase">Aucun compte trouvé pour "{{ $searchQuery }}"</p>
                <p class="text-[10px] text-slate-400 mt-1">Vérifiez l'orthographe du nom ou le numéro de compte.</p>
            </div>
        @endif
    </div>

    <!-- SECTION 2 : TABLEAU DES DOUBLONS DÉTECTÉS AUTOMATIQUEMENT -->
    <div class="bank-card overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between flex-wrap gap-4">
            <div>
                <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                    <i class="fas fa-triangle-exclamation text-amber-500"></i>
                    Doublons Détectés par Rapprochement Temporel
                </h3>
                <p class="text-[10px] text-slate-400 font-medium mt-0.5">
                    Écritures de même montant sur un même compte dans la même journée (susceptibles d'être des renvois mobiles).
                </p>
            </div>
            <span class="text-[10px] font-bold text-slate-400 uppercase">
                {{ count($anomalies) }} Cas Actifs
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-100 text-[10px] font-black uppercase text-slate-400 tracking-wider">
                        <th class="py-3 px-6">Titulaire & Compte</th>
                        <th class="py-3 px-6">Date & Émissions</th>
                        <th class="py-3 px-6">Montant Répété</th>
                        <th class="py-3 px-6">Répétitions</th>
                        <th class="py-3 px-6">Excédent Détecté</th>
                        <th class="py-3 px-6 text-right">Action de Résolution</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($anomalies as $index => $item)
                        <tr class="hover:bg-slate-50/60 transition group">
                            <!-- Titulaire & Compte -->
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold text-xs flex-shrink-0">
                                        {{ strtoupper(substr($item['account']->client->first_name ?? 'C', 0, 1)) }}
                                    </div>
                                    <div>
                                        <a href="{{ route('admin.accounts.show', $item['account']->id) }}" class="font-bold text-slate-900 hover:text-blue-600 transition block">
                                            {{ $item['account']->client->first_name }} {{ $item['account']->client->last_name }}
                                        </a>
                                        <p class="text-[10px] text-slate-400 font-mono">{{ $item['account']->account_number }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Date & Horodatages -->
                            <td class="py-4 px-6">
                                <span class="font-bold text-slate-700">{{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y') }}</span>
                                <div class="flex items-center gap-1 mt-1 flex-wrap">
                                    @foreach($item['transactions'] as $tx)
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 text-[9px] font-mono" title="Ref: {{ $tx->transaction_reference }}">
                                            {{ \Carbon\Carbon::parse($tx->created_at)->format('H:i') }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>

                            <!-- Montant Répété -->
                            <td class="py-4 px-6 font-bold text-slate-800">
                                {{ number_format($item['amount'], 0, ',', ' ') }} XOF
                            </td>

                            <!-- Répétitions -->
                            <td class="py-4 px-6">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-rose-50 text-rose-700 border border-rose-100 text-[10px] font-black uppercase">
                                    <i class="fas fa-repeat text-[8px]"></i> {{ $item['total_count'] }} fois
                                </span>
                            </td>

                            <!-- Excédent -->
                            <td class="py-4 px-6">
                                <span class="font-black text-rose-600 text-sm">
                                    +{{ number_format($item['excess_amount'], 0, ',', ' ') }} XOF
                                </span>
                                <span class="block text-[9px] text-slate-400">({{ $item['excess_count'] }} en trop)</span>
                            </td>

                            <!-- Action -->
                            <td class="py-4 px-6 text-right">
                                <form method="POST" action="{{ route('admin.anomalies.resolve-duplicates') }}" id="form-dup-{{ $index }}">
                                    @csrf
                                    <input type="hidden" name="account_id" value="{{ $item['account']->id }}">
                                    <input type="hidden" name="amount" value="{{ $item['amount'] }}">
                                    <input type="hidden" name="date" value="{{ $item['date'] }}">

                                    <button type="button" onclick="confirmResolveDuplicates({{ $index }}, '{{ $item['account']->client->first_name }} {{ $item['account']->client->last_name }}', {{ $item['excess_count'] }}, '{{ number_format($item['excess_amount'], 0, ',', ' ') }}')"
                                            class="px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-[10px] font-black uppercase transition inline-flex items-center gap-1.5 shadow-sm">
                                        <i class="fas fa-trash-can"></i> Supprimer les {{ $item['excess_count'] }} Doublons
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-16 text-center">
                                <i class="fas fa-circle-check text-4xl text-emerald-400 mb-3 block"></i>
                                <p class="text-sm font-bold text-slate-700">Aucun doublon suspect détecté</p>
                                <p class="text-xs text-slate-400 mt-1">Toutes les écritures de la base de données semblent cohérentes.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function confirmResolveDuplicates(index, clientName, count, amount) {
        Swal.fire({
            title: 'Confirmer la suppression des doublons ?',
            html: `Vous allez supprimer <strong>${count} écriture(s) en double</strong> pour <strong>${clientName}</strong>.<br><br>Le solde sera déduit de <strong class="text-rose-600">${amount} XOF</strong> et les cycles seront réalignés.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#E11D48',
            cancelButtonColor: '#64748B',
            confirmButtonText: 'Oui, supprimer les doublons',
            cancelButtonText: 'Annuler',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('form-dup-' + index).submit();
            }
        });
    }

    function confirmRegularize(accountId, clientName) {
        const form = document.getElementById('form-regularize-' + accountId);
        const amountInput = form.querySelector('input[name="target_amount"]');
        const amount = amountInput.value;

        if (!amount || amount < 0) {
            Swal.fire('Montant Invalide', 'Veuillez saisir un montant valide.', 'error');
            return;
        }

        Swal.fire({
            title: 'Confirmer la régularisation ?',
            html: `Le compte de <strong>${clientName}</strong> sera réaligné exactement à :<br><br><span class="text-xl font-black text-emerald-600">${Number(amount).toLocaleString('fr-FR')} XOF</span><br><br>Les écritures et cycles de tontine seront synchronisés sur ce montant.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            cancelButtonColor: '#64748B',
            confirmButtonText: 'Confirmer & Réaligner',
            cancelButtonText: 'Annuler',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    }
</script>
@endpush
@endsection
