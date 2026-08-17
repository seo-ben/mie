@extends('layouts.app_admin')

@section('title', 'Migration Inter-comptes')
@section('page-title', 'Trésorerie / Flux de Transfert')

@section('content')
<div class="max-w-5xl mx-auto space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Migration Inter-comptes</h2>
            <p class="text-slate-500 text-sm font-medium">Protocole de transfert d'actifs entre portefeuilles clients</p>
        </div>
        <a href="{{ route('admin.accounts.transfer.history') }}" class="btn-bank btn-bank-outline px-6">
            <i class="fas fa-history mr-2 text-[10px]"></i> Historique des Flux
        </a>
    </div>

    <!-- Alertes de Système -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-100 rounded-2xl flex items-center gap-3 animate-fade-in">
            <i class="fas fa-check-circle text-emerald-500"></i>
            <p class="text-[11px] font-black text-emerald-800 uppercase tracking-tight">{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-rose-50 border border-rose-100 rounded-2xl flex items-center gap-3 animate-fade-in">
            <i class="fas fa-triangle-exclamation text-rose-500"></i>
            <p class="text-[11px] font-black text-rose-800 uppercase tracking-tight">{{ session('error') }}</p>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-100 rounded-2xl space-y-2 animate-fade-in">
            <h3 class="text-[10px] font-black text-rose-800 uppercase tracking-widest px-1">Discordances d'Audit</h3>
            <ul class="text-[10px] font-bold text-rose-700/70 list-disc list-inside px-1 italic">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Terminal de Migration -->
    <form action="{{ route('admin.accounts.transfer.process') }}" method="POST" id="transferForm" class="space-y-8">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- compte Émetteur -->
            <div class="bank-card overflow-hidden border-rose-100 shadow-xl shadow-rose-500/5">
                <div class="px-8 py-5 bg-rose-600 text-white flex items-center gap-3">
                    <i class="fas fa-arrow-right-from-bracket text-sm"></i>
                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em]">Flux Sortant : compte Source</h3>
                </div>
                <div class="p-8 space-y-6">
                    <div>
                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Résolution du compte Source *</label>
                        <div class="relative">
                            <input type="text" id="sourceSearch" placeholder="Identification du Titulaire..." class="bank-input pl-10 text-xs font-bold" value="{{ $sourceAccount ? $sourceAccount->client->full_name : '' }}">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 text-[10px]"></i>
                        </div>
                        <input type="hidden" name="source_account_id" id="sourceAccountId" value="{{ $sourceAccount->id ?? '' }}" required>
                        <div id="sourceResults" class="hidden mt-2 bank-card !p-0 overflow-hidden shadow-2xl border-slate-200 divide-y divide-slate-50"></div>
                    </div>

                    <!-- Profil du compte Source -->
                    <div id="sourceAccountInfo" class="hidden animate-fade-in p-6 bg-rose-50 rounded-2xl border border-rose-100 relative group">
                        <button type="button" onclick="clearSource()" class="absolute top-4 right-4 text-rose-300 hover:text-rose-600 transition-colors">
                            <i class="fas fa-circle-xmark"></i>
                        </button>
                        <div class="space-y-4">
                            <div class="space-y-1">
                                <p class="text-[8px] font-black text-rose-800/40 uppercase tracking-widest">Titulaire Émetteur</p>
                                <p class="text-sm font-black text-slate-900" id="sourceClientName"></p>
                                <p class="text-[10px] font-mono font-bold text-rose-600" id="sourceAccountNumber"></p>
                            </div>
                            <div class="pt-4 border-t border-rose-100">
                                <p class="text-[8px] font-black text-rose-800/40 uppercase tracking-widest mb-1">Passif Disponible</p>
                                <p class="text-xl font-black text-rose-700 font-numeric" id="sourceBalance"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- compte Bénéficiaire -->
            <div class="bank-card overflow-hidden border-emerald-100 shadow-xl shadow-emerald-500/5">
                <div class="px-8 py-5 bg-emerald-600 text-white flex items-center gap-3">
                    <i class="fas fa-arrow-right-to-bracket text-sm"></i>
                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em]">Flux Entrant : compte Cible</h3>
                </div>
                <div class="p-8 space-y-6">
                    <div>
                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Résolution du compte Cible *</label>
                        <div class="relative">
                            <input type="text" id="destinationSearch" placeholder="Identification du Titulaire..." class="bank-input pl-10 text-xs font-bold text-emerald-600">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-emerald-300 text-[10px]"></i>
                        </div>
                        <input type="hidden" name="destination_account_id" id="destinationAccountId" required>
                        <div id="destinationResults" class="hidden mt-2 bank-card !p-0 overflow-hidden shadow-2xl border-slate-200 divide-y divide-slate-50"></div>
                    </div>

                    <!-- Profil du compte Cible -->
                    <div id="destinationAccountInfo" class="hidden animate-fade-in p-6 bg-emerald-50 rounded-2xl border border-emerald-100 relative group">
                        <button type="button" onclick="clearDestination()" class="absolute top-4 right-4 text-emerald-300 hover:text-emerald-600 transition-colors">
                            <i class="fas fa-circle-xmark"></i>
                        </button>
                        <div class="space-y-4">
                            <div class="space-y-1">
                                <p class="text-[8px] font-black text-emerald-800/40 uppercase tracking-widest">Titulaire Bénéficiaire</p>
                                <p class="text-sm font-black text-slate-900" id="destClientName"></p>
                                <p class="text-[10px] font-mono font-bold text-emerald-600" id="destAccountNumber"></p>
                            </div>
                            <div class="pt-4 border-t border-emerald-100">
                                <p class="text-[8px] font-black text-emerald-800/40 uppercase tracking-widest mb-1">Capacité Actuelle</p>
                                <p class="text-xl font-black text-emerald-700 font-numeric" id="destBalance"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Paramétrage de la Migration -->
        <div class="bank-card p-8">
            <h3 class="text-[10px] font-black text-slate-800 uppercase tracking-[0.2em] mb-8 flex items-center gap-2">
                <i class="fas fa-sliders text-blue-500"></i> Paramètres du Protocole de Migration
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-4">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest block">Volume de Migration (XOF) *</label>
                    <div class="relative">
                        <input type="number" name="amount" id="transferAmount" value="{{ old('amount') }}" min="100" step="0.01" required onkeyup="calculateTotal()" class="w-full bg-slate-50 border-2 border-slate-300 rounded-2xl px-6 py-4 text-2xl font-black text-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all" placeholder="0.00">
                        <span class="absolute right-6 top-1/2 -translate-y-1/2 font-black text-slate-200">XOF</span>
                    </div>
                </div>

                <div class="space-y-4">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest block">Redevance de Migration (Taxes d'Audit)</label>
                    <div class="relative">
                        <input type="number" name="transfer_fee" id="transferFee" value="{{ old('transfer_fee', 0) }}" min="0" step="0.01" onkeyup="calculateTotal()" class="bank-input text-xs font-bold" placeholder="Auto : 0.5% du volume">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 font-black text-slate-300 text-[10px]">XOF</span>
                    </div>
                    <p class="text-[9px] font-bold text-slate-400 uppercase italic px-2">Indexation standard institutionnelle : 0.5%</p>
                </div>

                <div class="md:col-span-2 space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Note d'Audit de Transfert (Optionnel)</label>
                    <textarea name="description" rows="2" class="bank-input text-xs font-bold italic" placeholder="Justification de la migration d'actifs...">{{ old('description') }}</textarea>
                </div>
            </div>

            <!-- Matrice de Récapitulation d'Audit -->
            <div class="mt-10 bg-slate-900 rounded-3xl p-8 text-white relative overflow-hidden shadow-2xl">
                <div class="absolute top-0 right-0 w-64 h-64 bg-blue-500 opacity-5 rounded-full -mr-32 -mt-32 blur-3xl"></div>
                <h6 class="text-[10px] font-black text-white/40 uppercase tracking-[0.2em] mb-6 flex items-center gap-2">
                    <i class="fas fa-bolt text-blue-400"></i> Simulation de l'Incidence de Flux
                </h6>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                    <div class="space-y-4 border-r border-white/5 pr-12">
                        <div class="flex justify-between items-center text-[11px] font-bold">
                            <span class="text-white/30 uppercase tracking-widest">Principal à Migrer</span>
                            <span id="summaryAmount" class="text-white tracking-tighter">0 XOF</span>
                        </div>
                        <div class="flex justify-between items-center text-[11px] font-bold">
                            <span class="text-white/30 uppercase tracking-widest">Redevance d'Audit</span>
                            <span id="summaryFee" class="text-rose-400 font-black">+ 0 XOF</span>
                        </div>
                        <div class="pt-4 border-t border-white/10 flex justify-between items-center">
                            <span class="text-white/40 text-[11px] font-black uppercase tracking-widest">Débit Consolidé Source</span>
                            <span id="summaryTotal" class="text-2xl font-black text-rose-500 font-numeric tracking-tighter">0 XOF</span>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center text-[11px] font-bold italic">
                            <span class="text-white/30 uppercase tracking-widest">Crédit Émis au Bénéficiaire</span>
                            <span id="summaryReceived" class="text-emerald-400 text-2xl font-black tracking-tight">0 XOF</span>
                        </div>
                        <p class="text-[9px] font-bold text-white/20 uppercase leading-relaxed font-inter">Ce montant sera injecté immédiatement après validation du protocole de sécurité et vérification des liquidités du compte émetteur.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contrôles de Validation -->
        <div class="flex items-center justify-end gap-4 pb-12">
            <a href="{{ route('admin.accounts.index') }}" class="btn-bank btn-bank-outline !py-4 px-10 text-xs font-black uppercase">Abandonner</a>
            <button type="submit" id="submitBtn" disabled class="btn-bank btn-bank-primary !py-4 px-12 text-sm font-black uppercase shadow-2xl shadow-blue-500/20 active:scale-95 transition-all disabled:opacity-30">
                <i class="fas fa-bolt-lightning mr-2"></i> Exécuter la Migration d'Actifs
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
let sourceAccountData = null;
let destinationAccountData = null;
let searchTimeout = null;

@if($sourceAccount)
    sourceAccountData = {
        id: {{ $sourceAccount->id }},
        account_number: '{{ $sourceAccount->account_number }}',
        balance: {{ $sourceAccount->balance }},
        client: {
            name: '{{ $sourceAccount->client->full_name }}',
            phone: '{{ $sourceAccount->client->phone }}'
        }
    };
    displaySourceAccount(sourceAccountData);
@endif

document.getElementById('sourceSearch').addEventListener('input', function(e) {
    clearTimeout(searchTimeout);
    const query = e.target.value.trim();
    if (query.length < 2) {
        document.getElementById('sourceResults').classList.add('hidden');
        return;
    }
    searchTimeout = setTimeout(() => { searchAccounts(query, 'source'); }, 300);
});

document.getElementById('destinationSearch').addEventListener('input', function(e) {
    clearTimeout(searchTimeout);
    const query = e.target.value.trim();
    if (query.length < 2) {
        document.getElementById('destinationResults').classList.add('hidden');
        return;
    }
    searchTimeout = setTimeout(() => { searchAccounts(query, 'destination'); }, 300);
});

async function searchAccounts(query, type) {
    const excludeId = type === 'destination' ? sourceAccountData?.id : destinationAccountData?.id;
    try {
        const response = await fetch(`{{ route('admin.accounts.search-for-transfer') }}?query=${encodeURIComponent(query)}&type=${type}&exclude_account_id=${excludeId || ''}`);
        const data = await response.json();
        if (data.success) displaySearchResults(data.data, type);
    } catch (error) { console.error('Audit Search Failed:', error); }
}

function displaySearchResults(accounts, type) {
    const resultsDiv = document.getElementById(type + 'Results');
    if (accounts.length === 0) {
        resultsDiv.innerHTML = '<p class="p-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center italic">Aucun compte Identifié</p>';
        resultsDiv.classList.remove('hidden');
        return;
    }
    let html = '';
    accounts.forEach(account => {
        html += `
            <div class="p-4 cursor-pointer hover:bg-slate-50 transition-colors" onclick="selectAccount(${JSON.stringify(account).replace(/"/g, '&quot;')}, '${type}')">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-black text-slate-800 uppercase leading-none mb-1">${account.client.name}</p>
                        <p class="text-[9px] font-mono font-bold text-slate-400">${account.account_number} • ${account.client.phone}</p>
                    </div>
                    <div class="text-right">
                        <span class="text-[8px] font-black px-2 py-0.5 rounded-full border ${account.account_type === 'savings' ? 'bg-blue-50 text-blue-600 border-blue-100' : 'bg-purple-50 text-purple-600 border-purple-100'} uppercase">
                            ${account.account_type === 'savings' ? 'Épargne' : 'Tontine'}
                        </span>
                        <p class="mt-1.5 text-[11px] font-black text-slate-900">${formatMoney(account.balance)} XOF</p>
                    </div>
                </div>
            </div>
        `;
    });
    resultsDiv.innerHTML = html;
    resultsDiv.classList.remove('hidden');
}

function selectAccount(account, type) {
    if (type === 'source') {
        sourceAccountData = account;
        displaySourceAccount(account);
        document.getElementById('sourceSearch').value = account.client.name;
        document.getElementById('sourceResults').classList.add('hidden');
    } else {
        destinationAccountData = account;
        displayDestinationAccount(account);
        document.getElementById('destinationSearch').value = account.client.name;
        document.getElementById('destinationResults').classList.add('hidden');
    }
    checkFormValidity();
}

function displaySourceAccount(account) {
    document.getElementById('sourceAccountId').value = account.id;
    document.getElementById('sourceClientName').textContent = account.client.name;
    document.getElementById('sourceAccountNumber').textContent = account.account_number;
    document.getElementById('sourceBalance').textContent = formatMoney(account.balance) + ' XOF';
    document.getElementById('sourceAccountInfo').classList.remove('hidden');
}

function displayDestinationAccount(account) {
    document.getElementById('destinationAccountId').value = account.id;
    document.getElementById('destClientName').textContent = account.client.name;
    document.getElementById('destAccountNumber').textContent = account.account_number;
    document.getElementById('destBalance').textContent = formatMoney(account.balance) + ' XOF';
    document.getElementById('destinationAccountInfo').classList.remove('hidden');
}

function clearSource() {
    sourceAccountData = null;
    document.getElementById('sourceAccountId').value = '';
    document.getElementById('sourceSearch').value = '';
    document.getElementById('sourceAccountInfo').classList.add('hidden');
    checkFormValidity();
}

function clearDestination() {
    destinationAccountData = null;
    document.getElementById('destinationAccountId').value = '';
    document.getElementById('destinationSearch').value = '';
    document.getElementById('destinationAccountInfo').classList.add('hidden');
    checkFormValidity();
}

function calculateTotal() {
    const amount = parseFloat(document.getElementById('transferAmount').value) || 0;
    let fee = parseFloat(document.getElementById('transferFee').value);
    if (isNaN(fee) || fee === 0) {
        fee = Math.round(amount * 0.005 * 100) / 100;
        document.getElementById('transferFee').value = fee;
    }
    const total = amount + fee;
    document.getElementById('summaryAmount').textContent = formatMoney(amount) + ' XOF';
    document.getElementById('summaryFee').textContent = '+ ' + formatMoney(fee) + ' XOF';
    document.getElementById('summaryTotal').textContent = formatMoney(total) + ' XOF';
    document.getElementById('summaryReceived').textContent = formatMoney(amount) + ' XOF';
    checkFormValidity();
}

function checkFormValidity() {
    const amount = parseFloat(document.getElementById('transferAmount').value) || 0;
    const sourceId = document.getElementById('sourceAccountId').value;
    const destId = document.getElementById('destinationAccountId').value;
    const sourceBalance = sourceAccountData?.balance || 0;
    const fee = parseFloat(document.getElementById('transferFee').value) || (amount * 0.005);
    const total = amount + fee;
    const isValid = sourceId && destId && amount >= 100 && total <= sourceBalance;
    document.getElementById('submitBtn').disabled = !isValid;
}

function formatMoney(amount) {
    return new Intl.NumberFormat('fr-FR').format(amount);
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('#sourceSearch') && !e.target.closest('#sourceResults')) document.getElementById('sourceResults').classList.add('hidden');
    if (!e.target.closest('#destinationSearch') && !e.target.closest('#destinationResults')) document.getElementById('destinationResults').classList.add('hidden');
});

calculateTotal();
</script>
@endpush
@endsection
