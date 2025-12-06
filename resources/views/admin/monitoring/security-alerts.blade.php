@extends('layouts.app_admin')

@section('title', 'Alertes de sécurité')

@section('page-title', 'Alertes de sécurité')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="metric-card bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-700">Total des alertes</h3>
                <p class="text-3xl font-bold text-gray-900">{{ $summary['total_alerts'] }}</p>
            </div>
            <div class="metric-card bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-700">Alertes haute priorité</h3>
                <p class="text-3xl font-bold text-red-600">{{ $summary['high_priority'] }}</p>
            </div>
            <div class="metric-card bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-700">Dernière mise à jour</h3>
                <p class="text-lg text-gray-900">{{ \Carbon\Carbon::parse($summary['last_update'])->format('d/m/Y H:i') }}</p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Alertes de sécurité</h3>
            @if (empty($alerts))
                <p class="text-gray-500">Aucune alerte de sécurité.</p>
            @else
                <div class="space-y-4">
                    @foreach ($alerts as $alert)
                        <div class="border-l-4 {{ $alert['severity'] === 'high' ? 'border-red-500' : 'border-yellow-500' }} p-4 bg-gray-50">
                            <p class="font-semibold">{{ $alert['title'] ?? 'Alerte sans titre' }}</p>
                            <p class="text-sm text-gray-600">{{ $alert['description'] ?? 'Aucune description' }}</p>
                            <p class="text-xs text-gray-500">Sévérité: {{ ucfirst($alert['severity'] ?? 'inconnue') }} | {{ \Carbon\Carbon::parse($alert['timestamp'])->format('d/m/Y H:i') }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Alertes de fraude</h3>
            @if (empty($fraudAlerts))
                <p class="text-gray-500">Aucune alerte de fraude.</p>
            @else
                <div class="space-y-4">
                    @foreach ($fraudAlerts as $alert)
                        <div class="border-l-4 {{ $alert['risk_level'] === 'high' ? 'border-red-500' : 'border-yellow-500' }} p-4 bg-gray-50">
                            <p class="font-semibold">{{ $alert['title'] ?? 'Alerte sans titre' }}</p>
                            <p class="text-sm text-gray-600">{{ $alert['description'] ?? 'Aucune description' }}</p>
                            <p class="text-xs text-gray-500">Niveau de risque: {{ ucfirst($alert['risk_level'] ?? 'inconnu') }} | {{ \Carbon\Carbon::parse($alert['timestamp'])->format('d/m/Y H:i') }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Connexions suspectes</h3>
            @if (empty($loginAlerts))
                <p class="text-gray-500">Aucune connexion suspecte.</p>
            @else
                <div class="space-y-4">
                    @foreach ($loginAlerts as $alert)
                        <div class="border-l-4 {{ $alert['priority'] === 'high' ? 'border-red-500' : 'border-yellow-500' }} p-4 bg-gray-50">
                            <p class="font-semibold">{{ $alert['title'] ?? 'Connexion suspecte' }}</p>
                            <p class="text-sm text-gray-600">{{ $alert['description'] ?? 'Aucune description' }}</p>
                            <p class="text-xs text-gray-500">Priorité: {{ ucfirst($alert['priority'] ?? 'inconnue') }} | {{ \Carbon\Carbon::parse($alert['timestamp'])->format('d/m/Y H:i') }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection

