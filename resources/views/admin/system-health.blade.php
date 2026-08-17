@extends('layouts.app_admin')

@section('title', 'Centre de Contrôle de l\'Infrastructure')
@section('page-title', 'Système / Monitoring Vital')

@section('content')
<div class="space-y-8">
    <!-- En-tête Dynamique -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-3xl font-black text-slate-900 tracking-tight">Diagnostic Vital de l'Infrastructure</h2>
            <p class="text-slate-500 text-sm font-medium">Analyse télémétrique des ressources noyau et des services de données</p>
        </div>
        <div class="flex items-center gap-3">
            @php
                $globalStatus = $systemHealth['status'] ?? 'healthy';
                $statusConfig = match($globalStatus) {
                    'healthy' => ['color' => 'emerald', 'label' => 'Système Nominal', 'icon' => 'fa-check-circle'],
                    'warning' => ['color' => 'amber', 'label' => 'Attention Requise', 'icon' => 'fa-exclamation-circle'],
                    'critical' => ['color' => 'rose', 'label' => 'Alerte Critique', 'icon' => 'fa-radiation'],
                    default => ['color' => 'slate', 'label' => 'État Inconnu', 'icon' => 'fa-question-circle']
                };
            @endphp
            <div class="flex items-center gap-2 px-4 py-2 bg-{{ $statusConfig['color'] }}-50 text-{{ $statusConfig['color'] }}-700 rounded-2xl border border-{{ $statusConfig['color'] }}-100 shadow-sm animate-fade-in">
                <i class="fas {{ $statusConfig['icon'] }} animate-pulse"></i>
                <span class="text-xs font-black uppercase tracking-widest">{{ $statusConfig['label'] }}</span>
            </div>
            <button onclick="window.location.reload()" class="w-10 h-10 bg-white border border-slate-200 rounded-xl flex items-center justify-center text-slate-500 hover:text-blue-600 hover:border-blue-200 transition-all shadow-sm group">
                <i class="fas fa-sync-alt group-hover:rotate-180 transition-transform duration-500"></i>
            </button>
        </div>
    </div>

    @if(!empty($systemHealth['issues']))
    <!-- Panneau d'Alertes Actives -->
    <div class="bank-card p-6 border-rose-100 bg-rose-50/30 animate-shake">
        <h3 class="text-[10px] font-black text-rose-800 uppercase tracking-widest mb-4 flex items-center gap-2">
            <i class="fas fa-triangle-exclamation"></i> Anomalies Détectées ({{ count($systemHealth['issues']) }})
        </h3>
        <div class="space-y-3">
            @foreach($systemHealth['issues'] as $issue)
            <div class="flex items-center gap-3 p-3 bg-white border border-rose-100 rounded-xl text-xs font-bold text-rose-700 shadow-sm">
                <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                {{ $issue }}
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Grille de Performance Temps Réel -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        @foreach(['cpu_usage' => 'Processeur (CPU)', 'memory_usage' => 'Mémoire Vive (RAM)', 'storage' => 'Stockage (SSD)'] as $key => $label)
            @php 
                $data = $systemHealth[$key] ?? ['percent' => 0, 'status' => 'healthy', 'message' => 'N/A'];
                $color = match($data['status']) {
                    'healthy' => 'emerald',
                    'warning' => 'amber',
                    'critical', 'error' => 'rose',
                    default => 'slate'
                };
            @endphp
            <div class="bank-card p-8 group hover:border-{{ $color }}-300 transition-all duration-500">
                <div class="flex items-center justify-between mb-6">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $label }}</span>
                    <i class="fas fa-{{ $key === 'cpu_usage' ? 'microchip' : ($key === 'memory_usage' ? 'memory' : 'hard-drive') }} text-{{ $color }}-500 text-lg opacity-20 group-hover:opacity-100 transition-opacity"></i>
                </div>
                
                <div class="relative flex items-end justify-between mb-4">
                    <h4 class="text-4xl font-black text-slate-900">{{ $data['message'] }}</h4>
                    <span class="text-[10px] font-black text-{{ $color }}-600 bg-{{ $color }}-50 px-2 py-1 rounded-lg uppercase tracking-tighter">{{ $data['status'] }}</span>
                </div>

                <!-- Barre de Charge Custom -->
                <div class="h-3 bg-slate-100 rounded-full overflow-hidden border border-slate-200">
                    <div class="h-full bg-gradient-to-r from-{{ $color }}-500 to-{{ $color }}-400 transition-all duration-1000 ease-out rounded-full shadow-lg" style="width: {{ $data['percent'] }}%"></div>
                </div>
                
                <div class="flex items-center justify-between mt-4 text-[9px] font-bold text-slate-400 uppercase">
                    <span>Charge System</span>
                    <span>100% Max</span>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Composants Infrastructures Secrétaires -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Services Critiques -->
        <div class="bank-card p-0 overflow-hidden">
            <div class="px-8 py-5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest">Connectivité des Services</h3>
                <span class="text-[10px] font-bold text-slate-400 bg-white px-3 py-1 rounded-full border border-slate-100 shadow-sm">Temps réel</span>
            </div>
            <div class="p-8 space-y-6">
                @foreach(['database' => 'Base de Données Principale', 'environment' => 'Environnement de Calcul', 'debug_mode' => 'Mode Débogage Applicatif'] as $key => $title)
                    @php
                        $item = $systemHealth[$key] ?? ['status' => 'healthy', 'message' => 'N/A'];
                        $isHealthy = $item['status'] === 'healthy';
                        $icon = match($key) {
                            'database' => 'fa-database',
                            'environment' => 'fa-cloud',
                            'debug_mode' => 'fa-bug',
                            default => 'fa-check'
                        };
                    @endphp
                    <div class="flex items-center justify-between p-4 rounded-2xl border border-slate-100 bg-slate-50/30">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center text-{{ $isHealthy ? 'emerald' : 'rose' }}-500 border border-slate-100 shadow-sm">
                                <i class="fas {{ $icon }}"></i>
                            </div>
                            <div>
                                <h4 class="text-[11px] font-black text-slate-900 uppercase leading-none">{{ $title }}</h4>
                                <p class="text-[10px] font-bold text-slate-400 mt-1 uppercase">{{ $item['message'] }}</p>
                            </div>
                        </div>
                        @if($isHealthy)
                            <span class="px-3 py-1 bg-emerald-50 text-emerald-600 text-[9px] font-black rounded-lg uppercase">Opérationnel</span>
                        @else
                            <span class="px-3 py-1 bg-rose-50 text-rose-600 text-[9px] font-black rounded-lg uppercase">Incident</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Versions et Architecture -->
        <div class="bank-card p-0 overflow-hidden">
            <div class="px-8 py-5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest">Architecture Logicielle</h3>
            </div>
            <div class="p-8">
                <div class="space-y-4">
                    @foreach(['php_version' => 'Moteur PHP Runtime', 'laravel_version' => 'Framework Laravel Core'] as $key => $title)
                        @php $item = $systemHealth[$key] ?? ['message' => 'N/A']; @endphp
                        <div class="flex items-center justify-between border-b border-slate-50 pb-4 last:border-0 last:pb-0">
                            <div>
                                <p class="text-[11px] font-black text-slate-900 uppercase">{{ $title }}</p>
                                <p class="text-[10px] font-bold text-slate-400 uppercase mt-1">Version Actuelle</p>
                            </div>
                            <span class="font-mono text-xs font-black text-blue-600 bg-blue-50 px-3 py-1.5 rounded-lg border border-blue-100">
                                {{ $item['message'] }}
                            </span>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8 p-6 bg-slate-900 rounded-2xl text-white relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-white/5 rounded-full -mr-8 -mt-8 group-hover:scale-150 transition-transform duration-700"></div>
                    <p class="text-[9px] font-black text-white/40 uppercase tracking-widest mb-2">Instanciation du Système</p>
                    <p class="text-xs font-medium text-white/80 leading-relaxed italic">"L'infrastructure est isolée et sécurisée. Tous les journaux d'audit sont chiffrés et transmis au nœud de supervision central toutes les 60 secondes."</p>
                    <div class="flex items-center gap-2 mt-4">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-[8px] font-black text-emerald-400 uppercase">Audit Live Actif</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pied de Page Surveillance -->
    <div class="text-center pb-8">
        <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.3em]">
            Dernière Analyse Complète de l'Infrastructure : {{ \Carbon\Carbon::parse($systemHealth['timestamp'])->format('d/m/Y - H:i:s') }}
        </p>
    </div>
</div>

<style>
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-4px); }
    75% { transform: translateX(4px); }
}
.animate-shake {
    animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
}
</style>
@endsection
