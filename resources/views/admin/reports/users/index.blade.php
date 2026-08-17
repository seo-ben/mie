@extends('layouts.app_admin')

@section('title', 'Analytique de Performance Institutionnelle')
@section('page-title', 'Protocole / Analytique de Performance')

@section('content')
<div class="space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Intelligence de Performance du Personnel</h2>
            <p class="text-slate-500 text-sm font-medium">Analytique comparative et surveillance de l'efficacité opérationnelle à travers le réseau d'officiers</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="openCompareModal()" class="btn-bank btn-bank-outline">
                <i class="fas fa-microscope mr-2 text-[10px]"></i> Audit Comparatif
            </button>
            <button onclick="exportAllData()" class="btn-bank btn-bank-primary">
                <i class="fas fa-file-export mr-2 text-[10px]"></i> Export Intelligence
            </button>
        </div>
    </div>

    <!-- Graphique de Performance Holistique -->
    <div class="bank-card p-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h3 class="text-xs font-extrabold text-slate-700 uppercase tracking-widest flex items-center gap-2">
                    <i class="fas fa-chart-line text-blue-600"></i> Flux Opérationnel Global (30j)
                </h3>
                <p class="text-[10px] text-slate-400 mt-1 font-bold italic">Données consolidées du réseau d'agences</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                    <span class="text-[9px] font-bold text-slate-500 uppercase">Volume Ops</span>
                </div>
            </div>
        </div>
        <div class="h-48">
            <canvas id="globalPerformanceChart"></canvas>
        </div>
    </div>

    <!-- Matrice Analytique -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bank-card p-5 border-l-4 border-blue-600 hover:shadow-lg transition-shadow">
            <span class="kpi-label">Effectif du Personnel</span>
            <div class="kpi-value !text-2xl mt-1 text-slate-900">{{ number_format($stats['total_users']) }}</div>
            <div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-100">
                <span class="text-[9px] font-bold text-slate-400 uppercase">Opérationnel: {{ $stats['active_users'] }}</span>
                <span class="text-[9px] font-extrabold text-blue-600 px-2 py-0.5 bg-blue-50 rounded">
                    {{ $stats['total_users'] > 0 ? round(($stats['active_users'] / $stats['total_users']) * 100) : 0 }}% Saturation
                </span>
            </div>
        </div>

        <div class="bank-card p-5 border-l-4 border-emerald-500 hover:shadow-lg transition-shadow">
            <span class="kpi-label">Officiers Actifs (24h)</span>
            <div class="kpi-value !text-2xl mt-1 text-emerald-600">{{ number_format($stats['users_logged_today']) }}</div>
            <div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-100">
                <span class="text-[9px] font-bold text-slate-400 uppercase">Débit Hebdomadaire:</span>
                <span class="text-[9px] font-extrabold text-emerald-600">{{ $stats['users_logged_week'] }}</span>
            </div>
        </div>

        <div class="bank-card p-5 border-l-4 border-purple-600 hover:shadow-lg transition-shadow">
            <span class="kpi-label">Capture Adhérents Total</span>
            <div class="kpi-value !text-2xl mt-1 text-purple-600">{{ number_format($stats['total_clients']) }}</div>
            <div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-100">
                <span class="text-[9px] font-bold text-slate-400 uppercase">Moyenne / Officier:</span>
                <span class="text-[9px] font-extrabold text-purple-600 px-2 py-0.5 bg-purple-50 rounded">
                    {{ number_format($stats['total_clients'] / max($stats['active_users'], 1), 1) }}
                </span>
            </div>
        </div>

        <div class="bank-card p-5 border-l-4 border-amber-500 hover:shadow-lg transition-shadow">
            <span class="kpi-label">Poids Transactionnel / Jour</span>
            <div class="kpi-value !text-2xl mt-1 text-amber-600">{{ number_format($stats['total_amount_today'], 0, ',', ' ') }} <small class="text-xs">XOF</small></div>
            <div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-100">
                <span class="text-[9px] font-bold text-slate-400 uppercase">Fréquence:</span>
                <span class="text-[9px] font-extrabold text-amber-600">{{ number_format($stats['total_transactions_today']) }} Ops</span>
            </div>
        </div>
    </div>

    <!-- Filtres d'Audit -->
    <div class="bank-card p-6 bg-slate-50 border-slate-200 shadow-inner">
        <form method="GET" action="{{ route('admin.reports.users.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <div class="md:col-span-3 relative">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Recherche par Nom, Email, Nœud..." class="w-full bg-white border border-slate-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:ring-1 focus:ring-blue-500 outline-none transition uppercase">
                <i class="fas fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            </div>

            <div class="md:col-span-2">
                <select name="role" class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold text-slate-600 focus:ring-1 focus:ring-blue-500 outline-none transition uppercase">
                    <option value="">Rôles Institutionnels</option>
                    @foreach([
                        'administrateur_systeme' => 'Système',
                        'administrateur_reglementaire' => 'Réglementaire',
                        'manager_agence' => 'Manager Agence',
                        'gestionnaire_credit' => 'Gérant Crédit',
                        'agent_agence' => 'Agent Agence',
                        'agent_terrain' => 'Agent Terrain'
                    ] as $val => $label)
                        <option value="{{ $val }}" {{ request('role') == $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2">
                <select name="agency_id" class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold text-slate-600 focus:ring-1 focus:ring-blue-500 outline-none transition uppercase">
                    <option value="">Divisions Régionales</option>
                    @foreach($agencies as $agency)
                        <option value="{{ $agency->id }}" {{ request('agency_id') == $agency->id ? 'selected' : '' }}>{{ $agency->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2">
                <select name="activity_period" class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold text-slate-600 focus:ring-1 focus:ring-blue-500 outline-none transition uppercase">
                    <option value="">Fenêtre Audit</option>
                    <option value="today" {{ request('activity_period') == 'today' ? 'selected' : '' }}>24 Heures</option>
                    <option value="week" {{ request('activity_period') == 'week' ? 'selected' : '' }}>7 Jours</option>
                    <option value="month" {{ request('activity_period') == 'month' ? 'selected' : '' }}>30 Jours</option>
                </select>
            </div>

            <div class="md:col-span-3 flex gap-2">
                <button type="submit" class="btn-bank btn-bank-primary flex-1">
                    <i class="fas fa-filter mr-2 text-[10px]"></i> Analyser
                </button>
                <a href="{{ route('admin.reports.users.index') }}" class="btn-bank btn-bank-outline px-4">
                    <i class="fas fa-rotate"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Registre Analytique -->
    <div class="bank-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="bank-table">
                <thead>
                    <tr>
                        <th class="w-10">
                            <input type="checkbox" id="selectAll" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        </th>
                        <th>Officier & Rang</th>
                        <th>Division</th>
                        <th>Adhérents</th>
                        <th>Statut</th>
                        <th>Dernier Accès</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $user)
                    <tr class="hover:bg-slate-50/50 transition-colors group" data-user-id="{{ $user->id }}">
                        <td>
                            <input type="checkbox" class="user-checkbox rounded border-slate-300 text-blue-600 focus:ring-blue-500" value="{{ $user->id }}">
                        </td>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl border border-slate-200 flex items-center justify-center bg-white shadow-sm text-[11px] font-black text-blue-600 group-hover:scale-110 transition-transform">
                                    {{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-black text-slate-800 leading-tight">{{ $user->full_name }}</p>
                                    <p class="text-[9px] font-bold text-blue-500 uppercase tracking-tighter mt-1 px-1.5 py-0.5 bg-blue-50 rounded w-fit">
                                        {{ ucfirst(str_replace('_', ' ', $user->role)) }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="text-xs font-bold text-slate-600">{{ $user->agency->name ?? 'Siège Central' }}</span>
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 bg-slate-100 text-slate-700 text-[10px] font-black rounded border border-slate-200">
                                    {{ $user->clients_count }}
                                </span>
                                @if($user->active_clients_count > 0)
                                    <span class="text-[8px] font-black text-emerald-600 uppercase bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-100">KYC: {{ $user->active_clients_count }}</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if($user->is_active)
                                <div class="flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span class="text-[9px] font-black text-emerald-700 uppercase">Actif</span>
                                </div>
                            @else
                                <div class="flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                    <span class="text-[9px] font-black text-rose-700 uppercase">Suspendu</span>
                                </div>
                            @endif
                        </td>
                        <td>
                            @if($user->last_login)
                                <div class="flex flex-col">
                                    <p class="text-[10px] font-bold text-slate-700">{{ $user->last_login->format('d M, H:i') }}</p>
                                    <p class="text-[8px] font-bold text-slate-400 uppercase tracking-tighter">{{ $user->last_login->diffForHumans() }}</p>
                                </div>
                            @else
                                <span class="text-[9px] font-bold text-slate-300 italic">Inactif</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.reports.users.show', $user->id) }}" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition" title="Dossier Analytique">
                                    <i class="fas fa-chart-pie text-xs"></i>
                                </a>
                                <a href="{{ route('admin.reports.users.export', $user->id) }}" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition" title="Export Intelligence">
                                    <i class="fas fa-file-arrow-down text-xs"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-20 text-center">
                            <div class="max-w-xs mx-auto">
                                <i class="fas fa-users-viewfinder text-3xl text-slate-200 mb-4 block"></i>
                                <h4 class="text-sm font-bold text-slate-800">Registre de Performance Vide</h4>
                                <p class="text-xs text-slate-400 mt-1 uppercase tracking-widest">Aucune donnée trouvée pour les filtres actifs</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/30">
            {{ $users->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Audit Comparatif -->
<div id="compareModal" class="fixed inset-0 z-50 flex items-center justify-center hidden p-4 bg-slate-900/60 backdrop-blur-sm">
    <div class="bank-card w-full max-w-xl animate-scale-in">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 rounded-t-2xl">
            <h3 class="text-xs font-extrabold text-slate-700 uppercase tracking-widest">Audit de Performance Comparatif</h3>
            <button onclick="closeCompareModal()" class="w-8 h-8 rounded-full flex items-center justify-center text-slate-400 hover:text-slate-900 hover:bg-white transition shadow-sm">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form action="{{ route('admin.reports.users.compare') }}" method="POST" class="p-8 space-y-6">
            @csrf
            <div>
                <label class="text-[9px] font-extrabold text-slate-400 uppercase tracking-widest mb-3 block">Entités d'Audit Sélectionnées (Max 5)</label>
                <div id="selectedUsers" class="min-h-[120px] border-2 border-dashed border-slate-200 rounded-2xl p-6 flex flex-wrap gap-3 items-start bg-slate-50/50">
                    <p class="text-[10px] font-bold text-slate-400 italic w-full text-center py-6">Cochez des officiers dans le registre pour les comparer</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[9px] font-extrabold text-slate-400 uppercase tracking-widest block">Début Fenêtre d'Audit</label>
                    <input type="date" name="start_date" value="{{ now()->startOfMonth()->format('Y-m-d') }}" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-xs font-bold focus:ring-1 focus:ring-blue-500 outline-none transition">
                </div>
                <div class="space-y-2">
                    <label class="text-[9px] font-extrabold text-slate-400 uppercase tracking-widest block">Fin Fenêtre d'Audit</label>
                    <input type="date" name="end_date" value="{{ now()->format('Y-m-d') }}" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-xs font-bold focus:ring-1 focus:ring-blue-500 outline-none transition">
                </div>
            </div>

            <div class="flex gap-3 pt-6">
                <button type="submit" id="compareBtn" disabled class="flex-1 btn-bank btn-bank-primary !py-4 !rounded-xl text-xs font-black uppercase tracking-widest disabled:grayscale disabled:opacity-50">
                    <i class="fas fa-microscope mr-2"></i> Exécuter Matrice Comparative
                </button>
                <button type="button" onclick="closeCompareModal()" class="btn-bank btn-bank-outline !py-4 px-8 !rounded-xl text-xs font-black uppercase tracking-widest">Abandonner</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // Configuration Graphique Global
    const globalCtx = document.getElementById('globalPerformanceChart').getContext('2d');
    new Chart(globalCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($chartData->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d/m'))) !!},
            datasets: [{
                label: 'Volume Ops',
                data: {!! json_encode($chartData->pluck('count')) !!},
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.05)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointRadius: 0,
                pointHitRadius: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
                    padding: 12,
                    titleFont: { family: 'Inter', size: 10, weight: 'bold' },
                    bodyFont: { family: 'Inter', size: 12 },
                    cornerRadius: 8
                }
            },
            scales: {
                y: { beginAtZero: true, display: false },
                x: { 
                    grid: { display: false },
                    ticks: { font: { family: 'Inter', size: 9, weight: '700' }, color: '#94a3b8' }
                }
            }
        }
    });

    // Logique de Sélection et Comparaison
    const selectedUserIds = new Set();

    document.getElementById('selectAll')?.addEventListener('change', function() {
        document.querySelectorAll('.user-checkbox').forEach(cb => {
            cb.checked = this.checked;
            this.checked ? selectedUserIds.add(cb.value) : selectedUserIds.delete(cb.value);
        });
        updateAuditControls();
    });

    document.querySelectorAll('.user-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            this.checked ? selectedUserIds.add(this.value) : selectedUserIds.delete(this.value);
            updateAuditControls();
        });
    });

    function updateAuditControls() {
        const btn = document.getElementById('compareBtn');
        if (btn) btn.disabled = selectedUserIds.size < 2 || selectedUserIds.size > 5;
        
        const container = document.getElementById('selectedUsers');
        container.innerHTML = '';
        
        if (selectedUserIds.size === 0) {
            container.innerHTML = '<p class="text-[10px] font-bold text-slate-400 italic w-full text-center py-6">Cochez des officiers dans le registre pour les comparer</p>';
            return;
        }

        selectedUserIds.forEach(id => {
            const tr = document.querySelector(`tr[data-user-id="${id}"]`);
            if (tr) {
                const name = tr.querySelector('.font-black.text-slate-800').innerText;
                const tag = document.createElement('div');
                tag.className = 'flex items-center gap-3 bg-white border border-slate-200 shadow-sm px-4 py-2 rounded-xl text-[10px] font-black text-slate-700 animate-scale-in';
                tag.innerHTML = `
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                    <span>${name}</span>
                    <button type="button" onclick="removeEntity('${id}')" class="text-slate-300 hover:text-rose-500 transition"><i class="fas fa-times-circle"></i></button>
                    <input type="hidden" name="user_ids[]" value="${id}">
                `;
                container.appendChild(tag);
            }
        });
    }

    function removeEntity(id) {
        selectedUserIds.delete(id);
        const cb = document.querySelector(`.user-checkbox[value="${id}"]`);
        if (cb) cb.checked = false;
        if (document.getElementById('selectAll')) document.getElementById('selectAll').checked = false;
        updateAuditControls();
    }

    function openCompareModal() {
        if (selectedUserIds.size < 2) return alert('Sélectionnez au moins 2 officiers pour activer la comparaison d\'audit.');
        if (selectedUserIds.size > 5) return alert('La matrice d\'audit comparatif est limitée à 5 entités simultanées.');
        document.getElementById('compareModal').classList.remove('hidden');
    }

    function closeCompareModal() {
        document.getElementById('compareModal').classList.add('hidden');
    }

    function exportAllData() {
        alert('Génération du Rapport d\'Intelligence Centralisé en cours...');
        // Logique d'exportation réelle ici
    }
</script>
@endpush
@endsection
