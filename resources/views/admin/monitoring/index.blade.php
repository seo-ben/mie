@extends('layouts.app_admin')

@section('title', 'Console de Supervision Système')
@section('page-title', 'Surveillance des Protocoles en Temps Réel')

@section('content')
<div class="space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Surveillance de l'Infrastructure en Temps Réel</h2>
            <p class="text-slate-500 text-sm font-medium">Télémétrie en direct et supervision des indicateurs opérationnels</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3 py-1 bg-blue-50 text-blue-700 text-[10px] font-extrabold rounded-full border border-blue-100 uppercase tracking-tighter animate-pulse">
                <i class="fas fa-tower-broadcast mr-1"></i> Flux de Données Actif
            </span>
        </div>
    </div>

    {{-- ==== Matrice de Télémétrie ==== --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- Intégrité du compte --}}
        <div class="bank-card p-6 border-trust relative overflow-hidden">
            @php
                $status = $overview['system_health']['status'] ?? 'inconnu';
                $translatedStatus = match($status) {
                    'healthy' => 'OPTIMAL',
                    'warning' => 'ATTENTION',
                    'critical', 'error' => 'CRITIQUE',
                    default => 'INCONNU'
                };
                $statusClass = match($status) {
                    'healthy' => 'text-emerald-600 border-emerald-500',
                    'warning' => 'text-amber-500 border-amber-500',
                    'critical', 'error' => 'text-rose-600 border-rose-500',
                    default => 'text-slate-500 border-slate-500'
                };
            @endphp
            <div class="flex items-center justify-between mb-4">
                <span class="kpi-label">Intégrité du Système</span>
                <span class="w-2 h-2 rounded-full {{ str_replace('text', 'bg', explode(' ', $statusClass)[0]) }} animate-ping"></span>
            </div>
            <div class="kpi-value !text-3xl mt-1 {{ explode(' ', $statusClass)[0] }} uppercase font-black">{{ $translatedStatus }}</div>
            <div class="mt-4 pt-4 border-t border-slate-100 grid grid-cols-2 gap-2">
                <div>
                    <p class="text-[8px] font-black text-slate-400 uppercase">Utilisation CPU</p>
                    <p class="text-xs font-black text-slate-800">{{ $overview['system_health']['cpu_usage'] ??'N/A' }}%</p>
                </div>
                <div>
                    <p class="text-[8px] font-black text-slate-400 uppercase">Mémoire RAM</p>
                    <p class="text-xs font-black text-slate-800">{{ $overview['system_health']['memory_usage'] ??'N/A' }}%</p>
                </div>
            </div>
            <p class="text-[8px] font-bold text-slate-400 uppercase mt-3 italic">Moteur de Base de Données: {{ $overview['system_health']['database'] ?? 'inconnu' }}</p>
        </div>

        {{-- Alertes d'Incidents --}}
        <div class="bank-card p-6">
            <div class="flex items-center justify-between mb-4">
                <span class="kpi-label">Incidents de Sécurité</span>
                <i class="fas fa-shield-exclamation text-rose-500 text-xs"></i>
            </div>
            <div class="kpi-value !text-3xl mt-1 text-slate-900">{{ number_format($overview['total_alerts'] ?? 0) }}</div>
            <div class="mt-4 pt-4 border-t border-slate-100">
                <span class="text-[9px] font-black text-slate-400 uppercase">Registre d'Audit Institutionnel</span>
            </div>
        </div>

        {{-- Connexions Actives --}}
        <div class="bank-card p-6">
            <div class="flex items-center justify-between mb-4">
                <span class="kpi-label">Sessions Simultanées</span>
                <i class="fas fa-users-rays text-blue-500 text-xs"></i>
            </div>
            <div class="kpi-value !text-3xl mt-1 text-slate-900">{{ number_format($overview['active_users'] ?? 0) }}</div>
            <div class="mt-4 pt-4 border-t border-slate-100">
                <span class="text-[9px] font-black text-slate-400 uppercase">Points d'Accès Authentifiés</span>
            </div>
        </div>

        {{-- Débit des Protocoles --}}
        <div class="bank-card p-6">
            <div class="flex items-center justify-between mb-4">
                <span class="kpi-label">Débit des Protocoles</span>
                <i class="fas fa-bolt-lightning text-amber-500 text-xs"></i>
            </div>
            <div class="kpi-value !text-3xl mt-1 text-slate-900">{{ number_format($overview['api_requests'] ?? 0) }}</div>
            <div class="mt-4 pt-4 border-t border-slate-100">
                <span class="text-[9px] font-black text-slate-400 uppercase">Requêtes Globales (24H)</span>
            </div>
        </div>
    </div>

    {{-- ==== Graphique d'Analyse ==== --}}
    <div class="bank-card p-8">
        <h3 class="text-xs font-extrabold text-slate-700 uppercase tracking-widest mb-8 block flex items-center gap-2">
            <i class="fas fa-chart-column text-blue-600"></i> Vélocité Opérationnelle de l'Infrastructure
        </h3>
        <div class="h-96">
            <canvas id="overviewChart"></canvas>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('overviewChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Capacité Session', 'Requêtes Protocolaires', 'Alertes Sécurité'],
            datasets: [{
                label: 'Télémétrie Système',
                data: [
                    {{ $overview['active_users'] ?? 0 }},
                    {{ $overview['api_requests'] ?? 0 }},
                    {{ $overview['total_alerts'] ?? 0 }}
                ],
                backgroundColor: ['#3b82f6', '#10b981', '#ef4444'],
                borderRadius: 8,
                maxBarThickness: 60
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    padding: 12,
                    titleFont: { size: 10, weight: 'bold' },
                    bodyFont: { size: 12, weight: '900' }
                }
            },
            scales: {
                y: { 
                    beginAtZero: true, 
                    grid: { color: 'rgba(0,0,0,0.03)' },
                    ticks: { font: { family: 'Inter', weight: '700', size: 9 } }
                },
                x: { 
                    grid: { display: false },
                    ticks: { font: { family: 'Inter', weight: '700', size: 9 }, uppercase: true }
                }
            }
        }
    });
});
</script>
@endpush
