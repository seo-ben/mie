@extends('layouts.app_admin')

@section('title', $account->account_type === 'tontine' ? 'Injection de Cotisation' : 'Injection de Capital')
@section('page-title', 'Trésorerie / Formulaire d\'Injection')

@section('content')
<div class="max-w-4xl mx-auto space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">
                @if($account->account_type === 'tontine')
                    Injection de Cotisation Mutuelle
                @else
                    Injection de Capital Épargne
                @endif
            </h2>
            <p class="text-slate-500 text-sm font-medium">
                @if($account->account_type === 'tontine')
                    Enregistrement d'un flux de capital pour le cycle opérationnel actif (Compte Mutuel)
                @else
                    Augmentation de l'exposition globale du compte de réserve (Compte Épargne)
                @endif
            </p>
        </div>
        <a href="{{ route('admin.accounts.show', $account->id) }}" class="btn-bank btn-bank-outline px-6">
            <i class="fas fa-chevron-left mr-2 text-[10px]"></i> Retour au compte
        </a>
    </div>

    @if($account->account_type === 'tontine' && $activeCycle)
        <!-- Diagnostic du Cycle Actif -->
        <div class="bank-card overflow-hidden border-l-4 border-purple-600 shadow-xl shadow-purple-500/5">
            <div class="px-8 py-5 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-[10px] font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                    <i class="fas fa-rotate text-purple-600"></i> Analyse du Protocole Actif : Cycle #{{ $activeCycle->cycle_number }}
                </h3>
                <span class="bank-badge badge-secondary !text-[8px] uppercase font-black">
                    Fréquence : {{ __($account->tontineAccount->payment_frequency) }}
                </span>
            </div>
            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-12 mb-8">
                    <div class="space-y-4">
                        <div class="space-y-1">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Tutelle de l'Entité</p>
                            <p class="text-lg font-black text-slate-900">{{ $account->client->full_name }}</p>
                            <p class="text-[10px] font-mono font-bold text-blue-600 uppercase tracking-tighter">{{ $account->account_number }}</p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="space-y-1">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Fenêtre Opérationnelle</p>
                            <p class="text-sm font-black text-slate-800 uppercase italic">
                                {{ $activeCycle->start_date->format('d/m/Y') }} <i class="fas fa-arrow-right mx-2 text-[10px] text-slate-300"></i> {{ $activeCycle->end_date->format('d/m/Y') }}
                            </p>
                            @php
                                $daysRemaining = now()->diffInDays($activeCycle->end_date, false);
                            @endphp
                            <p class="text-[9px] font-bold uppercase mt-1 {{ $daysRemaining > 0 ? 'text-emerald-500' : 'text-rose-500' }}">
                                <i class="fas {{ $daysRemaining > 0 ? 'fa-clock' : 'fa-triangle-exclamation' }} mr-1"></i>
                                {{ $daysRemaining > 0 ? $daysRemaining . ' Cycles Journaliers Restants' : 'Fenêtre de Cycle Clôturée' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Métriques de Performance de Cotisation -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="p-5 bg-white border border-slate-100 rounded-2xl shadow-sm">
                        <p class="text-[8px] font-black text-slate-400 uppercase mb-2">Cible du Cycle</p>
                        <p class="text-xl font-black text-slate-900">{{ number_format($activeCycle->target_amount, 0, ',', ' ') }} <small class="text-[10px]">XOF</small></p>
                    </div>
                    <div class="p-5 bg-emerald-50/50 border border-emerald-100 rounded-2xl shadow-sm">
                        <p class="text-[8px] font-black text-emerald-800/60 uppercase mb-2">Capitalisé</p>
                        <p class="text-xl font-black text-emerald-600">{{ number_format($activeCycle->collected_amount, 0, ',', ' ') }} <small class="text-[10px]">XOF</small></p>
                    </div>
                    <div class="p-5 bg-rose-50/50 border border-rose-100 rounded-2xl shadow-sm">
                        <p class="text-[8px] font-black text-rose-800/60 uppercase mb-2">Déficit Résiduel</p>
                        <p class="text-xl font-black text-rose-600">{{ number_format($remainingAmount, 0, ',', ' ') }} <small class="text-[10px]">XOF</small></p>
                    </div>
                </div>

                <div class="p-6 bg-purple-50 rounded-2xl border border-purple-100">
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-8">
                        <div>
                            <p class="text-[8px] font-black text-purple-800/40 uppercase mb-1">Durée Engagement</p>
                            <p class="text-xs font-black text-purple-900 uppercase italic">{{ $account->tontineAccount->cycle_duration_months }} Mois</p>
                        </div>
                        <div class="border-l border-purple-200 pl-8">
                            <p class="text-[8px] font-black text-purple-800/40 uppercase mb-1">Total Capitalisé (Vie)</p>
                            <p class="text-xs font-black text-emerald-600">{{ number_format($account->tontineAccount->total_paid, 0, ',', ' ') }} <small>XOF</small></p>
                        </div>
                        <div class="border-l border-purple-200 pl-8">
                            <p class="text-[8px] font-black text-purple-800/40 uppercase mb-1">Objectif Global</p>
                            <p class="text-xs font-black text-slate-900">{{ number_format($account->tontineAccount->total_expected, 0, ',', ' ') }} <small>XOF</small></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Diagnostic du compte d'Épargne -->
        <div class="bank-card overflow-hidden border-l-4 border-cyan-500 shadow-xl shadow-cyan-500/5">
            <div class="px-8 py-5 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-[10px] font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                    <i class="fas fa-vault text-cyan-600"></i> Configuration du compte d'Épargne Activa
                </h3>
            </div>
            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
                    <div class="space-y-1">
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Titulaire</p>
                        <p class="text-lg font-black text-slate-900 leading-tight">{{ $account->client->full_name }}</p>
                        <p class="text-[10px] font-mono font-bold text-blue-600 uppercase">{{ $account->client->client_number }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Identification compte</p>
                        <p class="text-lg font-black text-slate-800 font-mono tracking-widest uppercase">{{ $account->account_number }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Disponibilité Brute</p>
                        <p class="text-2xl font-black text-cyan-600">{{ number_format($account->balance, 0, ',', ' ') }} <small class="text-[10px]">XOF</small></p>
                    </div>
                </div>

                @if($account->savingsAccount)
                <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100 italic">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div>
                            <span class="text-[9px] font-black text-slate-400 uppercase">Rémunération :</span>
                            <span class="ml-2 text-xs font-black text-slate-900">{{ $account->savingsAccount->interest_rate }}% <small class="text-[8px] opacity-40 uppercase">Annuel</small></span>
                        </div>
                        <div class="border-l border-slate-200 pl-8">
                            <span class="text-[9px] font-black text-slate-400 uppercase">Seuil de Survie :</span>
                            <span class="ml-2 text-xs font-black text-slate-900">{{ number_format($account->savingsAccount->minimum_balance, 0, ',', ' ') }} <small class="text-[8px] opacity-40 uppercase">XOF</small></span>
                        </div>
                        <div class="border-l border-slate-200 pl-8">
                            <span class="text-[9px] font-black text-slate-400 uppercase">Tenue Mensuelle :</span>
                            <span class="ml-2 text-xs font-black text-slate-900">{{ number_format($account->savingsAccount->monthly_fee, 0, ',', ' ') }} <small class="text-[8px] opacity-40 uppercase">XOF</small></span>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Formulaire d'Exécution de l'Injection -->
    <div class="bank-card overflow-hidden">
        <div class="px-8 py-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-[10px] font-black text-slate-800 uppercase tracking-widest">Terminal de Traitement des Flux</h3>
            <i class="fas fa-microchip text-slate-200"></i>
        </div>

        <form action="{{ route('admin.accounts.deposit.process', $account->id) }}" method="POST" id="depositForm">
            @csrf

            <div class="p-8 space-y-10">
                <!-- Volume Financier -->
                <div class="space-y-4">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest flex items-center justify-between text-italic">
                        <span>Volume de l'Injection (XOF) *</span>
                        @if($account->account_type === 'tontine' && $activeCycle)
                            <span class="text-purple-600 font-bold tracking-tighter italic">Cible du Cycle : {{ number_format($suggestedAmount, 0, ',', ' ') }} XOF</span>
                        @endif
                    </label>
                    <div class="relative">
                        <input type="number" name="amount" id="amount" class="w-full bg-slate-50 border-2 border-slate-300 rounded-2xl px-6 py-5 text-4xl font-black text-slate-900 focus:ring-2 focus:ring-{{ $account->account_type === 'tontine' ? 'purple' : 'cyan' }}-500/20 focus:border-{{ $account->account_type === 'tontine' ? 'purple' : 'cyan' }}-500 outline-none transition-all" value="{{ old('amount', $suggestedAmount) }}" min="100" step="100" required>
                        <span class="absolute right-6 top-1/2 -translate-y-1/2 font-black text-slate-200">XOF</span>
                    </div>
                    @error('amount') <p class="text-[10px] font-bold text-rose-500 uppercase mt-2 italic px-2"><i class="fas fa-triangle-exclamation mr-1"></i> {{ $message }}</p> @enderror
                    
                    @if(!($account->account_type === 'tontine' && $activeCycle))
                        <p class="text-[9px] font-black text-slate-400 uppercase italic px-2 tracking-widest"><i class="fas fa-info-circle text-cyan-500 mr-1"></i> Seuil Minimum d'Arbitrage : 100 XOF</p>
                    @endif
                </div>

                <!-- Canaux de Règlement -->
                <div class="space-y-4">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Canal de Règlement du Flux *</label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        @foreach([
                            ['value' => 'cash', 'icon' => 'money-bill-wave', 'color' => 'emerald', 'label' => 'Espèces Fisiales'],
                            ['value' => 'mobile_money', 'icon' => 'mobile-screen', 'color' => 'blue', 'label' => 'Actif Numérique'],
                            ['value' => 'bank_transfer', 'icon' => 'building-columns', 'color' => 'purple', 'label' => 'Virement Interne']
                        ] as $method)
                        <label class="bank-card !p-5 relative cursor-pointer group transition-all duration-300 border-slate-100 hover:border-{{ $method['color'] }}-400 payment-method-label">
                            <input type="radio" name="payment_method" value="{{ $method['value'] }}" class="sr-only payment-method-radio" {{ old('payment_method', 'cash') === $method['value'] ? 'checked' : '' }} required>
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-{{ $method['color'] }}-50 flex items-center justify-center text-{{ $method['color'] }}-600 border border-{{ $method['color'] }}-100 group-hover:scale-110 transition-transform">
                                    <i class="fas fa-{{ $method['icon'] }} text-lg"></i>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-slate-800 uppercase leading-none">{{ $method['label'] }}</p>
                                    <p class="text-[8px] font-bold text-slate-400 uppercase mt-1.5 italic">Canal Sécurisé</p>
                                </div>
                            </div>
                            <i class="fas fa-circle-check text-{{ $method['color'] }}-500 text-sm absolute top-3 right-3 hidden check-icon"></i>
                        </label>
                        @endforeach
                    </div>
                </div>

                <!-- Paramètres Mobile Money (Conditionnel) -->
                <div id="mobile-money-operator-field" class="hidden animate-fade-in space-y-4">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Infrastructure de Liaison Numérique *</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <label class="bank-card !p-5 relative cursor-pointer group transition-all duration-300 border-slate-100 hover:border-rose-400 operator-label">
                            <input type="radio" name="mobile_money_operator" value="tmoney" class="sr-only operator-radio" {{ old('mobile_money_operator') === 'tmoney' ? 'checked' : '' }}>
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-rose-50 flex items-center justify-center text-rose-600 border border-rose-100 group-hover:rotate-6 transition-transform">
                                    <span class="font-black text-lg">T</span>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-slate-800 uppercase leading-none">Réseau TMoney</p>
                                    <p class="text-[8px] font-bold text-slate-400 uppercase mt-1.5 italic">Protocole Togocom</p>
                                </div>
                            </div>
                            <i class="fas fa-circle-check text-rose-500 text-sm absolute top-3 right-3 hidden operator-check-icon"></i>
                        </label>

                        <label class="bank-card !p-5 relative cursor-pointer group transition-all duration-300 border-slate-100 hover:border-orange-400 operator-label">
                            <input type="radio" name="mobile_money_operator" value="flooz" class="sr-only operator-radio" {{ old('mobile_money_operator') === 'flooz' ? 'checked' : '' }}>
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-orange-50 flex items-center justify-center text-orange-600 border border-orange-100 group-hover:rotate-6 transition-transform">
                                    <span class="font-black text-lg">F</span>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-slate-800 uppercase leading-none">Réseau Flooz</p>
                                    <p class="text-[8px] font-bold text-slate-400 uppercase mt-1.5 italic">Protocole Moov Africa</p>
                                </div>
                            </div>
                            <i class="fas fa-circle-check text-orange-500 text-sm absolute top-3 right-3 hidden operator-check-icon"></i>
                        </label>
                    </div>
                </div>

                <!-- Artefacts Documentaires -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Référence du Versement</label>
                        <input type="text" name="payment_reference" class="bank-input text-xs font-bold" placeholder="N° de Transaction Externe" value="{{ old('payment_reference') }}">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Annotation Docuemntaire</label>
                        <input type="text" name="description" class="bank-input text-xs font-bold" placeholder="Notes d'audit..." value="{{ old('description') }}">
                    </div>
                </div>

                <!-- Matrice de Récapitulation Finale -->
                <div class="bg-slate-900 rounded-3xl p-8 text-white space-y-6 shadow-2xl relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-{{ $account->account_type === 'tontine' ? 'purple' : 'cyan' }}-500 opacity-5 rounded-full -mr-32 -mt-32 blur-3xl"></div>
                    <h6 class="text-[10px] font-black text-white/40 uppercase tracking-[0.3em] flex items-center gap-2">
                        <i class="fas fa-microchip text-{{ $account->account_type === 'tontine' ? 'purple' : 'cyan' }}-400"></i> Simulation de l'Audit Post-Flux
                    </h6>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center text-[11px] font-bold">
                            <span class="text-white/40 uppercase tracking-widest">Identité du compte</span>
                            <span class="text-white uppercase">{{ $account->client->full_name }}</span>
                        </div>
                        <div class="flex justify-between items-center text-[11px] font-bold">
                            <span class="text-white/40 uppercase tracking-widest">Nature du Conteneur</span>
                            <span class="text-white uppercase">{{ $account->account_type === 'tontine' ? 'Mutuelle' : 'Épargne' }}</span>
                        </div>
                        <div class="flex justify-between items-center text-[11px] font-bold pt-4 border-t border-white/5">
                            <span class="text-white/40 uppercase tracking-widest">Exposition Initiale</span>
                            <span class="text-white/60" id="recap-balance-before">{{ number_format($account->balance, 0, ',', ' ') }} XOF</span>
                        </div>
                        <div class="flex justify-between items-center text-[11px] font-bold">
                            <span class="text-white/40 uppercase tracking-widest">Intrants à Injecter</span>
                            <span class="text-{{ $account->account_type === 'tontine' ? 'purple' : 'cyan' }}-400 text-lg" id="recap-amount">{{ number_format($suggestedAmount, 0, ',', ' ') }} XOF</span>
                        </div>
                        <div class="flex justify-between items-center pt-4 border-t border-white/10">
                            <span class="text-white/40 text-[11px] font-black uppercase tracking-widest">Consolidation Finale Attendue</span>
                            <span class="text-2xl font-black text-emerald-400 font-numeric" id="recap-new-balance">{{ number_format($account->balance + $suggestedAmount, 0, ',', ' ') }} XOF</span>
                        </div>
                        
                        @if($account->account_type === 'tontine' && $activeCycle)
                        <div class="pt-6 mt-6 border-t border-white/10 space-y-4">
                            <div class="flex justify-between items-center text-[11px] font-bold">
                                <span class="text-white/40 uppercase tracking-widest">Total Cycle Capitalisé</span>
                                <span class="text-emerald-500" id="recap-cycle-collected">{{ number_format($activeCycle->collected_amount + $suggestedAmount, 0, ',', ' ') }} XOF</span>
                            </div>
                            <div class="flex justify-between items-center text-[11px] font-bold">
                                <span class="text-white/40 uppercase tracking-widest">Reliquat de Cycle</span>
                                <span class="text-rose-400" id="recap-cycle-remaining">{{ number_format(max(0, $remainingAmount - $suggestedAmount), 0, ',', ' ') }} XOF</span>
                            </div>
                            <div id="completionMessage" class="hidden p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl animate-pulse text-center">
                                <p class="text-[10px] font-black text-emerald-400 uppercase tracking-widest">
                                    <i class="fas fa-check-double mr-2"></i> Saturation de l'Objectif de Cycle #{{ $activeCycle->cycle_number }} !
                                </p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Validation du Protocole -->
            <div class="flex flex-col md:flex-row px-8 py-6 gap-4 border-t border-slate-100 bg-slate-50/50">
                <button type="submit" class="flex-1 btn-bank btn-bank-primary !py-4 font-black text-xs uppercase shadow-xl shadow-{{ $account->account_type === 'tontine' ? 'purple' : 'cyan' }}-500/20 active:scale-95 transition-all">
                    <i class="fas fa-shield-check mr-2"></i> Certifier & Valider l'Injection
                </button>
                <a href="{{ route('admin.accounts.show', $account->id) }}" class="btn-bank btn-bank-outline !py-4 px-10 font-black text-xs uppercase text-center">
                    Révocquer l'Opération
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    const currentBalance = {{ $account->balance }};
    @if($account->account_type === 'tontine' && $activeCycle)
        const cycleCollected = {{ $activeCycle->collected_amount }};
        const cycleTarget = {{ $activeCycle->target_amount }};
        const remainingAmount = {{ $remainingAmount }};
    @endif

    const colorClass = '{{ $account->account_type === 'tontine' ? 'purple' : 'cyan' }}';

    document.querySelectorAll('.payment-method-radio').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.payment-method-label').forEach(label => {
                label.classList.remove(`border-${colorClass}-500`, `bg-${colorClass}-50/30`);
                label.classList.add('border-slate-100');
                label.querySelector('.check-icon').classList.add('hidden');
            });

            const selectedLabel = this.closest('.payment-method-label');
            selectedLabel.classList.remove('border-slate-100');
            selectedLabel.classList.add(`border-${colorClass}-500`, `bg-${colorClass}-50/30`);
            selectedLabel.querySelector('.check-icon').classList.remove('hidden');

            const mobileMoneyField = document.getElementById('mobile-money-operator-field');
            if (this.value === 'mobile_money') {
                mobileMoneyField.classList.remove('hidden');
            } else {
                mobileMoneyField.classList.add('hidden');
            }
        });
    });

    document.querySelectorAll('.operator-radio').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.operator-label').forEach(label => {
                label.classList.remove('border-red-500', 'bg-red-50/30', 'border-orange-500', 'bg-orange-50/30');
                label.classList.add('border-slate-100');
                label.querySelector('.operator-check-icon').classList.add('hidden');
            });

            const selectedLabel = this.closest('.operator-label');
            const isTmoney = this.value === 'tmoney';
            selectedLabel.classList.remove('border-slate-100');
            selectedLabel.classList.add(isTmoney ? 'border-red-500' : 'border-orange-500', isTmoney ? 'bg-red-50/30' : 'bg-orange-50/30');
            selectedLabel.querySelector('.operator-check-icon').classList.remove('hidden');
        });
    });

    const amountInput = document.getElementById('amount');
    const recapAmount = document.getElementById('recap-amount');
    const recapNewBalance = document.getElementById('recap-new-balance');

    @if($account->account_type === 'tontine' && $activeCycle)
        const recapCycleCollected = document.getElementById('recap-cycle-collected');
        const recapCycleRemaining = document.getElementById('recap-cycle-remaining');
        const completionMessage = document.getElementById('completionMessage');
    @endif

    function updateRecap() {
        const amount = parseFloat(amountInput.value) || 0;
        recapAmount.textContent = formatNumber(amount) + ' XOF';
        recapNewBalance.textContent = formatNumber(currentBalance + amount) + ' XOF';

        @if($account->account_type === 'tontine' && $activeCycle)
            const newCollected = cycleCollected + amount;
            const newRemaining = Math.max(0, cycleTarget - newCollected);
            recapCycleCollected.textContent = formatNumber(newCollected) + ' XOF';
            recapCycleRemaining.textContent = formatNumber(newRemaining) + ' XOF';
            if (completionMessage) {
                if (newCollected >= cycleTarget) completionMessage.classList.remove('hidden');
                else completionMessage.classList.add('hidden');
            }
        @endif
    }

    function formatNumber(num) {
        return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    }

    amountInput.addEventListener('input', updateRecap);

    document.addEventListener('DOMContentLoaded', function() {
        const checkedRadio = document.querySelector('.payment-method-radio:checked');
        if (checkedRadio) checkedRadio.dispatchEvent(new Event('change'));

        const checkedOperator = document.querySelector('.operator-radio:checked');
        if (checkedOperator) checkedOperator.dispatchEvent(new Event('change'));

        updateRecap();
    });
</script>
@endpush
@endsection
