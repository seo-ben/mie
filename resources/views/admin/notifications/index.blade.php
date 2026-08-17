@extends('layouts.app_admin')

@section('title', 'Notifications - Administration')
@section('page-title', 'Notifications')

@section('content')
<div class="space-y-6">
    <!-- En-tête -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Centre de Notifications</h2>
            <p class="text-sm text-slate-500 mt-1">Historique complet de vos notifications système</p>
        </div>
        <button id="markAllReadPageBtn" class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
            <i class="fas fa-check-double"></i>
            Tout marquer comme lu
        </button>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-slate-600">Filtrer par :</span>
                <select id="typeFilter" class="px-3 py-1.5 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="">Tous les types</option>
                    <option value="info">Information</option>
                    <option value="success">Succès</option>
                    <option value="warning">Avertissement</option>
                    <option value="error">Erreur</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <select id="statusFilter" class="px-3 py-1.5 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="">Tous les statuts</option>
                    <option value="unread">Non lues</option>
                    <option value="read">Lues</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Liste des notifications -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        @forelse($notifications as $notification)
            <div class="notification-row p-4 border-b border-slate-100 hover:bg-slate-50 transition {{ $notification->isRead() ? 'bg-slate-50/50' : 'bg-white' }}" 
                 data-id="{{ $notification->id }}"
                 data-type="{{ $notification->type }}"
                 data-read="{{ $notification->isRead() ? 'true' : 'false' }}">
                <div class="flex items-start gap-4">
                    <!-- Icône -->
                    <div class="w-10 h-10 rounded-lg {{ $notification->type_class }} flex items-center justify-center shrink-0">
                        <i class="fas {{ $notification->icon }}"></i>
                    </div>
                    
                    <!-- Contenu -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-2">
                                <h3 class="font-semibold text-slate-800 {{ $notification->isRead() ? '' : 'text-slate-900' }}">
                                    {{ $notification->title }}
                                </h3>
                                @unless($notification->isRead())
                                    <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                @endunless
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="text-xs text-slate-400">{{ $notification->created_at->format('d/m/Y H:i') }}</span>
                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded 
                                    @if($notification->type === 'success') bg-green-100 text-green-700
                                    @elseif($notification->type === 'warning') bg-yellow-100 text-yellow-700
                                    @elseif($notification->type === 'error') bg-red-100 text-red-700
                                    @else bg-blue-100 text-blue-700
                                    @endif">
                                    {{ $notification->type }}
                                </span>
                            </div>
                        </div>
                        <p class="text-sm text-slate-600 mt-1">{{ $notification->message }}</p>
                        <div class="flex items-center gap-4 mt-2">
                            <span class="text-xs text-slate-400">{{ $notification->time_ago }}</span>
                            @unless($notification->isRead())
                                <button onclick="markAsRead({{ $notification->id }})" class="text-xs text-blue-600 hover:text-blue-700 font-medium">
                                    Marquer comme lu
                                </button>
                            @endunless
                            <button onclick="deleteNotification({{ $notification->id }})" class="text-xs text-red-500 hover:text-red-600 font-medium">
                                Supprimer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="p-12 text-center">
                <div class="w-16 h-16 mx-auto rounded-full bg-slate-100 flex items-center justify-center mb-4">
                    <i class="fas fa-bell-slash text-2xl text-slate-400"></i>
                </div>
                <h3 class="font-semibold text-slate-800">Aucune notification</h3>
                <p class="text-sm text-slate-500 mt-1">Vous n'avez pas encore reçu de notifications</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($notifications->hasPages())
        <div class="flex justify-center">
            {{ $notifications->links() }}
        </div>
    @endif
</div>

@push('scripts')
<script>
    // Marquer comme lu
    async function markAsRead(id) {
        try {
            const response = await fetch(`/admin/notifications/${id}/read`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            
            if (response.ok) {
                location.reload();
            }
        } catch (error) {
            console.error('Erreur:', error);
        }
    }

    // Supprimer notification
    async function deleteNotification(id) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette notification ?')) return;
        
        try {
            const response = await fetch(`/admin/notifications/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            
            if (response.ok) {
                location.reload();
            }
        } catch (error) {
            console.error('Erreur:', error);
        }
    }

    // Tout marquer comme lu
    document.getElementById('markAllReadPageBtn')?.addEventListener('click', async () => {
        try {
            const response = await fetch('{{ route("admin.notifications.markAllRead") }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            
            if (response.ok) {
                location.reload();
            }
        } catch (error) {
            console.error('Erreur:', error);
        }
    });

    // Filtres
    document.getElementById('typeFilter')?.addEventListener('change', filterNotifications);
    document.getElementById('statusFilter')?.addEventListener('change', filterNotifications);

    function filterNotifications() {
        const typeFilter = document.getElementById('typeFilter').value;
        const statusFilter = document.getElementById('statusFilter').value;
        
        document.querySelectorAll('.notification-row').forEach(row => {
            const type = row.dataset.type;
            const isRead = row.dataset.read === 'true';
            
            let showType = !typeFilter || type === typeFilter;
            let showStatus = !statusFilter || 
                (statusFilter === 'read' && isRead) || 
                (statusFilter === 'unread' && !isRead);
            
            row.style.display = (showType && showStatus) ? 'block' : 'none';
        });
    }
</script>
@endpush
@endsection
