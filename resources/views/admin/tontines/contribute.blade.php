@extends('layouts.app_admin')

@section('title', 'Initialisation de Cotisation')
@section('page-title', 'Opérations / Collecte Tontine')

@section('content')
<div class="max-w-4xl mx-auto space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex items-center justify-between no-print">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.tontines.show', $tontine->id) }}" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-xl flex items-center justify-center transition border border-slate-200 text-slate-600">
                <i class="fas fa-chevron-left text-xs"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Protocole de Cotisation</h2>
                <p class="text-slate-500 text-sm font-medium">Enregistrement d'un flux entrant pour le cycle actif</p>
            </div>
        </div>
    </div>

    <!-- Alertes de Validation -->
    @if ($errors->any())
        <div class="bank-card !border-rose-200 !bg-rose-50/50 p-6">
            <div class="flex gap-3">
                <i class="fas fa-triangle-exclamation text-rose-500 mt-1"></i>
                <div>
                    <h3 class="text-sm font-black text-rose-900 uppercase tracking-widest leading-none">Anomalies de Protocole</h3>
                    <ul class="mt-3 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li class="text-[11px] font-bold text-rose-700 list-disc list-inside">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <!-- Alerte Compte Suspendu -->
    @if($tontine->account->status === 'suspended')
        <div class="bank-card !border-rose-300 !bg-rose-50/50 p-6">
            <div class="flex items-start justify-between gap-4">
                <div class="flex gap-4">
                    <div class="w-12 h-12 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-ban text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-rose-900 uppercase tracking-tight mb-1">Compte Suspendu - Cotisation Bloquée</h3>
                        <p class="text-xs text-rose-700 font-medium mb-2">Ce compte est actuellement suspendu. Vous devez d'abord le réactiver pour pouvoir enregistrer une cotisation.</p>
                        @if($tontine->account->suspension_reason)
                            <p class="text-[10px] font-bold text-rose-600 bg-rose-100 px-3 py-1 rounded-lg inline-block">
                                <i class="fas fa-info-circle mr-1"></i>
                                Raison : {{ $tontine->account->suspension_reason }}
                            </p>
                        @endif
                    </div>
                </div>
                <form action="{{ route('admin.accounts.reactivate', $tontine->account->id) }}" method="POST" 
                      onsubmit="return confirm('Réactiver ce compte permettra à nouveau les cotisations. Continuer ?');">
                    @csrf
                    <button type="submit" class="btn-bank btn-bank-primary whitespace-nowrap">
                        <i class="fas fa-unlock mr-2 text-[10px]"></i>
                        <span>Réactiver</span>
                    </button>
                </form>
            </div>
        </div>
    @endif

    <!-- Informations du cycle actif -->
    <div class="bank-card p-6 border-l-4 border-purple-600 bg-purple-50/10">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center text-lg shadow-sm">
                    <i class="fas fa-rotate"></i>
                </div>
                <div>
                    <h5 class="text-sm font-black text-slate-900 uppercase tracking-tight">Cycle Actif #{{ $activeCycle->cycle_number }}</h5>
                    <p class="text-[10px] font-bold text-slate-500 uppercase mt-0.5">Période : {{ $activeCycle->start_date->format('d/m/Y') }} → {{ $activeCycle->end_date->format('d/m/Y') }}</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-[9px] font-bold text-slate-400 uppercase">Titulaire du compte</p>
                <p class="text-sm font-black text-slate-900">{{ $tontine->account->client->full_name }}</p>
                <p class="text-[10px] font-mono font-bold text-blue-600 uppercase tracking-tighter">{{ $tontine->account->account_number }}</p>
            </div>
        </div>

        @php
            $remainingAmount = $activeCycle->target_amount - $activeCycle->collected_amount;
            $progressPercent = $activeCycle->target_amount > 0
                ? round(($activeCycle->collected_amount / $activeCycle->target_amount) * 100, 2)
                : 0;
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm">
                <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Cible du Cycle</p>
                <p class="text-lg font-black text-slate-900">{{ number_format($activeCycle->target_amount, 0, ',', ' ') }} <small class="text-[10px]">XOF</small></p>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm">
                <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Capital Collecté</p>
                <p class="text-lg font-black text-purple-600">{{ number_format($activeCycle->collected_amount, 0, ',', ' ') }} <small class="text-[10px]">XOF</small></p>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm">
                <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Solde Restant</p>
                <p class="text-lg font-black text-amber-600">{{ number_format($remainingAmount, 0, ',', ' ') }} <small class="text-[10px]">XOF</small></p>
            </div>
        </div>

        <div>
            <div class="flex justify-between text-[10px] font-black uppercase mb-2">
                <span class="text-slate-500">Progression de Collecte</span>
                <span class="text-purple-600">{{ $progressPercent }}%</span>
            </div>
            <div class="w-full bg-slate-200 rounded-full h-2 shadow-inner">
                <div class="bg-gradient-to-r from-purple-500 to-purple-700 h-2 rounded-full transition-all duration-1000" style="width: {{ min($progressPercent, 100) }}%"></div>
            </div>
        </div>
    </div>

    <!-- Formulaire de cotisation -->
    <div class="bank-card overflow-hidden">
        <div class="px-8 py-5 border-b border-slate-100 bg-slate-50/50">
            <h5 class="text-sm font-black text-slate-800 uppercase tracking-tight">Paramètres du Flux Entrant</h5>
        </div>

        <form action="{{ route('admin.tontines.contribute', $tontine->id) }}" method="POST" class="p-8 space-y-8">
            @csrf

            <!-- Montant -->
            <div class="space-y-3">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Montant de la Cotisation *</label>
                <div class="relative">
                    <input type="number"
                           name="amount"
                           id="amount"
                           class="w-full pl-4 pr-16 py-3 bg-white border border-slate-200 rounded-xl text-lg font-black text-slate-900 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 outline-none transition"
                           placeholder="0"
                           value="{{ old('amount', $suggestedAmount) }}"
                           min="100"
                           required>
                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                        <span class="text-xs font-black text-slate-400 uppercase">XOF</span>
                    </div>
                </div>
                <div class="space-y-2">
                    <div class="flex items-center gap-2 text-[9px] font-bold uppercase">
                        <i class="fas fa-lightbulb text-amber-500"></i>
                        <span class="text-slate-500">Suggéré pour ce cycle : <span class="text-purple-600">{{ number_format($suggestedAmount, 0, ',', ' ') }} XOF</span></span>
                        <span class="text-slate-300">|</span>
                        <span class="text-slate-500">Reste : <span class="text-amber-600">{{ number_format($remainingAmount, 0, ',', ' ') }} XOF</span></span>
                    </div>
                    <div class="flex items-start gap-2 bg-blue-50 border border-blue-100 rounded-lg p-3">
                        <i class="fas fa-info-circle text-blue-600 mt-0.5"></i>
                        <div class="text-[10px] font-bold text-blue-700 leading-relaxed">
                            <p class="uppercase mb-1">💡 Paiements Anticipés Autorisés</p>
                            <p class="text-blue-600 font-medium normal-case">Vous pouvez payer plus que le montant suggéré. Le surplus sera automatiquement réparti sur les prochains cycles.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Méthode de paiement -->
            <div class="space-y-3">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Canal de Transaction *</label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <label class="relative flex items-center p-4 border rounded-xl cursor-pointer hover:bg-slate-50 transition-all group border-slate-200 bg-white">
                        <input type="radio" name="payment_method" value="cash" class="peer sr-only payment-method-radio" {{ old('payment_method') === 'cash' ? 'checked' : '' }} required>
                        <div class="flex items-center w-full">
                            <i class="fas fa-money-bill-wave text-xl text-emerald-500 mr-3 opacity-50 peer-checked:opacity-100 grayscale peer-checked:grayscale-0 transition-all"></i>
                            <div>
                                <p class="text-xs font-black text-slate-700 uppercase peer-checked:text-emerald-700 transition-colors">Espèces</p>
                                <p class="text-[9px] font-bold text-slate-400 uppercase">Guichet Physique</p>
                            </div>
                        </div>
                        <div class="absolute inset-0 border-2 border-transparent peer-checked:border-emerald-500 rounded-xl transition-all"></div>
                        <i class="fas fa-check-circle text-emerald-500 absolute top-2 right-2 opacity-0 peer-checked:opacity-100 transition-all transform scale-50 peer-checked:scale-100"></i>
                    </label>

                    <label class="relative flex items-center p-4 border rounded-xl cursor-pointer hover:bg-slate-50 transition-all group border-slate-200 bg-white">
                        <input type="radio" name="payment_method" value="mobile_money" class="peer sr-only payment-method-radio" {{ old('payment_method') === 'mobile_money' ? 'checked' : '' }} required>
                        <div class="flex items-center w-full">
                            <i class="fas fa-mobile-screen-button text-xl text-blue-500 mr-3 opacity-50 peer-checked:opacity-100 grayscale peer-checked:grayscale-0 transition-all"></i>
                            <div>
                                <p class="text-xs font-black text-slate-700 uppercase peer-checked:text-blue-700 transition-colors">Mobile Money</p>
                                <p class="text-[9px] font-bold text-slate-400 uppercase">TMoney / Flooz</p>
                            </div>
                        </div>
                        <div class="absolute inset-0 border-2 border-transparent peer-checked:border-blue-500 rounded-xl transition-all"></div>
                        <i class="fas fa-check-circle text-blue-500 absolute top-2 right-2 opacity-0 peer-checked:opacity-100 transition-all transform scale-50 peer-checked:scale-100"></i>
                    </label>

                    <label class="relative flex items-center p-4 border rounded-xl cursor-pointer hover:bg-slate-50 transition-all group border-slate-200 bg-white">
                        <input type="radio" name="payment_method" value="bank_transfer" class="peer sr-only payment-method-radio" {{ old('payment_method') === 'bank_transfer' ? 'checked' : '' }} required>
                        <div class="flex items-center w-full">
                            <i class="fas fa-building-columns text-xl text-purple-500 mr-3 opacity-50 peer-checked:opacity-100 grayscale peer-checked:grayscale-0 transition-all"></i>
                            <div>
                                <p class="text-xs font-black text-slate-700 uppercase peer-checked:text-purple-700 transition-colors">Virement</p>
                                <p class="text-[9px] font-bold text-slate-400 uppercase">Interbancaire</p>
                            </div>
                        </div>
                        <div class="absolute inset-0 border-2 border-transparent peer-checked:border-purple-500 rounded-xl transition-all"></div>
                        <i class="fas fa-check-circle text-purple-500 absolute top-2 right-2 opacity-0 peer-checked:opacity-100 transition-all transform scale-50 peer-checked:scale-100"></i>
                    </label>
                </div>
            </div>

            <!-- Opérateur Mobile Money (conditionnel) -->
            <div id="mobile-money-operator-field" class="hidden space-y-3 bg-slate-50 p-6 rounded-xl border border-slate-100">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Opérateur Réseau *</label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="relative flex items-center p-4 border rounded-xl cursor-pointer hover:bg-white transition-all group border-slate-200 bg-white/50">
                        <input type="radio" name="mobile_money_operator" value="tmoney" class="peer sr-only operator-radio" {{ old('mobile_money_operator') === 'tmoney' ? 'checked' : '' }}>
                        <div class="flex items-center w-full">
                            <div class="w-10 h-10 bg-amber-100 text-amber-600 rounded-lg flex items-center justify-center font-black text-lg mr-3">T</div>
                            <div>
                                <p class="text-xs font-black text-slate-700 uppercase">TMoney</p>
                                <p class="text-[9px] font-bold text-slate-400 uppercase">Togocom</p>
                            </div>
                        </div>
                        <div class="absolute inset-0 border-2 border-transparent peer-checked:border-amber-400 rounded-xl transition-all"></div>
                        <i class="fas fa-check-circle text-amber-400 absolute top-2 right-2 opacity-0 peer-checked:opacity-100 transition-all transform scale-50 peer-checked:scale-100"></i>
                    </label>

                    <label class="relative flex items-center p-4 border rounded-xl cursor-pointer hover:bg-white transition-all group border-slate-200 bg-white/50">
                        <input type="radio" name="mobile_money_operator" value="flooz" class="peer sr-only operator-radio" {{ old('mobile_money_operator') === 'flooz' ? 'checked' : '' }}>
                        <div class="flex items-center w-full">
                            <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center font-black text-lg mr-3">F</div>
                            <div>
                                <p class="text-xs font-black text-slate-700 uppercase">Flooz</p>
                                <p class="text-[9px] font-bold text-slate-400 uppercase">Moov Africa</p>
                            </div>
                        </div>
                        <div class="absolute inset-0 border-2 border-transparent peer-checked:border-blue-400 rounded-xl transition-all"></div>
                        <i class="fas fa-check-circle text-blue-400 absolute top-2 right-2 opacity-0 peer-checked:opacity-100 transition-all transform scale-50 peer-checked:scale-100"></i>
                    </label>
                </div>
            </div>

            <!-- Référence de paiement -->
            <div class="space-y-3">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Référence Externe (Optionnel)</label>
                <div class="relative">
                    <input type="text"
                           name="payment_reference"
                           class="w-full pl-4 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-800 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 outline-none transition font-mono uppercase"
                           placeholder="ID Transaction..."
                           value="{{ old('payment_reference') }}">
                    <i class="fas fa-barcode absolute right-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                </div>
            </div>

            <!-- Description -->
            <div class="space-y-3">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Notes d'Audit (Optionnel)</label>
                <textarea name="description"
                          rows="2"
                          class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-medium text-slate-700 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 outline-none transition"
                          placeholder="Observations ou contexte de la transaction...">{{ old('description') }}</textarea>
            </div>

            <!-- Récapitulatif -->
            <div class="bg-slate-50 border border-slate-100 rounded-xl p-6">
                <h6 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Simulation d'Impact</h6>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-500 uppercase">Solde Actuel</span>
                        <span class="text-xs font-mono font-bold text-slate-700">{{ number_format($tontine->account->balance, 0, ',', ' ') }} XOF</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-500 uppercase">Apport Net</span>
                        <span class="text-xs font-mono font-black text-emerald-600">+ <span id="recap-amount">{{ number_format($suggestedAmount, 0, ',', ' ') }}</span> XOF</span>
                    </div>
                    <div class="border-t border-slate-200 pt-3 flex justify-between items-center">
                        <span class="text-xs font-black text-slate-800 uppercase">Nouveau Solde Projeté</span>
                        <span class="text-sm font-mono font-black text-slate-900"><span id="recap-new-balance">{{ number_format($tontine->account->balance + $suggestedAmount, 0, ',', ' ') }}</span> XOF</span>
                    </div>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="flex items-center justify-end gap-4 pt-4 border-t border-slate-100">
                <a href="{{ route('admin.tontines.show', $tontine->id) }}" class="btn-bank btn-bank-outline px-8">
                    Annuler
                </a>
                <button type="submit" class="btn-bank btn-bank-primary px-8 py-3 w-full md:w-auto shadow-lg shadow-purple-500/20">
                    <i class="fas fa-check-circle mr-2 text-xs"></i>
                    Valider le Protocole
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    // Gestion de la sélection mobile money
    document.querySelectorAll('.payment-method-radio').forEach(radio => {
        radio.addEventListener('change', function() {
            const operatorField = document.getElementById('mobile-money-operator-field');
            if (this.value === 'mobile_money') {
                operatorField.classList.remove('hidden');
                // Réinitialiser les opérateurs
                document.querySelectorAll('.operator-radio').forEach(r => r.checked = false);
            } else {
                operatorField.classList.add('hidden');
            }
        });
    });

    // Mise à jour du récapitulatif en temps réel
    const amountInput = document.getElementById('amount');
    const currentBalance = {{ $tontine->account->balance }};

    amountInput.addEventListener('input', function() {
        const amount = parseFloat(this.value) || 0;
        const newBalance = currentBalance + amount;

        document.getElementById('recap-amount').textContent = amount.toLocaleString('fr-FR').replace(/\s/g, ' ');
        document.getElementById('recap-new-balance').textContent = newBalance.toLocaleString('fr-FR').replace(/\s/g, ' ');
    });

    // Initialiser l'état au chargement
    document.addEventListener('DOMContentLoaded', function() {
        const checkedPaymentMethod = document.querySelector('.payment-method-radio:checked');
        if (checkedPaymentMethod) {
            checkedPaymentMethod.dispatchEvent(new Event('change'));
        }
    });
</script>
@endpush
@endsection
