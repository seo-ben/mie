@extends('layouts.app_admin')

@section('content')
<div class="min-h-screen py-8 bg-gray-50">
    <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">

        <!-- En-tête -->
        <div class="mb-8">
            <a href="{{ route('admin.clients.index') }}" class="inline-flex items-center mb-4 text-sm text-gray-500 transition hover:text-gray-700">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Retour à la liste
            </a>

            <div class="flex items-start justify-between">
                <div class="flex items-center space-x-4">
                    <!-- Photo de profil -->
                    @if($client->profile_photo_url)
                        <img src="{{ asset('storage/' . $client->profile_photo_url) }}"
                             alt="Photo de {{ $client->full_name }}"
                             class="object-cover w-20 h-20 border-2 border-gray-300 rounded-full shadow-lg">
                    @else
                        <div class="flex items-center justify-center w-20 h-20 border-2 border-gray-300 rounded-full shadow-lg bg-gradient-to-br from-blue-500 to-purple-600">
                            <span class="text-2xl font-bold text-white">
                                {{ strtoupper(substr($client->first_name,0,1) . substr($client->last_name,0,1)) }}
                            </span>
                        </div>
                    @endif

                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">{{ $client->full_name }}</h1>
                        <p class="mt-1 font-mono text-sm font-semibold text-gray-600">{{ $client->client_number }}</p>
                        <div class="flex items-center mt-2 space-x-2">
                            @if($client->is_active)
                                <span class="inline-flex items-center px-3 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-full">
                                    <span class="w-2 h-2 mr-2 bg-green-500 rounded-full animate-pulse"></span>
                                    Actif
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 text-xs font-semibold text-red-700 bg-red-100 rounded-full">
                                    <span class="w-2 h-2 mr-2 bg-red-500 rounded-full"></span>
                                    Désactivé
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex space-x-3">
                    <a href="{{ route('admin.clients.edit', $client->id) }}"
                       class="inline-flex items-center px-4 py-2 text-gray-700 transition bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 hover:shadow">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Modifier
                    </a>

                    @if($client->is_active)
                        <form action="{{ route('admin.clients.deactivate-accounts', $client->id) }}" method="POST"
                              onsubmit="return confirm('Voulez-vous vraiment désactiver ce client ?');">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 text-white transition bg-red-600 rounded-lg shadow-sm hover:bg-red-700 hover:shadow">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                </svg>
                                Désactiver
                            </button>
                        </form>
                    @else
                        <form action="{{ route('admin.clients.activate-accounts', $client->id) }}" method="POST"
                              onsubmit="return confirm('Voulez-vous réactiver ce client ?');">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 text-white transition bg-green-600 rounded-lg shadow-sm hover:bg-green-700 hover:shadow">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Réactiver
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <!-- Messages flash -->
        @if(session('success'))
            <div class="p-4 mb-6 border-l-4 border-green-500 rounded-lg shadow-sm bg-green-50 animate-fade-in">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="font-semibold text-green-700">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 mb-6 border-l-4 border-red-500 rounded-lg shadow-sm bg-red-50 animate-fade-in">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="font-semibold text-red-700">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        <!-- Statut KYC -->
        <div class="p-6 mb-6 transition bg-white rounded-lg shadow-sm hover:shadow">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        @if($client->kyc_status === 'approved')
                            <div class="flex items-center justify-center bg-green-100 rounded-full shadow-inner w-14 h-14">
                                <svg class="text-green-600 w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                        @elseif($client->kyc_status === 'rejected')
                            <div class="flex items-center justify-center bg-red-100 rounded-full shadow-inner w-14 h-14">
                                <svg class="text-red-600 w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        @else
                            <div class="flex items-center justify-center bg-yellow-100 rounded-full shadow-inner w-14 h-14">
                                <svg class="text-yellow-600 w-7 h-7 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        @endif
                    </div>
                    <div class="ml-5">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Statut KYC:
                            @if($client->kyc_status === 'approved')
                                <span class="text-green-600">✓ Approuvé</span>
                            @elseif($client->kyc_status === 'rejected')
                                <span class="text-red-600">✗ Rejeté</span>
                            @else
                                <span class="text-yellow-600">⏳ En attente</span>
                            @endif
                        </h3>
                        @if($client->kyc_status === 'approved' && $client->kyc_approved_at)
                            <p class="text-sm text-gray-600">Approuvé le {{ $client->kyc_approved_at->format('d/m/Y à H:i') }}</p>
                        @elseif($client->kyc_status === 'rejected')
                            <p class="text-sm text-red-600">Raison: {{ $client->rejection_reason }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex space-x-3">
                    @if($client->kyc_status === 'pending')
                        <a href="{{ route('admin.clients.validate-kyc', $client->id) }}"
                           class="inline-flex items-center px-4 py-2 text-white transition bg-blue-600 rounded-lg shadow-sm hover:bg-blue-700 hover:shadow">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Valider le KYC
                        </a>
                    @endif
                    @if($client->kyc_status === 'approved')
                        <a href="{{ route('admin.loans.create', ['client_id' => $client->id]) }}"
                           class="inline-flex items-center px-4 py-2 text-white transition bg-blue-600 rounded-lg shadow-sm hover:bg-blue-700 hover:shadow">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Demander un Prêt
                        </a>
                    @else
                        <button disabled
                                class="inline-flex items-center px-4 py-2 text-white bg-gray-400 rounded-lg cursor-not-allowed opacity-60"
                                title="Le KYC doit être approuvé avant de demander un prêt">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Demander un Prêt
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Statistiques des Comptes -->
        @php
            $totalBalance = $client->accounts->sum('balance');
            $activeAccounts = $client->accounts->where('status', 'active')->count();
            $savingsAccounts = $client->accounts->where('account_type', 'savings');
            $tontineAccounts = $client->accounts->where('account_type', 'tontine');
            $savingsBalance = $savingsAccounts->sum('balance');
            $tontineBalance = $tontineAccounts->sum('balance');
        @endphp

        <div class="grid grid-cols-1 gap-6 mb-6 md:grid-cols-2 lg:grid-cols-4">
            <!-- Total des Comptes -->
            <div class="p-6 transition bg-white rounded-lg shadow-sm hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total des Comptes</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ $client->accounts->count() }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $activeAccounts }} actif(s)</p>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-full">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Solde Total -->
            <div class="p-6 transition rounded-lg shadow-sm bg-gradient-to-br from-green-500 to-emerald-600 hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-green-100">Solde Total</p>
                        <p class="mt-2 text-3xl font-bold text-white">{{ number_format($totalBalance, 0, ',', ' ') }}</p>
                        <p class="mt-1 text-xs text-green-100">FCFA</p>
                    </div>
                    <div class="p-3 bg-white rounded-full bg-opacity-20">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Comptes Épargne -->
            <div class="p-6 transition bg-white rounded-lg shadow-sm hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Épargne</p>
                        <p class="mt-2 text-3xl font-bold text-cyan-600">{{ number_format($savingsBalance, 0, ',', ' ') }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $savingsAccounts->count() }} compte(s)</p>
                    </div>
                    <div class="p-3 rounded-full bg-cyan-100">
                        <svg class="w-8 h-8 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Comptes Tontine -->
            <div class="p-6 transition bg-white rounded-lg shadow-sm hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Tontine</p>
                        <p class="mt-2 text-3xl font-bold text-purple-600">{{ number_format($tontineBalance, 0, ',', ' ') }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $tontineAccounts->count() }} compte(s)</p>
                    </div>
                    <div class="p-3 bg-purple-100 rounded-full">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Colonne principale -->
            <div class="space-y-6 lg:col-span-2">

                <!-- Comptes détaillés -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-gray-900">
                            <svg class="inline-block w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                            Comptes
                        </h2>
                        @if($client->kyc_status === 'approved')
                            <a href="{{ route('admin.accounts.create', $client->id) }}"
                               class="inline-flex items-center px-4 py-2 text-sm font-semibold text-white transition bg-blue-600 rounded-lg shadow-sm hover:bg-blue-700 hover:shadow">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Nouveau Compte
                            </a>
                        @endif
                    </div>
                    <div class="divide-y divide-gray-200">
                        @forelse($client->accounts as $account)
                            <div class="px-6 py-5 transition hover:bg-gray-50">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center mb-2 space-x-3">
                                            <span class="inline-flex items-center px-3 py-1 text-xs font-bold rounded-full shadow-sm
                                                {{ $account->account_type === 'savings' ? 'bg-cyan-100 text-cyan-800' : 'bg-purple-100 text-purple-800' }}">
                                                @if($account->account_type === 'savings')
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                                                        <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                                                    </svg>
                                                    Épargne
                                                @else
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                                                    </svg>
                                                    Tontine
                                                @endif
                                            </span>
                                            <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full
                                                {{ $account->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                                @if($account->status === 'active')
                                                    <span class="w-2 h-2 mr-1.5 bg-green-500 rounded-full animate-pulse"></span>
                                                    Actif
                                                @else
                                                    <span class="w-2 h-2 mr-1.5 bg-gray-500 rounded-full"></span>
                                                    Inactif
                                                @endif
                                            </span>
                                        </div>
                                        <p class="mb-1 font-mono text-sm font-bold text-gray-900">{{ $account->account_number }}</p>
                                        @if($account->last_transaction_at)
                                            <p class="text-xs text-gray-500">
                                                Dernière transaction: {{ $account->last_transaction_at->diffForHumans() }}
                                            </p>
                                        @endif
                                    </div>
                                    <div class="ml-4 text-right">
                                        <p class="mb-2 text-2xl font-black {{ $account->account_type === 'savings' ? 'text-cyan-600' : 'text-purple-600' }}">
                                            {{ number_format($account->balance, 0, ',', ' ') }}
                                        </p>
                                        <p class="mb-3 text-xs font-semibold text-gray-500">FCFA</p>
                                        <a href="{{ route('admin.accounts.show', $account->id) }}"
                                           class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-blue-700 transition bg-blue-100 rounded-lg hover:bg-blue-200">
                                            Voir détails
                                            <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="px-6 py-12 text-center">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                                <p class="mb-4 text-gray-500">Aucun compte créé pour ce client</p>
                                @if($client->kyc_status === 'approved')
                                    <a href="{{ route('admin.accounts.create', $client->id) }}"
                                       class="inline-flex items-center px-4 py-2 text-white transition bg-blue-600 rounded-lg hover:bg-blue-700">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        Créer un compte
                                    </a>
                                @endif
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Informations personnelles -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-gray-900">
                            <svg class="inline-block w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Informations Personnelles
                        </h2>
                    </div>
                    <div class="px-6 py-5">
                        <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div class="p-4 transition border border-gray-200 rounded-lg hover:border-blue-300 hover:shadow-sm">
                                <dt class="flex items-center mb-2 text-sm font-semibold text-gray-600">
                                    <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    Nom complet
                                </dt>
                                <dd class="text-base font-bold text-gray-900">{{ $client->full_name }}</dd>
                            </div>
                            <div class="p-4 transition border border-gray-200 rounded-lg hover:border-blue-300 hover:shadow-sm">
                                <dt class="flex items-center mb-2 text-sm font-semibold text-gray-600">
                                    <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                    Téléphone
                                </dt>
                                <dd class="text-base font-bold text-gray-900">{{ $client->phone }}</dd>
                            </div>
                            <div class="p-4 transition border border-gray-200 rounded-lg hover:border-blue-300 hover:shadow-sm">
                                <dt class="flex items-center mb-2 text-sm font-semibold text-gray-600">
                                    <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    Date de naissance
                                </dt>
                                <dd class="text-base font-bold text-gray-900">{{ $client->date_of_birth?->format('d/m/Y') ?? 'Non renseigné' }}</dd>
                            </div>
                            <div class="p-4 transition border border-gray-200 rounded-lg hover:border-blue-300 hover:shadow-sm">
                                <dt class="flex items-center mb-2 text-sm font-semibold text-gray-600">
                                    <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                    </svg>
                                    Genre
                                </dt>
                                <dd class="text-base font-bold text-gray-900">{{ $client->gender === 'male' ? 'Masculin' : 'Féminin' }}</dd>
                            </div>
                            <div class="p-4 transition border border-gray-200 rounded-lg hover:border-blue-300 hover:shadow-sm">
                                <dt class="flex items-center mb-2 text-sm font-semibold text-gray-600">
                                    <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    Profession
                                </dt>
                                <dd class="text-base font-bold text-gray-900">{{ $client->profession ?? 'Non renseigné' }}</dd>
                            </div>
                            <div class="p-4 transition border border-gray-200 rounded-lg hover:border-blue-300 hover:shadow-sm">
                                <dt class="flex items-center mb-2 text-sm font-semibold text-gray-600">
                                    <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Revenu mensuel
                                </dt>
                                <dd class="text-base font-bold text-gray-900">{{ $client->monthly_income ? number_format($client->monthly_income, 0, ',', ' ') . ' FCFA' : 'Non renseigné' }}</dd>
                            </div>
                            <div class="p-4 transition border border-gray-200 rounded-lg sm:col-span-2 hover:border-blue-300 hover:shadow-sm">
                                <dt class="flex items-center mb-2 text-sm font-semibold text-gray-600">
                                    <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    Adresse
                                </dt>
                                <dd class="text-base font-bold text-gray-900">{{ $client->address }}, {{ $client->city }}, {{ $client->region }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Pièce d'identité -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-gray-900">
                            <svg class="inline-block w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>
                            </svg>
                            Pièce d'Identité
                        </h2>
                    </div>
                    <div class="px-6 py-5">
                        <dl class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                            <div class="p-4 transition border border-gray-200 rounded-lg hover:border-blue-300 hover:shadow-sm">
                                <dt class="mb-2 text-sm font-semibold text-gray-600">Type de pièce</dt>
                                <dd class="text-base font-bold text-gray-900">{{ ucfirst(str_replace('_', ' ', $client->id_type)) }}</dd>
                            </div>
                            <div class="p-4 transition border border-gray-200 rounded-lg hover:border-blue-300 hover:shadow-sm">
                                <dt class="mb-2 text-sm font-semibold text-gray-600">Numéro</dt>
                                <dd class="font-mono text-base font-bold text-gray-900">{{ $client->id_number }}</dd>
                            </div>
                            <div class="p-4 transition border border-gray-200 rounded-lg hover:border-blue-300 hover:shadow-sm">
                                <dt class="mb-2 text-sm font-semibold text-gray-600">Date d'expiration</dt>
                                <dd class="text-base font-bold text-gray-900">{{ $client->id_expiry_date?->format('d/m/Y') ?? 'Non renseigné' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Prêts -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-gray-900">
                            <svg class="inline-block w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Prêts ({{ $client->loans->count() }})
                        </h2>
                    </div>
                    <div class="divide-y divide-gray-200">
                        @forelse($client->loans as $loan)
                            <div class="px-6 py-5 transition hover:bg-gray-50">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="mb-1 font-mono text-sm font-bold text-gray-900">{{ $loan->loan_number }}</p>
                                        <p class="text-sm text-gray-600">{{ ucfirst($loan->loan_type) }}</p>
                                    </div>
                                    <div class="ml-4 text-right">
                                        <p class="mb-2 text-xl font-black text-blue-600">{{ number_format($loan->amount, 0, ',', ' ') }}</p>
                                        <p class="mb-2 text-xs font-semibold text-gray-500">FCFA</p>
                                        <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full
                                            {{ $loan->status === 'active' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ ucfirst($loan->status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="px-6 py-12 text-center">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-gray-500">Aucun prêt pour ce client</p>
                            </div>
                        @endforelse
                    </div>
                </div>

            </div>

            <!-- Sidebar -->
            <div class="space-y-6">

                <!-- Actions rapides -->
                <div class="p-6 bg-white rounded-lg shadow-sm">
                    <h3 class="mb-4 text-lg font-bold text-gray-900">Actions Rapides</h3>
                    <div class="space-y-3">
                        @if($client->kyc_status === 'approved')
                            <a href="{{ route('admin.accounts.create', $client->id) }}"
                               class="flex items-center w-full px-4 py-3 text-sm font-semibold text-left text-blue-700 transition bg-blue-100 rounded-lg hover:bg-blue-200">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Créer un compte
                            </a>
                            <a href="{{ route('admin.loans.create', ['client_id' => $client->id]) }}"
                               class="flex items-center w-full px-4 py-3 text-sm font-semibold text-left text-green-700 transition bg-green-100 rounded-lg hover:bg-green-200">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Demander un prêt
                            </a>
                        @endif
                        <a href="{{ route('admin.clients.edit', $client->id) }}"
                           class="flex items-center w-full px-4 py-3 text-sm font-semibold text-left text-gray-700 transition bg-gray-100 rounded-lg hover:bg-gray-200">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Modifier le profil
                        </a>
                    </div>
                </div>

                <!-- Informations système -->
                <div class="p-6 bg-white rounded-lg shadow-sm">
                    <h3 class="mb-4 text-lg font-bold text-gray-900">Informations Système</h3>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="font-semibold text-gray-600">Inscrit le</dt>
                            <dd class="mt-1 text-gray-900">{{ $client->created_at->format('d/m/Y à H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-gray-600">Dernière mise à jour</dt>
                            <dd class="mt-1 text-gray-900">{{ $client->updated_at->diffForHumans() }}</dd>
                        </div>
                        @if($client->registeredBy)
                            <div>
                                <dt class="font-semibold text-gray-600">Enregistré par</dt>
                                <dd class="mt-1 text-gray-900">{{ $client->registeredBy->name }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <!-- Graphique de répartition (optionnel) -->
                @if($client->accounts->count() > 0)
                    <div class="p-6 bg-white rounded-lg shadow-sm">
                        <h3 class="mb-4 text-lg font-bold text-gray-900">Répartition des Fonds</h3>
                        <div class="space-y-4">
                            @if($savingsBalance > 0)
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-semibold text-cyan-600">Épargne</span>
                                        <span class="text-sm font-bold text-gray-900">{{ number_format(($savingsBalance / $totalBalance) * 100, 1) }}%</span>
                                    </div>
                                    <div class="w-full h-3 bg-gray-200 rounded-full">
                                        <div class="h-3 transition-all duration-500 rounded-full bg-gradient-to-r from-cyan-500 to-blue-500"
                                             style="width: {{ ($savingsBalance / $totalBalance) * 100 }}%"></div>
                                    </div>
                                </div>
                            @endif
                            @if($tontineBalance > 0)
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-semibold text-purple-600">Tontine</span>
                                        <span class="text-sm font-bold text-gray-900">{{ number_format(($tontineBalance / $totalBalance) * 100, 1) }}%</span>
                                    </div>
                                    <div class="w-full h-3 bg-gray-200 rounded-full">
                                        <div class="h-3 transition-all duration-500 rounded-full bg-gradient-to-r from-purple-500 to-pink-500"
                                             style="width: {{ ($tontineBalance / $totalBalance) * 100 }}%"></div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>
</div>
@endsection
