@extends('layouts.app_admin')

@section('title', 'Piste d\'Audit Institutionnelle')
@section('page-title', 'Protocole / Audit de Gouvernance')

@section('content')
<div class="space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Grand Livre de Gouvernance des Configurations</h2>
            <p class="text-slate-500 text-sm font-medium">Piste d'audit forensique des modifications des paramètres institutionnels</p>
        </div>
        <div class="flex items-center gap-3 no-print">
            <button onclick="window.print()" class="btn-bank btn-bank-secondary">
                <i class="fas fa-print mr-2 text-[10px]"></i> Impression Légale
            </button>
        </div>
    </div>

    <!-- Filtres d'Investigation d'Audit -->
    <div class="bank-card p-6 no-print">
        <form method="GET" action="{{ route('admin.config.logs') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <div class="md:col-span-5">
                <label class="text-[9px] font-extrabold text-slate-400 uppercase tracking-widest block mb-1">Action Protocolaire</label>
                <select name="action" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2 text-xs font-bold text-slate-600 focus:ring-1 focus:ring-blue-500 outline-none transition uppercase">
                    <option value="">Toutes les Actions</option>
                    @foreach($actions as $key => $label)
                    <option value="{{ $key }}" {{ request('action') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-4">
                <label class="text-[9px] font-extrabold text-slate-400 uppercase tracking-widest block mb-1">Officier Opérationnel</label>
                <select name="user_id" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2 text-xs font-bold text-slate-600 focus:ring-1 focus:ring-blue-500 outline-none transition uppercase">
                    <option value="">Tout le Personnel Autorisé</option>
                </select>
            </div>

            <div class="md:col-span-3 flex items-end gap-2">
                <button type="submit" class="btn-bank btn-bank-primary flex-1 py-2.5">
                    <i class="fas fa-tower-observation text-[10px] mr-2"></i> Inspecter
                </button>
                <a href="{{ route('admin.config.logs') }}" class="btn-bank btn-bank-secondary p-2.5">
                    <i class="fas fa-rotate-right text-[10px]"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Grand Livre d'Audit Forensique -->
    <div class="bank-card overflow-hidden">
        <div class="px-8 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <h3 class="text-[10px] font-extrabold text-slate-700 uppercase tracking-widest">Chronologie de l'Audit des Protocoles</h3>
            <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">{{ $logs->total() }} Événements Totalisés</span>
        </div>
        <div class="overflow-x-auto">
            <table class="bank-table">
                <thead>
                    <tr>
                        <th class="w-48">Horodatage d'Audit</th>
                        <th>Entité Autorisée</th>
                        <th>Action Protocolaire Validée</th>
                        <th>Intégrité du Payload</th>
                        <th class="text-right">IP d'Origine</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="font-mono text-[9px] font-black text-slate-900 uppercase">
                            {{ $log->created_at->format('d M Y') }}
                            <span class="block text-[8px] font-bold text-slate-400 mt-0.5">{{ $log->created_at->format('H:i:s.v') }}</span>
                        </td>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-slate-900 flex items-center justify-center text-white text-[9px] font-black">
                                    {{ strtoupper(substr($log->user->first_name ?? 'U', 0, 1) . substr($log->user->last_name ?? 'S', 0, 1)) }}
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-slate-900 leading-tight uppercase">{{ $log->user->full_name ?? 'Entité Filtrée' }}</p>
                                    <p class="text-[8px] font-bold text-slate-400 uppercase tracking-tighter mt-1">{{ $log->user->email ?? '' }}</p>
                                </div>
                            </div>
                        </td>
                        <td>
                            @php
                                $badgeStyle = match($log->action) {
                                    'UPDATE_SYSTEM_PARAMETERS' => 'bg-blue-600/10 text-blue-700 border-blue-200',
                                    'CREATE_CONFIG_BACKUP' => 'bg-emerald-600/10 text-emerald-700 border-emerald-200',
                                    'RESTORE_CONFIG_BACKUP' => 'bg-amber-600/10 text-amber-700 border-amber-200',
                                    'RESET_CONFIG_DEFAULTS' => 'bg-rose-600/10 text-rose-700 border-rose-200',
                                    default => 'bg-slate-600/10 text-slate-700 border-slate-200'
                                };
                            @endphp
                            <span class="px-2 py-0.5 border {{ $badgeStyle }} text-[8px] font-black rounded uppercase tracking-widest">
                                {{ $actions[$log->action] ?? $log->action }}
                            </span>
                        </td>
                        <td>
                            @if($log->action === 'UPDATE_SYSTEM_PARAMETERS')
                                <button onclick="showDetails({{ json_encode($log->additional_data) }})" class="flex items-center gap-1.5 text-blue-600 hover:text-blue-800 transition group">
                                    <i class="fas fa-fingerprint text-[10px]"></i>
                                    <span class="text-[9px] font-black uppercase tracking-widest border-b border-blue-600/30 group-hover:border-blue-800">{{ count($log->additional_data['updated_parameters'] ?? []) }} Modifs Protocolaires</span>
                                </button>
                            @elseif($log->action === 'CREATE_CONFIG_BACKUP')
                                <div class="flex items-center gap-1.5 text-slate-600">
                                    <i class="fas fa-file-shield text-[10px]"></i>
                                    <span class="text-[9px] font-black uppercase tracking-widest">{{ $log->additional_data['backup_file'] ?? 'N/A' }}</span>
                                </div>
                            @elseif($log->action === 'RESTORE_CONFIG_BACKUP')
                                <div class="flex items-center gap-1.5 text-amber-600">
                                    <span class="text-[9px] font-black uppercase tracking-widest">{{ $log->additional_data['restored_count'] ?? 0 }} comptes Inversés</span>
                                </div>
                            @endif
                        </td>
                        <td class="text-right">
                            <span class="font-mono text-[9px] font-black text-slate-400">{{ $log->additional_data['user_ip'] ?? '127.0.0.1' }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-8 py-20 text-center">
                            <i class="fas fa-box-archive text-slate-200 text-5xl mb-6"></i>
                            <h4 class="text-sm font-black text-slate-800 uppercase tracking-widest">Registre d'Audit Initialisé</h4>
                            <p class="text-xs text-slate-400 mt-2">Aucune modification des protocoles institutionnels détectée pour cette fenêtre.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
        <div class="px-8 py-4 border-t border-slate-100 bg-slate-50/30">
            {{ $logs->links() }}
        </div>
        @endif
    </div>

    <!-- Modal d'Investigation Forensique -->
    <div id="detailsModal" class="hidden fixed inset-0 bg-slate-900/90 z-[100] flex items-center justify-center p-6 backdrop-blur-md">
        <div class="bank-card max-w-2xl w-full p-8 shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-xl font-black text-slate-900 leading-tight">Payload de Modification Protocolaire</h3>
                    <p class="text-[10px] font-bold text-slate-400 uppercase mt-1 tracking-widest">Artefact de Preuve Historique</p>
                </div>
                <button onclick="hideDetailsModal()" class="w-10 h-10 rounded-xl hover:bg-slate-100 transition flex items-center justify-center text-slate-400">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            
            <div id="detailsContent" class="bg-slate-900 p-6 rounded-2xl overflow-auto border border-white/5 shadow-inner">
                <pre class="text-[11px] font-mono text-blue-400 leading-relaxed"></pre>
            </div>

            <div class="mt-8 flex justify-end">
                <button onclick="hideDetailsModal()" class="btn-bank btn-bank-secondary px-8 py-3">Fermer la Vue d'Audit</button>
            </div>
        </div>
    </div>
</div>

<script>
function showDetails(data) {
    document.getElementById('detailsModal').classList.remove('hidden');
    document.getElementById('detailsContent').querySelector('pre').textContent = JSON.stringify(data, null, 2);
}
function hideDetailsModal() {
    document.getElementById('detailsModal').classList.add('hidden');
}
</script>

<style>
    @media print {
        .no-print { display: none !important; }
        .bank-card { box-shadow: none !important; border: 1px solid #e2e8f0 !important; }
    }
</style>
@endsection
