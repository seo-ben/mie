@extends('layouts.app_admin')
@section('title', 'Historique Configuration')
@section('content')

<!-- Header -->
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Historique des Modifications</h2>
    <p class="text-gray-600 mt-1">Journal d'audit des changements de configuration</p>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <form method="GET" action="{{ route('admin.config.logs') }}" class="flex flex-col md:flex-row gap-4">
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-2">Action</label>
            <select name="action" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="">Toutes les actions</option>
                @foreach($actions as $key => $label)
                <option value="{{ $key }}" {{ request('action') == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-2">Utilisateur</label>
            <select name="user_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="">Tous les utilisateurs</option>
                <!-- Populate with users -->
            </select>
        </div>

        <div class="flex items-end gap-2">
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-filter mr-2"></i>Filtrer
            </button>
            <a href="{{ route('admin.config.logs') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                <i class="fas fa-redo"></i>
            </a>
        </div>
    </form>
</div>

<!-- Logs List -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date/Heure</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Utilisateur</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Action</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Détails</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($logs as $log)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 text-sm text-gray-800">
                        {{ $log->created_at->format('d/m/Y H:i:s') }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xs font-semibold">
                                {{ strtoupper(substr($log->user->first_name ?? 'U', 0, 1) . substr($log->user->last_name ?? 'S', 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ $log->user->full_name ?? 'Utilisateur supprimé' }}</p>
                                <p class="text-xs text-gray-500">{{ $log->user->email ?? '' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        @php
                            $badgeColor = match($log->action) {
                                'UPDATE_SYSTEM_PARAMETERS' => 'blue',
                                'CREATE_CONFIG_BACKUP' => 'green',
                                'RESTORE_CONFIG_BACKUP' => 'orange',
                                'RESET_CONFIG_DEFAULTS' => 'red',
                                default => 'gray'
                            };
                        @endphp
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-{{ $badgeColor }}-100 text-{{ $badgeColor }}-800">
                            {{ $actions[$log->action] ?? $log->action }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        @if($log->action === 'UPDATE_SYSTEM_PARAMETERS')
                            <button onclick="showDetails({{ json_encode($log->additional_data) }})" class="text-blue-600 hover:text-blue-800 text-sm">
                                <i class="fas fa-eye mr-1"></i>
                                {{ count($log->additional_data['updated_parameters'] ?? []) }} paramètre(s)
                            </button>
                        @elseif($log->action === 'CREATE_CONFIG_BACKUP')
                            <span class="text-sm text-gray-600">
                                <i class="fas fa-file-archive mr-1"></i>
                                {{ $log->additional_data['backup_file'] ?? 'N/A' }}
                            </span>
                        @elseif($log->action === 'RESTORE_CONFIG_BACKUP')
                            <span class="text-sm text-gray-600">
                                <i class="fas fa-redo mr-1"></i>
                                {{ $log->additional_data['restored_count'] ?? 0 }} paramètre(s) restauré(s)
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 font-mono">
                        {{ $log->additional_data['user_ip'] ?? 'N/A' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center">
                        <i class="fas fa-history text-gray-300 text-6xl mb-4"></i>
                        <p class="text-gray-500 text-lg">Aucun log disponible</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $logs->links() }}
    </div>
    @endif
</div>

<!-- Details Modal -->
<div id="detailsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-2xl w-full p-6 max-h-[80vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-800">Détails de la modification</h3>
            <button onclick="hideDetailsModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div id="detailsContent" class="bg-gray-50 rounded-lg p-4">
            <pre class="text-sm text-gray-800 whitespace-pre-wrap"></pre>
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

@endsection
