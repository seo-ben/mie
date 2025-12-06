@extends('layouts.app_admin')

@section('title', 'Tableau de bord de monitoring')
@section('page-title', 'Tableau de bord de monitoring')

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- ==== MÉTRIQUES PRINCIPALES ==== --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">

        {{-- Santé du système --}}
        <div class="metric-card bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-700">Santé du système</h3>
            @php
                $status = $overview['system_health']['status'] ?? 'inconnu';
                $statusColor = match($status) {
                    'healthy' => 'text-green-600',
                    'warning' => 'text-yellow-500',
                    'critical', 'error' => 'text-red-600',
                    default => 'text-gray-500'
                };
            @endphp
            <p class="text-3xl font-bold {{ $statusColor }}">
                {{ ucfirst($status) }}
            </p>
            <p class="text-sm text-gray-500 mt-2">
                CPU : {{ $overview['system_health']['cpu_usage'] ?? 'N/A' }}% —
                RAM : {{ $overview['system_health']['memory_usage'] ?? 'N/A' }}%
            </p>
            <p class="text-xs text-gray-400 mt-1">
                Base de données : {{ $overview['system_health']['database'] ?? 'inconnu' }}
            </p>
        </div>

        {{-- Alertes totales --}}
        <div class="metric-card bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-700">Alertes totales</h3>
            <p class="text-3xl font-bold text-gray-900">
                {{ number_format($overview['total_alerts'] ?? 0) }}
            </p>
        </div>

        {{-- Utilisateurs actifs --}}
        <div class="metric-card bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-700">Utilisateurs actifs</h3>
            <p class="text-3xl font-bold text-gray-900">
                {{ number_format($overview['active_users'] ?? 0) }}
            </p>
        </div>

        {{-- Requêtes API --}}
        <div class="metric-card bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-700">Requêtes API</h3>
            <p class="text-3xl font-bold text-gray-900">
                {{ number_format($overview['api_requests'] ?? 0) }}
            </p>
        </div>
    </div>

    {{-- ==== GRAPHIQUE DES PERFORMANCES ==== --}}
    <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Aperçu des performances</h3>
        <div class="chart-container">
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
            labels: ['Utilisateurs actifs', 'Requêtes API', 'Alertes'],
            datasets: [{
                label: 'Métriques système',
                data: [
                    {{ $overview['active_users'] ?? 0 }},
                    {{ $overview['api_requests'] ?? 0 }},
                    {{ $overview['total_alerts'] ?? 0 }}
                ],
                backgroundColor: ['#3B82F6', '#10B981', '#EF4444']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ctx.formattedValue + ' unités'
                    }
                }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f3f4f6' } },
                x: { grid: { display: false } }
            }
        }
    });
});
</script>
@endpush
