@extends('layouts.app_admin')

@section('title', 'Dossier d\'Agence - ' . $agency->name)
@section('page-title', 'Infrastructure / Détails du Nœud')

@section('content')
<div class="space-y-8">
    <!-- En-tête avec navigation -->
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.agencies.index') }}" class="w-10 h-10 bg-white border border-slate-200 rounded-xl flex items-center justify-center text-slate-400 hover:text-blue-600 hover:border-blue-100 transition shadow-sm">
            <i class="fas fa-chevron-left text-xs"></i>
        </a>
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">{{ $agency->name }}</h2>
            <p class="text-slate-500 text-sm font-bold uppercase tracking-widest font-mono">{{ $agency->code }} • {{ $agency->city }}, {{ $agency->region }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Colonne Gauche: Informations Générales -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bank-card p-8">
                <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6">Profil de l'Infrastructure</h3>
                
                <div class="space-y-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600 border border-blue-100">
                            <i class="fas fa-landmark text-lg"></i>
                        </div>
                        <div>
                            <p class="text-[9px] font-black text-slate-400 uppercase">Statut Opérationnel</p>
                            @if($agency->is_active)
                                <span class="text-xs font-black text-emerald-600 uppercase">Actif & Vérifié</span>
                            @else
                                <span class="text-xs font-black text-rose-600 uppercase">Désactivé</span>
                            @endif
                        </div>
                    </div>

                    <div class="pt-6 border-t border-slate-100">
                        <p class="text-[9px] font-black text-slate-400 uppercase mb-3">Gouvernance</p>
                        @if($agency->manager)
                            <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white text-[10px] font-black">
                                    {{ strtoupper(substr($agency->manager->first_name, 0, 1) . substr($agency->manager->last_name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="text-xs font-black text-slate-800">{{ $agency->manager->full_name }}</p>
                                    <p class="text-[9px] text-slate-400 font-bold">Directeur de Nœud</p>
                                </div>
                            </div>
                        @else
                            <p class="text-xs font-bold text-slate-400 italic">Aucun directeur assigné</p>
                        @endif
                    </div>

                    <div class="pt-6 border-t border-slate-100 space-y-4">
                        <div>
                            <p class="text-[9px] font-black text-slate-400 uppercase">Localisation</p>
                            <p class="text-xs font-bold text-slate-700 mt-1 uppercase">{{ $agency->address ?? 'Non spécifiée' }}</p>
                            <p class="text-xs font-bold text-slate-700 uppercase">{{ $agency->city }} ({{ $agency->region }})</p>
                        </div>
                        <div>
                            <p class="text-[9px] font-black text-slate-400 uppercase">Contact</p>
                            <p class="text-xs font-bold text-slate-700 mt-1">{{ $agency->phone ?? 'Aucun numéro enregistré' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bank-card p-8 bg-slate-900 text-white">
                <div class="space-y-6">
                    <div>
                        <h3 class="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-3">Solde Actuel (Coffre-fort)</h3>
                        <div class="flex items-end gap-2">
                            <span class="text-3xl font-black text-emerald-400">{{ number_format($agency->vault_balance ?? 0, 0, ',', ' ') }}</span>
                            <span class="text-xs font-bold text-blue-400 mb-1.5 uppercase">XOF</span>
                        </div>
                        <p class="text-[9px] font-bold text-white/40 mt-1 uppercase">Liquidité réelle disponible en agence</p>
                    </div>

                    <div class="pt-6 border-t border-white/10">
                        <h3 class="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-3">Seuil d'Alerte (Limite)</h3>
                        <div class="flex items-end gap-2">
                            <span class="text-xl font-black">{{ number_format($agency->cash_limit ?? 0, 0, ',', ' ') }}</span>
                            <span class="text-[10px] font-bold text-white/40 mb-1 uppercase">XOF</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne Droite: Statistiques et Personnel -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Statistiques Rapides -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bank-card p-6 border-l-4 border-blue-600">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Officiers Affectés</span>
                        <i class="fas fa-user-tie text-blue-600 text-xs"></i>
                    </div>
                    <p class="text-2xl font-black text-slate-900 mt-2">{{ $agency->users->count() }}</p>
                </div>
                <div class="bank-card p-6 border-l-4 border-emerald-500">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Portefeuille Clients</span>
                        <i class="fas fa-users text-emerald-500 text-xs"></i>
                    </div>
                    <p class="text-2xl font-black text-slate-900 mt-2">{{ $agency->clients()->count() }}</p>
                </div>
            </div>

            <!-- Liste du Personnel -->
            <div class="bank-card overflow-hidden">
                <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                    <h3 class="text-xs font-black text-slate-700 uppercase tracking-widest">Personnel de l'Agence</h3>
                    <span class="text-[9px] font-black text-slate-400 uppercase bg-white px-2 py-1 rounded border border-slate-200">Registre Actif</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="bank-table">
                        <thead>
                            <tr>
                                <th>Officier</th>
                                <th>Rôle Institutionnel</th>
                                <th>Statut</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($agency->users as $user)
                            <tr class="hover:bg-slate-50/50 transition-colors group">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-[10px] font-black text-slate-500 border border-slate-200">
                                            {{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="text-xs font-black text-slate-800 leading-none">{{ $user->full_name }}</p>
                                            <p class="text-[9px] text-slate-400 font-bold mt-1 uppercase">{{ $user->username }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-[9px] font-black text-blue-600 uppercase px-2 py-0.5 bg-blue-50 rounded border border-blue-100">
                                        {{ ucfirst(str_replace('_', ' ', $user->role)) }}
                                    </span>
                                </td>
                                <td>
                                    @if($user->is_active)
                                        <span class="text-[9px] font-black text-emerald-600 uppercase flex items-center gap-1.5">
                                            <span class="w-1 h-1 rounded-full bg-emerald-500"></span> Actif
                                        </span>
                                    @else
                                        <span class="text-[9px] font-black text-rose-600 uppercase flex items-center gap-1.5">
                                            <span class="w-1 h-1 rounded-full bg-rose-500"></span> Inactif
                                        </span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.users.show', $user->id) }}" class="p-2 text-slate-400 hover:text-blue-600 transition">
                                        <i class="fas fa-arrow-right text-[10px]"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="py-12 text-center text-slate-400 text-xs italic">Aucun utilisateur affecté à cette division</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
