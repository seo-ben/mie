@extends('layouts.app_admin')

@section('title', 'Hub des Intégrations Institutionnelles')
@section('page-title', 'Protocole / Centre de Connecteurs')

@section('content')
<div class="space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Infrastructure des Connecteurs Institutionnels</h2>
            <p class="text-slate-500 text-sm font-medium">Configuration des passerelles de paiement Mobile Money et protocoles de communication</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3 py-1 bg-blue-50 text-blue-700 text-[10px] font-extrabold rounded-full border border-blue-100 uppercase tracking-tighter">
                <i class="fas fa-link mr-1"></i> Connexions aux Registres Actives
            </span>
        </div>
    </div>

    <!-- Mécanisme de Retour -->
    @if(session('success'))
    <div class="bank-card p-4 border-l-4 border-l-emerald-500 bg-emerald-50/50 flex items-center gap-3">
        <i class="fas fa-check-circle text-emerald-500"></i>
        <p class="text-xs font-black text-emerald-800 uppercase tracking-tight">{{ session('success') }}</p>
    </div>
    @endif

    <!-- Division Mobile Money -->
    <div>
        <h3 class="text-[10px] font-extrabold text-slate-700 uppercase tracking-[0.2em] mb-6 flex items-center gap-3">
            <i class="fas fa-mobile-screen-button text-blue-600"></i>
            Infrastructure de Paiement Mobile
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach(['mtn', 'orange', 'moov'] as $service)
            @php
                $config = $integrations[$service] ?? [];
                $isActive = $config['enabled'] ?? false;
            @endphp
            <div class="bank-card p-0 overflow-hidden group">
                <div class="p-8">
                    <div class="flex items-center justify-between mb-8">
                        <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center border border-slate-100 p-2 group-hover:scale-105 transition-transform">
                             <img src="{{ asset('assets/images/' . $availableServices[$service]['icon']) }}" alt="{{ $service }}" class="max-w-full max-h-full object-contain filter grayscale group-hover:grayscale-0 transition-all">
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-[9px] font-black uppercase {{ $isActive ? 'text-emerald-500' : 'text-slate-400' }}">
                                {{ $isActive ? 'compte Actif' : 'Suspendu' }}
                            </span>
                            <div class="w-10 h-5 bg-slate-100 rounded-full relative cursor-pointer border border-slate-200" onclick="toggleService('{{ $service }}')">
                                <div class="absolute w-3.5 h-3.5 rounded-full shadow-sm top-0.5 transition-all {{ $isActive ? 'right-0.5 bg-emerald-500' : 'left-0.5 bg-slate-400' }}"></div>
                            </div>
                        </div>
                    </div>

                    <h4 class="text-sm font-black text-slate-900 uppercase tracking-tight mb-4">Passerelle {{ $availableServices[$service]['name'] }}</h4>

                    <div class="space-y-3 mb-8">
                        <div class="flex justify-between items-center py-2 border-b border-slate-50">
                            <span class="text-[9px] font-bold text-slate-400 uppercase">Intégrité Gateway</span>
                            <span class="text-[10px] font-black {{ ($config['api_status'] ?? 'down') === 'up' ? 'text-emerald-600' : 'text-rose-600' }} uppercase">
                                {{ ($config['api_status'] ?? 'down') === 'up' ? 'Synchronisée' : 'Hors-ligne' }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-[9px] font-bold text-slate-400 uppercase">Dernière Vérification</span>
                            <span class="text-[10px] font-black text-slate-900 uppercase">{{ $config['last_check'] ?? 'État Initial' }}</span>
                        </div>
                    </div>

                    <div class="flex gap-3 mt-auto">
                        <button onclick="testIntegration('{{ $service }}')" class="flex-1 py-2.5 bg-blue-50 text-blue-700 text-[9px] font-black uppercase rounded-xl hover:bg-blue-600 hover:text-white transition tracking-widest border border-blue-100">
                            <i class="fas fa-shield-pulse mr-2"></i> Audit du compte
                        </button>
                        <button onclick="configureService('{{ $service }}')" class="px-3 py-2.5 bg-slate-900 text-white text-[9px] font-black uppercase rounded-xl hover:bg-blue-600 transition border border-slate-900">
                            <i class="fas fa-bolt"></i>
                        </button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Protocoles de Communication -->
    <div>
        <h3 class="text-[10px] font-extrabold text-slate-700 uppercase tracking-[0.2em] mb-6 flex items-center gap-3">
            <i class="fas fa-tower-broadcast text-purple-600"></i>
            Protocoles de Communication Institutionnelle
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            @foreach(['sms', 'email'] as $service)
            @php
                $config = $integrations[$service] ?? [];
                $isActive = $config['enabled'] ?? false;
            @endphp
            <div class="bank-card p-8 group">
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center gap-5">
                        <div class="w-14 h-14 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-center text-slate-900 group-hover:bg-slate-900 group-hover:text-white transition-all">
                            <i class="fas fa-{{ $service === 'sms' ? 'comment-sms' : 'envelope-open-text' }} text-xl"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-black text-slate-900 uppercase tracking-tight">Protocole {{ $availableServices[$service]['name'] }}</h4>
                            <p class="text-[10px] font-bold text-slate-400 uppercase mt-1 tracking-widest">Fournisseur : {{ $config['provider'] ?? 'Hub non assigné' }}</p>
                        </div>
                    </div>
                    <div class="w-12 h-6 bg-slate-100 rounded-full relative cursor-pointer border border-slate-200" onclick="toggleService('{{ $service }}')">
                        <div class="absolute w-4.5 h-4.5 rounded-full shadow-sm top-0.5 transition-all {{ $isActive ? 'right-0.5 bg-purple-600' : 'left-0.5 bg-slate-400' }}"></div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6 mb-8">
                    <div class="p-6 bg-slate-50 border border-slate-100 rounded-2xl">
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-2">Débit (24H)</p>
                        <p class="text-2xl font-black text-slate-900 leading-none">{{ number_format($config['sent_24h'] ?? 0) }} <small class="text-[10px] text-slate-400">Pkt</small></p>
                    </div>
                    <div class="p-6 bg-slate-50 border border-slate-100 rounded-2xl">
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-2">Vélocité de Succès</p>
                        <p class="text-2xl font-black text-emerald-600 leading-none">{{ $config['success_rate'] ?? '0' }}%</p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <button onclick="testIntegration('{{ $service }}')" class="flex-1 py-3 bg-slate-900 text-white text-[10px] font-black uppercase rounded-xl hover:bg-blue-600 transition tracking-widest shadow-xl shadow-slate-900/10">
                        <i class="fas fa-vial-circle-check mr-2"></i> Auditer le Protocole
                    </button>
                    <button onclick="configureService('{{ $service }}')" class="btn-bank btn-bank-secondary px-8 py-3">
                        <i class="fas fa-sliders mr-2"></i> Ajuster
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Modal d'Audit Forensique -->
    <div id="testModal" class="hidden fixed inset-0 bg-slate-900/90 z-[100] flex items-center justify-center p-6 backdrop-blur-md">
        <div class="bank-card max-w-lg w-full p-10 text-center relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-blue-600/5 rounded-full blur-3xl -mr-16 -mt-16"></div>
            <div class="flex items-center justify-between mb-8">
                <h3 class="text-xl font-black text-slate-900 uppercase">Résultat de l'Évaluation d'Audit</h3>
                <button onclick="hideTestModal()" class="text-slate-400 hover:text-slate-600 transition"><i class="fas fa-times text-xl"></i></button>
            </div>
            <div id="testResult" class="py-10">
                <div class="flex items-center justify-center">
                    <div class="w-20 h-20 border-4 border-slate-100 border-t-blue-600 rounded-full animate-spin"></div>
                </div>
            </div>
            <div class="mt-8">
                <button onclick="hideTestModal()" class="btn-bank btn-bank-primary w-full py-4 uppercase font-black text-[10px]">Prendre Acte de l'Évaluation</button>
            </div>
        </div>
    </div>
</div>

<script>
function testIntegration(service) {
    document.getElementById('testModal').classList.remove('hidden');
    document.getElementById('testResult').innerHTML = '<div class="flex items-center justify-center py-12"><div class="w-16 h-16 border-4 border-slate-100 border-t-blue-600 rounded-full animate-spin"></div></div>';

    fetch('{{ route("admin.config.integrations.test") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ service: service })
    })
    .then(response => response.json())
    .then(data => {
        const icon = data.success ? 'fa-circle-check text-emerald-500' : 'fa-circle-xmark text-rose-500';
        const color = data.success ? 'emerald' : 'rose';

        document.getElementById('testResult').innerHTML = `
            <div class="text-center">
                <i class="fas ${icon} text-7xl mb-8"></i>
                <p class="text-xl font-black text-slate-900 uppercase tracking-tight mb-2">${data.message}</p>
                ${data.response_time ? `<p class="text-[10px] font-black text-slate-400 uppercase mt-4">Latence : ${data.response_time}ms</p>` : ''}
            </div>
        `;
    })
    .catch(error => {
        document.getElementById('testResult').innerHTML = `
            <div class="text-center">
                <i class="fas fa-circle-exclamation text-rose-500 text-7xl mb-8"></i>
                <p class="text-xl font-black text-slate-900 uppercase">Échec du Protocole d'Évaluation</p>
                <p class="text-xs text-slate-400 mt-4 italic">Délai d'attente du réseau institutionnel ou rejet par la passerelle non autorisée.</p>
            </div>
        `;
    });
}
function hideTestModal() { document.getElementById('testModal').classList.add('hidden'); }
function toggleService(service) { console.log('Opération de basculement :', service); }
function configureService(service) { window.location.href = `/admin/config/integrations/${service}`; }
</script>
@endsection
