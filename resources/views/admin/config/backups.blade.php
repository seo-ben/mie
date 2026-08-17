@extends('layouts.app_admin')

@section('title', 'Services de Récupération du Registre')
@section('page-title', 'Protocole / Récupération du Cœur')

@section('content')
<div class="space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Récupération du Registre de Configuration</h2>
            <p class="text-slate-500 text-sm font-medium">Gérer les sauvegardes des paramètres institutionnels et la restauration de la base de référence</p>
        </div>
        <button onclick="showCreateBackupModal()" class="btn-bank btn-bank-primary">
            <i class="fas fa-plus mr-2 text-[10px]"></i> Initier un Nouvel Instantané
        </button>
    </div>

    <!-- Mécanisme de Retour -->
    @if(session('success'))
    <div class="bank-card p-4 border-l-4 border-l-emerald-500 bg-emerald-50/50 flex items-center gap-3">
        <i class="fas fa-check-circle text-emerald-500"></i>
        <p class="text-xs font-black text-emerald-800 uppercase tracking-tight">{{ session('success') }}</p>
    </div>
    @endif

    <!-- Métriques de Récupération du Portefeuille -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bank-card p-6">
            <div class="flex items-center justify-between mb-4">
                <span class="kpi-label">Instantanés Stockés</span>
                <i class="fas fa-database text-blue-500 text-xs"></i>
            </div>
            <div class="kpi-value mt-1">{{ count($backupsList) }}</div>
            <p class="text-[8px] font-black text-slate-400 uppercase mt-4">Nombre Total d'Archives</p>
        </div>

        <div class="bank-card p-6">
            <div class="flex items-center justify-between mb-4">
                <span class="kpi-label">Poids du Registre</span>
                <i class="fas fa-hdd text-emerald-500 text-xs"></i>
            </div>
            <div class="kpi-value mt-1">{{ formatBytes(array_sum(array_column($backupsList, 'size'))) }}</div>
            <p class="text-[8px] font-black text-slate-400 uppercase mt-4">Taille Cumulée du Stockage</p>
        </div>

        <div class="bank-card p-6">
            <div class="flex items-center justify-between mb-4">
                <span class="kpi-label">Dernier Point de Restauration</span>
                <i class="fas fa-clock text-purple-500 text-xs"></i>
            </div>
            <div class="text-sm font-black text-slate-900 mt-1 uppercase">{{ !empty($backupsList) ? $backupsList[0]['created_at'] : 'N/A' }}</div>
            <p class="text-[8px] font-black text-slate-400 uppercase mt-4 italic">Synchronisation de Base Disponible</p>
        </div>

        <div class="bank-card p-6 border-t-2 border-t-amber-500">
            <div class="flex items-center justify-between mb-4">
                <span class="kpi-label">Base Historique</span>
                <i class="fas fa-history text-amber-500 text-xs"></i>
            </div>
            <div class="text-sm font-black text-slate-900 mt-1 uppercase">{{ !empty($backupsList) ? end($backupsList)['created_at'] : 'N/A' }}</div>
            <p class="text-[8px] font-black text-slate-400 uppercase mt-4">Entrée de Rétention la Plus Ancienne</p>
        </div>
    </div>

    <!-- Registre de Récupération -->
    <div class="bank-card overflow-hidden">
        <div class="px-8 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <h3 class="text-[10px] font-extrabold text-slate-700 uppercase tracking-widest">Grand Livre des Sauvegardes du Registre</h3>
            <span class="text-[8px] font-black text-slate-400 uppercase">Politique de Rétention : Stockage Central Illimité</span>
        </div>
        <div class="overflow-x-auto">
            <table class="bank-table">
                <thead>
                    <tr>
                        <th>Artefact du compte de Registre</th>
                        <th>Horodatage de Création</th>
                        <th>Taille du Payload</th>
                        <th>Notes de Gouvernance</th>
                        <th class="text-right">Protocole d'Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($backupsList as $backup)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td>
                            <div class="flex items-center gap-3">
                                <i class="fas fa-file-shield text-slate-400 text-xs"></i>
                                <span class="font-mono text-[10px] font-black text-slate-900 uppercase tracking-tight">{{ $backup['filename'] }}</span>
                            </div>
                        </td>
                        <td class="text-[10px] font-bold text-slate-600 uppercase">{{ $backup['created_at'] }}</td>
                        <td>
                            <span class="px-2 py-0.5 bg-slate-100 text-slate-600 text-[9px] font-black rounded-lg">{{ formatBytes($backup['size']) }}</span>
                        </td>
                        <td class="text-xs text-slate-500 italic max-w-xs truncate">
                            {{ $backup['description'] ?? 'Sauvegarde Institutionnelle Automatisée' }}
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.config.backups.download', ['file' => $backup['filename']]) }}" class="p-2 text-slate-400 hover:text-blue-600 transition" title="Télécharger le Payload d'Audit">
                                    <i class="fas fa-download text-xs"></i>
                                </a>
                                <button onclick="confirmRestore('{{ $backup['filename'] }}')" class="p-2 text-slate-400 hover:text-amber-600 transition" title="Inversion du Registre">
                                    <i class="fas fa-arrows-rotate text-xs"></i>
                                </button>
                                <form method="POST" action="{{ route('admin.config.backups.delete', ['file' => $backup['filename']]) }}" class="inline" onsubmit="return confirm('Initier le protocole de suppression de l\'archive ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-slate-400 hover:text-rose-600 transition" title="Supprimer l'Artefact">
                                        <i class="fas fa-trash-can text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-8 py-20 text-center">
                            <i class="fas fa-database text-slate-200 text-5xl mb-6"></i>
                            <h4 class="text-sm font-black text-slate-800 uppercase tracking-widest">Coffre de Récupération Vide</h4>
                            <p class="text-xs text-slate-400 mt-2 mb-8">Aucun instantané de configuration n'a été capturé pour cet environnement.</p>
                            <button onclick="showCreateBackupModal()" class="btn-bank btn-bank-primary px-8">Initier un Instantané de Référence</button>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal d'Instantané -->
    <div id="createBackupModal" class="hidden fixed inset-0 bg-slate-900/80 z-[100] flex items-center justify-center p-6 backdrop-blur-sm">
        <div class="bank-card max-w-md w-full p-8 shadow-2xl">
            <h3 class="text-xl font-black text-slate-900 mb-6">Capturer l'État de la Configuration</h3>
            <form method="POST" action="{{ route('admin.config.backups.create') }}">
                @csrf
                <div class="mb-8">
                    <label class="text-[9px] font-extrabold text-slate-400 uppercase tracking-widest block mb-2">Note de Gouvernance (Optionnel)</label>
                    <textarea name="description" rows="3" class="bank-input font-bold italic" placeholder="ex: Instantané capturé avant la mise à jour du protocole des taux d'intérêt..."></textarea>
                </div>
                <div class="flex gap-4">
                    <button type="button" onclick="hideCreateBackupModal()" class="flex-1 px-4 py-3 bg-slate-100 text-slate-600 text-[10px] font-black uppercase rounded-xl hover:bg-slate-200 transition">Annuler</button>
                    <button type="submit" class="flex-1 px-4 py-3 bg-slate-900 text-white text-[10px] font-black uppercase rounded-xl hover:bg-blue-600 transition shadow-xl shadow-slate-900/10">Valider l'Instantané</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Restauration -->
    <div id="restoreModal" class="hidden fixed inset-0 bg-slate-910/90 z-[100] flex items-center justify-center p-6 backdrop-blur-md">
        <div class="bank-card max-w-md w-full p-8 shadow-2xl text-center border-amber-300">
            <div class="w-20 h-20 bg-amber-100 text-amber-600 rounded-2xl flex items-center justify-center mx-auto mb-6 border border-amber-200">
                <i class="fas fa-triangle-exclamation text-3xl"></i>
            </div>
            <h3 class="text-xl font-black text-slate-900">Restaurer la Configuration de Référence ?</h3>
            <p class="text-sm text-slate-500 mt-4 leading-relaxed">Ce protocole écrasera TOUS les paramètres système existants par l'état de l'artefact. Cette action est irréversible une fois validée.</p>

            <form method="POST" action="{{ route('admin.config.backups.restore') }}" id="restoreForm" class="mt-8">
                @csrf
                <input type="hidden" name="file" id="restoreBackupFile">
                <div class="bg-amber-50 border border-amber-100 p-4 rounded-xl mb-8 flex items-start gap-3 text-left">
                    <i class="fas fa-info-circle text-amber-500 mt-1"></i>
                    <p class="text-[9px] font-black text-amber-800 uppercase tracking-tight">Recommandation : Capturer l'état actuel avant l'exécution du protocole d'inversion.</p>
                </div>
                <div class="flex gap-4">
                    <button type="button" onclick="hideRestoreModal()" class="flex-1 px-4 py-3 bg-slate-100 text-slate-600 text-[10px] font-black uppercase rounded-xl hover:bg-slate-200 transition">Abandonner l'Inversion</button>
                    <button type="submit" class="flex-1 px-4 py-3 bg-amber-600 text-white text-[10px] font-black uppercase rounded-xl hover:bg-amber-700 transition shadow-xl shadow-amber-600/20">Exécuter la Restauration</button>
                </div>
            </form>
        </div>
    </div>
</div>

@php
function formatBytes($bytes, $precision = 2) {
    if (!$bytes) return '0 o';
    $bytes = floatval($bytes);
    $units = ['o', 'Ko', 'Mo', 'Go'];
    $pow = floor(log($bytes, 1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
@endphp

<script>
function showCreateBackupModal() { document.getElementById('createBackupModal').classList.remove('hidden'); }
function hideCreateBackupModal() { document.getElementById('createBackupModal').classList.add('hidden'); }
function confirmRestore(filename) {
    document.getElementById('restoreBackupFile').value = filename;
    document.getElementById('restoreModal').classList.remove('hidden');
}
function hideRestoreModal() { document.getElementById('restoreModal').classList.add('hidden'); }
</script>
@endsection
