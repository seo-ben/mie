@extends('layouts.app_admin')

@section('title', 'Trésorerie - Mouvements du Coffre-fort')
@section('page-title', 'Gestion de la Trésorerie d\'Agence')

@section('content')
<div class="space-y-8">

    {{-- En-tête --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight uppercase">Trésorerie Centrale</h2>
            <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest mt-1">Coffres-forts, mouvements de fonds & initialisation du système</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="openInitModal()" class="px-5 py-3 bg-indigo-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-700 transition shadow-lg flex items-center gap-2">
                <i class="fas fa-database"></i> Initialiser une Caisse
            </button>
            <button onclick="openMovementModal('credit')" class="px-5 py-3 bg-emerald-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-emerald-700 transition shadow-lg flex items-center gap-2">
                <i class="fas fa-plus-circle"></i> Entrée de Fonds
            </button>
            <button onclick="openMovementModal('debit')" class="px-5 py-3 bg-rose-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-rose-700 transition shadow-lg flex items-center gap-2">
                <i class="fas fa-minus-circle"></i> Sortie de Fonds
            </button>
        </div>
    </div>

    {{-- Alertes --}}
    @if(session('success'))
        <div class="flex items-center gap-3 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-xs font-bold">
            <i class="fas fa-check-circle text-emerald-500"></i>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="flex items-center gap-3 p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs font-bold">
            <i class="fas fa-exclamation-circle text-rose-500 text-lg"></i>
            {{ session('error') }}
        </div>
    @endif

    {{-- Coffres-forts par agence --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        @forelse($vaultStats as $agency)
            @php
                $ratio = $agency->cash_limit > 0 ? ($agency->vault_balance / $agency->cash_limit) * 100 : 100;
                $color = $ratio >= 50 ? 'emerald' : ($ratio >= 20 ? 'amber' : 'rose');
            @endphp
            <div class="bank-card p-6 relative overflow-hidden group hover:shadow-xl transition-shadow">
                {{-- Fond décoratif --}}
                <div class="absolute -top-4 -right-4 w-24 h-24 rounded-full bg-{{ $color }}-50 border border-{{ $color }}-100 opacity-60 group-hover:scale-125 transition-transform duration-500"></div>

                <div class="relative">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Coffre-fort</p>
                            <h3 class="text-sm font-black text-slate-900 mt-0.5">{{ $agency->name }}</h3>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-{{ $color }}-100 border border-{{ $color }}-200 flex items-center justify-center text-{{ $color }}-600">
                            <i class="fas fa-vault text-sm"></i>
                        </div>
                    </div>

                    <div class="mb-4">
                        <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Solde Actuel (Disponible)</p>
                        <p class="text-2xl font-black text-{{ $color }}-700 font-numeric">
                            {{ number_format($agency->vault_balance, 0, ',', ' ') }}
                            <span class="text-xs font-bold text-slate-400 ml-1">XOF</span>
                        </p>
                    </div>

                    @if($agency->cash_limit > 0)
                        <div class="space-y-1">
                            <div class="flex justify-between text-[9px] font-bold uppercase">
                                <span class="text-slate-400">Niveau vs Seuil d'alerte</span>
                                <span class="text-{{ $color }}-600">{{ number_format(min($ratio, 100), 0) }}%</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                <div class="bg-{{ $color }}-500 h-full rounded-full transition-all duration-700"
                                     style="width: {{ min($ratio, 100) }}%"></div>
                            </div>
                            <p class="text-[8px] text-slate-400 font-bold tracking-wide">
                                Seuil : {{ number_format($agency->cash_limit, 0, ',', ' ') }} XOF
                            </p>
                        </div>
                    @else
                        <p class="text-[9px] text-slate-300 italic font-bold">Aucun seuil d'alerte défini</p>
                    @endif

                    {{-- Statut visuel --}}
                    <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between">
                        @if($agency->vault_balance == 0)
                            <span class="flex items-center gap-1.5 text-[9px] font-black text-rose-600 uppercase">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                Non initialisé — Solde zéro
                            </span>
                        @elseif($color === 'rose')
                            <span class="flex items-center gap-1.5 text-[9px] font-black text-rose-600 uppercase">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                Liquidité critique
                            </span>
                        @elseif($color === 'amber')
                            <span class="flex items-center gap-1.5 text-[9px] font-black text-amber-600 uppercase">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                Liquidité modérée
                            </span>
                        @else
                            <span class="flex items-center gap-1.5 text-[9px] font-black text-emerald-600 uppercase">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Bonne liquidité
                            </span>
                        @endif
                        <button onclick='selectAgency({{ $agency->id }}, "{{ $agency->name }}")' class="text-[9px] font-black text-blue-600 hover:underline uppercase">Opérer →</button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-16 text-center bank-card border-dashed">
                <i class="fas fa-vault text-4xl text-slate-200 mb-4"></i>
                <p class="text-xs font-black text-slate-400 uppercase">Aucune agence active trouvée</p>
            </div>
        @endforelse
    </div>

    {{-- Résumé total --}}
    <div class="bank-card p-6 bg-slate-900 text-white flex items-center justify-between">
        <div>
            <p class="text-[9px] font-black text-blue-400 uppercase tracking-widest">Liquidité Consolidée — Réseau Total</p>
            <p class="text-4xl font-black mt-1 font-numeric">{{ number_format($totalVaultBalance, 0, ',', ' ') }} <span class="text-base text-white/40">XOF</span></p>
        </div>
        <div class="w-16 h-16 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-blue-400">
            <i class="fas fa-building-columns text-2xl"></i>
        </div>
    </div>

    {{-- Filtres journal --}}
    <div class="bank-card p-5">
        <form method="GET" action="{{ route('admin.treasury.index') }}" class="grid grid-cols-2 md:grid-cols-5 gap-3">
            <select name="agency_id" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-600 focus:ring-1 focus:ring-blue-500 outline-none">
                <option value="">Toutes les agences</option>
                @foreach($agencies as $ag)
                    <option value="{{ $ag->id }}" {{ request('agency_id') == $ag->id ? 'selected' : '' }}>{{ $ag->name }}</option>
                @endforeach
            </select>
            <select name="type" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-600 focus:ring-1 focus:ring-blue-500 outline-none">
                <option value="">Tous types</option>
                <option value="credit" {{ request('type') === 'credit' ? 'selected' : '' }}>Entrée (Crédit)</option>
                <option value="debit" {{ request('type') === 'debit' ? 'selected' : '' }}>Sortie (Débit)</option>
            </select>
            <input type="date" name="date_start" value="{{ request('date_start') }}" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-600 focus:ring-1 focus:ring-blue-500 outline-none">
            <input type="date" name="date_end" value="{{ request('date_end') }}" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-600 focus:ring-1 focus:ring-blue-500 outline-none">
            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-slate-900 text-white rounded-xl text-xs font-black uppercase hover:bg-slate-800 transition">
                    <i class="fas fa-filter mr-1"></i> Filtrer
                </button>
                <a href="{{ route('admin.treasury.index') }}" class="w-10 bg-slate-100 text-slate-600 rounded-xl flex items-center justify-center hover:bg-slate-200 transition">
                    <i class="fas fa-rotate text-xs"></i>
                </a>
            </div>
        </form>
    </div>

    {{-- Journal des mouvements --}}
    <div class="bank-card overflow-hidden !p-0">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <h3 class="text-[10px] font-black text-slate-700 uppercase tracking-widest flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full bg-slate-800 animate-pulse"></span>
                Journal des Mouvements de Trésorerie
            </h3>
            <span class="text-[9px] font-black text-slate-400 uppercase bg-white border border-slate-200 px-2 py-1 rounded">
                {{ $movements->total() }} mouvement(s)
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="bank-table !mb-0">
                <thead>
                    <tr>
                        <th class="!px-6">Date & Référence</th>
                        <th>Agence</th>
                        <th>Motif</th>
                        <th>Validé par</th>
                        <th class="text-right">Avant</th>
                        <th class="text-center">Mouvement</th>
                        <th class="text-right !px-6">Après</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($movements as $mv)
                        @php
                            $isCredit = $mv->type === 'credit';
                            $motifLabels = [
                                'initialisation_systeme' => ['🔧 Init. Système', 'indigo'],
                                'apport_fonds'           => ['💰 Apport de Fonds', 'emerald'],
                                'retrait_fonds'          => ['🏦 Retrait de Fonds', 'rose'],
                                'ajustement_caisse'      => ['⚖️ Ajustement', 'amber'],
                                'remboursement'          => ['↩️ Remboursement', 'blue'],
                                'autre'                  => ['📝 Autre', 'slate'],
                            ];
                            $motifInfo = $motifLabels[$mv->motive] ?? ['📝 ' . ucfirst($mv->motive), 'slate'];
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="!px-6">
                                <p class="text-[10px] font-black text-slate-800 uppercase">{{ $mv->created_at->format('d/m/Y H:i') }}</p>
                                <p class="text-[8px] font-mono font-bold text-blue-600 mt-0.5">{{ $mv->reference }}</p>
                            </td>
                            <td>
                                <span class="text-xs font-black text-slate-700 uppercase">{{ $mv->agency->name ?? '—' }}</span>
                            </td>
                            <td>
                                <span class="text-[9px] font-black px-2 py-1 rounded-full border border-{{ $motifInfo[1] }}-100 bg-{{ $motifInfo[1] }}-50 text-{{ $motifInfo[1] }}-700 uppercase tracking-widest">
                                    {{ $motifInfo[0] }}
                                </span>
                                @if($mv->notes)
                                    <p class="text-[8px] text-slate-400 mt-1 italic max-w-[200px] truncate">{{ $mv->notes }}</p>
                                @endif
                            </td>
                            <td>
                                <span class="text-[10px] font-bold text-slate-600">{{ $mv->processedBy->full_name ?? 'SYSTÈME' }}</span>
                            </td>
                            <td class="text-right font-numeric text-xs text-slate-500 font-bold">
                                {{ number_format($mv->balance_before, 0, ',', ' ') }}
                            </td>
                            <td class="text-center">
                                <span class="text-xs font-black {{ $isCredit ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $isCredit ? '+' : '-' }}{{ number_format($mv->amount, 0, ',', ' ') }} XOF
                                </span>
                            </td>
                            <td class="text-right !px-6 font-numeric text-xs font-black {{ $isCredit ? 'text-emerald-700' : 'text-rose-700' }}">
                                {{ number_format($mv->balance_after, 0, ',', ' ') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-20 text-center">
                                <i class="fas fa-file-invoice-dollar text-4xl text-slate-200 mb-4 block"></i>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest italic">Aucun mouvement enregistré</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($movements->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/30">
                {{ $movements->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>

{{-- MODAL : Initialisation du Système --}}
<div id="initModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-sm">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md animate-scale-in">
        <div class="px-8 pt-8 pb-6">
            <div class="flex items-center justify-between mb-6">
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 text-xl">
                    <i class="fas fa-database"></i>
                </div>
                <button onclick="closeInitModal()" class="text-slate-400 hover:text-slate-700 transition"><i class="fas fa-times"></i></button>
            </div>
            <h3 class="text-lg font-black text-slate-900 uppercase tracking-tight">Initialiser le Solde</h3>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">
                Utilisé au démarrage du système pour entrer le montant réel en caisse
            </p>

            <form action="{{ route('admin.treasury.initialize') }}" method="POST" class="mt-6 space-y-4">
                @csrf
                <div class="space-y-1.5">
                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Agence Cible</label>
                    <select name="agency_id" id="init_agency_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs font-bold text-slate-700 focus:ring-1 focus:ring-indigo-500 outline-none">
                        <option value="">-- Sélectionner une agence --</option>
                        @foreach($agencies as $ag)
                            <option value="{{ $ag->id }}">{{ $ag->name }} — Solde actuel : {{ number_format($ag->vault_balance, 0, ',', ' ') }} XOF</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Montant Initial (XOF)</label>
                    <div class="relative">
                        <input type="number" name="initial_balance" required min="0" step="100"
                               class="w-full bg-indigo-50/50 border border-indigo-100 rounded-xl px-4 py-4 text-2xl font-black text-indigo-700 focus:ring-1 focus:ring-indigo-500 outline-none font-numeric"
                               placeholder="0">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[9px] font-black text-indigo-300 uppercase">XOF</span>
                    </div>
                </div>
                <div class="space-y-1.5">
                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Note (optionnel)</label>
                    <input type="text" name="notes" placeholder="Ex: Fonds de démarrage comptés ce jour…" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs font-bold text-slate-700 focus:ring-1 focus:ring-indigo-500 outline-none">
                </div>
                <button type="submit" class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl text-xs font-black uppercase tracking-widest transition shadow-xl shadow-indigo-500/20 mt-2">
                    <i class="fas fa-check mr-2"></i> Confirmer l'Initialisation
                </button>
            </form>
        </div>
    </div>
</div>

{{-- MODAL : Mouvement de fonds (Crédit / Débit) --}}
<div id="movementModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-sm">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md animate-scale-in">
        <div class="px-8 pt-8 pb-6">
            <div class="flex items-center justify-between mb-6">
                <div id="mv_icon_wrap" class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl"></div>
                <button onclick="closeMovementModal()" class="text-slate-400 hover:text-slate-700 transition"><i class="fas fa-times"></i></button>
            </div>
            <h3 id="mv_title" class="text-lg font-black text-slate-900 uppercase tracking-tight"></h3>
            <p id="mv_subtitle" class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1"></p>

            <form action="{{ route('admin.treasury.store') }}" method="POST" class="mt-6 space-y-4">
                @csrf
                <input type="hidden" name="type" id="mv_type">
                <div class="space-y-1.5">
                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Agence Cible</label>
                    <select name="agency_id" id="mv_agency_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs font-bold text-slate-700 focus:ring-1 focus:ring-blue-500 outline-none">
                        <option value="">-- Sélectionner une agence --</option>
                        @foreach($agencies as $ag)
                            <option value="{{ $ag->id }}" data-balance="{{ $ag->vault_balance }}">{{ $ag->name }} ({{ number_format($ag->vault_balance, 0, ',', ' ') }} XOF)</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Montant (XOF)</label>
                    <div class="relative">
                        <input type="number" name="amount" id="mv_amount" required min="1" step="100"
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-4 text-2xl font-black text-slate-800 focus:ring-1 focus:ring-blue-500 outline-none font-numeric"
                               placeholder="0">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[9px] font-black text-slate-300 uppercase">XOF</span>
                    </div>
                </div>
                <div class="space-y-1.5">
                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Motif du Mouvement</label>
                    <select name="motive" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs font-bold text-slate-700 focus:ring-1 focus:ring-blue-500 outline-none">
                        <option value="">-- Motif --</option>
                        <option value="apport_fonds">Apport de fonds extérieur</option>
                        <option value="retrait_fonds">Retrait vers la banque</option>
                        <option value="ajustement_caisse">Ajustement de caisse</option>
                        <option value="remboursement">Remboursement reçu</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Note descriptive</label>
                    <textarea name="notes" rows="2" placeholder="Détail de l'opération..."
                              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs font-bold text-slate-700 focus:ring-1 focus:ring-blue-500 outline-none resize-none"></textarea>
                </div>
                <button type="submit" id="mv_submit_btn" class="w-full py-4 rounded-2xl text-xs font-black uppercase tracking-widest text-white transition-all shadow-xl mt-2">
                    <i class="fas fa-check mr-2"></i> Valider l'Opération
                </button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let selectedAgencyId = null;

    function selectAgency(agencyId, name) {
        selectedAgencyId = agencyId;
        // Pré-sélectionner l'agence dans les modaux
        document.getElementById('mv_agency_id').value = agencyId;
        document.getElementById('init_agency_id').value = agencyId;
        openMovementModal('credit');
    }

    function openInitModal() {
        document.getElementById('initModal').classList.remove('hidden');
    }

    function closeInitModal() {
        document.getElementById('initModal').classList.add('hidden');
    }

    function openMovementModal(type) {
        const icon = document.getElementById('mv_icon_wrap');
        const title = document.getElementById('mv_title');
        const subtitle = document.getElementById('mv_subtitle');
        const btn = document.getElementById('mv_submit_btn');
        document.getElementById('mv_type').value = type;

        if (type === 'credit') {
            icon.className = 'w-12 h-12 rounded-2xl flex items-center justify-center text-xl bg-emerald-50 border border-emerald-100 text-emerald-600';
            icon.innerHTML = '<i class="fas fa-plus-circle"></i>';
            title.innerText = 'Entrée de Fonds';
            subtitle.innerText = 'Créditer le coffre-fort de l\'agence';
            btn.className = 'w-full py-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl text-xs font-black uppercase tracking-widest transition-all shadow-xl shadow-emerald-500/20 mt-2';
        } else {
            icon.className = 'w-12 h-12 rounded-2xl flex items-center justify-center text-xl bg-rose-50 border border-rose-100 text-rose-600';
            icon.innerHTML = '<i class="fas fa-minus-circle"></i>';
            title.innerText = 'Sortie de Fonds';
            subtitle.innerText = 'Débiter le coffre-fort de l\'agence';
            btn.className = 'w-full py-4 bg-rose-600 hover:bg-rose-700 text-white rounded-2xl text-xs font-black uppercase tracking-widest transition-all shadow-xl shadow-rose-500/20 mt-2';
        }

        document.getElementById('movementModal').classList.remove('hidden');
    }

    function closeMovementModal() {
        document.getElementById('movementModal').classList.add('hidden');
    }

    // Fermer les modaux en cliquant à l'extérieur
    document.getElementById('initModal').addEventListener('click', function(e) {
        if (e.target === this) closeInitModal();
    });
    document.getElementById('movementModal').addEventListener('click', function(e) {
        if (e.target === this) closeMovementModal();
    });
</script>
@endpush
@endsection
