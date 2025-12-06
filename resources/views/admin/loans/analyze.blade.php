@extends('layouts.app_admin')

@section('title', 'Analyse de Prêt')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- En-tête -->
    <div class="flex items-center mb-6">
        <a href="{{ route('admin.loans.show', $loan->id) }}" class="text-gray-600 hover:text-gray-900 mr-4">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Analyse de Prêt</h1>
            <p class="text-gray-600">{{ $loan->loan_number }} - {{ $loan->client->first_name }} {{ $loan->client->last_name }}</p>
        </div>
    </div>

    <!-- Score d'éligibilité et recommandation -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Score -->
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-32 h-32 rounded-full border-8 mb-4
                            {{ $loan->eligibility_score >= 80 ? 'border-green-500' :
                               ($loan->eligibility_score >= 60 ? 'border-yellow-500' :
                               ($loan->eligibility_score >= 40 ? 'border-orange-500' : 'border-red-500')) }}">
                    <div>
                        <div class="text-4xl font-bold text-gray-900">{{ round($loan->eligibility_score, 1) }}</div>
                        <div class="text-sm text-gray-600">/ 100</div>
                    </div>
                </div>
                <p class="font-semibold text-gray-900">Score d'Éligibilité</p>
            </div>

            <!-- Niveau de risque -->
            <div class="text-center">
                @php
                    $riskConfig = [
                        'low' => ['color' => 'green', 'icon' => 'fa-check-circle', 'label' => 'RISQUE FAIBLE', 'desc' => 'Client fiable'],
                        'medium' => ['color' => 'yellow', 'icon' => 'fa-exclamation-circle', 'label' => 'RISQUE MOYEN', 'desc' => 'Vérification recommandée'],
                        'high' => ['color' => 'orange', 'icon' => 'fa-exclamation-triangle', 'label' => 'RISQUE ÉLEVÉ', 'desc' => 'Garanties nécessaires'],
                        'very_high' => ['color' => 'red', 'icon' => 'fa-times-circle', 'label' => 'RISQUE TRÈS ÉLEVÉ', 'desc' => 'Rejet recommandé'],
                    ];
                    $risk = $riskConfig[$loan->risk_level] ?? $riskConfig['high'];
                @endphp

                <div class="mb-4">
                    <i class="fas {{ $risk['icon'] }} text-6xl text-{{ $risk['color'] }}-500"></i>
                </div>
                <p class="font-bold text-{{ $risk['color'] }}-600 text-lg">{{ $risk['label'] }}</p>
                <p class="text-sm text-gray-600">{{ $risk['desc'] }}</p>
            </div>

            <!-- Recommandation -->
            <div class="text-center">
                @php
                    $recommendation = $analysis['recommendation'];
                    $recConfig = [
                        'approve' => ['color' => 'green', 'icon' => 'fa-thumbs-up', 'action' => 'APPROUVER'],
                        'review' => ['color' => 'yellow', 'icon' => 'fa-search', 'action' => 'EXAMINER'],
                        'caution' => ['color' => 'orange', 'icon' => 'fa-exclamation', 'action' => 'PRUDENCE'],
                        'reject' => ['color' => 'red', 'icon' => 'fa-thumbs-down', 'action' => 'REJETER'],
                    ];
                    $rec = $recConfig[$recommendation['status']] ?? $recConfig['review'];
                @endphp

                <div class="mb-4">
                    <i class="fas {{ $rec['icon'] }} text-6xl text-{{ $rec['color'] }}-500"></i>
                </div>
                <p class="font-bold text-{{ $rec['color'] }}-600 text-lg">{{ $rec['action'] }}</p>
                <p class="text-sm text-gray-600 mt-2">{{ $recommendation['message'] }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Profil du client -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-user-circle text-blue-500 mr-2"></i>
                Profil du Client
            </h3>

            <div class="space-y-4">
                <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                    <span class="text-gray-600">Ancienneté</span>
                    <div class="text-right">
                        <span class="font-semibold text-gray-900">{{ $analysis['client_profile']['months_as_client'] }} mois</span>
                        <p class="text-xs text-gray-500">Depuis {{ $analysis['client_profile']['registration_date']->format('d/m/Y') }}</p>
                    </div>
                </div>

                <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                    <span class="text-gray-600">Statut KYC</span>
                    <span class="px-3 py-1 rounded-full text-sm font-medium
                        {{ $analysis['client_profile']['kyc_status'] === 'approved'
                           ? 'bg-green-100 text-green-800'
                           : 'bg-yellow-100 text-yellow-800' }}">
                        {{ $analysis['client_profile']['kyc_status'] }}
                    </span>
                </div>

                <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                    <span class="text-gray-600">Activité (6 mois)</span>
                    <span class="font-semibold text-gray-900">{{ $analysis['transaction_activity']['last_6_months'] }} transactions</span>
                </div>
            </div>

            <!-- Évaluation visuelle -->
            <div class="mt-6 grid grid-cols-3 gap-3">
                <div class="text-center p-3 rounded-lg {{ $analysis['client_profile']['months_as_client'] >= 6 ? 'bg-green-50' : 'bg-red-50' }}">
                    <i class="fas fa-clock text-2xl {{ $analysis['client_profile']['months_as_client'] >= 6 ? 'text-green-600' : 'text-red-600' }}"></i>
                    <p class="text-xs mt-1 font-medium">Ancienneté</p>
                </div>
                <div class="text-center p-3 rounded-lg {{ $analysis['client_profile']['kyc_status'] === 'approved' ? 'bg-green-50' : 'bg-red-50' }}">
                    <i class="fas fa-id-card text-2xl {{ $analysis['client_profile']['kyc_status'] === 'approved' ? 'text-green-600' : 'text-red-600' }}"></i>
                    <p class="text-xs mt-1 font-medium">Documents</p>
                </div>
                <div class="text-center p-3 rounded-lg {{ $analysis['transaction_activity']['last_6_months'] >= 10 ? 'bg-green-50' : 'bg-red-50' }}">
                    <i class="fas fa-chart-line text-2xl {{ $analysis['transaction_activity']['last_6_months'] >= 10 ? 'text-green-600' : 'text-red-600' }}"></i>
                    <p class="text-xs mt-1 font-medium">Activité</p>
                </div>
            </div>
        </div>

        <!-- Analyse financière -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-wallet text-green-500 mr-2"></i>
                Capacité Financière
            </h3>

            <div class="space-y-4">
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600">Épargne disponible</span>
                        <span class="font-bold text-green-600">
                            {{ number_format($analysis['savings_analysis']['savings_balance'], 0, ',', ' ') }} FCFA
                        </span>
                    </div>

                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600">Montant demandé</span>
                        <span class="font-bold text-gray-900">
                            {{ number_format($loan->requested_amount, 0, ',', ' ') }} FCFA
                        </span>
                    </div>

                    <div class="w-full bg-gray-200 rounded-full h-3 mt-2">
                        <div class="bg-green-600 h-3 rounded-full"
                             style="width: {{ min($analysis['savings_analysis']['coverage_ratio'], 100) }}%"></div>
                    </div>
                    <p class="text-sm text-gray-600 mt-1">
                        Couverture: {{ $analysis['savings_analysis']['coverage_ratio'] }}%
                    </p>
                </div>

                <div class="pt-4 border-t border-gray-200">
                    <p class="text-sm text-gray-600 mb-2">Solde total des comptes</p>
                    <p class="text-2xl font-bold text-gray-900">
                        {{ number_format($analysis['savings_analysis']['total_balance'], 0, ',', ' ') }} FCFA
                    </p>
                </div>

                <!-- Indicateurs visuels -->
                <div class="grid grid-cols-2 gap-3 pt-4">
                    <div class="p-3 rounded-lg {{ $analysis['savings_analysis']['coverage_ratio'] >= 50 ? 'bg-green-50' : 'bg-red-50' }}">
                        <p class="text-xs text-gray-600">Ratio de couverture</p>
                        <p class="text-lg font-bold {{ $analysis['savings_analysis']['coverage_ratio'] >= 50 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $analysis['savings_analysis']['coverage_ratio'] >= 50 ? 'SUFFISANT' : 'INSUFFISANT' }}
                        </p>
                    </div>
                    <div class="p-3 rounded-lg {{ $analysis['savings_analysis']['total_balance'] >= 100000 ? 'bg-green-50' : 'bg-yellow-50' }}">
                        <p class="text-xs text-gray-600">Capacité d'épargne</p>
                        <p class="text-lg font-bold {{ $analysis['savings_analysis']['total_balance'] >= 100000 ? 'text-green-600' : 'text-yellow-600' }}">
                            {{ $analysis['savings_analysis']['total_balance'] >= 100000 ? 'BONNE' : 'MOYENNE' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Historique de prêts -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-history text-purple-500 mr-2"></i>
                Historique de Prêts
            </h3>

            <div class="grid grid-cols-3 gap-4 mb-4">
                <div class="text-center p-4 bg-blue-50 rounded-lg">
                    <p class="text-3xl font-bold text-blue-600">{{ $analysis['loan_history']['total_loans'] }}</p>
                    <p class="text-xs text-gray-600 mt-1">Total</p>
                </div>
                <div class="text-center p-4 bg-green-50 rounded-lg">
                    <p class="text-3xl font-bold text-green-600">{{ $analysis['loan_history']['completed_loans'] }}</p>
                    <p class="text-xs text-gray-600 mt-1">Soldés</p>
                </div>
                <div class="text-center p-4 bg-red-50 rounded-lg">
                    <p class="text-3xl font-bold text-red-600">{{ $analysis['loan_history']['defaulted_loans'] }}</p>
                    <p class="text-xs text-gray-600 mt-1">Défauts</p>
                </div>
            </div>

            @if($analysis['loan_history']['completed_loans'] > 0)
            <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                <p class="flex items-center text-green-800">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span class="font-semibold">Bon historique de remboursement</span>
                </p>
                <p class="text-sm text-green-700 mt-1">
                    Le client a remboursé {{ $analysis['loan_history']['completed_loans'] }} prêt(s) sans incident.
                </p>
            </div>
            @elseif($analysis['loan_history']['active_loans'] > 0)
            <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <p class="flex items-center text-yellow-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    <span class="font-semibold">Prêt(s) en cours</span>
                </p>
                <p class="text-sm text-yellow-700 mt-1">
                    Le client a actuellement {{ $analysis['loan_history']['active_loans'] }} prêt(s) actif(s).
                </p>
            </div>
            @else
            <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="flex items-center text-blue-800">
                    <i class="fas fa-star mr-2"></i>
                    <span class="font-semibold">Premier prêt</span>
                </p>
                <p class="text-sm text-blue-700 mt-1">
                    C'est la première demande de prêt du client. Évaluation basée sur l'épargne.
                </p>
            </div>
            @endif
        </div>

        <!-- Détails de la demande -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-file-alt text-orange-500 mr-2"></i>
                Détails de la Demande
            </h3>

            <div class="space-y-3">
                <div>
                    <p class="text-sm text-gray-600">Objectif</p>
                    <p class="font-medium text-gray-900">{{ $loan->purpose }}</p>
                </div>

                @if($loan->collateral_description)
                <div>
                    <p class="text-sm text-gray-600">Garanties</p>
                    <p class="font-medium text-gray-900">{{ $loan->collateral_description }}</p>
                </div>
                @endif

                @if($loan->guarantor_name)
                <div class="p-3 bg-gray-50 rounded-lg">
                    <p class="text-sm font-semibold text-gray-900 mb-2">Garant</p>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <span class="text-gray-600">Nom:</span>
                            <span class="font-medium text-gray-900">{{ $loan->guarantor_name }}</span>
                        </div>
                        @if($loan->guarantor_phone)
                        <div>
                            <span class="text-gray-600">Tél:</span>
                            <span class="font-medium text-gray-900">{{ $loan->guarantor_phone }}</span>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                <div class="pt-3 border-t border-gray-200">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Taux d'intérêt proposé</span>
                        <span class="font-bold text-gray-900">{{ $loan->interest_rate }}% / an</span>
                    </div>
                    <div class="flex justify-between mt-2">
                        <span class="text-gray-600">Durée demandée</span>
                        <span class="font-bold text-gray-900">{{ $loan->duration_months }} mois</span>
                    </div>
                    @if($loan->monthly_payment)
                    <div class="flex justify-between mt-2">
                        <span class="text-gray-600">Paiement mensuel estimé</span>
                        <span class="font-bold text-blue-600">
                            {{ number_format($loan->monthly_payment, 0, ',', ' ') }} FCFA
                        </span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="mt-6 bg-white rounded-lg shadow-sm p-6">
        <div class="flex justify-between items-center">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Décision</h3>
                <p class="text-sm text-gray-600">Basée sur l'analyse ci-dessus, prenez une décision</p>
            </div>

            <div class="flex space-x-3">
                <a href="{{ route('admin.loans.show', $loan->id) }}"
                   class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    Retour
                </a>

                @can('approve-loans')
                <button onclick="openRejectionModal()"
                        class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-times mr-2"></i>Rejeter
                </button>
                <button onclick="openApprovalModal()"
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-check mr-2"></i>Approuver
                </button>
                @endcan
            </div>
        </div>
    </div>
</div>

<script>
function openApprovalModal() {
    // Rediriger vers la page d'approbation ou ouvrir un modal
    alert('Modal d\'approbation - à implémenter avec formulaire détaillé');
}

function openRejectionModal() {
    // Rediriger vers la page de rejet ou ouvrir un modal
    alert('Modal de rejet - à implémenter avec formulaire de motifs');
}
</script>
@endsection
