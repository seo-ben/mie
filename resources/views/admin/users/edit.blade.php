@extends('layouts.app_admin')

@section('title', 'Modification du Profil Officier - ' . $user->full_name)
@section('page-title', 'Protocoles / Édition d\'Identité')

@section('content')
<div class="max-w-4xl mx-auto space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.users.show', $user->id) }}" class="w-10 h-10 bg-white border border-slate-200 rounded-xl flex items-center justify-center text-slate-400 hover:text-blue-600 hover:border-blue-100 transition shadow-sm">
            <i class="fas fa-chevron-left text-xs"></i>
        </a>
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Mise à jour de l'Entité</h2>
            <p class="text-slate-500 text-sm font-bold uppercase tracking-widest font-mono">Modification des privilèges et données de l'Officier #{{ $user->id }}</p>
        </div>
    </div>

    <div class="bank-card overflow-hidden">
        <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <h3 class="text-xs font-black text-slate-700 uppercase tracking-widest">Matrice de Configuration Profil</h3>
            <span class="text-[9px] font-black text-slate-400 uppercase bg-white px-3 py-1.5 rounded-xl border border-slate-200">ID Institutionnel : {{ $user->username }}</span>
        </div>

        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="p-8 space-y-8">
            @csrf
            @method('PUT')

            <!-- Section: Identité Civile -->
            <div class="space-y-6">
                <div class="flex items-center gap-3">
                    <span class="w-1 h-4 bg-blue-600 rounded-full"></span>
                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Identité Civile & Contact</h4>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-1.5">
                        <label for="first_name" class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Prénom de l'Officier</label>
                        <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $user->first_name) }}" required
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs font-bold focus:ring-1 focus:ring-blue-500 outline-none transition @error('first_name') border-rose-500 @enderror">
                        @error('first_name') <p class="text-[9px] font-black text-rose-500 uppercase mt-1 ml-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label for="last_name" class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Nom Patronyme</label>
                        <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $user->last_name) }}" required
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs font-bold focus:ring-1 focus:ring-blue-500 outline-none transition @error('last_name') border-rose-500 @enderror">
                        @error('last_name') <p class="text-[9px] font-black text-rose-500 uppercase mt-1 ml-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label for="email" class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Canal Email Officiel</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs font-bold focus:ring-1 focus:ring-blue-500 outline-none transition @error('email') border-rose-500 @enderror">
                        @error('email') <p class="text-[9px] font-black text-rose-500 uppercase mt-1 ml-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label for="phone" class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Terminal Mobile de Liaison</label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}" required
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs font-bold focus:ring-1 focus:ring-blue-500 outline-none transition @error('phone') border-rose-500 @enderror">
                        @error('phone') <p class="text-[9px] font-black text-rose-500 uppercase mt-1 ml-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <!-- Section: Habilitations & Gouvernance -->
            <div class="space-y-6 pt-8 border-t border-slate-100">
                <div class="flex items-center gap-3">
                    <span class="w-1 h-4 bg-purple-600 rounded-full"></span>
                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Habilitations & Affectations</h4>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-1.5">
                        <label for="role" class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Rôle Institutionnel</label>
                        <select name="role" id="role" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs font-black focus:ring-1 focus:ring-blue-500 outline-none transition uppercase">
                            @foreach([
                                'administrateur_systeme' => 'Système (Contrôle Total)',
                                'administrateur_reglementaire' => 'Audit & Réglementation',
                                'manager_agence' => 'Management de Division',
                                'gestionnaire_credit' => 'Gouvernance de Crédit',
                                'caissier' => 'caissier',
                                'agent_terrain' => 'Collecte & Terrain'
                            ] as $val => $label)
                                <option value="{{ $val }}" {{ old('role', $user->role) === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label for="agency_id" class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Division d'Affectation</label>
                        <select name="agency_id" id="agency_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs font-black focus:ring-1 focus:ring-blue-500 outline-none transition uppercase">
                            <option value="">Administration Centrale (Siège)</option>
                            @foreach(\App\Models\Agency::where('is_active', true)->get() as $agency)
                                <option value="{{ $agency->id }}" {{ old('agency_id', $user->agency_id) == $agency->id ? 'selected' : '' }}>Division {{ $agency->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label for="is_active" class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Privilèges d'Accès Terminal</label>
                        <select name="is_active" id="is_active" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs font-black focus:ring-1 focus:ring-blue-500 outline-none transition uppercase">
                            <option value="1" {{ old('is_active', $user->is_active) ? 'selected' : '' }}>Accès Autorisé (Actif)</option>
                            <option value="0" {{ old('is_active', $user->is_active) ? '' : 'selected' }}>Accès Révoqué (Désactivé)</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label for="username" class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Identifiant de Connexion</label>
                        <input type="text" name="username" id="username" value="{{ old('username', $user->username) }}" required
                               class="w-full bg-slate-100 border border-slate-200 rounded-xl px-4 py-3 text-xs font-bold text-slate-400 cursor-not-allowed outline-none uppercase font-mono" readonly>
                        <p class="text-[8px] font-bold text-slate-400 uppercase mt-1 ml-1 italic">L'identifiant institutionnel ne peut être modifié</p>
                    </div>
                </div>
            </div>

            <div class="flex gap-3 pt-8 border-t border-slate-100">
                <button type="submit" class="flex-1 btn-bank btn-bank-primary !py-4 !rounded-xl text-[10px] font-black uppercase tracking-widest transition-all hover:shadow-lg shadow-blue-200">
                    <i class="fas fa-check-double mr-2"></i> Valider les Modifications
                </button>
                <a href="{{ route('admin.users.show', $user->id) }}" class="btn-bank btn-bank-outline !py-4 px-10 !rounded-xl text-[10px] font-black uppercase tracking-widest border-slate-200">
                    Abandonner
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
