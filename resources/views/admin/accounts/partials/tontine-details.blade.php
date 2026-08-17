<!-- Paramètres Spécifiques : Épargne Mutuelle (Tontine) -->
<div class="bank-card overflow-hidden mb-8">
    <div class="px-8 py-5 bg-purple-600 text-white flex items-center justify-between">
        <h3 class="text-xs font-black uppercase tracking-[0.2em] flex items-center gap-2">
            <i class="fas fa-rotate"></i> Paramètres du compte Tontine
        </h3>
        <span class="text-[10px] font-bold text-white/70 uppercase">Code de Produit : TNT-FLEX</span>
    </div>

    <div class="p-8">
        @php
            $tontine = $account->tontineAccount;
        @endphp

        @if($tontine)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <!-- Configuration du Cycle -->
                <div class="space-y-6">
                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 pb-2">Configuration du Cycle</h4>
                    <div class="space-y-4">
                        <div class="flex justify-between items-end">
                            <span class="text-[10px] font-bold text-slate-500 uppercase">Volume de Cotisation</span>
                            <span class="text-sm font-black text-slate-800">{{ number_format($tontine->tontine_amount ?? 0, 0, ',', ' ') }} <small class="text-[9px] text-slate-400 font-bold uppercase">XOF</small></span>
                        </div>
                        <div class="flex justify-between items-end">
                            <span class="text-[10px] font-bold text-slate-500 uppercase">Fréquence des Flux</span>
                            <span class="text-sm font-black text-purple-600 uppercase">{{ __($tontine->payment_frequency ?? 'N/A') }}</span>
                        </div>
                        <div class="flex justify-between items-end">
                            <span class="text-[10px] font-bold text-slate-500 uppercase">Durée du Protocole</span>
                            <span class="text-sm font-black text-slate-800">{{ $tontine->cycle_duration_months ?? 0 }} <small class="text-[9px] text-slate-400 font-bold uppercase">Mois</small></span>
                        </div>
                    </div>
                </div>

                <!-- Analyse de Conformité du Cycle -->
                <div class="space-y-6">
                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 pb-2">Statistiques de Participation</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 text-center relative overflow-hidden group">
                            @if($tontine->activeCycle && $tontine->activeCycle->collected_amount >= $tontine->activeCycle->target_amount)
                                <div class="absolute -top-2 -right-2 w-8 h-8 bg-emerald-500 rotate-45 flex items-end justify-center pb-1">
                                    <i class="fas fa-check text-[8px] text-white -rotate-45"></i>
                                </div>
                            @endif
                            <p class="text-[8px] font-black text-slate-400 uppercase mb-1">Cotisé ce Cycle</p>
                            <p class="text-sm font-black text-slate-900">
                                {{ number_format($tontine->activeCycle->collected_amount ?? 0, 0, ',', ' ') }}
                            </p>
                        </div>
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 text-center">
                            <p class="text-[8px] font-black text-slate-400 uppercase mb-1">Objectif Global</p>
                            <p class="text-sm font-black text-blue-600">
                                {{ number_format($tontine->total_expected ?? 0, 0, ',', ' ') }}
                            </p>
                        </div>
                    </div>
                    
                    @php
                        $progress = ($tontine->total_expected > 0) 
                            ? min(100, ($tontine->total_paid / $tontine->total_expected) * 100) 
                            : 0;
                        $isCompleted = ($tontine->total_paid >= $tontine->total_expected && $tontine->total_expected > 0);
                    @endphp

                    <div class="p-5 {{ $isCompleted ? 'bg-amber-50 border-amber-100' : 'bg-purple-50 border-purple-100' }} rounded-2xl border transition-all">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full {{ $isCompleted ? 'bg-amber-100 text-amber-600' : 'bg-purple-100 text-purple-600' }} flex items-center justify-center flex-shrink-0">
                                <i class="fas {{ $isCompleted ? 'fa-star animate-spin-slow' : 'fa-sync fa-spin-slow' }}"></i>
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between items-center mb-1">
                                    <p class="text-[10px] font-black {{ $isCompleted ? 'text-amber-900' : 'text-purple-900' }} uppercase tracking-widest leading-tight">
                                        {{ $isCompleted ? 'Contrat Tontine Consommé' : 'Avancement du Protocole' }}
                                    </p>
                                    <span class="text-[10px] font-black {{ $isCompleted ? 'text-amber-600' : 'text-purple-600' }}">{{ round($progress, 1) }}%</span>
                                </div>
                                <div class="w-full h-1.5 {{ $isCompleted ? 'bg-amber-200' : 'bg-purple-200' }} rounded-full mt-2 overflow-hidden shadow-inner">
                                    <div class="h-full {{ $isCompleted ? 'bg-gradient-to-r from-amber-400 to-amber-600' : 'bg-purple-600' }} rounded-full transition-all duration-1000" style="width: {{ $progress }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="py-12 text-center border-2 border-dashed border-slate-100 rounded-3xl">
                <i class="fas fa-users-slash text-4xl text-slate-100 mb-4 block"></i>
                <p class="text-[10px] font-black text-slate-300 uppercase tracking-[0.2em]">Configuration de Tontine non Détectée</p>
            </div>
        @endif
    </div>
</div>
