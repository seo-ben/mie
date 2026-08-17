@extends('layouts.app_admin')

@section('title', 'Ouvrir une Session de Caisse - MIE YAYRA')

@section('content')
<div class="max-w-4xl mx-auto space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight uppercase">Ouverture de Session</h2>
            <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest mt-1">Approvisionnement Initial du Guichet</p>
        </div>
        <a href="{{ route('admin.cashier.sessions.index') }}" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-200 transition">
            Retour
        </a>
    </div>

    <div class="bank-card p-8">
        <form action="{{ route('admin.cashier.sessions.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Sélection du Caissier -->
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Caissier Responsable</label>
                    <div class="relative">
                        <select name="user_id" required class="w-full bg-slate-50 border-slate-200 rounded-xl px-4 py-3 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 transition appearance-none">
                            <option value="">Sélectionner un caissier...</option>
                            @foreach($cashiers as $cashier)
                                <option value="{{ $cashier->id }}" {{ old('user_id') == $cashier->id ? 'selected' : '' }}>
                                    {{ $cashier->full_name }} ({{ $cashier->agency->name ?? 'Sans Agence' }})
                                </option>
                            @endforeach
                        </select>
                        <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                    </div>
                </div>

                <!-- Sélection de l'Agence -->
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Agence du Guichet</label>
                    <div class="relative">
                        <select name="agency_id" required class="w-full bg-slate-50 border-slate-200 rounded-xl px-4 py-3 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 transition appearance-none">
                            @foreach($agencies as $agency)
                                <option value="{{ $agency->id }}" {{ (old('agency_id') ?? auth()->user()->agency_id) == $agency->id ? 'selected' : '' }}>
                                    {{ $agency->name }} — Trésorerie: {{ number_format($agency->vault_balance, 0, ',', ' ') }} CFA
                                </option>
                            @endforeach
                        </select>
                        <i class="fas fa-university absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pt-4">
                <!-- Montant d'Approvisionnement (Provision) -->
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Fonds de Roulement (Provision)</label>
                    <div class="relative">
                        <input type="number" name="opening_balance" value="{{ old('opening_balance', 0) }}" step="100" min="0" required
                            class="w-full bg-slate-50 border-slate-200 rounded-xl px-4 py-3 text-lg font-black text-slate-900 focus:ring-2 focus:ring-emerald-500/10 focus:border-emerald-500 transition placeholder:text-slate-300"
                            placeholder="0">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-400 uppercase">CFA</span>
                    </div>
                    <p class="text-[9px] text-slate-400 italic">Ce montant sera déduit de la Grande Trésorerie de l'agence.</p>
                </div>

                <!-- Notes / Commentaire -->
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Notes d'Ouverture</label>
                    <textarea name="notes" rows="2" class="w-full bg-slate-50 border-slate-200 rounded-xl px-4 py-3 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 transition" placeholder="Ex: Approvisionnement matinal standard...">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="pt-8 border-t border-slate-100 flex justify-end">
                <button type="submit" class="px-8 py-4 bg-slate-900 text-white rounded-2xl text-sm font-black uppercase tracking-widest hover:bg-slate-800 hover:shadow-2xl hover:shadow-slate-900/20 transition-all active:scale-95 flex items-center gap-3">
                    <i class="fas fa-lock-open"></i>
                    Lancer la Session
                </button>
            </div>
        </form>
    </div>

    <!-- Rappels de Sécurité -->
    <div class="bg-amber-50 border border-amber-100 rounded-2xl p-6 flex gap-4">
        <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center text-amber-600 flex-shrink-0">
            <i class="fas fa-shield-halved"></i>
        </div>
        <div>
            <h4 class="text-xs font-black text-amber-900 uppercase tracking-widest">Contrôle de conformité</h4>
            <p class="text-[10px] text-amber-700 font-bold leading-relaxed uppercase mt-1">
                L'ouverture d'une session engage la responsabilité du caissier. Assurez-vous d'avoir physiquement remis les fonds avant de valider cette opération dans le système.
            </p>
        </div>
    </div>
</div>
@endsection
