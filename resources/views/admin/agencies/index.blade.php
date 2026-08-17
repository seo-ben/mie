@extends('layouts.app_admin')

@section('title', 'Registre Institutionnel')
@section('page-title', 'Infrastructure du Réseau d\'Agences')

@section('content')
<div class="space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Nœuds Régionaux</h2>
            <p class="text-slate-500 text-sm font-medium">Supervision opérationnelle du réseau d'agences physiques</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.treasury.index') }}" class="flex items-center gap-2 px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-[10px] font-black uppercase tracking-widest transition shadow-lg shadow-indigo-200">
                <i class="fas fa-building-columns"></i>
                <span>Grande Caisse</span>
            </a>
            <button onclick="openCreateModal()" class="btn-bank btn-bank-primary">
                <i class="fas fa-plus mr-2"></i>
                <span>Nouveau Nœud</span>
            </button>
        </div>
    </div>

    <!-- Filtres de Supervision -->
    <div class="bank-card p-6">
        <form method="GET" action="{{ route('admin.agencies.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2 relative">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher par code, nom ou ville..." class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:ring-1 focus:ring-blue-500 outline-none transition uppercase">
                <i class="fas fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            </div>

            <select name="region" class="bg-slate-50 border border-slate-200 rounded-lg px-4 py-2 text-sm focus:ring-1 focus:ring-blue-500 outline-none transition text-slate-600 font-bold uppercase">
                <option value="">Toutes les Régions</option>
                @foreach(['Maritime', 'Plateaux', 'Centrale', 'Kara', 'Savanes'] as $region)
                    <option value="{{ $region }}" {{ request('region') == $region ? 'selected' : '' }}>{{ $region }}</option>
                @endforeach
            </select>

            <div class="flex gap-2">
                <button type="submit" class="btn-bank btn-bank-primary flex-1">
                    <i class="fas fa-filter mr-2 text-[10px]"></i> Filtrer
                </button>
                <a href="{{ route('admin.agencies.index') }}" class="btn-bank btn-bank-outline px-4">
                    <i class="fas fa-rotate"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Registre d'Infrastructure -->
    <div class="bank-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="bank-table">
                <thead>
                    <tr>
                        <th>Infrastructure</th>
                        <th>Déploiement</th>
                        <th>Gouvernance</th>
                        <th class="text-right">Trésorerie</th>
                        <th class="text-center">Effectif</th>
                        <th class="text-center">Statut</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($agencies as $agency)
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl border border-slate-200 flex items-center justify-center bg-white shadow-sm text-blue-600 group-hover:scale-110 transition-transform">
                                    <i class="fas fa-landmark"></i>
                                </div>
                                <div>
                                    <p class="font-black text-slate-800 leading-tight">{{ $agency->name }}</p>
                                    <p class="text-[10px] font-mono font-bold text-blue-600 uppercase tracking-tighter mt-1">{{ $agency->code }}</p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-location-dot text-[10px] text-slate-300"></i>
                                    <span class="text-xs font-bold text-slate-600">{{ $agency->city }}</span>
                                </div>
                                <span class="text-[9px] bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded font-black uppercase w-fit">{{ $agency->region }}</span>
                            </div>
                        </td>
                        <td>
                            @if($agency->manager)
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 text-[10px] font-black border border-blue-200">
                                        {{ strtoupper(substr($agency->manager->first_name, 0, 1) . substr($agency->manager->last_name, 0, 1)) }}
                                    </div>
                                    <div class="flex flex-col">
                                        <p class="text-xs font-black text-slate-700 leading-none">{{ $agency->manager->full_name }}</p>
                                        <p class="text-[9px] text-slate-400 font-bold mt-1 uppercase">Directeur d'Agence</p>
                                    </div>
                                </div>
                            @else
                                <span class="text-[9px] font-black text-slate-300 uppercase italic bg-slate-50 px-2 py-0.5 rounded border border-dashed border-slate-200">Non assigné</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="flex flex-col items-end">
                                <span class="text-xs font-black text-slate-800">{{ number_format($agency->vault_balance, 0, ',', ' ') }}</span>
                                <span class="text-[8px] font-bold text-blue-600 uppercase">XOF (Caisse)</span>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="text-[10px] font-black text-slate-600 bg-slate-100 px-2.5 py-1 rounded-full border border-slate-200">
                                {{ $agency->users->count() }} <i class="fas fa-user-tie ml-1 text-[8px]"></i>
                            </span>
                        </td>
                        <td class="text-center">
                            @if($agency->is_active)
                                <div class="flex items-center justify-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span class="text-[10px] font-black text-emerald-700 uppercase">Opérationnel</span>
                                </div>
                            @else
                                <div class="flex items-center justify-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                    <span class="text-[10px] font-black text-rose-700 uppercase">Hors-ligne</span>
                                </div>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.treasury.index') }}?agency_id={{ $agency->id }}" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition" title="Trésorerie de {{ $agency->name }}">
                                    <i class="fas fa-building-columns text-xs"></i>
                                </a>
                                <button onclick="viewAgency({{ $agency->id }})" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition" title="Audit">
                                    <i class="fas fa-eye text-xs"></i>
                                </button>
                                <button onclick="openEditModal({{ $agency->id }})" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition" title="Éditer">
                                    <i class="fas fa-pen text-xs"></i>
                                </button>
                                <button onclick="confirmDelete({{ $agency->id }}, '{{ $agency->name }}')" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition" title="Supprimer">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-20 text-center">
                            <div class="max-w-xs mx-auto">
                                <div class="w-16 h-16 rounded-3xl bg-slate-50 flex items-center justify-center text-slate-200 mx-auto mb-4 border border-slate-200 border-dashed">
                                    <i class="fas fa-building-slash text-2xl"></i>
                                </div>
                                <h4 class="text-sm font-black text-slate-800">Aucune Infrastructure Détectée</h4>
                                <p class="text-slate-400 text-[10px] mt-1 uppercase tracking-widest font-bold font-mono">Le registre est actuellement vide</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($agencies->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/30">
            {{ $agencies->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Création/Édition -->
<div id="agencyModal" class="fixed inset-0 z-50 flex items-center justify-center hidden p-4 bg-slate-900/60 backdrop-blur-sm">
    <div class="bank-card w-full max-w-lg animate-scale-in max-h-[92vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 rounded-t-2xl sticky top-0 z-10">
            <h3 id="modalTitle" class="text-xs font-extrabold text-slate-700 uppercase tracking-widest">Enregistrement d'un Nouveau Nœud</h3>
            <button onclick="closeModal()" class="w-8 h-8 rounded-full flex items-center justify-center text-slate-400 hover:text-slate-900 hover:bg-white transition shadow-sm">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="agencyForm" method="POST" action="{{ route('admin.agencies.store') }}" class="p-8 space-y-5">
            @csrf
            <input type="hidden" id="formMethod" name="_method" value="POST">
            
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Nom de l'Agence</label>
                    <input type="text" name="name" id="agency_name" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs font-bold focus:ring-1 focus:ring-blue-500 outline-none transition">
                </div>
                <div class="space-y-1.5">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Code Institutionnel</label>
                    <input type="text" name="code" id="agency_code" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs font-bold focus:ring-1 focus:ring-blue-500 outline-none transition uppercase font-mono">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Ville d'Implantation</label>
                    <input type="text" name="city" id="agency_city" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs font-bold focus:ring-1 focus:ring-blue-500 outline-none transition uppercase">
                </div>
                <div class="space-y-1.5">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Région</label>
                    <select name="region" id="agency_region" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs font-black focus:ring-1 focus:ring-blue-500 outline-none transition uppercase">
                        @foreach(['Maritime', 'Plateaux', 'Centrale', 'Kara', 'Savanes'] as $region)
                            <option value="{{ $region }}">{{ $region }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Directeur d'agence --}}
            <div class="space-y-1.5">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Directeur d'Agence Assigné</label>
                <select name="manager_id" id="agency_manager" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs font-black focus:ring-1 focus:ring-blue-500 outline-none transition uppercase">
                    <option value="">-- Non assigné --</option>
                    @foreach($managers as $manager)
                        <option value="{{ $manager->id }}">{{ $manager->full_name }} ({{ $manager->username }})</option>
                    @endforeach
                </select>
            </div>

            {{-- Trésorerie & Limites --}}
            <div class="grid grid-cols-2 gap-4 pt-2 border-t border-slate-100">
                <div class="space-y-1.5">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Solde Grande Caisse</label>
                    <div class="relative">
                        <input type="number" name="vault_balance" id="agency_vault_balance" step="0.01" min="0"
                               class="w-full bg-blue-50/50 border border-blue-100 rounded-xl px-4 py-3 text-xs font-black text-blue-700 focus:ring-1 focus:ring-blue-500 outline-none transition">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[9px] font-black text-blue-400 uppercase">XOF</span>
                    </div>
                </div>
                <div class="space-y-1.5">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Seuil d'Alerte (Limite)</label>
                    <div class="relative">
                        <input type="number" name="cash_limit" id="agency_cash_limit" step="0.01" min="0"
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs font-bold focus:ring-1 focus:ring-blue-500 outline-none transition">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[9px] font-black text-slate-300 uppercase">XOF</span>
                    </div>
                </div>
            </div>

            {{-- Contact --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Adresse Physique</label>
                    <input type="text" name="address" id="agency_address"
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs font-bold focus:ring-1 focus:ring-blue-500 outline-none transition">
                </div>
                <div class="space-y-1.5">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Téléphone Contact</label>
                    <input type="text" name="phone" id="agency_phone"
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs font-bold focus:ring-1 focus:ring-blue-500 outline-none transition">
                </div>
            </div>

            <div class="flex items-center gap-3 p-4 bg-slate-50 rounded-xl border border-slate-100 italic">
                <input type="checkbox" name="is_active" id="agency_active" value="1" checked class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                <label for="agency_active" class="text-[10px] font-black text-slate-600 uppercase">Activer immédiatement les opérations bancaires</label>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 btn-bank btn-bank-primary !py-4 !rounded-xl text-xs font-black uppercase tracking-widest">
                    <i class="fas fa-save mr-2"></i> Valider l'Enregistrement
                </button>
                <button type="button" onclick="closeModal()" class="btn-bank btn-bank-outline !py-4 px-8 !rounded-xl text-xs font-black uppercase tracking-widest">Abandonner</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Suppression -->
<div id="deleteModal" class="fixed inset-0 z-50 flex items-center justify-center hidden p-4 bg-slate-900/60 backdrop-blur-sm">
    <div class="bank-card w-full max-w-sm animate-scale-in p-8 text-center">
        <div class="w-16 h-16 rounded-full bg-rose-50 flex items-center justify-center text-rose-500 mx-auto mb-6 border border-rose-100">
            <i class="fas fa-triangle-exclamation text-2xl"></i>
        </div>
        <h3 class="text-sm font-black text-slate-800 uppercase tracking-tight">Révoquer l'Infrastructure ?</h3>
        <p class="text-[10px] text-slate-400 font-bold mt-3 uppercase leading-relaxed">
            Vous êtes sur le point de désactiver l'agence <span id="deleteAgencyName" class="text-rose-600"></span>. Cette action restreindra l'accès à tous les agents associés.
        </p>

        <form id="deleteForm" method="POST" class="mt-8 flex gap-3">
            @csrf
            @method('DELETE')
            <button type="submit" class="flex-1 bg-rose-600 text-white !py-3 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-rose-700 transition">Confirmer</button>
            <button type="button" onclick="closeDeleteModal()" class="flex-1 bg-slate-100 text-slate-600 !py-3 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-200 transition">Annuler</button>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openCreateModal() {
        document.getElementById('agencyForm').action = "{{ route('admin.agencies.store') }}";
        document.getElementById('formMethod').value = "POST";
        document.getElementById('modalTitle').innerText = "Enregistrement d'un Nouveau Nœud";
        document.getElementById('agencyForm').reset();
        document.getElementById('agencyModal').classList.remove('hidden');
    }

    function openEditModal(id) {
        fetch(`/admin/agencies/${id}/json`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? ''
            }
        })
            .then(response => {
                if (!response.ok) throw new Error('Erreur HTTP ' + response.status);
                const contentType = response.headers.get('content-type') ?? '';
                if (!contentType.includes('application/json')) {
                    throw new Error('Réponse inattendue (non-JSON). Vérifiez la session Laravel.');
                }
                return response.json();
            })
            .then(payload => {
                if (payload.success) {
                    const agency = payload.data;
                    document.getElementById('agencyForm').action = `/admin/agencies/${id}`;
                    document.getElementById('formMethod').value = "PUT";
                    document.getElementById('modalTitle').innerText = "Mise à jour de l'Infrastructure";
                    
                    document.getElementById('agency_name').value = agency.name ?? '';
                    document.getElementById('agency_code').value = agency.code ?? '';
                    document.getElementById('agency_city').value = agency.city ?? '';
                    document.getElementById('agency_region').value = agency.region ?? '';
                    document.getElementById('agency_manager').value = agency.manager_id ?? '';
                    document.getElementById('agency_active').checked = !!agency.is_active;

                    // Champs financiers et contact
                    document.getElementById('agency_vault_balance').value = agency.vault_balance ?? 0;
                    document.getElementById('agency_cash_limit').value = agency.cash_limit ?? 0;
                    document.getElementById('agency_address').value = agency.address ?? '';
                    document.getElementById('agency_phone').value = agency.phone ?? '';

                    document.getElementById('agencyModal').classList.remove('hidden');
                } else {
                    alert('Erreur: ' + (payload.message ?? 'Impossible de charger les données.'));
                }
            })
            .catch(error => {
                console.error('Erreur chargement agence:', error);
                alert('Erreur: ' + error.message);
            });
    }

    function closeModal() {
        document.getElementById('agencyModal').classList.add('hidden');
    }

    function confirmDelete(id, name) {
        document.getElementById('deleteForm').action = `/admin/agencies/${id}`;
        document.getElementById('deleteAgencyName').innerText = name;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }

    function viewAgency(id) {
        window.location.href = `/admin/agencies/${id}`;
    }
</script>
@endpush
@endsection
