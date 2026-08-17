@extends('layouts.app_admin')

@section('title', 'Gestion du Profil - ' . $user->full_name)
@section('page-title', 'Administration / Profil Officier')

@section('content')
<div class="space-y-8">
    <!-- En-tête de Navigation -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.users.index') }}" class="w-10 h-10 bg-white border border-slate-200 rounded-xl flex items-center justify-center text-slate-400 hover:text-blue-600 hover:border-blue-100 transition shadow-sm">
                <i class="fas fa-chevron-left text-xs"></i>
            </a>
            <div>
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">{{ $user->full_name }}</h2>
                <p class="text-slate-500 text-sm font-bold uppercase tracking-widest font-mono">MATRICULE #{{ str_pad($user->id, 5, '0', STR_PAD_LEFT) }} • {{ $user->username }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.users.edit', $user) }}" class="btn-bank btn-bank-outline !py-2.5">
                <i class="fas fa-pen mr-2 text-[10px]"></i> Modifier le Profil
            </a>
            <button onclick="confirmResetPassword()" class="btn-bank btn-bank-outline !py-2.5 text-amber-600 border-amber-100 hover:bg-amber-50">
                <i class="fas fa-key mr-2 text-[10px]"></i> Sécurité
            </button>
            @if($user->clients->count() > 0)
            <button onclick="openTransferModal()" class="btn-bank !py-2.5 bg-blue-600 text-white hover:bg-blue-700 shadow-lg shadow-blue-200">
                <i class="fas fa-exchange-alt mr-2 text-[10px]"></i> Transférer Portefeuille
            </button>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Colonne Gauche: Carte d'Identité Institutionnelle -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bank-card overflow-hidden">
                <div class="h-24 gradient-bg relative">
                    <div class="absolute -bottom-10 left-8">
                        <div class="w-20 h-20 rounded-2xl bg-white p-1 shadow-xl border border-slate-100">
                            <div class="w-full h-full rounded-xl bg-slate-100 flex items-center justify-center text-2xl font-black text-blue-600">
                                {{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-8 pt-14">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <span class="text-[9px] font-black text-blue-600 uppercase px-2 py-0.5 bg-blue-50 rounded-full border border-blue-100">
                                {{ ucfirst(str_replace('_', ' ', $user->role)) }}
                            </span>
                        </div>
                        @if($user->is_active)
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span class="text-[9px] font-black text-emerald-700 uppercase">Compte Actif</span>
                            </div>
                        @else
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                <span class="text-[9px] font-black text-rose-700 uppercase">Suspendu</span>
                            </div>
                        @endif
                    </div>

                    <div class="space-y-4 pt-4 border-t border-slate-100">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Email Officiel</span>
                            <span class="text-xs font-bold text-slate-700">{{ $user->email }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Terminal Mobile</span>
                            <span class="text-xs font-bold text-slate-700">{{ $user->phone }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Division Affectée</span>
                            <span class="text-xs font-black text-blue-600 lowercase bg-blue-50 px-2 py-0.5 rounded italic">
                                {{ $user->agency->name ?? 'Administration Centrale' }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-8 p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-4">
                        <h4 class="text-[9px] font-black text-slate-400 uppercase tracking-widest px-2">Paramètres de Sécurité</h4>
                        <div class="flex items-center justify-between p-2 bg-white rounded-xl shadow-sm border border-slate-100">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-shield-halved text-blue-600 text-xs"></i>
                                <span class="text-[10px] font-black text-slate-700 uppercase">Double Facteur</span>
                            </div>
                            <span class="bank-badge {{ $user->mfa_enabled ? 'badge-success' : 'badge-danger' }} !text-[8px]">
                                {{ $user->mfa_enabled ? 'Activé' : 'Inactif' }}
                            </span>
                        </div>
                        <a href="{{ route('admin.users.toggle-2fa', $user) }}" class="block w-full text-center py-2 text-[10px] font-black text-blue-600 hover:text-blue-700 uppercase tracking-widest transition">
                            Gérer la protection 2FA
                        </a>
                    </div>
                </div>
            </div>

            <!-- KPI Portefeuille -->
            <div class="grid grid-cols-2 gap-4">
                <div class="bank-card p-5 border-l-4 border-purple-600">
                    <span class="text-[9px] font-black text-slate-400 uppercase">Adhérents</span>
                    <p class="text-xl font-black text-slate-900 mt-1">{{ $user->clients->count() }}</p>
                </div>
                <div class="bank-card p-5 border-l-4 border-amber-500">
                    <span class="text-[9px] font-black text-slate-400 uppercase">Logins (30j)</span>
                    <p class="text-xl font-black text-slate-900 mt-1">{{ $recent_activities->where('action', 'LOGIN')->count() }}</p>
                </div>
            </div>
        </div>

        <!-- Colonne Droite: Activités et Registre -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Journal d'Audit du Terminal -->
            <div class="bank-card overflow-hidden">
                <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-black text-slate-700 uppercase tracking-widest">Journal d'Audit des Actions</h3>
                        <p class="text-[9px] font-bold text-slate-400 mt-1 italic uppercase tracking-tighter">Historique de sécurité du terminal</p>
                    </div>
                    <span class="text-[9px] font-black text-slate-400 uppercase bg-white px-3 py-1.5 rounded-xl border border-slate-200">Derniers Logins</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="bank-table">
                        <thead>
                            <tr>
                                <th>Horodatage</th>
                                <th>Action Système</th>
                                <th>Données Supplémentaires</th>
                                <th class="text-right">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($recent_activities as $activity)
                                <tr class="hover:bg-slate-50/50 transition-colors group">
                                    <td>
                                        <div class="flex flex-col">
                                            <span class="text-xs font-black text-slate-700">{{ $activity->created_at->format('d M, Y') }}</span>
                                            <span class="text-[9px] font-bold text-slate-400">{{ $activity->created_at->format('H:i') }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <div class="w-2 h-2 rounded-full {{ Str::contains($activity->action, 'DELETE') ? 'bg-rose-500' : (Str::contains($activity->action, 'CREATE') ? 'bg-emerald-500' : 'bg-blue-500') }}"></div>
                                            <span class="text-[10px] font-black text-slate-600 uppercase">{{ str_replace('_', ' ', $activity->action) }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        @if($activity->additional_data)
                                            @php
                                                $dataStr = collect($activity->additional_data)->map(function($v, $k) {
                                                    $val = is_array($v) || is_object($v) ? json_encode($v) : $v;
                                                    return "$k: $val";
                                                })->implode(', ');
                                            @endphp
                                            <p class="text-[9px] font-bold text-slate-500 font-mono line-clamp-1 max-w-[200px]" title="{{ json_encode($activity->additional_data) }}">
                                                {{ $dataStr }}
                                            </p>
                                        @else
                                            <span class="text-[9px] text-slate-300 italic font-bold uppercase">N/A</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <span class="bank-badge badge-info !text-[8px] group-hover:scale-105 transition-transform">Enregistré</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-12 text-center">
                                        <i class="fas fa-shield text-3xl text-slate-100 mb-4 block"></i>
                                        <p class="text-xs font-black text-slate-300 uppercase tracking-widest italic">Aucune activité enregistrée pour cet officier</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Liste des Adhérents Capture -->
            <div class="bank-card overflow-hidden">
                <div class="px-8 py-6 border-b border-slate-100 bg-white flex items-center justify-between">
                    <h3 class="text-xs font-black text-slate-700 uppercase tracking-widest">Adhérents Capturés</h3>
                    <span class="text-[9px] font-black text-purple-600 uppercase bg-purple-50 px-3 py-1.5 rounded-xl border border-purple-100">{{ $user->clients->count() }} Entités</span>
                </div>
                <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    @forelse ($user->clients->take(8) as $client)
                        <div class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 hover:border-blue-200 hover:bg-blue-50/30 transition-all cursor-pointer group">
                            <div class="w-8 h-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-[10px] font-black text-blue-600 group-hover:scale-110 transition-transform">
                                {{ strtoupper(substr($client->first_name, 0, 1) . substr($client->last_name, 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-black text-slate-800 leading-none truncate">{{ $client->full_name }}</p>
                                <p class="text-[9px] text-slate-400 font-bold mt-1 uppercase truncate">{{ $client->email }}</p>
                            </div>
                            <a href="{{ route('admin.clients.show', $client->id) }}" class="w-6 h-6 rounded-md flex items-center justify-center text-slate-300 hover:text-blue-600">
                                <i class="fas fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    @empty
                        <div class="col-span-2 py-8 text-center text-slate-400 text-xs italic font-bold uppercase tracking-widest">Aucun client rattaché à ce profil</div>
                    @endforelse
                </div>
                @if($user->clients->count() > 8)
                    <div class="px-8 py-3 bg-slate-50 text-center border-t border-slate-100">
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">+ {{ $user->clients->count() - 8 }} autres clients en base</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal Confirmation Réinitialisation -->
<div id="resetPasswordModal" class="fixed inset-0 z-50 flex items-center justify-center hidden p-4 bg-slate-900/60 backdrop-blur-sm">
    <div class="bank-card w-full max-w-sm animate-scale-in p-8 text-center">
        <div class="w-16 h-16 rounded-3xl bg-amber-50 flex items-center justify-center text-amber-500 mx-auto mb-6 border border-amber-100">
            <i class="fas fa-key text-2xl"></i>
        </div>
        <h3 class="text-sm font-black text-slate-800 uppercase tracking-tight">Réinitialisation Sécurisée</h3>
        <p class="text-[11px] text-slate-400 font-bold mt-3 uppercase leading-relaxed">
            Vous allez générer un nouveau mot de passe temporaire pour <br><span class="text-slate-900">{{ $user->full_name }}</span>.
        </p>

        <form action="{{ route('admin.users.reset-password', $user) }}" method="GET" class="mt-8 flex gap-3">
            <button type="submit" class="flex-1 bg-amber-600 text-white !py-3 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-amber-700 transition shadow-lg shadow-amber-200">Générer</button>
            <button type="button" onclick="closeResetModal()" class="flex-1 bg-slate-100 text-slate-600 !py-3 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-200 transition">Annuler</button>
        </form>
    </div>
</div>

<!-- Modal Confirmation Transfert Portefeuille -->
<div id="transferClientsModal" class="fixed inset-0 z-50 flex items-center justify-center hidden p-4 bg-slate-900/60 backdrop-blur-sm">
    <div class="bank-card w-full max-w-md animate-scale-in p-8">
        <div class="w-16 h-16 rounded-3xl bg-blue-50 flex items-center justify-center text-blue-500 mx-auto mb-6 border border-blue-100">
            <i class="fas fa-exchange-alt text-2xl"></i>
        </div>
        <h3 class="text-sm font-black text-slate-800 uppercase tracking-tight text-center">Transfert de Portefeuille</h3>
        <p class="text-[11px] text-slate-400 font-bold mt-3 uppercase leading-relaxed text-center">
            Vous allez transférer <span class="text-blue-600">{{ $user->clients->count() }} clients</span> de <br><span class="text-slate-900">{{ $user->full_name }}</span> vers un nouvel agent.
        </p>

        <form action="{{ route('admin.users.transfer-clients', $user) }}" method="POST" class="mt-8 space-y-6">
            @csrf
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 px-1">Nouvel Agent Responsable</label>
                <select name="target_user_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none">
                    <option value="">Sélectionner un agent...</option>
                    @foreach($otherUsers as $otherUser)
                        <option value="{{ $otherUser->id }}">
                            {{ $otherUser->full_name }} ({{ $otherUser->agency->name ?? 'N/A' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="p-4 bg-amber-50 rounded-2xl border border-amber-100">
                <div class="flex gap-3">
                    <i class="fas fa-exclamation-triangle text-amber-500 mt-0.5"></i>
                    <p class="text-[10px] font-bold text-amber-700 leading-relaxed uppercase">
                        Cette action est irréversible. Toutes les statistiques futures de ces clients seront attribuées au nouvel agent.
                    </p>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-blue-600 text-white !py-3 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-700 transition shadow-lg shadow-blue-200">Confirmer le transfert</button>
                <button type="button" onclick="closeTransferModal()" class="flex-1 bg-slate-100 text-slate-600 !py-3 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-200 transition">Annuler</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function confirmResetPassword() {
        document.getElementById('resetPasswordModal').classList.remove('hidden');
    }

    function closeResetModal() {
        document.getElementById('resetPasswordModal').classList.add('hidden');
    }

    function openTransferModal() {
        document.getElementById('transferClientsModal').classList.remove('hidden');
    }

    function closeTransferModal() {
        document.getElementById('transferClientsModal').classList.add('hidden');
    }
</script>
@endpush
@endsection
