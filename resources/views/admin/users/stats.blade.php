@extends('layouts.admin')
@section('title', 'Statistiques des utilisateurs')
@section('page-title', 'Statistiques des utilisateurs')
@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="metric-card bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-700">Total des utilisateurs</h3>
                <p class="text-3xl font-bold text-gray-900">{{ $total_users }}</p>
            </div>
            <div class="metric-card bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-700">Utilisateurs actifs</h3>
                <p class="text-3xl font-bold text-gray-900">{{ $active_users }}</p>
            </div>
            <div class="metric-card bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-700">Utilisateurs inactifs</h3>
                <p class="text-3xl font-bold text-gray-900">{{ $total_users - $active_users }}</p>
            </div>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Répartition par rôle</h3>
            <div class="chart-container">
                <canvas id="roleChart"></canvas>
            </div>
        </div>
        <div class="mt-6">
            <a href="{{ route('admin.users.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">Retour</a>
        </div>
    </div>

    @push('scripts')
        <script>
            const ctx = document.getElementById('roleChart').getContext('2d');
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: @json(array_keys($stats_by_role)),
                    datasets: [{
                        data: @json(array_values($stats_by_role)),
                        backgroundColor: [
                            '#3B82F6',
                            '#10B981',
                            '#EF4444',
                            '#F59E0B',
                            '#8B5CF6'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    }
                }
            });
        </script>
    @endpush
@endsection
