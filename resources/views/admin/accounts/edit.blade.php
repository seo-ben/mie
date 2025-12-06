@extends('layouts.app_admin')

@section('title', 'Modifier le Compte')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Breadcrumb -->
        <nav class="flex mb-6" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('admin.accounts.index') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                        </svg>
                        Comptes
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <a href="{{ route('admin.accounts.show', $account->id) }}" class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2">
                            {{ $account->account_number }}
                        </a>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Modifier</span>
                    </div>
                </li>
            </ol>
        </nav>

        <!-- En-tête -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Modifier le Compte</h1>
            <p class="mt-2 text-sm text-gray-600">
                Modifiez les paramètres du compte {{ $account->account_number }}
            </p>
        </div>

        <!-- Alerte d'information -->
        <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-r-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        <strong>Attention :</strong> Les modifications ne peuvent être effectuées que sur les comptes suspendus.
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Colonne gauche - Informations -->
            <div class="lg:col-span-1 space-y-6">

                <!-- Informations client -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Titulaire</h2>
                    </div>
                    <div class="px-6 py-4">
                        <div class="flex items-center space-x-4 mb-4">
                            @if($account->client->profile_photo_url)
                                <img src="{{ Storage::url($account->client->profile_photo_url) }}"
                                     alt="{{ $account->client->first_name }}"
                                     class="w-16 h-16 rounded-full object-cover border-2 border-gray-200">
                            @else
                                <div class="w-16 h-16 rounded-full bg-blue-500 flex items-center justify-center text-white text-xl font-bold">
                                    {{ substr($account->client->first_name, 0, 1) }}{{ substr($account->client->last_name, 0, 1) }}
                                </div>
                            @endif
                            <div>
                                <h3 class="text-base font-semibold text-gray-900">
                                    {{ $account->client->first_name }} {{ $account->client->last_name }}
                                </h3>
                                <p class="text-sm text-gray-500">{{ $account->client->client_number }}</p>
                            </div>
                        </div>

                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Téléphone</span>
                                <span class="font-medium text-gray-900">{{ $account->client->phone }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Email</span>
                                <span class="font-medium text-gray-900">{{ $account->client->email ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500">Statut KYC</span>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $account->client->kyc_status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ ucfirst($account->client->kyc_status) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informations du compte -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Informations Compte</h2>
                    </div>
                    <div class="px-6 py-4 space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">N° Compte</span>
                            <code class="px-2 py-1 bg-gray-100 rounded text-xs font-mono">{{ $account->account_number }}</code>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500">Type</span>
                            @if($account->account_type === 'savings')
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-semibold">Épargne</span>
                            @else
                                <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded-full text-xs font-semibold">Tontine</span>
                            @endif
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500">Statut</span>
                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-semibold">
                                {{ ucfirst($account->status) }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Solde actuel</span>
                            <span class="font-bold text-gray-900">{{ number_format($account->balance, 0, ',', ' ') }} FCFA</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Créé le</span>
                            <span class="font-medium text-gray-900">{{ $account->created_at->format('d/m/Y') }}</span>
                        </div>
                    </div>
                </div>

                @if($account->suspension_reason)
                <!-- Raison de suspension -->
                <div class="bg-red-50 border border-red-200 rounded-lg shadow-sm">
                    <div class="px-6 py-4 border-b border-red-200">
                        <h2 class="text-lg font-semibold text-red-900">Raison de Suspension</h2>
                    </div>
                    <div class="px-6 py-4">
                        <p class="text-sm text-red-800">{{ $account->suspension_reason }}</p>
                        @if($account->suspended_at)
                            <p class="text-xs text-red-600 mt-2">
                                Suspendu le {{ $account->suspended_at->format('d/m/Y à H:i') }}
                            </p>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            <!-- Colonne droite - Formulaire -->
            <div class="lg:col-span-2">
                <form method="POST" action="{{ route('admin.accounts.update', $account->id) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    @if($account->account_type === 'savings')
                        <!-- Configuration compte d'épargne -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                            <div class="px-6 py-4 bg-blue-50 border-b border-blue-200 rounded-t-lg">
                                <div class="flex items-center">
                                    <svg class="w-6 h-6 text-blue-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                    </svg>
                                    <h2 class="text-lg font-semibold text-blue-900">Configuration Compte d'Épargne</h2>
                                </div>
                            </div>
                            <div class="px-6 py-6 space-y-6">

                                <!-- Taux d'intérêt -->
                                <div>
                                    <label for="interest_rate" class="block text-sm font-medium text-gray-700 mb-2">
                                        Taux d'intérêt annuel (%)
                                    </label>
                                    <div class="relative rounded-md shadow-sm">
                                        <input type="number"
                                               step="0.01"
                                               name="interest_rate"
                                               id="interest_rate"
                                               value="{{ old('interest_rate', $account->savingsAccount->interest_rate) }}"
                                               min="0"
                                               max="100"
                                               class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('interest_rate') border-red-500 @enderror">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm">%</span>
                                        </div>
                                    </div>
                                    @error('interest_rate')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-1 text-sm text-gray-500">Les intérêts seront calculés mensuellement</p>
                                </div>

                                <!-- Solde minimum -->
                                <div>
                                    <label for="minimum_balance" class="block text-sm font-medium text-gray-700 mb-2">
                                        Solde minimum requis (FCFA)
                                    </label>
                                    <div class="relative rounded-md shadow-sm">
                                        <input type="number"
                                               name="minimum_balance"
                                               id="minimum_balance"
                                               value="{{ old('minimum_balance', $account->savingsAccount->minimum_balance) }}"
                                               min="0"
                                               step="1000"
                                               class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('minimum_balance') border-red-500 @enderror">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm">FCFA</span>
                                        </div>
                                    </div>
                                    @error('minimum_balance')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-1 text-sm text-gray-500">Solde minimum à maintenir sur le compte</p>
                                </div>

                                <!-- Frais mensuels -->
                                <div>
                                    <label for="monthly_fee" class="block text-sm font-medium text-gray-700 mb-2">
                                        Frais de tenue de compte mensuels (FCFA)
                                    </label>
                                    <div class="relative rounded-md shadow-sm">
                                        <input type="number"
                                               name="monthly_fee"
                                               id="monthly_fee"
                                               value="{{ old('monthly_fee', $account->savingsAccount->monthly_fee) }}"
                                               min="0"
                                               step="100"
                                               class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('monthly_fee') border-red-500 @enderror">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm">FCFA</span>
                                        </div>
                                    </div>
                                    @error('monthly_fee')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-1 text-sm text-gray-500">Frais prélevés automatiquement chaque mois</p>
                                </div>

                                <!-- Informations actuelles -->
                                <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                                    <h4 class="text-sm font-medium text-blue-900 mb-3">Statistiques Actuelles</h4>
                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <p class="text-blue-700">Total dépôts</p>
                                            <p class="font-bold text-blue-900">{{ number_format($account->savingsAccount->total_deposits, 0, ',', ' ') }} FCFA</p>
                                        </div>
                                        <div>
                                            <p class="text-blue-700">Total retraits</p>
                                            <p class="font-bold text-blue-900">{{ number_format($account->savingsAccount->total_withdrawals, 0, ',', ' ') }} FCFA</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    @else
                        <!-- Configuration compte tontine -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                            <div class="px-6 py-4 bg-purple-50 border-b border-purple-200 rounded-t-lg">
                                <div class="flex items-center">
                                    <svg class="w-6 h-6 text-purple-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"></path>
                                    </svg>
                                    <h2 class="text-lg font-semibold text-purple-900">Configuration Compte Tontine</h2>
                                </div>
                            </div>
                            <div class="px-6 py-6 space-y-6">

                                <!-- Montant tontine -->
                                <div>
                                    <label for="tontine_amount" class="block text-sm font-medium text-gray-700 mb-2">
                                        Montant de la tontine (FCFA)
                                    </label>
                                    <div class="relative rounded-md shadow-sm">
                                        <input type="number"
                                               name="tontine_amount"
                                               id="tontine_amount"
                                               value="{{ old('tontine_amount', $account->tontineAccount->tontine_amount) }}"
                                               min="200"
                                               class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('tontine_amount') border-red-500 @enderror">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm">FCFA</span>
                                        </div>
                                    </div>
                                    @error('tontine_amount')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-1 text-sm text-gray-500">Montant total à collecter par cycle</p>
                                </div>

                                <!-- Durée du cycle -->
                                <div>
                                    <label for="cycle_duration_months" class="block text-sm font-medium text-gray-700 mb-2">
                                        Durée du cycle (mois)
                                    </label>
                                    <select name="cycle_duration_months"
                                            id="cycle_duration_months"
                                            class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('cycle_duration_months') border-red-500 @enderror">
                                        <option value="">Sélectionner...</option>
                                        @foreach([1, 3, 6, 12, 18, 24] as $months)
                                            <option value="{{ $months }}" {{ old('cycle_duration_months', $account->tontineAccount->cycle_duration_months) == $months ? 'selected' : '' }}>
                                                {{ $months }} {{ $months > 1 ? 'mois' : 'mois' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('cycle_duration_months')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Fréquence de paiement -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-3">
                                        Fréquence de paiement
                                    </label>
                                    <div class="grid grid-cols-3 gap-4">
                                        <label class="relative flex cursor-pointer rounded-lg border border-gray-300 bg-white p-4 shadow-sm focus:outline-none hover:border-purple-500 transition">
                                            <input type="radio"
                                                   name="payment_frequency"
                                                   value="daily"
                                                   {{ old('payment_frequency', $account->tontineAccount->payment_frequency) === 'daily' ? 'checked' : '' }}
                                                   class="sr-only">
                                            <span class="flex flex-1">
                                                <span class="flex flex-col">
                                                    <span class="block text-sm font-medium text-gray-900">Quotidien</span>
                                                    <span class="mt-1 flex items-center text-sm text-gray-500">Paiements journaliers</span>
                                                </span>
                                            </span>
                                            <svg class="h-5 w-5 text-purple-600 hidden" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                        </label>

                                        <label class="relative flex cursor-pointer rounded-lg border border-gray-300 bg-white p-4 shadow-sm focus:outline-none hover:border-purple-500 transition">
                                            <input type="radio"
                                                   name="payment_frequency"
                                                   value="weekly"
                                                   {{ old('payment_frequency', $account->tontineAccount->payment_frequency) === 'weekly' ? 'checked' : '' }}
                                                   class="sr-only">
                                            <span class="flex flex-1">
                                                <span class="flex flex-col">
                                                    <span class="block text-sm font-medium text-gray-900">Hebdomadaire</span>
                                                    <span class="mt-1 flex items-center text-sm text-gray-500">Chaque semaine</span>
                                                </span>
                                            </span>
                                            <svg class="h-5 w-5 text-purple-600 hidden" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                        </label>

                                        <label class="relative flex cursor-pointer rounded-lg border border-gray-300 bg-white p-4 shadow-sm focus:outline-none hover:border-purple-500 transition">
                                            <input type="radio"
                                                   name="payment_frequency"
                                                   value="monthly"
                                                   {{ old('payment_frequency', $account->tontineAccount->payment_frequency) === 'monthly' ? 'checked' : '' }}
                                                   class="sr-only">
                                            <span class="flex flex-1">
                                                <span class="flex flex-col">
                                                    <span class="block text-sm font-medium text-gray-900">Mensuel</span>
                                                    <span class="mt-1 flex items-center text-sm text-gray-500">Chaque mois</span>
                                                </span>
                                            </span>
                                            <svg class="h-5 w-5 text-purple-600 hidden" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                        </label>
                                    </div>
                                    @error('payment_frequency')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Informations actuelles -->
                                <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
                                    <h4 class="text-sm font-medium text-purple-900 mb-3">Progression Actuelle</h4>
                                    <div class="grid grid-cols-2 gap-4 text-sm mb-4">
                                        <div>
                                            <p class="text-purple-700">Total payé</p>
                                            <p class="font-bold text-purple-900">{{ number_format($account->tontineAccount->total_paid, 0, ',', ' ') }} FCFA</p>
                                        </div>
                                        <div>
                                            <p class="text-purple-700">Total attendu</p>
                                            <p class="font-bold text-purple-900">{{ number_format($account->tontineAccount->total_expected, 0, ',', ' ') }} FCFA</p>
                                        </div>
                                    </div>
                                    @php
                                        $progress = $account->tontineAccount->total_expected > 0
                                            ? ($account->tontineAccount->total_paid / $account->tontineAccount->total_expected * 100)
                                            : 0;
                                    @endphp
                                    <div class="w-full bg-purple-200 rounded-full h-2">
                                        <div class="bg-purple-600 h-2 rounded-full transition-all duration-300" style="width: {{ $progress }}%"></div>
                                    </div>
                                    <p class="text-xs text-purple-700 mt-2 text-right">{{ number_format($progress, 1) }}% complété</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Boutons d'action -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <a href="{{ route('admin.accounts.show', $account->id) }}"
                               class="inline-flex items-center px-6 py-3 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Annuler
                            </a>

                            <button type="submit"
                                    class="inline-flex items-center px-6 py-3 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Enregistrer les modifications
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion de l'état visuel des radio buttons
    const radioLabels = document.querySelectorAll('input[type="radio"][name="payment_frequency"]');

    radioLabels.forEach(radio => {
        const label = radio.closest('label');
        const checkIcon = label.querySelector('svg');

        // État initial
        updateRadioState(radio, label, checkIcon);

        // Écouter les changements
        radio.addEventListener('change', function() {
            // Réinitialiser tous les labels
            radioLabels.forEach(r => {
                const l = r.closest('label');
                const c = l.querySelector('svg');
                l.classList.remove('border-purple-500', 'ring-2', 'ring-purple-500', 'bg-purple-50');
                l.classList.add('border-gray-300');
                c.classList.add('hidden');
            });

            // Activer le label sélectionné
            updateRadioState(this, label, checkIcon);
        });
    });

    function updateRadioState(radio, label, checkIcon) {
        if (radio.checked) {
            label.classList.remove('border-gray-300');
            label.classList.add('border-purple-500', 'ring-2', 'ring-purple-500', 'bg-purple-50');
            checkIcon.classList.remove('hidden');
        }
    }

    // Calculer automatiquement le paiement attendu pour les tontines
    const tontineAmount = document.getElementById('tontine_amount');
    const cycleDuration = document.getElementById('cycle_duration_months');
    const paymentFrequency = document.querySelectorAll('input[name="payment_frequency"]');

    function calculateExpectedPayment() {
        if (!tontineAmount || !cycleDuration) return;

        const amount = parseFloat(tontineAmount.value) || 0;
        const months = parseInt(cycleDuration.value) || 1;
        let frequency = 'monthly';

        paymentFrequency.forEach(radio => {
            if (radio.checked) frequency = radio.value;
        });

        let totalPayments = months;
        switch(frequency) {
            case 'daily':
                totalPayments = months * 30;
                break;
            case 'weekly':
                totalPayments = months * 4;
                break;
            case 'monthly':
                totalPayments = months;
                break;
        }

        const expectedPayment = amount / totalPayments;

        // Afficher le résultat (vous pouvez ajouter un élément pour afficher)
        console.log('Paiement attendu:', expectedPayment.toFixed(2), 'FCFA');
    }

    if (tontineAmount) {
        tontineAmount.addEventListener('input', calculateExpectedPayment);
    }
    if (cycleDuration) {
        cycleDuration.addEventListener('change', calculateExpectedPayment);
    }
    paymentFrequency.forEach(radio => {
        radio.addEventListener('change', calculateExpectedPayment);
    });
});
</script>
@endpush

<style>
/* Animation pour les inputs au focus */
input:focus, select:focus {
    transition: all 0.3s ease;
}

/* Style pour les radio buttons personnalisés */
input[type="radio"]:checked + span {
    border-color: rgb(147, 51, 234);
}

/* Animation de la barre de progression */
@keyframes progressAnimation {
    from {
        width: 0;
    }
}

.bg-purple-600 {
    animation: progressAnimation 1s ease-out;
}

/* Hover effect sur les cartes */
.group:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}
</style>
@endsection
