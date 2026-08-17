@extends('layouts.app_admin')

@section('title', 'Décaissement de Fonds Institutionnels')
@section('page-title', 'Trésorerie / Traitement des Retraits')

@section('content')
<div class="space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Terminal de Décaissement Rapide</h2>
            <p class="text-slate-500 text-sm font-medium">Extraction sécurisée de capitaux des portefeuilles actifs</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3 py-1 bg-rose-50 text-rose-700 text-[10px] font-extrabold rounded-full border border-rose-100 uppercase tracking-tighter">
                <i class="fas fa-shield-halved mr-1"></i> Protocole de Sécurité Renforcé
            </span>
        </div>
    </div>

    <!-- Grille Opérationnelle Principale -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Panneau de Recherche & Contrôle -->
        <div class="lg:col-span-4 space-y-6">
            <div class="bank-card p-6 sticky top-24">
                <div class="mb-6">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Source du Décaissement</label>
                    <div class="relative">
                        <input type="text" id="searchInput" class="bank-input pl-10 pr-4 py-3 text-sm" placeholder="N° de compte, Nom de l'Adhérent..." autocomplete="off" autofocus>
                        <i class="fas fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <div id="searchSpinner" class="absolute right-4 top-1/2 -translate-y-1/2 hidden">
                            <i class="fas fa-circle-notch fa-spin text-rose-600 text-xs"></i>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="p-4 bg-rose-50/50 rounded-2xl border border-rose-100">
                        <h4 class="text-[10px] font-black text-rose-800 uppercase tracking-widest mb-2">Audit de Sécurité Retrait</h4>
                        <ul class="space-y-2">
                            <li class="flex items-start gap-2 text-[10px] font-bold text-rose-700">
                                <i class="fas fa-id-card mt-0.5 text-[8px]"></i>
                                <span>Vérifier l'identité physique de l'adhérent</span>
                            </li>
                            <li class="flex items-start gap-2 text-[10px] font-bold text-rose-700">
                                <i class="fas fa-check-double mt-0.5 text-[8px]"></i>
                                <span>Confirmer le solde disponible avant déblocage</span>
                            </li>
                        </ul>
                    </div>

                    <div class="flex flex-col gap-2 pt-2">
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-tighter">Commandes Rapides</p>
                        <button onclick="focusSearch()" class="flex items-center justify-between px-3 py-2 bg-slate-50 border border-slate-100 rounded-lg text-[10px] font-bold text-slate-600 hover:bg-white hover:border-rose-200 transition">
                            <span>Focus Recherche Audit</span>
                            <span class="px-1.5 py-0.5 bg-slate-200 rounded text-[8px]">CTRL+K</span>
                        </button>
                        <button onclick="resetAll()" class="flex items-center justify-between px-3 py-2 bg-slate-50 border border-slate-100 rounded-lg text-[10px] font-bold text-slate-600 hover:bg-red-50 hover:text-red-700 hover:border-red-100 transition">
                            <span>Réinitialiser Terminal</span>
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
                    <i class="fas fa-hand-holding-dollar text-2xl"></i>
                </div>
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-2">Audit de Retrait en Attente</h3>
                <p class="text-xs text-slate-400 font-medium max-w-xs mx-auto">Recherchez un compte pour initier le protocole de retrait de fonds</p>
            </div>

            <!-- Matrice des Résultats de Recherche -->
            <div id="searchResults" class="hidden bank-card overflow-hidden">
                <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-[10px] font-black text-slate-700 uppercase tracking-widest">Registres avec Solde Disponible</h3>
                    <span id="resultsCount" class="text-[10px] font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded"></span>
                </div>
                <div class="p-4 space-y-4" id="accountsList"></div>
            </div>

            <!-- Message Matrice Vide -->
            <div id="emptyMessage" class="hidden bank-card p-16 text-center border-dashed border-2">
                <i class="fas fa-triangle-exclamation text-3xl text-slate-200 mb-4 block"></i>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Aucun compte actif avec solde détecté</p>
            </div>

            <!-- TERMINAL DE DÉCAISSEMENT (Formulaire Principal) -->
            <div id="withdrawalFormContainer" class="hidden transition-all duration-500 transform translate-y-4">
                <div id="accountInfoCard" class="mb-6"></div>

                <div class="bank-card overflow-hidden shadow-2xl border-rose-100">
                    <div class="px-8 py-5 bg-slate-900 flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-rose-500 flex items-center justify-center text-white text-lg ring-4 ring-rose-500/20">
                            <i class="fas fa-outdent"></i>
                        </div>
                        <div>
                            <h3 class="text-xs font-black text-white uppercase tracking-widest leading-none mb-1">Terminal de Décaissement</h3>
                            <p class="text-[9px] font-bold text-white/50 uppercase">Type d'opération : Retrait Immédiat</p>
                        </div>
                    </div>

                    <form id="withdrawalForm" class="p-8 space-y-8">
                        @csrf
                        <input type="hidden" id="accountId" name="account_id">

                        <div class="space-y-10">
                            <!-- Matrice de Volume -->
                            <div class="space-y-4">
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest flex items-center justify-between">
                                    <span>Volume du Retrait (XOF)</span>
                                    <span class="text-rose-600 flex items-center gap-1"><i class="fas fa-lock text-[8px]"></i> Plafonné au Solde Live</span>
                                </label>
                                <div class="relative">
                                    <input type="number" name="amount" id="amount" class="w-full bg-slate-50 border-2 border-slate-300 rounded-2xl px-6 py-5 text-4xl font-black text-slate-900 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 outline-none transition-all placeholder:text-slate-200" placeholder="0" min="100" step="100" required>
                                    <span class="absolute right-6 top-1/2 -translate-y-1/2 font-black text-slate-300">XOF</span>
                                </div>
                                
                                <!-- Boutons de Saisie Rapide -->
                                <div class="flex flex-wrap gap-2 px-1">
                               
                                    <button type="button" onclick="setMaxAmount()" class="px-4 py-2 bg-rose-600 border border-rose-600 rounded-xl text-[10px] font-black text-white hover:bg-rose-700 transition-all shadow-sm">
                                        MAXIMUM NET
                                    </button>
                                </div>

                                <div id="amountHint" class="px-2"></div>
                            </div>

                            <!-- Matrice des Canaux de Décaissement -->
                            <div class="space-y-4">
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Mode de Remise des Fonds</label>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    @foreach([
                                        ['value' => 'cash', 'icon' => 'money-bill-1', 'color' => 'emerald', 'label' => 'Espèces Guichet', 'desc' => 'Remise Physique'],
                                        ['value' => 'mobile_money', 'icon' => 'mobile-retro', 'color' => 'blue', 'label' => 'Sortie Mobile', 'desc' => 'Canal Numérique'],
                                        ['value' => 'bank_transfer', 'icon' => 'building-columns', 'color' => 'purple', 'label' => 'Transfert Tierce', 'desc' => 'Système Bancaire']
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
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Protocole d'Envoi Numérique</label>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @foreach([
                                        ['value' => 'tmoney', 'color' => 'rose', 'letter' => 'T', 'name' => 'Canal TMoney', 'provider' => 'Infrastructure Togocom'],
                                        ['value' => 'flooz', 'color' => 'orange', 'letter' => 'F', 'name' => 'Canal Flooz', 'provider' => 'Infrastructure Moov']
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
                                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Référence Externe</label>
                                    <input type="text" name="payment_reference" class="bank-input text-xs font-bold" placeholder="REF-RETRAIT-XXXX">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Mémo Opérationnel</label>
                                    <input type="text" name="description" class="bank-input text-xs font-bold" placeholder="Justification du décaissement...">
                                </div>
                            </div>

                            <!-- Récapitulatif de Sortie -->
                            <div class="p-8 bg-rose-950 rounded-3xl text-white space-y-6 relative overflow-hidden shadow-2xl">
                                <div class="absolute top-0 right-0 w-32 h-32 bg-rose-500 opacity-10 rounded-full -mr-16 -mt-16 blur-2xl"></div>
                                <div class="flex items-center justify-between border-b border-white/10 pb-4">
                                    <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-white/50">Audit Final du Décaissement</h4>
                                    <div class="flex gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                                        <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse delay-75"></span>
                                        <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse delay-150"></span>
                                    </div>
                                </div>
                                <div id="recapContent" class="space-y-4">
                                    <!-- Contenu du Recaps Dynamique -->
                                </div>
                            </div>
                        </div>

                        <!-- Contrôles d'Action -->
                        <div class="flex items-center gap-4 pt-6">
                            <button type="submit" id="submitBtn" class="flex-1 btn-bank btn-bank-danger !py-5 text-sm uppercase font-black shadow-2xl shadow-rose-500/20 active:scale-95 transition-all">
                                <i class="fas fa-unlock-keyhole mr-2"></i> Valider & Débloquer les Fonds
                            </button>
                            <button type="button" onclick="resetAll()" class="btn-bank btn-bank-outline !py-5 px-10 text-xs uppercase font-black">
                                <i class="fas fa-ban mr-1 text-[10px]"></i> Annuler
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmation de Retrait -->
<div id="successModal" class="fixed inset-0 z-50 flex items-center justify-center hidden p-6 bg-slate-900/60 backdrop-blur-md">
    <div class="bank-card w-full max-w-sm p-10 text-center animate-scale-in">
        <div class="w-24 h-24 bg-rose-50 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-8 border-4 border-rose-100 shadow-inner">
            <i class="fas fa-money-bill-transfer text-4xl"></i>
        </div>
        <h3 class="text-xl font-black text-slate-900 uppercase tracking-widest mb-4">Décaissement Terminé</h3>
        <p id="successMessage" class="text-xs text-slate-500 font-bold uppercase tracking-tight leading-relaxed mb-8"></p>
        <button onclick="closeSuccessModal()" class="w-full btn-bank btn-bank-primary !py-4 text-xs font-black uppercase">Terminer l'Audit</button>
    </div>
</div>

@push('scripts')
<script>
    // Configuration des Frais (Injectée par Blade)
    const FEES_CONFIG = {
        savings: {
            percent: {{ $savingsFeePercentage ?? 2.0 }},
            fixed: {{ $savingsFeeFixed ?? 0 }}
        },
        tontine: {
            percent: {{ $tontineFeePercentage ?? 3.0 }},
            fixed: {{ $tontineFeeFixed ?? 0 }}
        }
    };

    let searchTimeout = null;
    let selectedAccount = null;

    const SEARCH_DEBOUNCE_MS = 400;
    const MIN_SEARCH_LENGTH = 2;

    document.addEventListener('DOMContentLoaded', () => {
        initializeTerminal();
    });

    function initializeTerminal() {
        const searchInput = document.getElementById('searchInput');
        const amountInput = document.getElementById('amount');
        const form = document.getElementById('withdrawalForm');

        searchInput.addEventListener('input', handleSearch);
        amountInput.addEventListener('input', updateAuditRecap);
        form.addEventListener('submit', handleExecution);

        document.querySelectorAll('.payment-method-radio').forEach(radio => {
            radio.addEventListener('change', (e) => {
                const label = e.target.closest('label');
                document.querySelectorAll('.payment-method-radio').forEach(r => {
                    const l = r.closest('label');
                    l.classList.remove('border-rose-500', 'bg-rose-50/10');
                    l.querySelector('.check-icon')?.classList.add('hidden');
                });
                
                label.classList.add('border-rose-500', 'bg-rose-50/10');
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
                    l.classList.remove('border-rose-500', 'bg-rose-50/10');
                    l.querySelector('.operator-check-icon')?.classList.add('hidden');
                });
                const label = e.target.closest('label');
                label.classList.add('border-rose-500', 'bg-rose-50/10');
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
                const response = await fetch(`{{ route('admin.accounts.quick-withdrawal-search') }}?query=${encodeURIComponent(query)}`);
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
            div.className = "flex items-center justify-between p-5 bg-slate-50 border border-slate-100 rounded-2xl hover:border-rose-300 hover:bg-white transition-all group";
            
            const isSavings = acc.account_type === 'savings';
            const typeClass = isSavings ? 'text-blue-600 bg-blue-50 border-blue-100' : 'text-purple-600 bg-purple-50 border-purple-100';
            const icon = isSavings ? 'fa-vault' : 'fa-rotate';

            div.innerHTML = `
                <div class="flex items-center gap-5">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center border shadow-sm ${typeClass}">
                        <i class="fas ${icon} text-lg"></i>
                    </div>
                    <div>
                        <p class="text-[12px] font-black text-slate-800 uppercase leading-none">${acc.client.name}</p>
                        <div class="flex items-center gap-3 mt-2">
                            <span class="text-[10px] font-mono font-bold text-slate-500 uppercase tracking-tighter">${acc.account_number}</span>
                            <span class="text-[8px] font-black px-2 py-0.5 rounded-full border ${typeClass} uppercase shadow-sm">${isSavings ? 'Épargne' : 'Tontine'}</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-8">
                    <div class="text-right">
                        <p class="text-[13px] font-black text-slate-900 font-numeric">${formatCurrency(acc.balance)}</p>
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mt-1">Solde Brut</p>
                    </div>
                    ${!isSavings && acc.tontine ? `
                    <div class="text-right border-l border-slate-200 pl-4">
                        <p class="text-[13px] font-bold text-emerald-600 font-numeric" id="net-display-${acc.id}">
                            ${(() => {
                                const mise = acc.tontine.tontine_amount;
                                const freq = acc.tontine.payment_frequency;
                                const totalPossibleDays = acc.balance / mise;
                                // Approximation rapide pour l'affichage de recherche
                                const estNbCycles = Math.ceil(totalPossibleDays / (freq === 'daily' ? 31 : 52)) || 1;
                                return formatCurrency(Math.max(0, acc.balance - (estNbCycles * mise)));
                            })()}
                        </p>
                        <p class="text-[8px] font-black text-emerald-400 uppercase tracking-widest mt-1">Net Estimé</p>
                    </div>
                    ` : ''}
                    <div class="flex flex-col gap-2">
                        <button onclick='initiateWithdrawal(${JSON.stringify(acc).replace(/'/g, "&#39;")})' class="btn-bank btn-bank-danger !text-[8px] !px-4 !py-2.5 uppercase tracking-widest opacity-0 group-hover:opacity-100 transition-all shadow-lg hover:scale-105 active:scale-95">Préparer Retrait</button>
                        <a href="javascript:void(0)" 
                           onclick="checkKycBeforeLoan('${acc.client.kyc_status}', '{{ route('admin.loans.create') }}?client_id=${acc.client_id}', '{{ url('admin/clients') }}/${acc.client_id}/edit')"
                           class="btn-bank btn-bank-outline !text-[8px] !px-4 !py-2.5 uppercase tracking-widest opacity-0 group-hover:opacity-100 transition-all shadow-lg hover:scale-105 active:scale-95">Initier Crédit</a>
                    </div>
                </div>
            `;
            list.appendChild(div);
        });

        document.getElementById('initialState').classList.add('hidden');
        document.getElementById('emptyMessage').classList.add('hidden');
        document.getElementById('searchResults').classList.remove('hidden');
        document.getElementById('resultsCount').innerText = `${accounts.length} COMPTES TROUVÉS`;
    }

    function initiateWithdrawal(acc) {
        selectedAccount = acc;
        document.getElementById('accountId').value = acc.id;
        document.getElementById('withdrawalForm').action = acc.withdrawal_url;

        const summary = document.getElementById('accountInfoCard');
        summary.innerHTML = `
            <div class="bank-card !bg-white p-6 flex items-center justify-between animate-fade-in shadow-2xl border-rose-200">
                <div class="flex items-center gap-5">
                    <div class="w-16 h-16 bg-rose-50 rounded-2xl flex items-center justify-center text-rose-600 border border-rose-100 shadow-inner">
                        <i class="fas ${acc.account_type === 'savings' ? 'fa-vault' : 'fa-rotate'} text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Source du Décaissement</p>
                        <h4 class="text-xl font-black text-slate-900 leading-none mt-2">${acc.client.name}</h4>
                        <p class="text-[10px] font-mono text-rose-600 font-bold mt-2 uppercase tracking-widest">${acc.account_number} • ${acc.client.client_number}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Net Théorique (Fin de Tontine)</p>
                    <p class="text-3xl font-black text-emerald-600 font-numeric leading-none mt-2" id="theoretical-net">
                        ${(() => {
                            const mise = acc.tontine ? acc.tontine.tontine_amount : 0;
                            const freq = acc.tontine ? acc.tontine.payment_frequency : 'daily';
                            const estNbCycles = Math.ceil((acc.balance / (mise || 1)) / (freq === 'daily' ? 31 : 52)) || 1;
                            return formatCurrency(Math.max(0, acc.balance - (estNbCycles * (mise || 0))));
                        })()}
                    </p>
                </div>
            </div>
        `;

        const amountInput = document.getElementById('amount');
        const commission = acc.account_type === 'tontine' ? acc.tontine.institutional_fee : 0;
        const maxNet = Math.max(0, acc.balance - commission);
        
        amountInput.max = maxNet;
        amountInput.value = '';
        
        document.getElementById('amountHint').innerHTML = `
            <div class="flex justify-between items-center mt-1">
                <p class="text-[10px] font-black text-rose-600 uppercase italic tracking-tighter">Solde : ${formatCurrency(acc.balance)}</p>
            </div>
        `;

        document.getElementById('withdrawalFormContainer').classList.remove('hidden');
        document.getElementById('withdrawalFormContainer').classList.add('translate-y-0');
        updateAuditRecap();
        
        window.scrollTo({
            top: document.getElementById('withdrawalFormContainer').offsetTop - 100,
            behavior: 'smooth'
        });
        
        setTimeout(() => amountInput.focus(), 500);
    }

    function updateAuditRecap() {
        if (!selectedAccount) return;
        const amount = parseFloat(document.getElementById('amount').value) || 0;
        const method = document.querySelector('[name="payment_method"]:checked')?.value;
        const recap = document.getElementById('recapContent');
        
        // Calcul des frais selon la règle (1/31 pour Tontine, % pour Épargne)
        let feeAmount = 0;
        let feeLabel = "";
        
        if (selectedAccount.account_type === 'tontine') {
            const mise = selectedAccount.tontine.tontine_amount;
            const freq = selectedAccount.tontine.payment_frequency;
            const divisor = freq === 'daily' ? 31 : (freq === 'weekly' ? 52 : 12);
            
            const volumeUnits = amount / mise;
            const nbCommissions = Math.ceil(volumeUnits / divisor) || 1;
            feeAmount = nbCommissions * mise;
            
            const cycleName = freq === 'daily' ? 'Mois' : (freq === 'weekly' ? 'Ans' : 'Cycles');
            feeLabel = `Prélèvement Institutionnel (${nbCommissions} ${cycleName})`;

            // Génération visuelle des cycles de commission
            let cyclesHtml = '';
            for(let i=0; i < nbCommissions; i++) {
                cyclesHtml += `<span class="w-3 h-3 rounded-full bg-rose-500 shadow-[0_0_10px_rgba(244,63,94,0.5)]"></span>`;
            }

            recap.innerHTML = `
                <div class="space-y-6 animate-fade-in">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-white/40 text-[9px] font-black uppercase tracking-widest block mb-1">Détails des Frais Cumulés</span>
                            <div class="flex gap-1.5 mt-2">
                                ${cyclesHtml}
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-rose-400 text-[10px] font-black uppercase tracking-widest block">${feeLabel}</span>
                            <span class="font-numeric font-black text-rose-400 text-xl">- ${formatCurrency(feeAmount)}</span>
                        </div>
                    </div>

                    <div class="py-6 border-y border-white/5">
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-white/40 text-[11px] font-bold uppercase tracking-widest">Total Débité du Compte</span>
                            <span class="font-numeric font-black text-white text-xl">${formatCurrency(amount + feeAmount)}</span>
                        </div>
                        <div class="p-6 bg-white/5 rounded-2xl border border-white/10 ring-1 ring-white/5">
                            <div class="flex justify-between items-center">
                                <div>
                                    <span class="text-emerald-400 text-[11px] font-black uppercase tracking-[0.2em] block mb-1">Montant Net à Remettre</span>
                                    <p class="text-[9px] text-white/30 font-bold uppercase italic">Fonds à décaisser physiquement pour le client</p>
                                </div>
                                <span class="font-numeric font-black text-emerald-400 text-4xl drop-shadow-sm">${formatCurrency(amount)}</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-white/40 text-[9px] font-black uppercase tracking-widest block mb-1">Mode de Remise</span>
                            <span class="font-black text-white uppercase text-[10px] px-2 py-1 bg-white/5 rounded border border-white/10 italic">${method ? method.replace('_', ' ') : '-'}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-white/40 text-[9px] font-black uppercase tracking-widest block mb-1">Solde Post-Retrait</span>
                            <span class="font-numeric font-black text-white text-sm">${formatCurrency(selectedAccount.balance - (amount + feeAmount))}</span>
                        </div>
                    </div>
                </div>
            `;
        } else {
            const config = FEES_CONFIG.savings;
            feeAmount = Math.round((amount * (config.percent / 100)) + config.fixed);
            feeLabel = `Frais de Retrait (${config.percent}% + ${config.fixed}F)`;

            recap.innerHTML = `
                <div class="space-y-6 animate-fade-in">
                    <div class="flex justify-between items-center">
                        <span class="text-white/40 text-[10px] font-black uppercase tracking-widest">Capital Brut</span>
                        <span class="font-numeric font-black text-white text-xl">${formatCurrency(amount + feeAmount)}</span>
                    </div>
                    <div class="flex justify-between items-center pb-6 border-b border-white/5">
                        <span class="text-rose-400 text-[10px] font-black uppercase tracking-widest">${feeLabel}</span>
                        <span class="font-numeric font-black text-rose-400 text-base">- ${formatCurrency(feeAmount)}</span>
                    </div>
                    <div class="p-6 bg-white/5 rounded-2xl border border-white/10">
                        <div class="flex justify-between items-center">
                            <div>
                                <span class="text-emerald-400 text-[11px] font-black uppercase tracking-[0.2em] block mb-1">Montant Net Client</span>
                            </div>
                            <span class="font-numeric font-black text-emerald-400 text-4xl">${formatCurrency(amount)}</span>
                        </div>
                    </div>
                </div>
            `;
        }

        const error = document.getElementById('amountError');
        const submit = document.getElementById('submitBtn');
        
        if (totalDeduction > selectedAccount.balance) {
            error.innerText = "CRITIQUE : LE TOTAL (MONTANT + FRAIS) DÉPASSE LA CAPACITÉ DU COMPTE";
            error.classList.remove('hidden');
            submit.disabled = true;
            submit.classList.add('opacity-50', 'grayscale');
        } else if (amount < 100 && amount > 0) {
            error.innerText = "MINIMUM DE RETRAIT : 100 XOF";
            error.classList.remove('hidden');
            submit.disabled = true;
        } else {
            error.classList.add('hidden');
            submit.disabled = amount <= 0;
            submit.classList.remove('opacity-50', 'grayscale');
        }
    }

    async function handleExecution(e) {
        e.preventDefault();
        const submitBtn = document.getElementById('submitBtn');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = `<i class="fas fa-circle-notch fa-spin mr-2"></i> PROTOCOLE DE DÉCAISSEMENT EN COURS...`;

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
                alert('Échec de Décaissement : ' + (result.message || 'Exception de solde inconnue'));
            }
        } catch (error) {
            console.error('Erreur d\'Exécution :', error);
            alert('Erreur Environnementale Fatale lors du décaissement.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }

    function resetAll() {
        selectedAccount = null;
        document.getElementById('searchInput').value = '';
        document.getElementById('withdrawalFormContainer').classList.add('hidden');
        document.getElementById('searchResults').classList.add('hidden');
        document.getElementById('initialState').classList.remove('hidden');
        document.getElementById('emptyMessage').classList.add('hidden');
        document.getElementById('withdrawalForm').reset();
    }

    function closeSuccessModal() {
        document.getElementById('successModal').classList.add('hidden');
        resetAll();
    }

    function formatCurrency(val) {
        return new Intl.NumberFormat('fr-FR').format(val) + ' XOF';
    }

    function setQuickAmount(val) {
        const input = document.getElementById('amount');
        input.value = val;
        input.dispatchEvent(new Event('input'));
    }

    function setMaxAmount() {
        if (!selectedAccount) return;
        const mise = selectedAccount.tontine ? selectedAccount.tontine.tontine_amount : 0;
        const freq = selectedAccount.tontine ? selectedAccount.tontine.payment_frequency : 'daily';
        const balance = selectedAccount.balance;
        
        if (selectedAccount.account_type === 'savings') {
            const config = FEES_CONFIG.savings;
            // Balance = Net + (Net * % + Fixed) -> Net = (Balance - Fixed) / (1 + %)
            const maxNet = Math.floor((balance - config.fixed) / (1 + config.percent/100));
            setQuickAmount(maxNet);
        } else {
            // Pour la tontine, c'est par paliers, on cherche le plus grand Net tel que Net + Fees(Net) <= Balance
            let maxNet = 0;
            const divisor = freq === 'daily' ? 31 : 52;
            
            // On peut l'approcher par itération ou par la règle
            // Max possible units total = balance / mise
            // Max units net = total units - ceil(total units / divisor)
            const totalUnits = Math.floor(balance / mise);
            const nbFees = Math.ceil(totalUnits / divisor);
            maxNet = (totalUnits - nbFees) * mise;
            
            setQuickAmount(maxNet);
        }
    }

    function focusSearch() {
        document.getElementById('searchInput').focus();
    }

    function showEmptyMatrix() {
        document.getElementById('initialState').classList.add('hidden');
        document.getElementById('searchResults').classList.add('hidden');
        document.getElementById('emptyMessage').classList.remove('hidden');
    }

    window.checkKycBeforeLoan = function(status, loanUrl, editUrl) {
        if (status === 'approved') {
            window.location.href = loanUrl;
        } else {
            Swal.fire({
                title: '<span class="text-rose-600 uppercase font-black text-sm">Action Requise : KYC Non Validé</span>',
                html: `
                    <div class="py-4 text-center">
                        <div class="w-16 h-16 bg-rose-50 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-4 border border-rose-100 shadow-inner">
                            <i class="fas fa-shield-halved text-2xl"></i>
                        </div>
                        <p class="text-xs font-bold text-slate-600 leading-relaxed uppercase">
                            Le protocole KYC de cet adhérent n'est pas encore <span class="text-rose-600">Approuvé</span>.<br>
                            La validation du dossier est obligatoire avant toute demande de crédit.
                        </p>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Vérifier le dossier',
                cancelButtonText: 'Annuler',
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#94a3b8',
                customClass: {
                    popup: 'rounded-3xl border-2 border-slate-100 shadow-2xl',
                    confirmButton: 'rounded-xl font-bold uppercase text-[10px] px-6',
                    cancelButton: 'rounded-xl font-bold uppercase text-[10px] px-6'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = editUrl + '?focus=kyc_validation';
                }
            });
        }
    }
</script>
@endpush
@endsection
