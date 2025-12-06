@extends('layouts.app_admin')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <!-- En-tête -->
        <div class="mb-6">
            <a href="{{ route('admin.clients.show', $client->id) }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-2">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Retour au profil
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Validation KYC</h1>
            <p class="mt-1 text-sm text-gray-600">Vérifiez les informations du client avant validation</p>
        </div>

        <!-- Informations principales -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center">
                    <div class="h-16 w-16 rounded-full bg-blue-100 flex items-center justify-center overflow-hidden">
                        @if($client->profile_photo_url)
                            <img src="{{ asset('storage/' . $client->profile_photo_url) }}"
                                alt="Photo de {{ $client->full_name }}"
                                class="object-cover w-16 h-16 border border-gray-300 rounded-full">
                        @else
                            <span class="text-2xl text-blue-600 font-bold">{{ strtoupper(substr($client->first_name,0,1)) }}{{ strtoupper(substr($client->last_name,0,1)) }}</span>
                        @endif
                    </div>
                    <div class="ml-4">
                        <h2 class="text-xl font-semibold text-gray-900">{{ $client->full_name }}</h2>
                        <p class="text-sm text-gray-600">{{ $client->client_number }} • {{ $client->phone }}</p>
                    </div>
                </div>
                <span class="px-4 py-1 text-sm font-medium rounded-full bg-yellow-100 text-yellow-800">
                    En attente
                </span>
            </div>

            <div class="px-6 py-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Informations personnelles -->
                <div>
                    <h3 class="text-gray-900 font-semibold text-sm mb-3 uppercase tracking-wide">Informations Personnelles</h3>
                    <dl class="space-y-2">
                        <div class="flex justify-between border-b border-gray-100 py-2">
                            <dt class="text-sm text-gray-600">Nom complet</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ $client->full_name }}</dd>
                        </div>
                        <div class="flex justify-between border-b border-gray-100 py-2">
                            <dt class="text-sm text-gray-600">Date de naissance</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ $client->date_of_birth?->format('d/m/Y') }}</dd>
                        </div>
                        <div class="flex justify-between border-b border-gray-100 py-2">
                            <dt class="text-sm text-gray-600">Genre</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ $client->gender === 'male' ? 'Masculin' : 'Féminin' }}</dd>
                        </div>
                        <div class="flex justify-between border-b border-gray-100 py-2">
                            <dt class="text-sm text-gray-600">Téléphone</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ $client->phone }}</dd>
                        </div>
                        <div class="flex justify-between py-2">
                            <dt class="text-sm text-gray-600">Adresse</dt>
                            <dd class="text-sm font-medium text-gray-900 text-right">{{ $client->address }}, {{ $client->city }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Pièce d'identité -->
                <div>
                    <h3 class="text-gray-900 font-semibold text-sm mb-3 uppercase tracking-wide">Pièce d'identité</h3>
                    <dl class="space-y-2">
                        <div class="flex justify-between border-b border-gray-100 py-2">
                            <dt class="text-sm text-gray-600">Type</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ ucfirst(str_replace('_',' ',$client->id_type)) }}</dd>
                        </div>
                        <div class="flex justify-between border-b border-gray-100 py-2">
                            <dt class="text-sm text-gray-600">Numéro</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ $client->id_number }}</dd>
                        </div>
                        <div class="flex justify-between border-b border-gray-100 py-2">
                            <dt class="text-sm text-gray-600">Date d'expiration</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ $client->id_expiry_date?->format('d/m/Y') ?? 'Non renseignée' }}</dd>
                        </div>
                        <div class="flex justify-between border-b border-gray-100 py-2">
                            <dt class="text-sm text-gray-600">Profession</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ $client->profession ?? 'Non renseignée' }}</dd>
                        </div>
                        <div class="flex justify-between py-2">
                            <dt class="text-sm text-gray-600">Revenu mensuel</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ $client->monthly_income ? number_format($client->monthly_income,0,',',' ').' FCFA' : 'Non renseigné' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Documents -->
        @if($client->documents->isNotEmpty())
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Documents joints</h3>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($client->documents as $document)
                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-900">{{ ucfirst(str_replace('_',' ',$document->document_type)) }}</span>
                            @if($document->is_verified)
                                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            @endif
                        </div>
                        <a href="{{ $document->file_path }}" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            Voir le document
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- KYC checklist -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Liste de vérification KYC</h3>
            </div>
            <div class="p-6 space-y-3">
                @php
                    $checklist = [
                        ['label'=>'Nom et prénom renseignés','check'=>$client->first_name && $client->last_name],
                        ['label'=>'Numéro de téléphone valide','check'=>$client->phone],
                        ['label'=>'Date de naissance fournie','check'=>$client->date_of_birth],
                        ['label'=>'Adresse complète fournie','check'=>$client->address && $client->city],
                        ['label'=>'Pièce d\'identité valide','check'=>$client->id_type && $client->id_number],
                        ['label'=>'Documents justificatifs','check'=>$client->documents->isNotEmpty()],
                    ];
                @endphp

                @foreach($checklist as $item)
                    <li class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="h-6 w-6 rounded-full {{ $item['check'] ? 'bg-green-100' : 'bg-gray-100' }} flex items-center justify-center">
                                @if($item['check'])
                                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                @endif
                            </div>
                        </div>
                        <p class="ml-3 text-sm text-gray-700">{{ $item['label'] }}</p>
                    </li>
                @endforeach
            </div>
        </div>

        <!-- Actions -->
        <div class="bg-white rounded-lg shadow p-6 space-y-4">
            <h3 class="text-lg font-semibold text-gray-900">Décision</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <form action="{{ route('admin.clients.approve-kyc', $client->id) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir approuver ce KYC ?')">
                    @csrf
                    <button type="submit" class="w-full px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow flex items-center justify-center transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Approuver le KYC
                    </button>
                </form>

                <button type="button" onclick="openRejectModal()" class="w-full px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg shadow flex items-center justify-center transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Rejeter le KYC
                </button>
            </div>

            <a href="{{ route('admin.clients.show', $client->id) }}" class="block text-center px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">Annuler</a>
        </div>
    </div>
</div>

<!-- Modal Rejet -->
<div id="rejectModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 z-50 flex items-start justify-center overflow-y-auto pt-20">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-5 relative">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Rejeter le KYC</h3>
            <button onclick="closeRejectModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form action="{{ route('admin.clients.reject-kyc', $client->id) }}" method="POST">
            @csrf
            <textarea name="rejection_reason" rows="4" required placeholder="Expliquez pourquoi le KYC est rejeté..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent mb-4"></textarea>
            <div class="flex space-x-3">
                <button type="button" onclick="closeRejectModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">Annuler</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">Confirmer le rejet</button>
            </div>
        </form>
    </div>
</div>

<script>
function openRejectModal(){ document.getElementById('rejectModal').classList.remove('hidden'); }
function closeRejectModal(){ document.getElementById('rejectModal').classList.add('hidden'); }
document.getElementById('rejectModal')?.addEventListener('click', e => { if(e.target===e.currentTarget) closeRejectModal(); });
</script>
@endsection
