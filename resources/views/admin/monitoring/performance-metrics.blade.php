@extends('layouts.app_admin')

@section('title', 'Métriques de performance')

@section('page-title', 'Métriques de performance')

@section('content')
    <div class="max-w-7xl mx-auto">
        <form method="GET" action="{{ route('admin.monitoring.performance-metrics') }}" class="mb-6">
            <select name="period" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" onchange="this.form.submit()">
                <option value="1h" {{ $period === '1h' ? 'selected' : '' }}>1 heure</option>
                <option value="24h" {{ $period === '24h' ? 'selected' : '' }}>24 heures</option>
                <option value="7d" {{ $period === '7d' ? 'selected' : '' }}>7 jours</option>
                <option value="30d" {{ $period === '30d' ? 'selected' : '' }}>30 jours</option>
            </select>
        </form>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="metric-card bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-700">Santé du système</h3>
                <p class="text-3xl font-bold {{ $metrics['system_health']['status'] === 'healthy' ? 'text-green-600' : 'text-red-600' }}">
                    {{ ucfirst($metrics['system_health']['status'] ?? 'Inconnu') }}
                </p>
            </div>
            <div class="metric-card bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-700">Temps de réponse API</h3>
                <p class="text-3xl font-bold text-gray-900">{{ number_format($metrics['api_performance']['avg_response_time'] ?? 0, 2) }} ms</p>
            </div>
            <div class="metric-card bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-700">Taux d'erreur</h3>
                <p class="text-3xl font-bold text-gray-900">{{ number_format($metrics['error_rates']['error_rate'] ?? 0, 2) }}%</p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Performance API</h3>
            <div class="chart-container">
                <canvas id="apiPerformanceChart"></canvas>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Statistiques base de données</h3>
                <ul class="space-y-2">
                    <li><strong>Requêtes:</strong> {{ $metrics['database_stats']['query_count'] ?? 0 }}</li>
                    <li><strong>Temps moyen:</strong> {{ number_format($metrics['database_stats']['avg_query_time'] ?? 0, 2) }} ms</li>
                    <li><strong>Connexions actives:</strong> {{ $metrics['database_stats']['active_connections'] ?? 0 }}</li>
                </ul>
            </div>
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Statistiques de file d'attente</h3>
                <ul class="space-y-2">
                    <li><strong>Jobs en attente:</strong> {{ $metrics['queue_stats']['pending_jobs'] ?? 0 }}</li>
                    <li><strong>Jobs échoués:</strong> {{ $metrics['queue_stats']['failed_jobs'] ?? 0 }}</li>
                    <li><strong>Temps de traitement moyen:</strong> {{ number_format($metrics['queue_stats']['avg_processing_time'] ?? 0, 2) }} ms</li>
                </ul>
            </div>
        </div>

        @push('scripts')
            <script>
                const ctx = document.getElementById('apiPerformanceChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: @json($metrics['api_performance']['response_times'] ? array_keys($metrics['api_performance']['response_times']) : []),
                        datasets: [{
                            label: 'Temps de réponse API (ms)',
                            data: @json($metrics['api_performance']['response_times'] ? array_values($metrics['api_performance']['response_times']) : []),
                            borderColor: '#3B82F6',
                            fill: false
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            </script>
        @endpush
    </div>
@endsection
