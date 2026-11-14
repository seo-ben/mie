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
            @php
                $activeCycle = $tontine->activeCycle ?? $tontine->cycles()->first();
                $mise = (float) $tontine->tontine_amount;
                $collected = $activeCycle ? (float) $activeCycle->collected_amount : (float) $tontine->total_paid;
                $filledSlots = $mise > 0 ? min(31, (int) floor($collected / $mise)) : 0;
                $remainingSlots = max(0, 31 - $filledSlots);
                $remainingAmount = $remainingSlots * $mise;
                $commission = $mise;
                $clientNet = max(0, 30 * $mise);
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 mb-8">
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

            <!-- GRILLE VISUELLE DU CALENDRIER 1-31 & CALCULS FINANCIERS -->
            <div class="mt-8 pt-8 border-t border-slate-100 space-y-6">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div>
                        <h4 class="text-xs font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                            <i class="fas fa-calendar-days text-purple-600"></i> Calendrier du Cycle (1 - 31 Mises)
                        </h4>
                        <p class="text-[10px] text-slate-400 font-medium mt-0.5">Règle du 1/31 &bull; Cycle {{ $activeCycle->cycle_number ?? 1 }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-[10px] font-bold text-purple-700 bg-purple-50 border border-purple-100 px-3 py-1 rounded-full uppercase">
                            {{ $filledSlots }} / 31 Jours Cotisés
                        </span>
                        <a href="{{ route('admin.tontines.show', $tontine->id) }}" class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-[10px] font-black uppercase transition flex items-center gap-1.5 shadow-sm">
                            <i class="fas fa-arrow-up-right-from-square"></i> Audit Tontine Approfondi
                        </a>
                    </div>
                </div>

                <!-- 4 Indicateurs de Calcul (Règle 1/31) -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 text-center">
                        <span class="text-[8px] font-bold text-slate-400 uppercase block mb-1">Mise Journalière</span>
                        <span class="text-sm font-black text-slate-900">{{ number_format($mise, 0, ',', ' ') }} XOF</span>
                    </div>
                    <div class="p-3 bg-emerald-50/50 rounded-xl border border-emerald-100 text-center">
                        <span class="text-[8px] font-bold text-emerald-600 uppercase block mb-1">Cotisé ({{ $filledSlots }} j)</span>
                        <span class="text-sm font-black text-emerald-700">{{ number_format($filledSlots * $mise, 0, ',', ' ') }} XOF</span>
                    </div>
                    <div class="p-3 bg-amber-50/50 rounded-xl border border-amber-100 text-center">
                        <span class="text-[8px] font-bold text-amber-600 uppercase block mb-1">Reste ({{ $remainingSlots }} j)</span>
                        <span class="text-sm font-black text-amber-700">{{ number_format($remainingAmount, 0, ',', ' ') }} XOF</span>
                    </div>
                    <div class="p-3 bg-purple-50/50 rounded-xl border border-purple-100 text-center">
                        <span class="text-[8px] font-bold text-purple-600 uppercase block mb-1">Net Client (30 j)</span>
                        <span class="text-sm font-black text-purple-700">{{ number_format($clientNet, 0, ',', ' ') }} XOF</span>
                        <span class="text-[8px] text-slate-400 block mt-0.5">Commission 1/31 : {{ number_format($commission, 0, ',', ' ') }}</span>
                    </div>
                </div>

                <!-- Grille des 31 cases -->
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">Cases de Cotisation Journalière (1 à 31)</span>
                        <div class="flex items-center gap-3 text-[9px] font-medium text-slate-400">
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-emerald-500 inline-block"></span> Cotisé</span>
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-slate-100 border border-slate-200 inline-block"></span> En attente</span>
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-purple-50 border border-purple-300 inline-block"></span> Case 31 (Frais 1/31)</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-7 sm:grid-cols-11 md:grid-cols-16 lg:grid-cols-31 gap-1.5">
                        @for($d = 1; $d <= 31; $d++)
                            @php
                                $paid = ($d <= $filledSlots);
                                $is31 = ($d === 31);
                            @endphp
                            <div class="h-9 rounded-lg border flex flex-col items-center justify-center font-bold text-[10px] transition-all relative group
                                {{ $paid ? 'bg-emerald-500 border-emerald-600 text-white shadow-sm' : ($is31 ? 'bg-purple-50 border-purple-300 text-purple-700 border-dashed' : 'bg-slate-50 border-slate-200 text-slate-400') }}"
                                title="Jour {{ $d }} : {{ $paid ? 'Cotisé (' . number_format($mise, 0, ',', ' ') . ' XOF)' : ($is31 ? '31e mise = Commission Microfinance (1/31)' : 'En attente (' . number_format($mise, 0, ',', ' ') . ' XOF)') }}">
                                <span>{{ $d }}</span>
                                @if($paid)
                                    <i class="fas fa-check text-[7px] leading-none mt-0.5"></i>
                                @elseif($is31)
                                    <i class="fas fa-hand-holding-dollar text-[7px] opacity-75 leading-none mt-0.5"></i>
                                @endif
                            </div>
                        @endfor
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
