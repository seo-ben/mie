@extends('layouts.app_admin')

@section('title', 'Registre Institutionnel des Adhérents')
@section('page-title', 'Gestion du Portefeuille des Membres')

@section('content')
<div class="space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Registre des Adhérents</h2>
            <p class="text-slate-500 text-sm font-medium">Supervision de la base de clients institutionnels et du statut de vérification d'identité</p>
        </div>
        <a href="{{ route('admin.clients.create') }}" class="btn-bank btn-bank-primary">
            <i class="fas fa-plus mr-2 text-[10px]"></i>
            <span>Enregistrer un Nouvel Adhérent</span>
        </a>
    </div>

    <!-- Matrice des Portefeuilles -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bank-card p-5 border-trust">
            <span class="kpi-label">Base Totale</span>
            <div class="kpi-value !text-xl mt-1">{{ number_format($clients->total()) }}</div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">Entités Uniques Vérifiées</p>
        </div>
        <div class="bank-card p-5">
            <span class="kpi-label">Vérification d'Identité</span>
            <div class="kpi-value !text-xl mt-1 text-amber-600">{{ $clients->where('kyc_status', 'pending')->count() }}</div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">En Attente de Protocole KYC</p>
        </div>
        <div class="bank-card p-5">
            <span class="kpi-label">Ratio d'Activité</span>
            <div class="kpi-value !text-xl mt-1 text-emerald-600">
                {{ number_format(($clients->where('kyc_status', 'approved')->count() / max($clients->total(), 1)) * 100, 1) }}%
            </div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">Saturation de Conformité</p>
        </div>
        <div class="bank-card p-5">
            <span class="kpi-label">comptes Régionaux</span>
            <div class="kpi-value !text-xl mt-1 text-blue-600">{{ count(array_unique($clients->pluck('city')->toArray())) }}</div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">Empreinte Géographique</p>
        </div>
    </div>

    <!-- Filtres d'Audit -->
    <div class="bank-card p-6">
        <form method="GET" action="{{ route('admin.clients.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <div class="md:col-span-5 relative">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher par Nom, Téléphone ou ID Interne..." class="bank-input pl-10">
                <i class="fas fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            </div>

            <div class="md:col-span-3">
                <select name="kyc_status" class="bank-input uppercase">
                    <option value="">Hiérarchie du Statut KYC</option>
                    <option value="pending" {{ request('kyc_status') == 'pending' ? 'selected' : '' }}>Protocole en Attente</option>
                    <option value="approved" {{ request('kyc_status') == 'approved' ? 'selected' : '' }}>Adhérent Vérifié</option>
                    <option value="rejected" {{ request('kyc_status') == 'rejected' ? 'selected' : '' }}>Signalé/Rejeté</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <select name="city" class="bank-input uppercase">
                    <option value="">Toutes les Régions</option>
                    @foreach(['Lomé', 'Kara', 'Sokodé'] as $city)
                        <option value="{{ $city }}" {{ request('city') == $city ? 'selected' : '' }}>Division {{ $city }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2 flex gap-2">
                <button type="submit" class="btn-bank btn-bank-primary flex-1">
                    <i class="fas fa-search text-[10px]"></i>
                </button>
                <a href="{{ route('admin.clients.index') }}" class="btn-bank btn-bank-outline px-4">
                    <i class="fas fa-rotate"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Volet du Registre Institutionnel -->
    <div class="bank-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="bank-table">
                <thead>
                    <tr>
                        <th>Entité Adhérente</th>
                        <th>Lieu de Supervision</th>
                        <th>Exposition Financière</th>
                        <th class="text-center">Statut du Protocole</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($clients as $client)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded border border-slate-200 flex items-center justify-center bg-slate-50 overflow-hidden">
                                        @if($client->profile_photo_url)
                                            <img src="{{ asset('storage/' . $client->profile_photo_url) }}" class="w-full h-full object-cover">
                                        @else
                                            <span class="text-[10px] font-bold text-slate-400">
                                                {{ strtoupper(substr($client->first_name, 0, 1) . substr($client->last_name, 0, 1)) }}
                                            </span>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-800 leading-tight">{{ $client->full_name }}</p>
                                        <p class="text-[9px] font-mono font-bold text-blue-600 uppercase tracking-tighter mt-0.5">{{ $client->client_number }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="flex flex-col">
                                    <p class="text-[11px] font-bold text-slate-700">{{ $client->phone }}</p>
                                    <p class="text-[9px] font-bold text-slate-400 uppercase">Division {{ $client->city ?? 'Région Inconnue' }}</p>
                                </div>
                            </td>
                            <td>
                                <div class="flex flex-col">
                                    <p class="text-[11px] font-extrabold text-slate-800 font-numeric">
                                        {{ number_format($client->total_savings + $client->total_tontine, 0, ',', ' ') }} <small class="text-[9px] text-slate-400">XOF</small>
                                    </p>
                                    <p class="text-[9px] font-bold text-slate-400 uppercase">{{ $client->accounts->count() }} comptes Gérés</p>
                                </div>
                            </td>
                            <td class="text-center">
                                @if($client->kyc_status === 'approved')
                                    <div class="flex items-center justify-center gap-1.5">
                                        <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                                        <span class="bank-badge badge-success !text-[8px]">Profil Conforme</span>
                                    </div>
                                @elseif($client->kyc_status === 'rejected')
                                    <div class="flex items-center justify-center gap-1.5">
                                        <div class="w-2 h-2 rounded-full bg-rose-500"></div>
                                        <span class="bank-badge badge-danger !text-[8px]">Risque Détecté</span>
                                    </div>
                                @else
                                    <div class="flex items-center justify-center gap-1.5">
                                        <div class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></div>
                                        <span class="bank-badge badge-warning !text-[8px]">Audit de Sécurité</span>
                                    </div>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('admin.clients.show', $client->id) }}" class="p-2 text-slate-400 hover:text-blue-600 transition" title="Supervision du Membre">
                                        <i class="fas fa-eye text-xs"></i>
                                    </a>
                                    <a href="{{ route('admin.clients.edit', $client->id) }}" class="p-2 text-slate-400 hover:text-emerald-600 transition" title="Modifier le Registre">
                                        <i class="fas fa-pen text-xs"></i>
                                    </a>
                                    <div class="relative group">
                                        <button class="p-2 text-slate-400 hover:text-slate-900 transition">
                                            <i class="fas fa-ellipsis-v text-xs"></i>
                                        </button>
                                        <div class="absolute right-0 top-full mt-1 w-48 bg-white border border-slate-200 rounded-lg shadow-xl hidden group-hover:block z-50">
                                            <a href="{{ route('admin.accounts.create', $client->id) }}" class="block px-4 py-2 text-[10px] font-bold text-slate-700 hover:bg-slate-50 border-b border-slate-100">Initialiser un Compte</a>
                                            <a href="javascript:void(0)" 
                                               onclick="checkKycBeforeLoan('{{ $client->kyc_status }}', '{{ route('admin.loans.create', ['client_id' => $client->id]) }}', '{{ route('admin.clients.edit', $client->id) }}')" 
                                               class="block px-4 py-2 text-[10px] font-bold text-slate-700 hover:bg-slate-50 border-b border-slate-100">
                                                Initier une Demande de Crédit
                                             </a>
                                            @if($client->kyc_status === 'pending')
                                                <a href="{{ route('admin.clients.validate-kyc', $client->id) }}" class="block px-4 py-2 text-[10px] font-extrabold text-emerald-600 hover:bg-emerald-50">Autoriser le KYC</a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-20 text-center">
                                <i class="fas fa-users-slash text-3xl text-slate-200 mb-4"></i>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Aucune entité trouvée dans le registre actuel</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($clients->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/30">
                {{ $clients->links() }}
            </div>
        @endif
    </div>
</div>
@push('scripts')
<script>
function checkKycBeforeLoan(status, loanUrl, editUrl) {
    if (status === 'approved') {
        window.location.href = loanUrl;
    } else {
        Swal.fire({
            title: '<span class="text-rose-600 uppercase font-black text-sm">Action Requise : KYC Non Validé</span>',
            html: `
                <div class="py-4 text-center">
                    <div class="w-16 h-16 bg-rose-50 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-4 border border-rose-100 shadow-inner">
                        <i class="fas fa-shield-halved text-2xl"></i>
                    </div>
                    <p class="text-xs font-bold text-slate-600 leading-relaxed uppercase">
                        Le protocole KYC de cet adhérent n'est pas encore <span class="text-rose-600">Approuvé</span>.<br>
                        La validation du dossier est obligatoire avant toute demande de crédit.
                    </p>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Vérifier le dossier',
            cancelButtonText: 'Annuler',
            confirmButtonColor: '#2563eb', // blue-600
            cancelButtonColor: '#94a3b8', // slate-400
            customClass: {
                popup: 'rounded-3xl border-2 border-slate-100 shadow-2xl',
                confirmButton: 'rounded-xl font-bold uppercase text-[10px] px-6',
                cancelButton: 'rounded-xl font-bold uppercase text-[10px] px-6'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Rediriger vers l'edit avec le paramètre de focus
                window.location.href = editUrl + '?focus=kyc_validation';
            }
        });
    }
}
</script>
@endpush
@endsection
