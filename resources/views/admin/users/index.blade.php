@extends('layouts.app_admin')

@section('title', 'Registre du Personnel Institutionnel')
@section('page-title', 'Infrastructure & Accès du Personnel')

@section('content')
<div class="space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Registre de Gouvernance du Personnel</h2>
            <p class="text-slate-500 text-sm font-medium">Supervision des rôles utilisateurs, niveaux d'accès et statut opérationnel</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="btn-bank btn-bank-primary">
            <i class="fas fa-user-plus mr-2 text-[10px]"></i>
            <span>Enregistrer un Nouvel Agent</span>
        </a>
    </div>

    <!-- Matrice de Gouvernance -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bank-card p-5 border-trust">
            <span class="kpi-label">Agents Actifs</span>
            <div class="kpi-value !text-xl mt-1 text-slate-900">{{ $users->where('is_active', true)->count() }}</div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">Personnel opérationnel vérifié</p>
        </div>
        <div class="bank-card p-5">
            <span class="kpi-label">Administration Réglementaire</span>
            <div class="kpi-value !text-xl mt-1 text-blue-600">{{ $users->where('role', 'administrateur_reglementaire')->count() }}</div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">Rôles de Conformité & Supervision</p>
        </div>
        <div class="bank-card p-5">
            <span class="kpi-label">Agents de Terrain</span>
            <div class="kpi-value !text-xl mt-1 text-purple-600">{{ $users->where('role', 'agent_terrain')->count() }}</div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">Gestion de Portefeuille Externe</p>
        </div>
    </div>

    <!-- Contrôles d'Audit -->
    <div class="bank-card p-6">
        <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <div class="md:col-span-5 relative">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Recherche par Nom, Email ou ID..." class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:ring-1 focus:ring-blue-500 outline-none transition uppercase">
                <i class="fas fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            </div>

            <div class="md:col-span-3">
                <select name="role" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold text-slate-600 focus:ring-1 focus:ring-blue-500 outline-none transition uppercase">
                    <option value="">Rôles de Gouvernance</option>
                    <option value="administrateur_reglementaire" {{ request('role') === 'administrateur_reglementaire' ? 'selected' : '' }}>Officier Réglementaire</option>
                    <option value="administrateur_systeme" {{ request('role') === 'administrateur_systeme' ? 'selected' : '' }}>Auditeur Système</option>
                    <option value="agent_terrain" {{ request('role') === 'agent_terrain' ? 'selected' : '' }}>Opérations Terrain</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <select name="is_active" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold text-slate-600 focus:ring-1 focus:ring-blue-500 outline-none transition uppercase">
                    <option value="">Statut du Personnel</option>
                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Opérationnel</option>
                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Désactivé</option>
                </select>
            </div>

            <div class="md:col-span-2 flex gap-2">
                <button type="submit" class="btn-bank btn-bank-primary flex-1">
                    <i class="fas fa-search text-[10px]"></i>
                </button>
                <a href="{{ route('admin.users.index') }}" class="btn-bank btn-bank-outline px-4">
                    <i class="fas fa-rotate"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Volet du Registre du Personnel -->
    <div class="bank-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="bank-table">
                <thead>
                    <tr>
                        <th>Identité de l'Agent</th>
                        <th>Vecteur de Communication</th>
                        <th>Rôle de Gouvernance</th>
                        <th>Affectation</th>
                        <th class="text-center">Statut du Protocole</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded bg-slate-100 flex items-center justify-center text-slate-500 border border-slate-200">
                                        {{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-800 leading-tight">{{ $user->full_name }}</p>
                                        <p class="text-[9px] font-mono font-bold text-blue-600 uppercase tracking-tighter mt-0.5">UID-{{ str_pad($user->id, 4, '0', STR_PAD_LEFT) }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <p class="text-[11px] font-bold text-slate-700">{{ $user->email }}</p>
                            </td>
                            <td>
                                @php
                                    $roles = [
                                        'administrateur_reglementaire' => ['label' => 'Officier Réglementaire', 'class' => 'text-blue-700 bg-blue-50'],
                                        'administrateur_systeme' => ['label' => 'Auditeur Système', 'class' => 'text-slate-700 bg-slate-100'],
                                        'agent_terrain' => ['label' => 'Opérations Terrain', 'class' => 'text-purple-700 bg-purple-50'],
                                    ];
                                    $roleInfo = $roles[$user->role] ?? ['label' => $user->role, 'class' => 'text-slate-400 bg-slate-50'];
                                @endphp
                                <span class="text-[9px] font-extrabold uppercase px-2 py-1 rounded-full {{ $roleInfo['class'] }}">
                                    {{ $roleInfo['label'] }}
                                </span>
                            </td>
                            <td>
                                <span class="text-[10px] font-black {{ $user->agency ? 'text-blue-600' : 'text-slate-400' }} uppercase">
                                    {{ $user->agency->name ?? 'Siège / Central' }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($user->is_active)
                                    <span class="bank-badge badge-success !text-[8px]">Opérationnel</span>
                                @else
                                    <span class="bank-badge badge-danger !text-[8px]">Désactivé</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('admin.users.show', $user) }}" class="p-2 text-slate-400 hover:text-blue-600 transition" title="Supervision Personnel">
                                        <i class="fas fa-id-card-clip text-xs"></i>
                                    </a>
                                    <a href="{{ route('admin.users.edit', $user) }}" class="p-2 text-slate-400 hover:text-emerald-600 transition" title="Modifier Accès">
                                        <i class="fas fa-user-gear text-xs"></i>
                                    </a>
                                    @if($user->id !== auth()->id())
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline-block" onsubmit="return confirm('Initier le Protocole de Désactivation ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 text-slate-400 hover:text-red-600 transition" title="Révoquer Accès">
                                                <i class="fas fa-user-slash text-xs"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-20 text-center">
                                <i class="fas fa-users-viewfinder text-3xl text-slate-200 mb-4 block"></i>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Le registre du personnel est actuellement vide</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
