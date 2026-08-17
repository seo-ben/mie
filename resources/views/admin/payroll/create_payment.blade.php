@extends('layouts.app_admin')

@section('title', 'Ordre de Paiement')
@section('page-title', 'Émission d\'un Titre de Paiement Personnel')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bank-card overflow-hidden">
        <div class="bg-slate-900 px-8 py-10 text-white relative">
            <div class="absolute top-0 right-0 p-6 opacity-10">
                <i class="fas fa-hand-holding-dollar text-9xl"></i>
            </div>
            
            <div class="relative flex items-center gap-6">
                <div class="w-20 h-20 rounded-2xl bg-white/10 flex items-center justify-center text-3xl font-black border border-white/20">
                    {{ substr($user->first_name, 0, 1) }}{{ substr($user->last_name, 0, 1) }}
                </div>
                <div>
                    <h2 class="text-3xl font-black uppercase tracking-tight">{{ $user->full_name }}</h2>
                    <p class="text-blue-300 font-bold uppercase text-xs tracking-widest mt-1">{{ str_replace('_', ' ', $user->role) }} — {{ $user->agency->name ?? 'Siège' }}</p>
                </div>
            </div>
        </div>

        <div class="p-8">
            <form action="{{ route('admin.payroll.store-payment') }}" method="POST" class="space-y-8">
                @csrf
                <input type="hidden" name="user_id" value="{{ $user->id }}">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Section Montant -->
                    <div class="space-y-4">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">Allocation Financière</label>
                        <div class="relative">
                            <input type="number" name="amount" value="{{ $user->base_salary }}" step="0.01" required
                                class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-6 py-4 text-3xl font-black text-slate-900 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 outline-none transition-all">
                            <span class="absolute right-6 top-1/2 -translate-y-1/2 text-slate-400 font-bold">XOF</span>
                        </div>
                        <p class="text-[10px] text-slate-400 italic">Montant de référence basé sur le contrat : {{ number_format($user->base_salary ?? 0, 0, ',', ' ') }} XOF</p>
                    </div>

                    <!-- Section Type & Méthode -->
                    <div class="space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">Type d'Attribution</label>
                                <select name="payment_type" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="salary">Salaire Mensuel</option>
                                    <option value="bonus">Prime / Bonus</option>
                                    <option value="advance">Avance sur Salaire</option>
                                    <option value="expense_reimbursement">Remboursement de Frais</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">Canal de Règlement</label>
                                <select name="payment_method" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="cash" {{ $user->payment_method === 'cash' ? 'selected' : '' }}>Espèces (Caisse)</option>
                                    <option value="bank" {{ $user->payment_method === 'bank' ? 'selected' : '' }}>Virement Bancaire</option>
                                    <option value="mobile_money" {{ $user->payment_method === 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
                                </select>
                            </div>
                        </div>

                        <!-- Info Caisse si Espèces -->
                        <div id="cashier_info" class="{{ $user->payment_method === 'cash' ? '' : 'hidden' }} p-4 bg-blue-50 border border-blue-100 rounded-xl">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center text-xs">
                                    <i class="fas fa-cash-register"></i>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-blue-800 uppercase tracking-tighter">Terminal de Caisse Actif</p>
                                    @if($activeSession)
                                        <p class="text-[9px] font-bold text-blue-600">Session #{{ $activeSession->id }} — Solde : {{ number_format($activeSession->opening_balance, 0, ',', ' ') }} XOF</p>
                                    @else
                                        <p class="text-[9px] font-bold text-rose-600 uppercase">Attention : Aucune session de caisse ouverte !</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">Notes & Justificatifs du Règlement</label>
                    <textarea name="notes" rows="3" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-6 py-4 text-sm font-medium text-slate-700 focus:border-blue-500 outline-none transition-all placeholder:text-slate-300" placeholder="Précisez la période de paie ou l'objet de la prime..."></textarea>
                </div>

                <div class="pt-6 border-t border-slate-100 flex items-center justify-between">
                    <a href="{{ route('admin.payroll.index') }}" class="btn-bank btn-bank-outline px-8 py-3">Annuler</a>
                    <button type="submit" class="btn-bank btn-bank-primary px-12 py-3 text-sm" {{ !$activeSession && $user->payment_method === 'cash' ? 'disabled' : '' }}>
                        Valider & Libérer le Paiement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.querySelector('select[name="payment_method"]').onchange = function() {
        const cashierInfo = document.getElementById('cashier_info');
        const submitBtn = document.querySelector('button[type="submit"]');
        const hasSession = {{ $activeSession ? 'true' : 'false' }};
        
        if (this.value === 'cash') {
            cashierInfo.classList.remove('hidden');
            if (!hasSession) submitBtn.disabled = true;
        } else {
            cashierInfo.classList.add('hidden');
            submitBtn.disabled = false;
        }
    };
</script>
@endpush
@endsection
