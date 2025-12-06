@extends('layouts.app_admin')
@section('title', 'Sauvegardes Configuration')
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
<div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Sauvegardes de Configuration</h2>
        <p class="text-gray-600 mt-1">Gérez les sauvegardes des paramètres système</p>
    </div>
    <button onclick="showCreateBackupModal()" class="mt-4 md:mt-0 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
        <i class="fas fa-plus mr-2"></i>Nouvelle sauvegarde
    </button>
</div>

<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm mb-1">Total</p>
                <p class="text-2xl font-bold text-gray-800">{{ count($backupsList) }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-database text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm mb-1">Taille totale</p>
                <p class="text-2xl font-bold text-gray-800">{{ formatBytes(array_sum(array_column($backupsList, 'size'))) }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-hdd text-green-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm mb-1">Plus récente</p>
                <p class="text-sm font-bold text-gray-800">{{ !empty($backupsList) ? $backupsList[0]['created_at'] : 'N/A' }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-clock text-purple-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm mb-1">Plus ancienne</p>
                <p class="text-sm font-bold text-gray-800">{{ !empty($backupsList) ? end($backupsList)['created_at'] : 'N/A' }}</p>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-history text-orange-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Backups List -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Fichier</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Taille</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Description</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($backupsList as $backup)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-file-archive text-gray-400"></i>
                            <span class="font-mono text-sm text-gray-800">{{ $backup['filename'] }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ $backup['created_at'] }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ formatBytes($backup['size']) }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ $backup['description'] ?? 'Sauvegarde automatique' }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.config.backups.download', ['file' => $backup['filename']]) }}" class="px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition text-sm">
                                <i class="fas fa-download"></i>
                            </a>
                            <button onclick="confirmRestore('{{ $backup['filename'] }}')" class="px-3 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200 transition text-sm">
                                <i class="fas fa-redo"></i>
                            </button>
                            <form method="POST" action="{{ route('admin.config.backups.delete', ['file' => $backup['filename']]) }}" class="inline" onsubmit="return confirm('Supprimer cette sauvegarde ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200 transition text-sm">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center">
                        <i class="fas fa-database text-gray-300 text-6xl mb-4"></i>
                        <p class="text-gray-500 text-lg">Aucune sauvegarde disponible</p>
                        <button onclick="showCreateBackupModal()" class="mt-4 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            Créer la première sauvegarde
                        </button>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Create Backup Modal -->
<div id="createBackupModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Créer une sauvegarde</h3>

        <form method="POST" action="{{ route('admin.config.backups.create') }}">
            @csrf
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description (optionnel)</label>
                <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Ex: Avant mise à jour des taux d'intérêt..."></textarea>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="hideCreateBackupModal()" class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                    Annuler
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-save mr-2"></i>Créer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Restore Confirmation Modal -->
<div id="restoreModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full p-6">
        <div class="text-center mb-6">
            <i class="fas fa-exclamation-triangle text-orange-500 text-6xl mb-4"></i>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Restaurer cette sauvegarde ?</h3>
            <p class="text-gray-600">Cette action va remplacer tous les paramètres actuels par ceux de la sauvegarde.</p>
        </div>

        <form method="POST" action="{{ route('admin.config.backups.restore') }}" id="restoreForm">
            @csrf
            <input type="hidden" name="file" id="restoreBackupFile">

            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-orange-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    Il est recommandé de créer une sauvegarde avant de restaurer.
                </p>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="hideRestoreModal()" class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                    Annuler
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                    Restaurer
                </button>
            </div>
        </form>
    </div>
</div>

@php
function formatBytes($bytes, $precision = 2) {
    $bytes = floatval($bytes); // 🔥 conversion explicite en nombre
    $units = ['B', 'KB', 'MB', 'GB'];

    if ($bytes <= 0) {
        return '0 B';
    }

    $pow = floor(log($bytes, 1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
@endphp

<script>
function showCreateBackupModal() {
    document.getElementById('createBackupModal').classList.remove('hidden');
}

function hideCreateBackupModal() {
    document.getElementById('createBackupModal').classList.add('hidden');
}

function confirmRestore(filename) {
    document.getElementById('restoreBackupFile').value = filename;
    document.getElementById('restoreModal').classList.remove('hidden');
}

function hideRestoreModal() {
    document.getElementById('restoreModal').classList.add('hidden');
}
</script>

@endsection
