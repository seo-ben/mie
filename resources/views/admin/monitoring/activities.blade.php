@extends('layouts.app_admin')

@section('title', 'Journal des activités')

@section('page-title', 'Journal des activités')

@section('content')
    <div class="max-w-7xl mx-auto">
        <form method="GET" action="{{ route('admin.monitoring.activities') }}" class="mb-6 bg-white p-6 rounded-lg shadow">
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div>
                    <label for="user_id" class="block text-sm font-medium text-gray-700">Utilisateur</label>
                    <select name="user_id" id="user_id" class="mt-1 w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="">Tous</option>
                        @foreach ($filters['users'] as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->first_name }} {{ $user->last_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="action" class="block text-sm font-medium text-gray-700">Action</label>
                    <select name="action" id="action" class="mt-1 w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="">Toutes</option>
                        @foreach ($filters['actions'] as $action)
                            <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>{{ $action }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="entity_type" class="block text-sm font-medium text-gray-700">Type d'entité</label>
                    <select name="entity_type" id="entity_type" class="mt-1 w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="">Tous</option>
                        {{-- @foreach ($filters['entity_types'] as $type)
                            <option value="{{ $type }}" {{ request('entity_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach --}}
                    </select>
                </div>
                <div>
                    <label for="per_page" class="block text-sm font-medium text-gray-700">Par page</label>
                    <select name="per_page" id="per_page" class="mt-1 w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" onchange="this.form.submit()">
                        <option value="10" {{ request('per_page', 20) == 10 ? 'selected' : '' }}>10</option>
                        <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>20</option>
                        <option value="50" {{ request('per_page', 20) == 50 ? 'selected' : '' }}>50</option>
                    </select>
                </div>
            </div>
            <div class="mt-4 flex gap-4">
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-filter mr-2"></i> Filtrer
                </button>
            </div>
        </form>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilisateur</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Entité</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Détails</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($activities as $activity)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $activity->user ? $activity->user->first_name . ' ' . $activity->user->last_name : 'Système' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $activity->action }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $activity->entity_type }} (ID: {{ $activity->entity_id }})</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $activity->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-4">{{ json_encode($activity->additional_data) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">Aucune activité trouvée.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">
            {{ $activities->links() }}
        </div>
    </div>

    @push('scripts')
        <script>
            document.querySelectorAll('#user_id, #action, #entity_type').forEach(element => {
                element.addEventListener('change', function() {
                    fetch('{{ route('admin.monitoring.activities') }}?' + new URLSearchParams({
                        user_id: document.getElementById('user_id').value,
                        action: document.getElementById('action').value,
                        entity_type: document.getElementById('entity_type').value,
                        date_from: document.querySelector('input[name="date_from"]').value,
                        date_to: document.querySelector('input[name="date_to"]').value,
                        per_page: document.getElementById('per_page').value
                    }), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        const tbody = document.querySelector('tbody');
                        tbody.innerHTML = '';
                        if (data.data.data.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Aucune activité trouvée.</td></tr>';
                            return;
                        }
                        data.data.data.forEach(activity => {
                            const userName = activity.user ? `${activity.user.first_name} ${activity.user.last_name}` : 'Système';
                            tbody.innerHTML += `
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">${userName}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">${activity.action}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">${activity.entity_type} (ID: ${activity.entity_id})</td>
                                    <td class="px-6 py-4 whitespace-nowrap">${new Date(activity.created_at).toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })}</td>
                                    <td class="px-6 py-4">${JSON.stringify(activity.additional_data)}</td>
                                </tr>
                            `;
                        });
                    });
                });
            });
        </script>
    @endpush
@endsection

