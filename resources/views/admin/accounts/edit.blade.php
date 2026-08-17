@extends('layouts.app_admin')

@section('title', 'Révision des Paramètres du compte - ' . $account->account_number)
@section('page-title', 'Protocole / Mise à Jour du Compte')

@section('content')
<div class="space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 no-print">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.accounts.show', $account->id) }}" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-xl flex items-center justify-center transition border border-slate-200 text-slate-600">
                <i class="fas fa-chevron-left text-xs"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Révision des Paramètres du compte</h2>
                <p class="text-slate-500 text-sm font-medium">Modification des attributs du conteneur <span class="font-mono text-blue-600 font-bold tracking-widest">{{ $account->account_number }}</span></p>
            </div>
        </div>
    </div>

    <!-- Alerte Critique -->
    <div class="bank-card !bg-amber-50 !border-amber-200 p-6 flex items-start gap-4 shadow-lg shadow-amber-500/5">
        <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center flex-shrink-0">
            <i class="fas fa-shield-alert text-lg"></i>
        </div>
        <div>
            <h3 class="text-xs font-black text-amber-900 uppercase tracking-widest mb-1">Avis de Supervision</h3>
            <p class="text-[11px] font-bold text-amber-800 leading-relaxed uppercase tracking-tight">La modification des paramètres structurels est strictement restreinte aux comptes sous protocole de suspension.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Signalétique de l'Entité -->
        <div class="lg:col-span-4 space-y-8">
            <!-- Tutelle Adhérente -->
            <div class="bank-card overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="text-[10px] font-black text-slate-800 uppercase tracking-widest">Tutelle de l'Entité</h3>
                </div>
                <div class="p-6">
                    <div class="flex items-center gap-4 mb-6">
                        @if($account->client->profile_photo_url)
                            <img src="{{ Storage::url($account->client->profile_photo_url) }}" class="w-14 h-14 rounded-xl object-cover border-2 border-white shadow-sm">
                        @else
                            <div class="w-14 h-14 bg-slate-900 rounded-xl flex items-center justify-center text-white font-black text-lg">
                                {{ substr($account->client->first_name, 0, 1) }}{{ substr($account->client->last_name, 0, 1) }}
                            </div>
                        @endif
                        <div>
                            <p class="text-sm font-black text-slate-900 truncate">{{ $account->client->full_name }}</p>
                            <p class="text-[10px] font-mono font-bold text-blue-600 uppercase">{{ $account->client->client_number }}</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex justify-between items-center text-[10px]">
                            <span class="font-bold text-slate-400 uppercase tracking-tight">Vérification KYC</span>
                            <span class="bank-badge {{ $account->client->kyc_status === 'approved' ? 'badge-success' : 'badge-warning' }} !text-[8px] uppercase font-black">
                                {{ $account->client->kyc_status === 'approved' ? 'Validé' : 'En Audit' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- État du compte -->
            <div class="bank-card p-6 space-y-4">
                <h3 class="text-[10px] font-black text-slate-800 uppercase tracking-widest border-b border-slate-100 pb-2">Diagnostic du compte</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-end">
                        <span class="text-[10px] font-bold text-slate-500 uppercase">Nature de l'Actif</span>
                        <span class="bank-badge {{ $account->account_type === 'savings' ? 'badge-primary' : 'badge-secondary' }} !text-[8px] uppercase font-black">
                            {{ $account->account_type === 'savings' ? 'Épargne' : 'Tontine' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-end">
                        <span class="text-[10px] font-bold text-slate-500 uppercase">Statut Opérationnel</span>
                        <span class="bank-badge badge-danger !text-[8px] uppercase font-black">Suspendu</span>
                    </div>
                    <div class="flex justify-between items-end">
                        <span class="text-[10px] font-bold text-slate-500 uppercase">Solde Consolidé</span>
                        <span class="text-sm font-black text-slate-900">{{ number_format($account->balance, 0, ',', ' ') }} <small class="text-[9px] uppercase">XOF</small></span>
                    </div>
                </div>

                @if($account->suspension_reason)
                <div class="mt-6 p-4 bg-rose-50 rounded-xl border border-rose-100">
                    <p class="text-[9px] font-black text-rose-700 uppercase mb-2">Note de Suspension</p>
                    <p class="text-[10px] font-bold text-rose-600 italic">"{{ $account->suspension_reason }}"</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Formulaire de Mise à Jour -->
        <div class="lg:col-span-8">
            <form method="POST" action="{{ route('admin.accounts.update', $account->id) }}" class="space-y-8">
                @csrf
                @method('PUT')

                @if($account->account_type === 'savings')
                <!-- Configuration Épargne -->
                <div class="bank-card overflow-hidden">
                    <div class="px-8 py-5 bg-blue-600 text-white flex items-center justify-between">
                        <h3 class="text-xs font-black uppercase tracking-[0.2em] flex items-center gap-2">
                            <i class="fas fa-vault"></i> Paramètres du compte d'Épargne
                        </h3>
                    </div>
                    <div class="p-8 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Taux d'Intérêt Annuel (%) *</label>
                                <div class="relative">
                                    <input type="number" step="0.01" name="interest_rate" value="{{ old('interest_rate', $account->savingsAccount->interest_rate) }}" min="0" max="100" class="bank-input font-black text-blue-600 @error('interest_rate') border-rose-500 @enderror">
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 font-black text-xs">%</span>
                                </div>
                                @error('interest_rate') <p class="text-[9px] font-bold text-rose-500 uppercase mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Seuil de Survie (Minimum XOF) *</label>
                                <div class="relative">
                                    <input type="number" name="minimum_balance" value="{{ old('minimum_balance', $account->savingsAccount->minimum_balance) }}" min="0" step="1000" class="bank-input font-black text-blue-600 @error('minimum_balance') border-rose-500 @enderror">
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 font-black text-xs">XOF</span>
                                </div>
                                @error('minimum_balance') <p class="text-[9px] font-bold text-rose-500 uppercase mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="space-y-2 md:col-span-2">
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Redevance de Maintenance Mensuelle (XOF) *</label>
                                <div class="relative">
                                    <input type="number" name="monthly_fee" value="{{ old('monthly_fee', $account->savingsAccount->monthly_fee) }}" min="0" step="100" class="bank-input font-black text-blue-600 @error('monthly_fee') border-rose-500 @enderror">
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 font-black text-xs">XOF</span>
                                </div>
                                @error('monthly_fee') <p class="text-[9px] font-bold text-rose-500 uppercase mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="p-6 bg-blue-50 rounded-2xl border border-blue-100 grid grid-cols-2 gap-8">
                            <div>
                                <p class="text-[8px] font-black text-blue-800/40 uppercase tracking-widest mb-1">Total Injections</p>
                                <p class="text-sm font-black text-blue-900">{{ number_format($account->savingsAccount->total_deposits, 0, ',', ' ') }} <small class="text-[9px]">XOF</small></p>
                            </div>
                            <div>
                                <p class="text-[8px] font-black text-blue-800/40 uppercase tracking-widest mb-1">Total Extractions</p>
                                <p class="text-sm font-black text-blue-900">{{ number_format($account->savingsAccount->total_withdrawals, 0, ',', ' ') }} <small class="text-[9px]">XOF</small></p>
                            </div>
                        </div>
                    </div>
                </div>

                @else
                <!-- Configuration Tontine -->
                <div class="bank-card overflow-hidden">
                    <div class="px-8 py-5 bg-purple-600 text-white flex items-center justify-between">
                        <h3 class="text-xs font-black uppercase tracking-[0.2em] flex items-center gap-2">
                            <i class="fas fa-rotate"></i> Paramètres du compte Tontine
                        </h3>
                    </div>
                    <div class="p-8 space-y-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Volume de Cotisation (XOF) *</label>
                                <div class="relative">
                                    <input type="number" name="tontine_amount" id="tontine_amount" value="{{ old('tontine_amount', $account->tontineAccount->tontine_amount) }}" min="200" class="bank-input font-black text-purple-600 text-lg @error('tontine_amount') border-rose-500 @enderror">
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 font-black text-xs">XOF</span>
                                </div>
                                @error('tontine_amount') <p class="text-[9px] font-bold text-rose-500 uppercase mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Durée de l'Engagement (Mois) *</label>
                                <select name="cycle_duration_months" id="cycle_duration_months" class="bank-input uppercase font-bold text-purple-600 @error('cycle_duration_months') border-rose-500 @enderror">
                                    @foreach([1, 3, 6, 12, 18, 24] as $months)
                                        <option value="{{ $months }}" {{ old('cycle_duration_months', $account->tontineAccount->cycle_duration_months) == $months ? 'selected' : '' }}>
                                            {{ $months }} Mois {{ $months > 1 ? '(Cycle Long)' : '(Cycle Flash)' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('cycle_duration_months') <p class="text-[9px] font-bold text-rose-500 uppercase mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="space-y-4">
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Fréquence des Flux d'Injection *</label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                @foreach(['daily' => 'Quotidien', 'weekly' => 'Hebdomadaire', 'monthly' => 'Mensuel'] as $val => $label)
                                <label class="flex flex-col p-4 bg-slate-50 border-2 border-slate-100 rounded-2xl cursor-pointer hover:border-purple-200 transition-all frequency-option relative overflow-hidden group">
                                    <input type="radio" name="payment_frequency" value="{{ $val }}" {{ old('payment_frequency', $account->tontineAccount->payment_frequency) === $val ? 'checked' : '' }} class="peer hidden">
                                    <div class="absolute inset-0 bg-purple-600 opacity-0 peer-checked:opacity-5 transition-opacity"></div>
                                    <span class="text-xs font-black text-slate-900 uppercase peer-checked:text-purple-600">{{ $label }}</span>
                                    <span class="text-[8px] font-bold text-slate-400 uppercase mt-1">Audit {{ $val === 'daily' ? 'Journalier' : ($val === 'weekly' ? 'par Décanie' : 'Lunaire') }}</span>
                                    <div class="absolute top-4 right-4 text-purple-600 opacity-0 peer-checked:opacity-100 transition-opacity">
                                        <i class="fas fa-circle-check text-xs"></i>
                                    </div>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Analyse d'Avancement -->
                        <div class="p-6 bg-purple-50 rounded-2xl border border-purple-100">
                            <h4 class="text-[9px] font-black text-purple-900 uppercase mb-4 flex items-center gap-2">
                                <i class="fas fa-chart-pie"></i> Diagnostic d'Avancement du Cycle
                            </h4>
                            <div class="grid grid-cols-2 gap-8 mb-4">
                                <div>
                                    <p class="text-[8px] font-black text-purple-800/40 uppercase mb-1">Capitalisé</p>
                                    <p class="text-sm font-black text-purple-900">{{ number_format($account->tontineAccount->total_paid, 0, ',', ' ') }} <small class="text-[9px]">XOF</small></p>
                                </div>
                                <div>
                                    <p class="text-[8px] font-black text-purple-800/40 uppercase mb-1">Cible Brute</p>
                                    <p class="text-sm font-black text-purple-900">{{ number_format($account->tontineAccount->total_expected, 0, ',', ' ') }} <small class="text-[9px]">XOF</small></p>
                                </div>
                            </div>
                            @php
                                $progress = $account->tontineAccount->total_expected > 0 ? ($account->tontineAccount->total_paid / $account->tontineAccount->total_expected * 100) : 0;
                            @endphp
                            <div class="w-full bg-purple-200 rounded-full h-1.5 overflow-hidden">
                                <div class="bg-purple-600 h-full rounded-full transition-all duration-1000" style="width: {{ $progress }}%"></div>
                            </div>
                            <p class="text-[8px] font-black text-purple-500 uppercase mt-2 text-right tracking-widest">{{ number_format($progress, 1) }}% de l'objectif consolidé</p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Validation Finale -->
                <div class="bank-card p-6 border-trust shadow-2xl shadow-slate-900/10">
                    <div class="flex items-center justify-between gap-4">
                        <a href="{{ route('admin.accounts.show', $account->id) }}" class="btn-bank btn-bank-outline px-10 text-xs uppercase font-black">
                            Abandonner
                        </a>
                        <button type="submit" class="btn-bank btn-bank-primary px-12 py-4 text-xs uppercase font-black shadow-lg shadow-blue-500/20">
                            Valider les Nouvelles Clauses
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.frequency-option input:checked + span { color: #9333ea !important; }
.frequency-option input:checked + span + span { color: #a855f7 !important; }
@keyframes progressIn { from { width: 0; } }
.bg-purple-600 { animation: progressIn 1.5s cubic-bezier(0.16, 1, 0.3, 1); }
</style>
@endsection
