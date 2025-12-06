@extends('layouts.app_admin')
@section('title', 'Intégrations Externes')
@section('content')

<!-- Messages Flash -->
@if(session('success'))
<div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
    <div class="flex items-center">
        <i class="fas fa-check-circle text-green-500 mr-3"></i>
        <p class="text-green-700">{{ session('success') }}</p>
    </div>
</div>
@endif

<!-- Header -->
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Intégrations Externes</h2>
    <p class="text-gray-600 mt-1">Configuration des services tiers (Mobile Money, SMS, Email)</p>
</div>

<!-- Mobile Money Services -->
<div class="mb-8">
    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
        <i class="fas fa-mobile-alt text-blue-600"></i>
        Mobile Money
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach(['mtn', 'orange', 'moov'] as $service)
        @php
            $config = $integrations[$service] ?? [];
            $isActive = $config['enabled'] ?? false;
        @endphp
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <img src="{{ asset('assets/images/' . $availableServices[$service]['icon']) }}" alt="{{ $service }}" class="h-12">
                    <div class="flex items-center gap-2">
                        <span class="text-sm {{ $isActive ? 'text-green-600' : 'text-gray-400' }}">
                            {{ $isActive ? 'Actif' : 'Inactif' }}
                        </span>
                        <div class="w-12 h-6 bg-gray-200 rounded-full relative cursor-pointer" onclick="toggleService('{{ $service }}')">
                            <div class="toggle-dot absolute w-5 h-5 bg-white rounded-full shadow top-0.5 {{ $isActive ? 'right-0.5 bg-green-500' : 'left-0.5' }} transition-all"></div>
                        </div>
                    </div>
                </div>

                <h4 class="font-semibold text-gray-800 mb-2">{{ $availableServices[$service]['name'] }}</h4>

                <div class="space-y-2 mb-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Statut API:</span>
                        <span class="font-medium {{ ($config['api_status'] ?? 'down') === 'up' ? 'text-green-600' : 'text-red-600' }}">
                            {{ ucfirst($config['api_status'] ?? 'Non testé') }}
                        </span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Dernière vérif:</span>
                        <span class="text-gray-800">{{ $config['last_check'] ?? 'Jamais' }}</span>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button onclick="testIntegration('{{ $service }}')" class="flex-1 px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition text-sm">
                        <i class="fas fa-vial mr-1"></i>Tester
                    </button>
                    <button onclick="configureService('{{ $service }}')" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition text-sm">
                        <i class="fas fa-cog mr-1"></i>Config
                    </button>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Notification Services -->
<div>
    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
        <i class="fas fa-bell text-purple-600"></i>
        Services de Notification
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach(['sms', 'email'] as $service)
        @php
            $config = $integrations[$service] ?? [];
            $isActive = $config['enabled'] ?? false;
        @endphp
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-{{ $service === 'sms' ? 'green' : 'red' }}-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-{{ $service === 'sms' ? 'sms' : 'envelope' }} text-{{ $service === 'sms' ? 'green' : 'red' }}-600 text-xl"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-800">{{ $availableServices[$service]['name'] }}</h4>
                        <p class="text-sm text-gray-600">{{ $config['provider'] ?? 'Non configuré' }}</p>
                    </div>
                </div>
                <div class="w-12 h-6 bg-gray-200 rounded-full relative cursor-pointer" onclick="toggleService('{{ $service }}')">
                    <div class="toggle-dot absolute w-5 h-5 bg-white rounded-full shadow top-0.5 {{ $isActive ? 'right-0.5 bg-green-500' : 'left-0.5' }} transition-all"></div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-xs text-gray-600 mb-1">Envoyés (24h)</p>
                    <p class="text-xl font-bold text-gray-800">{{ number_format($config['sent_24h'] ?? 0) }}</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-xs text-gray-600 mb-1">Taux de succès</p>
                    <p class="text-xl font-bold text-green-600">{{ $config['success_rate'] ?? '0' }}%</p>
                </div>
            </div>

            <div class="flex gap-2">
                <button onclick="testIntegration('{{ $service }}')" class="flex-1 px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition text-sm">
                    <i class="fas fa-vial mr-1"></i>Tester
                </button>
                <button onclick="configureService('{{ $service }}')" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition text-sm">
                    <i class="fas fa-cog mr-1"></i>Configurer
                </button>
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Test Result Modal -->
<div id="testModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-lg w-full p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-800">Résultat du test</h3>
            <button onclick="hideTestModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="testResult" class="text-gray-600">
            <div class="flex items-center justify-center py-8">
                <i class="fas fa-spinner fa-spin text-4xl text-blue-600"></i>
            </div>
        </div>
    </div>
</div>

<script>
function testIntegration(service) {
    document.getElementById('testModal').classList.remove('hidden');
    document.getElementById('testResult').innerHTML = '<div class="flex items-center justify-center py-8"><i class="fas fa-spinner fa-spin text-4xl text-blue-600"></i></div>';

    fetch('{{ route("admin.config.integrations.test") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ service: service })
    })
    .then(response => response.json())
    .then(data => {
        const icon = data.success ? 'fa-check-circle text-green-500' : 'fa-times-circle text-red-500';
        const color = data.success ? 'green' : 'red';

        document.getElementById('testResult').innerHTML = `
            <div class="text-center py-4">
                <i class="fas ${icon} text-6xl mb-4"></i>
                <p class="text-lg font-semibold text-${color}-600 mb-2">${data.message}</p>
                ${data.response_time ? `<p class="text-sm text-gray-600">Temps de réponse: ${data.response_time}ms</p>` : ''}
            </div>
        `;
    })
    .catch(error => {
        document.getElementById('testResult').innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-times-circle text-red-500 text-6xl mb-4"></i>
                <p class="text-lg font-semibold text-red-600">Erreur lors du test</p>
            </div>
        `;
    });
}

function hideTestModal() {
    document.getElementById('testModal').classList.add('hidden');
}

function toggleService(service) {
    // Implémenter la logique de toggle
    console.log('Toggle service:', service);
}

function configureService(service) {
    // Rediriger vers la page de configuration
    window.location.href = `/admin/config/integrations/${service}`;
}
</script>

@endsection
