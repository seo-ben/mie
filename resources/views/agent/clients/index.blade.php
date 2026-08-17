@extends($layout ?? 'layouts.agent')

@section('title', 'Mes Clients')

@section('content')
<div class="py-4 container-fluid">
    <!-- En-tête avec statistiques -->
    <div class="mb-4 row">
        <div class="col-12">
            <div class="mb-3 d-flex justify-content-between align-items-center">
                <h2 class="mb-0">Mes Clients</h2>
                <a href="{{ route('agent.clients.create') }}" class="btn btn-primary">
                    <i class="fas fa-user-plus me-2"></i>Nouveau Client
                </a>
            </div>
        </div>
    </div>

    <!-- Cartes de statistiques -->
    <div class="mb-4 row">
        <div class="mb-3 col-xl-3 col-md-6">
            <div class="py-2 shadow card border-left-primary h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="mr-2 col">
                            <div class="mb-1 text-xs font-weight-bold text-primary text-uppercase">Total Clients</div>
                            <div class="mb-0 text-gray-800 h5 font-weight-bold">{{ $stats['total'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="text-gray-300 fas fa-users fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(auth()->user()->role !== 'agent_terrain')
        <div class="mb-3 col-xl-3 col-md-6">
            <div class="py-2 shadow card border-left-warning h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="mr-2 col">
                            <div class="mb-1 text-xs font-weight-bold text-warning text-uppercase">KYC En Attente</div>
                            <div class="mb-0 text-gray-800 h5 font-weight-bold">{{ $stats['pending_kyc'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="text-gray-300 fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-3 col-xl-3 col-md-6">
            <div class="py-2 shadow card border-left-success h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="mr-2 col">
                            <div class="mb-1 text-xs font-weight-bold text-success text-uppercase">KYC Approuvés</div>
                            <div class="mb-0 text-gray-800 h5 font-weight-bold">{{ $stats['approved'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="text-gray-300 fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="mb-3 col-xl-3 col-md-6">
            <div class="py-2 shadow card border-left-info h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="mr-2 col">
                            <div class="mb-1 text-xs font-weight-bold text-info text-uppercase">Aujourd'hui</div>
                            <div class="mb-0 text-gray-800 h5 font-weight-bold">{{ $stats['today'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="text-gray-300 fas fa-calendar-day fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="mb-4 shadow card">
        <div class="py-3 card-header">
            <h6 class="m-0 font-weight-bold text-primary">Filtres</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('agent.clients.index') }}">
                <div class="row">
                    <div class="mb-3 col-md-4">
                        <label for="search" class="form-label">Rechercher</label>
                        <input type="text" class="form-control" id="search" name="search"
                               placeholder="Nom, téléphone, numéro client..."
                               value="{{ request('search') }}">
                    </div>

                    <div class="mb-3 col-md-3">
                        <label for="kyc_status" class="form-label">Statut KYC</label>
                        <select class="form-select" id="kyc_status" name="kyc_status">
                            <option value="">Tous</option>
                            <option value="pending" {{ request('kyc_status') == 'pending' ? 'selected' : '' }}>En attente</option>
                            <option value="approved" {{ request('kyc_status') == 'approved' ? 'selected' : '' }}>Approuvé</option>
                            <option value="rejected" {{ request('kyc_status') == 'rejected' ? 'selected' : '' }}>Rejeté</option>
                        </select>
                    </div>

                    <div class="mb-3 col-md-3">
                        <label for="registration_status" class="form-label">Statut Inscription</label>
                        <select class="form-select" id="registration_status" name="registration_status">
                            <option value="">Tous</option>
                            <option value="pending" {{ request('registration_status') == 'pending' ? 'selected' : '' }}>En attente</option>
                            <option value="approved" {{ request('registration_status') == 'approved' ? 'selected' : '' }}>Approuvé</option>
                            <option value="rejected" {{ request('registration_status') == 'rejected' ? 'selected' : '' }}>Rejeté</option>
                        </select>
                    </div>

                    <div class="mb-3 col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-1"></i>Filtrer
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tableau des clients -->
    <div class="mb-4 shadow card">
        <div class="py-3 card-header">
            <h6 class="m-0 font-weight-bold text-primary">Liste des Clients ({{ $clients->total() }})</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>N° Client</th>
                            <th>Nom Complet</th>
                            <th>Téléphone</th>
                            <th>Comptes</th>
                            @if(auth()->user()->role !== 'agent_terrain')
                            <th>KYC</th>
                            @endif
                            <th>Statut</th>
                            <th>Date Inscription</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clients as $client)
                        <tr>
                            <td>
                                <strong>{{ $client->client_number }}</strong>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    @if($client->profile_photo_url)
                                        <img src="{{ Storage::url($client->profile_photo_url) }}"
                                             class="rounded-circle me-2"
                                             style="width: 40px; height: 40px; object-fit: cover;">
                                    @else
                                        <div class="text-white rounded-circle bg-primary d-flex align-items-center justify-content-center me-2"
                                             style="width: 40px; height: 40px;">
                                            {{ substr($client->first_name, 0, 1) }}{{ substr($client->last_name, 0, 1) }}
                                        </div>
                                    @endif
                                    <div>
                                        <div class="fw-bold">{{ $client->first_name }} {{ $client->last_name }}</div>
                                        <small class="text-muted">{{ $client->email }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $client->phone }}</td>
                            <td>
                                <span class="badge bg-info">{{ $client->accounts->count() }} compte(s)</span>
                                @php
                                    $suspended = $client->accounts->where('status', 'suspended')->count();
                                @endphp
                                @if($suspended > 0)
                                    <span class="badge bg-warning">{{ $suspended }} suspendu(s)</span>
                                @endif
                            </td>
                            @if(auth()->user()->role !== 'agent_terrain')
                            <td>
                                @switch($client->kyc_status)
                                    @case('pending')
                                        <span class="badge bg-warning">En attente</span>
                                        @break
                                    @case('approved')
                                        <span class="badge bg-success">Approuvé</span>
                                        @break
                                    @case('rejected')
                                        <span class="badge bg-danger">Rejeté</span>
                                        @break
                                @endswitch
                            </td>
                            @endif
                            <td>
                                @switch($client->registration_status)
                                    @case('pending')
                                        <span class="badge bg-secondary">Pending</span>
                                        @break
                                    @case('approved')
                                        <span class="badge bg-success">Approuvé</span>
                                        @break
                                    @case('rejected')
                                        <span class="badge bg-danger">Rejeté</span>
                                        @break
                                @endswitch
                            </td>
                            <td>{{ $client->created_at->format('d/m/Y') }}</td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('agent.clients.show', $client->id) }}"
                                       class="btn btn-sm btn-info" title="Voir">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('agent.accounts.create', $client->id) }}"
                                       class="btn btn-sm btn-success" title="créer un compte">
                                        <i class="fas fa-plus-circle"></i>
                                    </a>
                                    @if($client->kyc_status !== 'approved')
                                        <a href="{{ route('agent.clients.edit', $client->id) }}"
                                           class="btn btn-sm btn-primary" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="py-4 text-center">
                                <i class="mb-3 fas fa-users fa-3x text-muted"></i>
                                <p class="text-muted">Aucun client trouvé</p>
                                <a href="{{ route('agent.clients.create') }}" class="btn btn-primary">
                                    <i class="fas fa-user-plus me-2"></i>Créer votre premier client
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($clients->hasPages())
            <div class="mt-3 d-flex justify-content-between align-items-center">
                <div>
                    Affichage de {{ $clients->firstItem() }} à {{ $clients->lastItem() }} sur {{ $clients->total() }} clients
                </div>
                <div>
                    {{ $clients->links() }}
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .border-left-primary {
        border-left: 0.25rem solid #4e73df !important;
    }
    .border-left-success {
        border-left: 0.25rem solid #1cc88a !important;
    }
    .border-left-info {
        border-left: 0.25rem solid #36b9cc !important;
    }
    .border-left-warning {
        border-left: 0.25rem solid #f6c23e !important;
    }
</style>
@endpush
