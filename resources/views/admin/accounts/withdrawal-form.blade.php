@extends('layouts.app_admin')

@section('title', 'Terminal de Retrait')
@section('page-title', 'Trésorerie / Opérations de Débit')

@section('content')
<div class="max-w-4xl mx-auto space-y-8">
    <!-- En-tête -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.accounts.show', $account->id) }}" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-xl flex items-center justify-center transition border border-slate-200 text-slate-600">
                <i class="fas fa-arrow-left text-sm"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Paramétrer le Retrait</h2>
                <p class="text-slate-500 text-sm font-medium">Compte : <span class="font-mono text-slate-800 font-bold tracking-widest">{{ $account->account_number }}</span></p>
            </div>
        </div>
        <div>
             @if($account->account_type === 'savings')
                <span class="px-3 py-1 bg-blue-50 text-blue-700 text-[10px] font-black rounded-full border border-blue-100 uppercase tracking-widest">
                    <i class="fas fa-piggy-bank mr-1"></i> Compte Épargne
                </span>
            @else
                <span class="px-3 py-1 bg-purple-50 text-purple-700 text-[10px] font-black rounded-full border border-purple-100 uppercase tracking-widest">
                    <i class="fas fa-hand-holding-usd mr-1"></i> Compte Tontine
                </span>
            @endif
        </div>
    </div>

    <form action="{{ route('admin.accounts.withdrawal.process', $account->id) }}" method="POST" id="withdrawalForm" class="space-y-8">
        @csrf
        
        <!-- Carte Information Solde -->
        <div class="bank-card p-8 border-l-4 border-l-blue-500 shadow-xl">
            <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Titulaire du Compte</p>
                    <h3 class="text-xl font-bold text-slate-900">{{ $account->client->name }}</h3>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Capacité de retrait actuelle</p>
                    <p class="text-3xl font-black text-emerald-600 font-mono tracking-tight">{{ number_format($maxWithdrawal, 0, ',', ' ') }} <span class="text-sm text-emerald-800">XOF</span></p>
                    <p class="text-[10px] font-bold text-slate-400 mt-2 uppercase tracking-wide">Solde Disponible : {{ number_format($account->balance - $minimumBalance, 0, ',', ' ') }} XOF</p>
                </div>
            </div>
            
            <div class="mt-6 pt-6 border-t border-slate-100 flex gap-6 text-xs text-slate-500">
                <div class="flex item-center gap-2">
                    <i class="fas fa-info-circle text-blue-500"></i>
                    <span>Frais appliqués : <strong>{{ $feeLabel }}</strong></span>
                </div>
                @if($minimumBalance > 0)
                <div class="flex item-center gap-2">
                    <i class="fas fa-lock text-rose-500"></i>
                    <span>Réserve minimale bloquée : <strong>{{ number_format($minimumBalance, 0, ',', ' ') }} XOF</strong></span>
                </div>
                @endif
            </div>
        </div>

        <!-- Section Saisie -->
        <div class="bank-card p-8">
            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-8 border-b border-slate-100 pb-4">Configuration de la Transaction</h3>
            
            <div class="space-y-8">
                <!-- Montant -->
                <div class="space-y-4">
                     <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide">Montant à remettre au client</label>
                     <div class="relative">
                        <input type="number" name="amount" id="amount" 
                               class="w-full bg-slate-50 border-2 border-slate-200 rounded-xl px-6 py-5 text-4xl font-black text-slate-900 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition font-mono placeholder:text-slate-300" 
                               placeholder="0" min="100" max="{{ $maxWithdrawal }}" step="100" required>
                        <span class="absolute right-6 top-1/2 -translate-y-1/2 font-black text-slate-400">XOF</span>
                     </div>
                     <div id="amountError" class="hidden text-[10px] font-bold text-rose-600 uppercase mt-2">
                        <i class="fas fa-exclamation-triangle mr-1"></i> Le montant dépasse la capacité du compte.
                     </div>
                </div>

                <!-- Moyen de Paiement -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">Moyen de Paiement</label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach([
                            ['value' => 'cash', 'icon' => 'money-bill-1', 'label' => 'Espèces'],
                            ['value' => 'mobile_money', 'icon' => 'mobile-screen', 'label' => 'Mobile Money'],
                            ['value' => 'bank_transfer', 'icon' => 'building-columns', 'label' => 'Virement']
                        ] as $method)
                        <label class="cursor-pointer">
                            <input type="radio" name="payment_method" value="{{ $method['value'] }}" class="peer sr-only payment-method-radio" {{ $loop->first ? 'checked' : '' }}>
                            <div class="p-4 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700 transition flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-slate-100 peer-checked:bg-white flex items-center justify-center text-lg">
                                    <i class="fas fa-{{ $method['icon'] }}"></i>
                                </div>
                                <span class="text-xs font-bold uppercase">{{ $method['label'] }}</span>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>
                
                 <!-- Opérateurs Mobile Money -->
                <div id="mobile-money-operator-field" class="hidden p-6 bg-slate-50 rounded-xl border border-slate-200 animate-fade-in">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-3">Sélectionnez l'opérateur</label>
                    <div class="flex gap-4">
                        @foreach([
                            ['value' => 'tmoney', 'label' => 'TMoney (Togocom)', 'color' => 'amber'],
                            ['value' => 'flooz', 'label' => 'Flooz (Moov)', 'color' => 'blue']
                        ] as $op)
                        <label class="flex-1 cursor-pointer group">
                            <input type="radio" name="mobile_money_operator" value="{{ $op['value'] }}" class="peer sr-only">
                            <div class="p-4 rounded-xl border border-slate-200 bg-white text-center peer-checked:border-{{ $op['color'] }}-500 peer-checked:ring-1 peer-checked:ring-{{ $op['color'] }}-500 transition group-hover:bg-slate-50">
                                <span class="block text-xs font-bold uppercase text-slate-700">{{ $op['label'] }}</span>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>

                <!-- Info Bénéficiaire -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-slate-100">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Bénéficiaire</label>
                        <input type="text" name="recipient_name" value="{{ $account->client->name }}" class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-sm font-bold text-slate-900 focus:ring-2 focus:ring-blue-500 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Motif (Optionnel)</label>
                        <input type="text" name="description" class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-sm font-medium text-slate-900 focus:ring-2 focus:ring-blue-500 outline-none transition" placeholder="Retrait guichet...">
                    </div>
                </div>
            </div>
        </div>

        <!-- Simulation Financière -->
        <div class="bg-slate-900 rounded-2xl p-8 text-white shadow-2xl">
            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6">Simulation Avant Validation</h4>
            <div class="space-y-4">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-400">Montant net client</span>
                    <span id="sim-amount" class="font-bold">0 XOF</span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-400">Commission (<span id="sim-fee-label">{{ $feeLabel }}</span>)</span>
                    <span id="sim-fees" class="font-bold text-rose-400">+ 0 XOF</span>
                </div>
                <div class="h-px bg-white/10 my-4"></div>
                <div class="flex justify-between items-center text-lg">
                    <span class="font-black uppercase tracking-wide text-slate-300">Total Débit Compte</span>
                    <span id="sim-total" class="font-black text-white">0 XOF</span>
                </div>
            </div>
            
            <div class="mt-8 flex gap-4">
                 <button type="submit" id="submitBtn" class="flex-1 bg-rose-600 hover:bg-rose-700 text-white font-black uppercase text-xs py-4 rounded-xl shadow-lg shadow-rose-900/50 transition transform active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                    Confirmer le Retrait
                </button>
                 <a href="{{ route('admin.accounts.show', $account->id) }}" class="px-8 py-4 bg-white/10 hover:bg-white/20 text-white font-bold uppercase text-xs rounded-xl transition">
                    Annuler
                </a>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const amountInput = document.getElementById('amount');
        const submitBtn = document.getElementById('submitBtn');
        const maxWithdrawal = {{ $maxWithdrawal }};
        const feePercent = {{ $feePercentage }};
        const feeFixed = {{ $feeFixed }};

        // Mobile Money Toggle
        document.querySelectorAll('.payment-method-radio').forEach(radio => {
            radio.addEventListener('change', (e) => {
                const mmField = document.getElementById('mobile-money-operator-field');
                if (e.target.value === 'mobile_money') {
                    mmField.classList.remove('hidden');
                } else {
                    mmField.classList.add('hidden');
                    document.querySelectorAll('input[name="mobile_money_operator"]').forEach(el => el.checked = false);
                }
            });
        });

        // Calcul en temps réel
        amountInput.addEventListener('input', () => {
            const amount = parseFloat(amountInput.value) || 0;
            let fees = 0;
            
            @if($account->account_type === 'tontine')
                const mise = {{ $account->tontineAccount->tontine_amount ?? 0 }};
                const freq = "{{ $account->tontineAccount->payment_frequency ?? '' }}";
                
                if (freq === 'daily') {
                    const nbCommissions = Math.ceil(amount / (mise || 1) / 31);
                    fees = nbCommissions * mise;
                    document.getElementById('sim-fee-label').innerText = "Règle 1/31 (" + nbCommissions + " mise(s))";
                } else if (freq === 'weekly') {
                    const nbCommissions = Math.ceil(amount / (mise || 1) / 52);
                    fees = nbCommissions * mise;
                    document.getElementById('sim-fee-label').innerText = "Règle 1/52 (" + nbCommissions + " mise(s))";
                } else {
                    fees = mise;
                    document.getElementById('sim-fee-label').innerText = "Forfait 1 mise";
                }
            @else
                fees = Math.round((amount * (feePercent / 100)) + feeFixed);
            @endif

            const total = amount + fees;

            document.getElementById('sim-amount').innerText = formatCurrency(amount);
            document.getElementById('sim-fees').innerText = '+ ' + formatCurrency(fees);
            document.getElementById('sim-total').innerText = formatCurrency(total);

            // Validation
            const errorDiv = document.getElementById('amountError');
            if (total > {{ $account->balance }}) { // Le débit total ne doit pas dépasser le solde
                errorDiv.classList.remove('hidden');
                errorDiv.innerText = "Solde insuffisant (Débit total requis: " + formatCurrency(total) + ")";
                submitBtn.disabled = true;
                amountInput.classList.add('border-rose-500', 'text-rose-600');
            } else if (amount > maxWithdrawal) {
                errorDiv.classList.remove('hidden');
                errorDiv.innerText = "Le montant dépasse la capacité recommandée.";
                submitBtn.disabled = false; // On l'autorise quand même si c'est admin mais warning
                amountInput.classList.add('border-rose-500', 'text-rose-600');
            } else {
                errorDiv.classList.add('hidden');
                submitBtn.disabled = (amount <= 0);
                amountInput.classList.remove('border-rose-500', 'text-rose-600');
            }
        });

        function formatCurrency(val) {
            return new Intl.NumberFormat('fr-FR').format(val) + ' XOF';
        }
        
        // Init state
        submitBtn.disabled = true;
    });
</script>
@endpush
@endsection
