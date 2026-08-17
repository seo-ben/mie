@extends('layouts.app_admin')

@section('title', 'Initialisation d\'un Nouveau compte d\'Actifs')
@section('page-title', 'Protocole / Création de Compte')

@section('content')
<div class="space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 no-print">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.clients.show', $client->id) }}" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-xl flex items-center justify-center transition border border-slate-200 text-slate-600">
                <i class="fas fa-chevron-left text-xs"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Initialisation de compte d'Actifs</h2>
                <p class="text-slate-500 text-sm font-medium">Création d'un nouveau conteneur financier pour <span class="font-black text-slate-900">{{ $client->full_name }}</span></p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Signalétique de l'Adhérent -->
        <div class="lg:col-span-4 space-y-6">
            <div class="bank-card p-6">
                <h3 class="text-[10px] font-black text-slate-800 uppercase tracking-widest mb-6 border-b border-slate-100 pb-2">Tutelle Adhérente</h3>
                <div class="flex flex-col items-center mb-6">
                    @if($client->profile_photo_url)
                        <img src="{{ Storage::url($client->profile_photo_url) }}" class="w-24 h-24 rounded-2xl object-cover border-4 border-white shadow-xl mb-4">
                    @else
                        <div class="w-24 h-24 bg-slate-900 rounded-2xl flex items-center justify-center text-white font-black text-3xl shadow-xl mb-4">
                            {{ substr($client->first_name, 0, 1) }}{{ substr($client->last_name, 0, 1) }}
                        </div>
                    @endif
                    <h4 class="text-lg font-black text-slate-900">{{ $client->full_name }}</h4>
                    <span class="text-[10px] font-mono font-bold text-blue-600 uppercase tracking-widest mt-1">{{ $client->client_number }}</span>
                </div>

                <div class="space-y-3">
                    <div class="flex justify-between items-center text-[10px]">
                        <span class="font-bold text-slate-400 uppercase">Signalétique</span>
                        <span class="font-black text-slate-800">{{ $client->phone }}</span>
                    </div>
                    <div class="flex justify-between items-center text-[10px]">
                        <span class="font-bold text-slate-400 uppercase">Conformité KYC</span>
                        <span class="bank-badge {{ $client->client_kyc_status === 'approved' ? 'badge-success' : 'badge-warning' }} !text-[8px] uppercase font-black">
                            {{ $client->kyc_status === 'approved' ? 'Vérifié' : 'En Audit' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center text-[10px]">
                        <span class="font-bold text-slate-400 uppercase">comptes Existants</span>
                        <span class="font-black text-slate-800">{{ $client->accounts->count() }}</span>
                    </div>
                </div>
            </div>

            @if($client->accounts->count() > 0)
            <div class="bank-card p-6">
                <h3 class="text-[10px] font-black text-slate-800 uppercase tracking-widest mb-4 border-b border-slate-100 pb-2">Inventaire des comptes</h3>
                <div class="space-y-3">
                    @foreach($client->accounts as $existingAccount)
                    <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <div>
                            <p class="text-[10px] font-black text-slate-900 leading-none">{{ $existingAccount->account_number }}</p>
                            <p class="text-[8px] font-bold text-slate-400 uppercase mt-1">{{ $existingAccount->account_type === 'savings' ? 'Épargne' : 'Tontine' }}</p>
                        </div>
                        <span class="w-2 h-2 rounded-full {{ $existingAccount->status === 'active' ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Configuration du Nouveau compte -->
        <div class="lg:col-span-8">
            <form method="POST" action="{{ route('admin.accounts.store', $client->id) }}" class="space-y-8">
                @csrf

                <!-- Sélection de la Nature de l'Actif -->
                <div class="bank-card p-8">
                    <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest mb-6 border-b border-slate-100 pb-2">Nature de l'Actif Financier</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="relative group">
                            <input type="radio" name="account_type" id="savings" value="savings" class="peer hidden" {{ !$hasSavingsAccount ? 'checked' : 'disabled' }} required>
                            <label for="savings" class="block p-6 rounded-2xl border-2 {{ !$hasSavingsAccount ? 'border-slate-100 bg-slate-50/30 cursor-pointer peer-checked:border-blue-600 peer-checked:bg-blue-50/30 group-hover:border-blue-200' : 'border-slate-50 bg-slate-50/10 opacity-40 cursor-not-allowed' }} transition-all">
                                <div class="flex items-center gap-4 mb-3">
                                    <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-xl">
                                        <i class="fas fa-vault"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-black text-slate-900 uppercase">Épargne Institutionnelle</h4>
                                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">compte Unique de Trésorerie</p>
                                    </div>
                                </div>
                                <p class="text-[11px] font-medium text-slate-600 leading-relaxed mb-4 italic">Compte de réserve liquide avec génération d'intérêts mensuels.</p>
                                <div class="text-[10px] font-black text-blue-700 uppercase flex items-center gap-2">
                                    <i class="fas fa-tag"></i> Redevance d'Initialisation : {{ number_format($savingsActivationFee, 0, ',', ' ') }} XOF
                                </div>
                                @if($hasSavingsAccount)
                                    <div class="mt-4 p-2 bg-amber-50 rounded-lg border border-amber-100 text-[8px] font-black text-amber-700 uppercase text-center">Protocol Unique Déjà Actif</div>
                                @endif
                            </label>
                        </div>

                        <div class="relative group">
                            <input type="radio" name="account_type" id="tontine" value="tontine" class="peer hidden" {{ $hasSavingsAccount ? 'checked' : '' }} required>
                            <label for="tontine" class="block p-6 rounded-2xl border-2 border-slate-100 bg-slate-50/30 cursor-pointer peer-checked:border-purple-600 peer-checked:bg-purple-50/30 group-hover:border-purple-200 transition-all">
                                <div class="flex items-center gap-4 mb-3">
                                    <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center text-xl">
                                        <i class="fas fa-rotate"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-black text-slate-900 uppercase">Épargne Mutuelle (Tontine)</h4>
                                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">comptes de Cycles Flexibles</p>
                                    </div>
                                </div>
                                <p class="text-[11px] font-medium text-slate-600 leading-relaxed mb-4 italic">Système de cotisations régulières avec objectifs de capitalisation.</p>
                                <div class="text-[10px] font-black text-purple-700 uppercase flex items-center gap-2">
                                    <i class="fas fa-tag"></i> Frais de Carnet : {{ number_format($tontineCarnetFee, 0, ',', ' ') }} XOF
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Configuration : Épargne (Conditionnel) -->
                <div id="savings-config" class="bank-card overflow-hidden transition-all duration-500 hidden transform scale-95 opacity-0">
                    <div class="px-8 py-5 bg-blue-600 text-white flex items-center justify-between">
                        <h3 class="text-[10px] font-black uppercase tracking-[0.2em] flex items-center gap-2">
                            <i class="fas fa-sliders"></i> Configuration du compte d'Épargne
                        </h3>
                    </div>
                    <div class="p-8 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Taux d'Intérêt (%) *</label>
                                <input type="number" step="0.01" name="interest_rate" value="2.5" class="bank-input font-bold" min="0" max="100">
                                <p class="text-[8px] font-bold text-slate-400 uppercase">Valeur Standard : 2.5%</p>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Solde de Survie (Min) *</label>
                                <input type="number" name="minimum_balance" value="5000" class="bank-input font-bold" min="0">
                                <p class="text-[8px] font-bold text-slate-400 uppercase">Valeur Standard : 5,000 XOF</p>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Redevance de Maintenance *</label>
                                <input type="number" name="monthly_fee" value="500" class="bank-input font-bold" min="0">
                                <p class="text-[8px] font-bold text-slate-400 uppercase">Valeur Standard : 500 XOF</p>
                            </div>
                        </div>
                        <div class="p-4 bg-blue-50 rounded-xl border border-blue-100 flex items-start gap-4">
                            <i class="fas fa-circle-info text-blue-500 mt-0.5"></i>
                            <p class="text-[9px] font-bold text-blue-800 uppercase leading-snug tracking-tight">Le calcul des intérêts sera automatisé mensuellement sur la base du solde moyen pondéré du compte.</p>
                        </div>
                    </div>
                </div>

                <!-- Configuration : Tontine (Conditionnel) -->
                <div id="tontine-config" class="bank-card overflow-hidden transition-all duration-500 hidden transform scale-95 opacity-0">
                    <div class="px-8 py-5 bg-purple-600 text-white flex items-center justify-between">
                        <h3 class="text-[10px] font-black uppercase tracking-[0.2em] flex items-center gap-2">
                            <i class="fas fa-rotate"></i> Configuration du Protocole Tontine
                        </h3>
                    </div>
                    <div class="p-8 space-y-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Charge de Cotisation (XOF) *</label>
                                <input type="number" name="target_amount" id="target_amount" value="5000" class="bank-input font-black text-blue-600 text-lg" min="200">
                                <p class="text-[8px] font-bold text-slate-400 uppercase leading-tight">Volume de liquide à injecter par cycle périodique</p>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Durée de l'Engagement (Mois) *</label>
                                <select name="cycle_duration_months" id="cycle_duration_months" class="bank-input uppercase font-bold">
                                    <option value="1">01 Mois (Cycle Éclair)</option>
                                    <option value="3">03 Mois (Trimestre)</option>
                                    <option value="6">06 Mois (Semestre)</option>
                                    <option value="12" selected>12 Mois (Cycle Annuel)</option>
                                    <option value="18">18 Mois (Cycle Long)</option>
                                    <option value="24">24 Mois (Cycle Majeur)</option>
                                </select>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest block">Fréquence des Flux d'Injection *</label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <label class="flex flex-col p-4 bg-slate-50 border-2 border-slate-100 rounded-2xl cursor-pointer hover:border-purple-200 transition-all frequency-option relative overflow-hidden group">
                                    <input type="radio" name="payment_frequency" value="daily" class="peer hidden">
                                    <div class="absolute inset-0 bg-purple-600 opacity-0 peer-checked:opacity-5 transition-opacity"></div>
                                    <span class="text-xs font-black text-slate-900 uppercase peer-checked:text-purple-600">Quotidiens</span>
                                    <span class="text-[8px] font-bold text-slate-400 uppercase mt-1">Audit Journalier (31x / mois)</span>
                                    <div class="absolute top-4 right-4 text-purple-600 opacity-0 peer-checked:opacity-100 transition-opacity">
                                        <i class="fas fa-circle-check"></i>
                                    </div>
                                </label>
                                <label class="flex flex-col p-4 bg-slate-50 border-2 border-slate-100 rounded-2xl cursor-pointer hover:border-purple-200 transition-all frequency-option relative overflow-hidden group">
                                    <input type="radio" name="payment_frequency" value="weekly" class="peer hidden">
                                    <div class="absolute inset-0 bg-purple-600 opacity-0 peer-checked:opacity-5 transition-opacity"></div>
                                    <span class="text-xs font-black text-slate-900 uppercase peer-checked:text-purple-600">Hebdomadaires</span>
                                    <span class="text-[8px] font-bold text-slate-400 uppercase mt-1">Audit Hebdomadaire (52x / an)</span>
                                    <div class="absolute top-4 right-4 text-purple-600 opacity-0 peer-checked:opacity-100 transition-opacity">
                                        <i class="fas fa-circle-check"></i>
                                    </div>
                                </label>
                                <label class="flex flex-col p-4 bg-slate-50 border-2 border-slate-100 rounded-2xl cursor-pointer hover:border-purple-200 transition-all frequency-option relative overflow-hidden group">
                                    <input type="radio" name="payment_frequency" value="monthly" checked class="peer hidden">
                                    <div class="absolute inset-0 bg-purple-600 opacity-0 peer-checked:opacity-5 transition-opacity"></div>
                                    <span class="text-xs font-black text-slate-900 uppercase peer-checked:text-purple-600">Mensuels</span>
                                    <span class="text-[8px] font-bold text-slate-400 uppercase mt-1">Audit Lunaire (1x)</span>
                                    <div class="absolute top-4 right-4 text-purple-600 opacity-0 peer-checked:opacity-100 transition-opacity">
                                        <i class="fas fa-circle-check"></i>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Récapitulatif Analytique -->
                        <div class="p-8 bg-slate-900 rounded-3xl text-white relative overflow-hidden shadow-2xl">
                            <div class="absolute top-0 right-0 w-64 h-64 bg-purple-500 opacity-10 rounded-full -mr-32 -mt-32 blur-3xl"></div>
                            <h4 class="text-[9px] font-black text-white/50 uppercase tracking-[0.3em] mb-6 flex items-center gap-2">
                                <i class="fas fa-calculator text-purple-400"></i> Simulation du Protocole de Capitalisation
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                                <div>
                                    <p class="text-[8px] font-bold text-white/40 uppercase tracking-widest mb-1">Itérations de Flux</p>
                                    <p class="text-3xl font-black text-white" id="total-periods">12</p>
                                    <p class="text-[9px] font-bold text-purple-400 uppercase mt-1 tracking-tighter" id="period-unit">Mensuels</p>
                                </div>
                                <div>
                                    <p class="text-[8px] font-bold text-white/40 uppercase tracking-widest mb-1">Volume par Itération</p>
                                    <p class="text-3xl font-black text-white" id="per-period">5 000</p>
                                    <p class="text-[9px] font-bold text-blue-400 uppercase mt-1 tracking-tighter">XOF / Cycle</p>
                                </div>
                                <div>
                                    <p class="text-[8px] font-bold text-white/40 uppercase tracking-widest mb-1">Cible de Liquidation</p>
                                    <p class="text-3xl font-black text-emerald-400" id="total-expected">60 000</p>
                                    <p class="text-[9px] font-bold text-emerald-500 uppercase mt-1 tracking-tighter">XOF Brut Final</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions de Validation de Protocole -->
                <div class="bank-card p-6 border-trust no-print">
                    <div class="flex items-center justify-between">
                        <a href="{{ route('admin.clients.show', $client->id) }}" class="btn-bank btn-bank-outline px-10">
                            Abandonner l'Initialisation
                        </a>
                        <button type="submit" class="btn-bank btn-bank-primary px-12 py-3 text-sm shadow-xl shadow-blue-500/20">
                            Initialiser le compte dans le Grand Livre
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.frequency-option input:checked + div + span { color: #9333ea !important; }
.frequency-option input:checked + div + span + span { color: #a855f7 !important; }
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const savingsRadio = document.getElementById('savings');
    const tontineRadio = document.getElementById('tontine');
    const savingsConfig = document.getElementById('savings-config');
    const tontineConfig = document.getElementById('tontine-config');

    function updateDisplay(animate = true) {
        if (savingsRadio.checked) {
            savingsConfig.classList.remove('hidden');
            setTimeout(() => {
                savingsConfig.classList.remove('opacity-0', 'scale-95');
                savingsConfig.classList.add('opacity-100', 'scale-100');
            }, 10);
            tontineConfig.classList.add('hidden');
            tontineConfig.classList.add('opacity-0', 'scale-95');
        } else if (tontineRadio.checked) {
            tontineConfig.classList.remove('hidden');
            setTimeout(() => {
                tontineConfig.classList.remove('opacity-0', 'scale-95');
                tontineConfig.classList.add('opacity-100', 'scale-100');
            }, 10);
            savingsConfig.classList.add('hidden');
            savingsConfig.classList.add('opacity-0', 'scale-95');
            updateTontineSummary();
        }
    }

    savingsRadio.addEventListener('change', updateDisplay);
    tontineRadio.addEventListener('change', updateDisplay);

    updateDisplay(false);
});

function updateTontineSummary() {
    const targetAmount = parseFloat(document.getElementById('target_amount').value) || 0;
    const durationMonths = parseInt(document.getElementById('cycle_duration_months').value) || 12;
    const frequency = document.querySelector('input[name="payment_frequency"]:checked')?.value || 'monthly';

    let totalPeriods = 0;
    let frequencyText = '';

    switch(frequency) {
        case 'daily':
            totalPeriods = durationMonths * 31;
            frequencyText = 'Jours';
            break;
        case 'weekly':
            totalPeriods = Math.round((durationMonths * 52) / 12);
            frequencyText = 'Semaines';
            break;
        case 'monthly':
            totalPeriods = durationMonths;
            frequencyText = 'Mois';
            break;
    }

    const totalExpected = targetAmount * totalPeriods;

    document.getElementById('total-periods').textContent = totalPeriods;
    document.getElementById('per-period').textContent = targetAmount.toLocaleString('fr-FR');
    document.getElementById('total-expected').textContent = totalExpected.toLocaleString('fr-FR');
    document.getElementById('period-unit').textContent = frequencyText;
}

document.getElementById('target_amount')?.addEventListener('input', updateTontineSummary);
document.getElementById('cycle_duration_months')?.addEventListener('change', updateTontineSummary);
document.querySelectorAll('input[name="payment_frequency"]').forEach(radio => {
    radio.addEventListener('change', updateTontineSummary);
});
</script>
@endpush
@endsection
