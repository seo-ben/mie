@extends('layouts.app_admin')

@section('title', 'Injection de Capital Institutionnel')
@section('page-title', 'Trésorerie / Traitement des Dépôts')

@section('content')
<div class="space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Terminal de Traitement des Flux</h2>
            <p class="text-slate-500 text-sm font-medium">Injection fiscale en temps réel dans les portefeuilles actifs</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3 py-1 bg-emerald-50 text-emerald-700 text-[10px] font-extrabold rounded-full border border-emerald-100 uppercase tracking-tighter">
                <i class="fas fa-shield-halved mr-1"></i> Protocole Sécurisé Actif
            </span>
        </div>
    </div>

    <!-- Grille Opérationnelle Principale -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Panneau de Recherche & Contrôle -->
        <div class="lg:col-span-4 space-y-6">
            <div class="bank-card p-6 sticky top-24">
                <div class="mb-6">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Identification du compte Cible</label>
                    <div class="relative">
                        <input type="text" id="searchInput" class="bank-input pl-10 pr-4 py-3 text-sm" placeholder="N° de compte, Nom de l'Adhérent..." autocomplete="off" autofocus>
                        <i class="fas fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <div id="searchSpinner" class="absolute right-4 top-1/2 -translate-y-1/2 hidden">
                            <i class="fas fa-circle-notch fa-spin text-blue-600 text-xs"></i>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="p-4 bg-blue-50/50 rounded-2xl border border-blue-100">
                        <h4 class="text-[10px] font-black text-blue-800 uppercase tracking-widest mb-2">Protocole d'Audit de Recherche</h4>
                        <ul class="space-y-2">
                            <li class="flex items-start gap-2 text-[10px] font-bold text-blue-700">
                                <i class="fas fa-check-circle mt-0.5 text-[8px]"></i>
                                <span>Utiliser l'identité complète pour une résolution précise</span>
                            </li>
                            <li class="flex items-start gap-2 text-[10px] font-bold text-blue-700">
                                <i class="fas fa-check-circle mt-0.5 text-[8px]"></i>
                                <span>Vérifier la concordance avec les registres fiscaux</span>
                            </li>
                        </ul>
                    </div>

                    <div class="flex flex-col gap-2 pt-2">
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-tighter">Contrôles Opérationnels</p>
                        <button onclick="focusSearch()" class="flex items-center justify-between px-3 py-2 bg-slate-50 border border-slate-100 rounded-lg text-[10px] font-bold text-slate-600 hover:bg-white hover:border-blue-200 transition">
                            <span>Focus Entrée Audit</span>
                            <span class="px-1.5 py-0.5 bg-slate-200 rounded text-[8px]">CTRL+K</span>
                        </button>
                        <button onclick="resetAll()" class="flex items-center justify-between px-3 py-2 bg-slate-50 border border-slate-100 rounded-lg text-[10px] font-bold text-slate-600 hover:bg-red-50 hover:text-red-700 hover:border-red-100 transition">
                            <span>Purger l'État du Terminal</span>
                            <i class="fas fa-rotate text-[8px]"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Zone d'Affichage Dynamique & Formulaire -->
        <div class="lg:col-span-8 space-y-8">
            
            <!-- État Initial -->
            <div id="initialState" class="bank-card p-16 text-center">
                <div class="w-16 h-16 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-6 text-slate-300">
                    <i class="fas fa-vault text-2xl"></i>
                </div>
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-2">Registre en Attente</h3>
                <p class="text-xs text-slate-400 font-medium max-w-xs mx-auto">Recherchez un adhérent pour activer le protocole d'injection fiscale</p>
            </div>

            <!-- Matrice des Résultats de Recherche -->
            <div id="searchResults" class="hidden bank-card overflow-hidden">
                <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-[10px] font-black text-slate-700 uppercase tracking-widest">Matrice des comptes Identifiés</h3>
                    <span id="resultsCount" class="text-[10px] font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded"></span>
                </div>
                <div class="p-4 space-y-4" id="accountsList"></div>
            </div>

            <!-- Message Matrice Vide -->
            <div id="emptyMessage" class="hidden bank-card p-16 text-center border-dashed border-2">
                <i class="fas fa-inbox text-3xl text-slate-200 mb-4 block"></i>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Aucun compte correspondant dans le registre</p>
            </div>

            <!-- TERMINAL D'INJECTION FISCALE (Formulaire Principal) -->
            <div id="depositFormContainer" class="hidden transition-all duration-500 transform translate-y-4">
                <div id="accountInfoCard" class="mb-6"></div>

                <div class="bank-card overflow-hidden shadow-2xl border-emerald-100">
                    <div class="px-8 py-5 bg-emerald-600 flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center text-white text-lg">
                            <i class="fas fa-money-bill-transfer"></i>
                        </div>
                        <div>
                            <h3 class="text-xs font-black text-white uppercase tracking-widest leading-none mb-1">Terminal d'Injection Fiscale</h3>
                            <p class="text-[9px] font-bold text-white/70 uppercase">Nature de l'Actif : Injection Active</p>
                        </div>
                    </div>

                    <form id="depositForm" class="p-8 space-y-8">
                        @csrf
                        <input type="hidden" id="accountId" name="account_id">

                        <div class="space-y-10">
                            <!-- Matrice de Volume -->
                            <div class="space-y-4">
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest flex items-center justify-between">
                                    <span>Volume d'Injection (XOF)</span>
                                    <span class="text-emerald-600 flex items-center gap-1"><i class="fas fa-check-double text-[8px]"></i> Vérification Requise</span>
                                </label>
                                <div class="relative">
                                    <input type="number" name="amount" id="amount" class="w-full bg-slate-50 border-2 border-slate-300 rounded-2xl px-6 py-5 text-4xl font-black text-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all placeholder:text-slate-200" placeholder="0" min="100" step="100" required>
                                    <span class="absolute right-6 top-1/2 -translate-y-1/2 font-black text-slate-300">XOF</span>
                                </div>
                                <div id="amountHint" class="px-2"></div>
                                <div id="amountError" class="hidden px-2 text-[10px] font-black text-rose-600 uppercase"></div>
                            </div>

                            <!-- Matrice des Canaux de Règlement -->
                            <div class="space-y-4">
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Canal de Règlement de Trésorerie</label>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    @foreach([
                                        ['value' => 'cash', 'icon' => 'money-bill-wave', 'color' => 'emerald', 'label' => 'Espèces Physiques', 'desc' => 'Injection Guichet'],
                                        ['value' => 'mobile_money', 'icon' => 'mobile-screen', 'color' => 'blue', 'label' => 'Actif Numérique', 'desc' => 'Réseau Mobile'],
                                        ['value' => 'bank_transfer', 'icon' => 'building-columns', 'color' => 'purple', 'label' => 'Interbancaire', 'desc' => 'Compensation']
                                    ] as $method)
                                    <label class="bank-card !p-5 cursor-pointer relative group border-slate-100 hover:border-{{ $method['color'] }}-400 transition-all">
                                        <input type="radio" name="payment_method" value="{{ $method['value'] }}" class="sr-only payment-method-radio" {{ $loop->first ? 'checked' : '' }} required>
                                        <div class="flex items-center gap-4">
                                            <div class="w-12 h-12 rounded-xl bg-{{ $method['color'] }}-50 flex items-center justify-center text-{{ $method['color'] }}-600 border border-{{ $method['color'] }}-100 group-hover:scale-110 transition-transform">
                                                <i class="fas fa-{{ $method['icon'] }} text-lg"></i>
                                            </div>
                                            <div>
                                                <p class="text-[11px] font-black text-slate-800 uppercase leading-none">{{ $method['label'] }}</p>
                                                <p class="text-[9px] font-bold text-slate-400 uppercase mt-1.5">{{ $method['desc'] }}</p>
                                            </div>
                                        </div>
                                        <div class="absolute top-3 right-3 hidden check-icon">
                                            <i class="fas fa-circle-check text-{{ $method['color'] }}-500 text-sm"></i>
                                        </div>
                                    </label>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Opérateurs Mobiles (Conditionnel) -->
                            <div id="mobile-money-operator-field" class="hidden transition-all duration-300 space-y-4">
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Protocole d'Opérateur Numérique</label>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @foreach([
                                        ['value' => 'tmoney', 'color' => 'rose', 'letter' => 'T', 'name' => 'Protocole TMoney', 'provider' => 'Infrastructure Togocom'],
                                        ['value' => 'flooz', 'color' => 'orange', 'letter' => 'F', 'name' => 'Protocole Flooz', 'provider' => 'compte Moov Africa']
                                    ] as $operator)
                                    <label class="bank-card !p-5 cursor-pointer relative group border-slate-100 hover:border-{{ $operator['color'] }}-400 transition-all">
                                        <input type="radio" name="mobile_money_operator" value="{{ $operator['value'] }}" class="sr-only operator-radio">
                                        <div class="flex items-center gap-4">
                                            <div class="w-12 h-12 rounded-xl bg-{{ $operator['color'] }}-50 flex items-center justify-center text-{{ $operator['color'] }}-600 border border-{{ $operator['color'] }}-100 group-hover:rotate-6 transition-transform">
                                                <span class="font-black text-lg">{{ $operator['letter'] }}</span>
                                            </div>
                                            <div>
                                                <p class="text-[11px] font-black text-slate-800 uppercase leading-none">{{ $operator['name'] }}</p>
                                                <p class="text-[9px] font-bold text-slate-400 uppercase mt-1.5">{{ $operator['provider'] }}</p>
                                            </div>
                                        </div>
                                        <div class="absolute top-3 right-3 hidden operator-check-icon">
                                            <i class="fas fa-circle-check text-{{ $operator['color'] }}-500 text-sm"></i>
                                        </div>
                                    </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Référence d'Audit Externe</label>
                                    <input type="text" name="payment_reference" class="bank-input text-xs font-bold" placeholder="TRX-compte-XXXX">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Mémo du Terminal</label>
                                    <input type="text" name="description" class="bank-input text-xs font-bold" placeholder="Notes d'audit optionnelles...">
                                </div>
                            </div>

                            <!-- Récapitulatif Final -->
                            <div class="p-8 bg-slate-900 rounded-3xl text-white space-y-6 relative overflow-hidden shadow-2xl">
                                <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-500 opacity-5 rounded-full -mr-16 -mt-16 blur-2xl"></div>
                                <div class="flex items-center justify-between border-b border-white/10 pb-4">
                                    <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-white/50">Pré-Vérification d'Audit</h4>
                                    <div class="flex gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse delay-75"></span>
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse delay-150"></span>
                                    </div>
                                </div>
                                <div id="recapContent" class="space-y-4">
                                    <!-- Contenu du Recaps Dynamique -->
                                </div>
                            </div>
                        </div>

                        <!-- Contrôles d'Action -->
                        <div class="flex items-center gap-4 pt-6">
                            <button type="submit" id="submitBtn" class="flex-1 btn-bank btn-bank-primary !py-5 text-sm uppercase font-black shadow-2xl shadow-emerald-500/20 active:scale-95 transition-all">
                                <i class="fas fa-shield-check mr-2"></i> Autoriser & Exécuter l'Injection
                            </button>
                            <button type="button" onclick="resetAll()" class="btn-bank btn-bank-outline !py-5 px-10 text-xs uppercase font-black">
                                <i class="fas fa-ban mr-1 text-[10px]"></i> Abandonner
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmation de Succès -->
<div id="successModal" class="fixed inset-0 z-50 flex items-center justify-center hidden p-6 bg-slate-900/60 backdrop-blur-md">
    <div class="bank-card w-full max-w-sm p-10 text-center animate-scale-in">
        <div class="w-24 h-24 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-8 border-4 border-emerald-100 shadow-inner">
            <i class="fas fa-check text-4xl"></i>
        </div>
        <h3 class="text-xl font-black text-slate-900 uppercase tracking-widest mb-4">Injection Réussie</h3>
        <p id="successMessage" class="text-xs text-slate-500 font-bold uppercase tracking-tight leading-relaxed mb-8"></p>
        <button onclick="closeSuccessModal()" class="w-full btn-bank btn-bank-primary !py-4 text-xs font-black uppercase">Accuser Réception Audit</button>
    </div>
</div>

@push('scripts')
<script>
    let searchTimeout = null;
    let selectedAccount = null;

    const SEARCH_DEBOUNCE_MS = 400;
    const MIN_SEARCH_LENGTH = 2;
    const MIN_DEPOSIT_AMOUNT = 100;

    document.addEventListener('DOMContentLoaded', () => {
        initializeTerminal();
    });

    function initializeTerminal() {
        const searchInput = document.getElementById('searchInput');
        const amountInput = document.getElementById('amount');
        const form = document.getElementById('depositForm');

        searchInput.addEventListener('input', handleSearch);
        amountInput.addEventListener('input', updateAuditRecap);
        form.addEventListener('submit', handleExecution);

        document.querySelectorAll('.payment-method-radio').forEach(radio => {
            radio.addEventListener('change', (e) => {
                const label = e.target.closest('label');
                document.querySelectorAll('.payment-method-radio').forEach(r => {
                    const l = r.closest('label');
                    l.classList.remove('border-emerald-500', 'bg-emerald-50/10');
                    l.querySelector('.check-icon')?.classList.add('hidden');
                });
                
                label.classList.add('border-emerald-500', 'bg-emerald-50/10');
                label.querySelector('.check-icon').classList.remove('hidden');

                const mmField = document.getElementById('mobile-money-operator-field');
                if (e.target.value === 'mobile_money') {
                    mmField.classList.remove('hidden');
                } else {
                    mmField.classList.add('hidden');
                }
                updateAuditRecap();
            });
        });

        document.querySelectorAll('.operator-radio').forEach(radio => {
            radio.addEventListener('change', (e) => {
                document.querySelectorAll('.operator-radio').forEach(r => {
                    const l = r.closest('label');
                    l.classList.remove('border-blue-500', 'bg-blue-50/10');
                    l.querySelector('.operator-check-icon')?.classList.add('hidden');
                });
                const label = e.target.closest('label');
                label.classList.add('border-blue-500', 'bg-blue-50/10');
                label.querySelector('.operator-check-icon').classList.remove('hidden');
                updateAuditRecap();
            });
        });

        const first = document.querySelector('.payment-method-radio:checked');
        if (first) first.dispatchEvent(new Event('change'));

        window.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                searchInput.focus();
            }
        });
    }

    function handleSearch(e) {
        const query = e.target.value.trim();
        clearTimeout(searchTimeout);
        
        if (query.length < MIN_SEARCH_LENGTH) {
            document.getElementById('initialState').classList.remove('hidden');
            document.getElementById('searchResults').classList.add('hidden');
            document.getElementById('emptyMessage').classList.add('hidden');
            return;
        }

        document.getElementById('searchSpinner').classList.remove('hidden');
        searchTimeout = setTimeout(async () => {
            try {
                const response = await fetch(`./quick-deposit-search?query=${encodeURIComponent(query)}`);
                const data = await response.json();
                document.getElementById('searchSpinner').classList.add('hidden');
                
                if (data.success && data.data.length > 0) {
                    renderRegistryResults(data.data);
                } else {
                    showEmptyMatrix();
                }
            } catch (error) {
                console.error('Échec Audit Recherche:', error);
                document.getElementById('searchSpinner').classList.add('hidden');
            }
        }, SEARCH_DEBOUNCE_MS);
    }

    function renderRegistryResults(accounts) {
        const list = document.getElementById('accountsList');
        list.innerHTML = '';
        
        accounts.forEach(acc => {
            const div = document.createElement('div');
            div.className = "flex items-center justify-between p-5 bg-slate-50 border border-slate-100 rounded-2xl hover:border-blue-300 hover:bg-white transition-all group";
            
            const isSavings = acc.account_type === 'savings';
            const isLoan = acc.account_type === 'loan';
            let typeClass = 'text-purple-600 bg-purple-50 border-purple-100'; // Par défaut Tontine
            let icon = 'fa-rotate';
            let typeName = 'Tontine';
            
            if (isSavings) {
                typeClass = 'text-blue-600 bg-blue-50 border-blue-100';
                icon = 'fa-vault';
                typeName = 'Épargne';
            } else if (isLoan) {
                typeClass = 'text-orange-600 bg-orange-50 border-orange-100';
                icon = 'fa-hand-holding-dollar';
                typeName = 'Prêt';
            }

            div.innerHTML = `
                <div class="flex items-center gap-5">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center border shadow-sm ${typeClass}">
                        <i class="fas ${icon} text-lg"></i>
                    </div>
                    <div>
                        <p class="text-[12px] font-black text-slate-800 uppercase leading-none">${acc.client.name}</p>
                        <div class="flex items-center gap-3 mt-2">
                            <span class="text-[10px] font-mono font-bold text-slate-500 uppercase tracking-tighter">${acc.account_number}</span>
                            <span class="text-[8px] font-black px-2 py-0.5 rounded-full border ${typeClass} uppercase shadow-sm">compte ${typeName}</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-8">
                    <div class="text-right">
                        ${isLoan 
                            ? `<p class="text-[13px] font-black text-rose-600 font-numeric">${formatCurrency(acc.balance)}</p>
                               <p class="text-[8px] font-black text-rose-400 uppercase tracking-widest mt-1">Reste à payer</p>`
                            : `<p class="text-[13px] font-black text-slate-900 font-numeric">${formatCurrency(acc.balance)}</p>
                               <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mt-1">Crédit Live</p>`
                        }
                    </div>
                    <div class="flex gap-2">
                        ${acc.schedule_url ? `<a href="${acc.schedule_url}" class="btn-bank !bg-slate-200 !text-slate-700 !text-[9px] !px-4 !py-2.5 uppercase tracking-widest opacity-0 group-hover:opacity-100 transition-all shadow-sm hover:scale-105 active:scale-95" title="Détails du prêt"><i class="fas fa-list-ul"></i> Détail</a>` : ''}
                        <button onclick='initiateInjection(${JSON.stringify(acc).replace(/'/g, "&#39;")})' class="btn-bank btn-bank-primary !text-[9px] !px-6 !py-2.5 uppercase tracking-widest opacity-0 group-hover:opacity-100 transition-all shadow-lg hover:scale-105 active:scale-95">Sélect. compte</button>
                    </div>
                </div>
            `;
            list.appendChild(div);
        });

        document.getElementById('initialState').classList.add('hidden');
        document.getElementById('emptyMessage').classList.add('hidden');
        document.getElementById('searchResults').classList.remove('hidden');
        document.getElementById('resultsCount').innerText = `${accounts.length} compteS IDENTIFIÉS`;
    }

    function initiateInjection(acc) {
        selectedAccount = acc;
        document.getElementById('accountId').value = acc.id;
        document.getElementById('depositForm').action = acc.deposit_url;

        const summary = document.getElementById('accountInfoCard');
        const isLoanInfo = acc.account_type === 'loan';
        const infoIcon = isLoanInfo ? 'fa-hand-holding-dollar' : (acc.account_type === 'savings' ? 'fa-vault' : 'fa-rotate');
        const infoColor = isLoanInfo ? 'text-rose-400' : 'text-emerald-400';
        const labelText = isLoanInfo ? 'Reste à Payé' : 'Solde Consolidé';

        summary.innerHTML = `
            <div class="bank-card !bg-slate-900 p-6 flex items-center justify-between animate-fade-in shadow-2xl">
                <div class="flex items-center gap-5">
                    <div class="w-16 h-16 bg-white/5 rounded-2xl flex items-center justify-center text-white border border-white/10 shadow-inner">
                        <i class="fas ${infoIcon} text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-white/30 uppercase tracking-[0.2em]">Cible d'Injection Active</p>
                        <h4 class="text-xl font-black text-white leading-none mt-2">${acc.client.name}</h4>
                        <p class="text-[10px] font-mono text-blue-400 font-bold mt-2 uppercase tracking-widest">${acc.account_number} • ${acc.client.client_number}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-black text-white/30 uppercase tracking-[0.2em]">${labelText}</p>
                    <p class="text-3xl font-black ${infoColor} font-numeric leading-none mt-2">${formatCurrency(acc.balance)}</p>
                </div>
            </div>
        `;

        const amountInput = document.getElementById('amount');
        if (acc.account_type === 'savings') {
            amountInput.value = acc.savings.suggested_amount;
            document.getElementById('amountHint').innerHTML = `<p class="text-[10px] font-black text-blue-600 uppercase italic tracking-tighter">Suggestion compte Épargne : ${formatCurrency(acc.savings.suggested_amount)}</p>`;
        } else if (acc.account_type === 'tontine') {
            amountInput.value = acc.tontine.suggested_amount;
            document.getElementById('amountHint').innerHTML = `<p class="text-[10px] font-black text-purple-600 uppercase italic tracking-tighter">Objectif Cycle Tontine : ${formatCurrency(acc.tontine.suggested_amount)}</p>`;
        } else if (acc.account_type === 'loan') {
            amountInput.value = ''; // Laisser l'agent sasir le monntant remboursé
            document.getElementById('amountHint').innerHTML = `<p class="text-[10px] font-black text-orange-600 uppercase italic tracking-tighter">Information: Prêt - Reste à payer ${formatCurrency(acc.balance)}</p>`;
        }

        document.getElementById('depositFormContainer').classList.remove('hidden');
        document.getElementById('depositFormContainer').classList.add('translate-y-0');
        updateAuditRecap();
        
        window.scrollTo({
            top: document.getElementById('depositFormContainer').offsetTop - 100,
            behavior: 'smooth'
        });
    }

    function updateAuditRecap() {
        if (!selectedAccount) return;
        const amount = parseFloat(document.getElementById('amount').value) || 0;
        const method = document.querySelector('[name="payment_method"]:checked')?.value;
        const recap = document.getElementById('recapContent');
        
        const isLoanRecap = selectedAccount.account_type === 'loan';
        
        const balanceNumber = parseFloat(selectedAccount.balance) || 0;
        
        // Si cest un prêt, le resultat n'est pas "Solde Consolidé" mais "Nouveau Reste à Payer"
        const finalCalculated = isLoanRecap 
            ? Math.max(0, balanceNumber - amount)
            : balanceNumber + amount;
            
        recap.innerHTML = `
            <div class="flex justify-between items-center">
                <span class="text-white/40 text-[11px] font-bold uppercase tracking-widest">Injection Principale</span>
                <span class="font-numeric font-black text-emerald-400 text-lg">${formatCurrency(amount)}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-white/40 text-[11px] font-bold uppercase tracking-widest">Canal de Règlement</span>
                <span class="font-black text-white uppercase text-[11px] px-3 py-1 bg-white/5 rounded-lg border border-white/10 italic">${method ? method.replace('_', ' ') : 'N/A'}</span>
            </div>
            <div class="flex justify-between items-center pt-4 border-t border-white/5">
                <span class="text-white/40 text-[11px] font-bold uppercase tracking-widest">Attendu Post-Exécution</span>
                <span class="font-numeric font-black text-white text-lg">${formatCurrency(finalCalculated)}</span>
            </div>
        `;

        const error = document.getElementById('amountError');
        const submit = document.getElementById('submitBtn');
        if (amount < 100) {
            error.innerText = "CRITIQUE : INJECTION MINIMUM DE 100 XOF REQUISE";
            error.classList.remove('hidden');
            submit.disabled = true;
            submit.classList.add('opacity-50', 'grayscale');
        } else {
            error.classList.add('hidden');
            submit.disabled = false;
            submit.classList.remove('opacity-50', 'grayscale');
        }
    }

    async function handleExecution(e) {
        e.preventDefault();
        const submitBtn = document.getElementById('submitBtn');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = `<i class="fas fa-circle-notch fa-spin mr-2"></i> EXÉCUTION DU PROTOCOLE EN COURS...`;

        const formData = new FormData(e.target);
        
        try {
            const response = await fetch(e.target.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: formData
            });

            const result = await response.json();
            
            if (result.success) {
                document.getElementById('successMessage').innerHTML = result.message;
                
                const modalInner = document.querySelector('#successModal .bank-card');
                const existingBtn = modalInner.querySelector('.btn-print-receipt');
                if(existingBtn) existingBtn.remove();
                
                if (result.receipt_url) {
                    const printBtn = document.createElement('a');
                    printBtn.href = result.receipt_url;
                    printBtn.target = '_blank';
                    printBtn.className = 'w-full btn-bank text-emerald-600 bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 !py-4 text-xs font-black uppercase mb-4 block btn-print-receipt';
                    printBtn.innerHTML = '<i class="fas fa-print mr-2"></i> Imprimer le reçu';
                    
                    modalInner.insertBefore(printBtn, modalInner.querySelector('button[onclick="closeSuccessModal()"]'));
                }
                
                document.getElementById('successModal').classList.remove('hidden');
            } else {
                alert('Échec de Protocole : ' + (result.message || result.error || 'Exception fiscale inconnue'));            
                
            }
        } catch (error) {
            console.error('Erreur d\'Exécution :', error);
            alert('Erreur Environnementale Fatale lors de l\'exécution fiscale.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }

    function resetAll() {
        selectedAccount = null;
        document.getElementById('searchInput').value = '';
        document.getElementById('depositFormContainer').classList.add('hidden');
        document.getElementById('searchResults').classList.add('hidden');
        document.getElementById('initialState').classList.remove('hidden');
        document.getElementById('emptyMessage').classList.add('hidden');
        document.getElementById('depositForm').reset();
    }

    function closeSuccessModal() {
        document.getElementById('successModal').classList.add('hidden');
        resetAll();
    }

    function formatCurrency(val) {
        return new Intl.NumberFormat('fr-FR').format(val) + ' XOF';
    }

    function focusSearch() {
        document.getElementById('searchInput').focus();
    }

    function showEmptyMatrix() {
        document.getElementById('initialState').classList.add('hidden');
        document.getElementById('searchResults').classList.add('hidden');
        document.getElementById('emptyMessage').classList.remove('hidden');
    }
</script>
@endpush
@endsection
