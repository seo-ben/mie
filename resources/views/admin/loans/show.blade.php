@extends('layouts.app_admin')

@section('title', 'Détails du Prêt')

@section('content')
<div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
    <!-- En-tête -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center">
            <a href="{{ route('admin.loans.index') }}" class="mr-4 text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">{{ $loan->loan_number }}</h1>
                <p class="text-gray-600">Demande de prêt</p>
            </div>
        </div>

        <div class="flex space-x-3">
            @if(in_array($loan->status, ['disbursed', 'active']))
            <a href="{{ route('admin.loans.schedule', $loan->id) }}"
               class="px-4 py-2 text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700">
                <i class="mr-2 fas fa-calendar-alt"></i>Échéancier
            </a>
            @endif
        </div>
    </div>

    <!-- Statut et actions -->
    <div class="p-6 mb-6 bg-white rounded-lg shadow-sm">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                @php
                    $statusConfig = [
                        'pending' => [
                            'bg' => 'bg-yellow-100',
                            'text' => 'text-yellow-800',
                            'label' => 'En Attente de Validation'
                        ],
                        'approved' => [
                            'bg' => 'bg-green-100',
                            'text' => 'text-green-800',
                            'label' => 'Approuvé - En Attente de Décaissement'
                        ],
                        'disbursed' => [
                            'bg' => 'bg-blue-100',
                            'text' => 'text-blue-800',
                            'label' => 'Décaissé - En Cours'
                        ],
                        'active' => [
                            'bg' => 'bg-blue-100',
                            'text' => 'text-blue-800',
                            'label' => 'Actif - En Remboursement'
                        ],
                        'completed' => [
                            'bg' => 'bg-gray-100',
                            'text' => 'text-gray-800',
                            'label' => 'Soldé'
                        ],
                        'rejected' => [
                            'bg' => 'bg-red-100',
                            'text' => 'text-red-800',
                            'label' => 'Rejeté'
                        ],
                    ];

                    $currentStatus = $loan->status;
                    $status = $statusConfig[$currentStatus] ?? [
                        'bg' => 'bg-gray-100',
                        'text' => 'text-gray-800',
                        'label' => ucfirst($currentStatus)
                    ];
                @endphp

                <span class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-lg {{ $status['bg'] }} {{ $status['text'] }}" data-status="{{ $currentStatus }}" data-critical="true">
                    <i class="mr-2 text-xs fas fa-circle"></i>
                    {{ $status['label'] }}
                </span>

                @php
                    $riskConfig = [
                        'low' => [
                            'bg' => 'bg-green-100',
                            'text' => 'text-green-800',
                            'icon' => 'fa-check-circle',
                            'label' => 'Risque Faible'
                        ],
                        'medium' => [
                            'bg' => 'bg-yellow-100',
                            'text' => 'text-yellow-800',
                            'icon' => 'fa-exclamation-circle',
                            'label' => 'Risque Moyen'
                        ],
                        'high' => [
                            'bg' => 'bg-orange-100',
                            'text' => 'text-orange-800',
                            'icon' => 'fa-exclamation-triangle',
                            'label' => 'Risque Élevé'
                        ],
                        'very_high' => [
                            'bg' => 'bg-red-100',
                            'text' => 'text-red-800',
                            'icon' => 'fa-times-circle',
                            'label' => 'Risque Très Élevé'
                        ],
                    ];

                    $currentRisk = $loan->risk_level ?? 'medium';
                    $risk = $riskConfig[$currentRisk] ?? [
                        'bg' => 'bg-gray-100',
                        'text' => 'text-gray-800',
                        'icon' => 'fa-question',
                        'label' => ucfirst($currentRisk)
                    ];
                @endphp

                <span class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-lg {{ $risk['bg'] }} {{ $risk['text'] }}" data-risk="{{ $currentRisk }}" data-critical="true">
                    <i class="mr-2 fas {{ $risk['icon'] }}"></i>
                    {{ $risk['label'] }}
                </span>

                @if($loan->eligibility_score)
                <div class="text-center">
                    <p class="text-xs text-gray-600">Score</p>
                    <p class="text-lg font-bold text-gray-900">{{ round($loan->eligibility_score, 1) }}%</p>
                </div>
                @endif
            </div>

            <div class="flex space-x-2">
                @if($loan->status === 'pending' && auth()->user()->hasRole(['administrateur_systeme', 'manager_agence']))
                <button onclick="openApprovalModal()"
                        class="px-4 py-2 text-white transition-colors bg-green-600 rounded-lg hover:bg-green-700">
                    <i class="mr-2 fas fa-check"></i>Approuver
                </button>
                <button onclick="openRejectionModal()"
                        class="px-4 py-2 text-white transition-colors bg-red-600 rounded-lg hover:bg-red-700">
                    <i class="mr-2 fas fa-times"></i>Rejeter
                </button>
                @endif

                @if($loan->status === 'approved' && auth()->user()->hasRole(['administrateur_systeme', 'manager_agence']))
                <button onclick="openDisbursementModal()"
                        class="px-4 py-2 text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700">
                    <i class="mr-2 fas fa-money-bill-wave"></i>Décaisser
                </button>
                @endif
            </div>
        </div>
    </div>

    @if(in_array($loan->status, ['pending', 'approved']))
    <!-- Section Audit Intelligent -->
    <div class="p-6 mb-6 overflow-hidden relative border-2 border-slate-200 bg-white rounded-2xl shadow-xl">
        <div class="absolute top-0 right-0 p-8 opacity-[0.03] scale-150 rotate-12">
            <i class="fas fa-brain-circuit text-9xl text-slate-900"></i>
        </div>
        
        <div class="flex items-center justify-between mb-8 relative border-b border-slate-100 pb-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-slate-900 text-white flex items-center justify-center shadow-2xl shadow-slate-200">
                    <i class="fas fa-microchip text-xl animate-pulse"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest leading-none">Diagnostic Audit Cognitif</h3>
                    <p class="text-[10px] text-slate-400 font-bold uppercase mt-1">Évaluation Automatisée de Solvabilité</p>
                </div>
            </div>
            <div class="text-right">
                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-1">Score d'Éligibilité</span>
                <span class="text-3xl font-black {{ $loan->eligibility_score >= 80 ? 'text-emerald-600' : ($loan->eligibility_score >= 50 ? 'text-blue-600' : 'text-rose-600') }}">
                    {{ round($loan->eligibility_score, 1) }}%
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-10 relative">
            <!-- Colonne Gauche : Diagnostic Rapide -->
            <div class="space-y-6">
                <!-- Signaux Positifs -->
                @if(!empty($analysis['insights']['positive']))
                <div>
                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-tighter border-b border-slate-100 pb-2 mb-3 flex items-center gap-2">
                        <i class="fas fa-circle-check text-emerald-500"></i> Signaux de Confiance
                    </h4>
                    <div class="space-y-2">
                        @foreach($analysis['insights']['positive'] as $insight)
                        <div class="flex items-center gap-3">
                            <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                            <p class="text-[11px] font-bold text-slate-600 uppercase tracking-tight">{{ $insight }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Points de Vigilance -->
                <div>
                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-tighter border-b border-slate-100 pb-2 mb-3 flex items-center gap-2">
                        <i class="fas fa-triangle-exclamation text-rose-500"></i> Points de Vigilance
                    </h4>
                    <div class="space-y-2">
                        @if(!empty($analysis['insights']['warning']))
                            @foreach($analysis['insights']['warning'] as $insight)
                            <div class="flex items-center gap-3">
                                <div class="w-1.5 h-1.5 rounded-full bg-rose-500"></div>
                                <p class="text-[11px] font-bold text-slate-600 uppercase tracking-tight">{{ $insight }}</p>
                            </div>
                            @endforeach
                        @else
                            <p class="text-[10px] font-medium text-slate-400 italic">Aucune anomalie critique détectée par le moteur d'analyse.</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Colonne Droite : États des Comptes Financial Insights -->
            <div class="space-y-6">
               <div>
                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-tighter border-b border-slate-100 pb-2 mb-3 flex items-center gap-2">
                        <i class="fas fa-vault text-blue-500"></i> Actifs & Comptes Adhérent
                    </h4>
                    <div class="grid grid-cols-1 gap-3">
                        @foreach($loan->client->accounts as $account)
                        <div class="flex justify-between items-center bg-slate-50 p-3 rounded-xl border border-slate-100">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg {{ $account->account_type === 'savings' ? 'bg-blue-100 text-blue-600' : 'bg-emerald-100 text-emerald-600' }} flex items-center justify-center text-xs">
                                    <i class="fas {{ $account->account_type === 'savings' ? 'fa-wallet' : 'fa-piggy-bank' }}"></i>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-slate-700 uppercase leading-none">{{ $account->account_type === 'savings' ? 'Compte Épargne' : 'Compte Tontine' }}</p>
                                    <p class="text-[9px] text-slate-400 font-mono mt-1">{{ $account->account_number }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-black text-slate-900 font-mono">{{ number_format($account->balance, 0, ',', ' ') }}</span>
                                <span class="text-[8px] font-black text-slate-400 uppercase ml-1">XOF</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    
                    <div class="mt-4 p-3 bg-blue-50 border border-blue-100 rounded-xl flex justify-between items-center">
                        <span class="text-[10px] font-black text-blue-700 uppercase">Total Liquidités</span>
                        <span class="text-sm font-black text-blue-900 font-mono">{{ number_format($loan->client->accounts->sum('balance'), 0, ',', ' ') }} <span class="text-[10px]">XOF</span></span>
                    </div>
               </div>
            </div>
        </div>

        <!-- Recommandation Algorithmique -->
        <div class="mt-8 pt-6 border-t border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                    <i class="fas fa-robot text-sm"></i>
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Recommandation du Système</p>
                    <p class="text-xs font-black {{ $analysis['recommendation']['status'] === 'approve' ? 'text-emerald-600' : ($analysis['recommendation']['status'] === 'reject' ? 'text-rose-600' : 'text-yellow-600') }} uppercase">
                        {{ $analysis['recommendation']['message'] }}
                    </p>
                </div>
            </div>
            
            <div class="flex gap-2">
                @if($loan->status === 'pending')
                    <span class="px-4 py-1.5 rounded-full bg-slate-900 text-white text-[9px] font-black uppercase tracking-widest shadow-xl shadow-slate-200">Validation Humaine Requise</span>
                @else
                    <span class="px-4 py-1.5 rounded-full bg-emerald-100 text-emerald-700 text-[9px] font-black uppercase tracking-widest">Diagnostic Archivé</span>
                @endif
            </div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 gap-6 mb-6 lg:grid-cols-3">
        <!-- Informations principales -->
        <div class="lg:col-span-2">
            <!-- Informations du prêt -->
            <div class="p-6 mb-6 bg-white rounded-lg shadow-sm">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">Informations du Prêt</h3>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Montant Demandé</p>
                        <p class="text-lg font-semibold text-gray-900">{{ number_format($loan->requested_amount, 0, ',', ' ') }} FCFA</p>
                    </div>

                    @if($loan->approved_amount)
                    <div>
                        <p class="text-sm text-gray-600">Montant Approuvé</p>
                        <p class="text-lg font-semibold text-green-600">{{ number_format($loan->approved_amount, 0, ',', ' ') }} FCFA</p>
                    </div>
                    @endif

                    <div>
                        <p class="text-sm text-gray-600">Taux d'Intérêt</p>
                        <p class="font-semibold text-gray-900">{{ $loan->interest_rate }}% / an</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-600">Durée</p>
                        <p class="font-semibold text-gray-900">{{ $loan->duration_months }} mois</p>
                    </div>

                    @if($loan->monthly_payment)
                    <div>
                        <p class="text-sm text-gray-600">Paiement Mensuel</p>
                        <p class="font-semibold text-blue-600">{{ number_format($loan->monthly_payment, 0, ',', ' ') }} FCFA</p>
                    </div>
                    @endif

                    @if($loan->total_amount_due)
                    <div>
                        <p class="text-sm text-gray-600">Montant Total Dû</p>
                        <p class="font-semibold text-gray-900">{{ number_format($loan->total_amount_due, 0, ',', ' ') }} FCFA</p>
                    </div>
                    @endif

                    <div class="col-span-2">
                        <p class="text-sm text-gray-600">Objectif</p>
                        <p class="mt-1 text-gray-900">{{ $loan->purpose }}</p>
                    </div>

                    @if($loan->collateral_description)
                    <div class="col-span-2">
                        <p class="text-sm text-gray-600">Garanties</p>
                        <p class="mt-1 text-gray-900">{{ $loan->collateral_description }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Progression du remboursement -->
            @if(in_array($loan->status, ['disbursed', 'active', 'completed']))
            <div class="p-6 mb-6 bg-white rounded-lg shadow-sm">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">Progression du Remboursement</h3>

                @php
                    $totalDue = $loan->total_amount_due ?? 0;
                    $totalPaid = $loan->total_paid ?? 0;
                    $remaining = $totalDue - $totalPaid;
                    $progress = $totalDue > 0 ? ($totalPaid / $totalDue) * 100 : 0;
                @endphp

                <div class="mb-4">
                    <div class="flex justify-between mb-2 text-sm">
                        <span class="text-gray-600">Payé: {{ number_format($totalPaid, 0, ',', ' ') }} FCFA</span>
                        <span class="text-gray-600">Restant: {{ number_format($remaining, 0, ',', ' ') }} FCFA</span>
                    </div>
                    <div class="w-full h-4 bg-gray-200 rounded-full">
                        <div class="h-4 transition-all duration-300 bg-green-600 rounded-full" style="width: {{ min($progress, 100) }}%"></div>
                    </div>
                    <p class="mt-2 text-sm text-center text-gray-600">{{ round($progress, 1) }}% remboursé</p>
                </div>

                <div class="grid grid-cols-3 gap-4 mt-4">
                    <div class="p-3 text-center rounded-lg bg-green-50">
                        <p class="text-2xl font-bold text-green-600">{{ $stats['paid_payments'] ?? 0 }}</p>
                        <p class="text-xs text-gray-600">Payés</p>
                    </div>
                    <div class="p-3 text-center rounded-lg bg-yellow-50">
                        <p class="text-2xl font-bold text-yellow-600">{{ $stats['pending_payments'] ?? 0 }}</p>
                        <p class="text-xs text-gray-600">En Attente</p>
                    </div>
                    <div class="p-3 text-center rounded-lg bg-red-50">
                        <p class="text-2xl font-bold text-red-600">{{ $stats['overdue_payments'] ?? 0 }}</p>
                        <p class="text-xs text-gray-600">En Retard</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Prochains paiements -->
            @if($loan->payments && $loan->payments->where('status', 'pending')->count() > 0)
            <div class="p-6 bg-white rounded-lg shadow-sm">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">Prochains Paiements</h3>

                <div class="space-y-3">
                    @foreach($loan->payments->where('status', 'pending')->take(3) as $payment)
                    <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                        <div>
                            <p class="font-semibold text-gray-900">Échéance #{{ $payment->payment_number }}</p>
                            <p class="text-sm text-gray-600">{{ $payment->due_date->format('d/m/Y') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-gray-900">{{ number_format($payment->expected_amount, 0, ',', ' ') }} FCFA</p>
                            @if($payment->due_date->isPast())
                            <span class="text-xs text-red-600">En retard</span>
                            @endif
                        </div>
                        <button onclick="openPaymentModal({{ $payment->id }}, {{ $payment->expected_amount }})"
                                class="px-3 py-1 text-sm text-white bg-blue-600 rounded hover:bg-blue-700">
                            Payer
                        </button>
                    </div>
                    @endforeach
                </div>

                @if($loan->payments->where('status', 'pending')->count() > 3)
                <a href="{{ route('admin.loans.schedule', $loan->id) }}" class="block mt-4 text-center text-blue-600 hover:text-blue-800">
                    Voir tout l'échéancier
                </a>
                @endif
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Informations du client -->
            <div class="p-6 bg-white rounded-lg shadow-sm">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">Client</h3>

                <div class="mb-4 text-center">
                    @if($loan->client->profile_photo_url)
                    <img src="{{ asset('storage/' . $loan->client->profile_photo_url) }}"
                         class="object-cover w-20 h-20 mx-auto mb-3 rounded-full">
                    @else
                    <div class="flex items-center justify-center w-20 h-20 mx-auto mb-3 bg-blue-100 rounded-full">
                        <i class="text-3xl text-blue-600 fas fa-user"></i>
                    </div>
                    @endif

                    <h4 class="font-semibold text-gray-900">{{ $loan->client->first_name }} {{ $loan->client->last_name }}</h4>
                    <p class="text-sm text-gray-600">{{ $loan->client->client_number }}</p>
                </div>

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Téléphone:</span>
                        <span class="font-medium text-gray-900">{{ $loan->client->phone }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">KYC:</span>
                        @php
                            $kycStatus = $loan->client->kyc_status ?? 'pending';
                            $kycClass = $kycStatus === 'approved' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
                        @endphp
                        <span class="px-2 py-1 text-xs rounded {{ $kycClass }}">
                            {{ $kycStatus }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Client depuis:</span>
                        <span class="font-medium text-gray-900">{{ $loan->client->created_at->format('d/m/Y') }}</span>
                    </div>
                </div>

                <a href="{{ route('admin.clients.show', $loan->client->id) }}"
                   class="block px-4 py-2 mt-4 text-center text-blue-600 transition-colors border border-blue-600 rounded hover:bg-blue-50">
                    Voir le Profil
                </a>
            </div>

            <!-- Historique -->
            <div class="p-6 bg-white rounded-lg shadow-sm">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">Historique</h3>

                <div class="space-y-3 text-sm">
                    <div>
                        <p class="text-gray-600">Demande soumise</p>
                        <p class="font-medium text-gray-900">{{ $loan->application_date->format('d/m/Y H:i') }}</p>
                    </div>

                    @if($loan->approved_at)
                    <div>
                        <p class="text-gray-600">Approuvé le</p>
                        <p class="font-medium text-gray-900">{{ $loan->approved_at->format('d/m/Y H:i') }}</p>
                        @if($loan->approvedBy)
                        <p class="text-xs text-gray-500">par {{ $loan->approvedBy->full_name }}</p>
                        @endif
                    </div>
                    @endif

                    @if($loan->disbursed_at)
                    <div>
                        <p class="text-gray-600">Décaissé le</p>
                        <p class="font-medium text-gray-900">{{ $loan->disbursed_at->format('d/m/Y H:i') }}</p>
                        @if($loan->disbursedBy)
                        <p class="text-xs text-gray-500">par {{ $loan->disbursedBy->full_name }}</p>
                        @endif
                    </div>
                    @endif

                    <div>
                        <p class="text-gray-600">1er paiement</p>
                        <p class="font-medium text-gray-900">
                            {{ optional($loan->first_payment_date)->format('d/m/Y') ?? now()->format('d/m/Y') }}
                        </p>
                    </div>

                    @if($loan->maturity_date)
                    <div>
                        <p class="text-gray-600">Échéance finale</p>
                        <p class="font-medium text-gray-900">{{ $loan->maturity_date->format('d/m/Y') }}</p>
                    </div>
                    @endif
                </div>
            </div>

            @if($loan->guarantor_name)
            <!-- Informations du garant -->
            <div class="p-6 bg-white rounded-lg shadow-sm">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">Garant</h3>

                <div class="space-y-2 text-sm">
                    <div>
                        <p class="text-gray-600">Nom</p>
                        <p class="font-medium text-gray-900">{{ $loan->guarantor_name }}</p>
                    </div>
                    @if($loan->guarantor_phone)
                    <div>
                        <p class="text-gray-600">Téléphone</p>
                        <p class="font-medium text-gray-900">{{ $loan->guarantor_phone }}</p>
                    </div>
                    @endif
                    @if($loan->guarantor_relationship)
                    <div>
                        <p class="text-gray-600">Relation</p>
                        <p class="font-medium text-gray-900">{{ $loan->guarantor_relationship }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Approbation -->
<div id="approvalModal" class="fixed inset-0 z-50 hidden w-full h-full overflow-y-auto bg-gray-600 bg-opacity-50">
    <div class="relative w-full max-w-2xl p-5 mx-auto bg-white border rounded-lg shadow-lg top-20">
        <div class="flex items-center justify-between pb-3 border-b border-gray-200">
            <h3 class="text-xl font-semibold text-gray-900">Approuver le Prêt</h3>
            <button onclick="closeApprovalModal()" class="text-gray-400 hover:text-gray-600">
                <i class="text-xl fas fa-times"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.loans.approve', $loan->id) }}" class="mt-4">
            @csrf
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">
                            Montant Approuvé <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="approved_amount" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                               value="{{ $loan->requested_amount }}"
                               min="1000" max="5000000" >
                        <p class="mt-1 text-xs text-gray-500">Montant demandé: {{ number_format($loan->requested_amount, 0, ',', ' ') }} FCFA</p>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">
                            Taux d'Intérêt (%) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="interest_rate" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                               value="{{ $loan->interest_rate }}"
                               min="0" max="50" step="0.1">
                        <p class="mt-1 text-xs text-gray-500">Taux suggéré: {{ $loan->interest_rate }}%</p>
                    </div>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        Durée (mois) <span class="text-red-500">*</span>
                    </label>
                    <select name="duration_months" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        @foreach([3, 6, 12, 18, 24] as $months)
                        <option value="{{ $months }}" {{ $loan->duration_months == $months ? 'selected' : '' }}>
                            {{ $months }} mois
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        Notes d'Approbation
                    </label>
                    <textarea name="approval_notes" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                              placeholder="Commentaires ou conditions particulières..."></textarea>
                </div>

                <div class="p-4 border border-blue-200 rounded-lg bg-blue-50">
                    <p class="text-sm text-blue-800">
                        <i class="mr-2 fas fa-info-circle"></i>
                        <strong>Note:</strong> Après approbation, le prêt sera prêt pour le décaissement. L'échéancier de paiement sera généré automatiquement lors du décaissement.
                    </p>
                </div>
            </div>

            <div class="flex justify-end pt-4 mt-6 space-x-3 border-t border-gray-200">
                <button type="button" onclick="closeApprovalModal()"
                        class="px-6 py-2 text-gray-700 transition-colors border border-gray-300 rounded-lg hover:bg-gray-50">
                    Annuler
                </button>
                <button type="submit"
                        class="px-6 py-2 text-white transition-colors bg-green-600 rounded-lg hover:bg-green-700">
                    <i class="mr-2 fas fa-check"></i>Approuver le Prêt
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Rejet -->
<div id="rejectionModal" class="fixed inset-0 z-50 hidden w-full h-full overflow-y-auto bg-gray-600 bg-opacity-50">
    <div class="relative w-full max-w-xl p-5 mx-auto bg-white border rounded-lg shadow-lg top-20">
        <div class="flex items-center justify-between pb-3 border-b border-gray-200">
            <h3 class="text-xl font-semibold text-gray-900">Rejeter la Demande de Prêt</h3>
            <button onclick="closeRejectionModal()" class="text-gray-400 hover:text-gray-600">
                <i class="text-xl fas fa-times"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.loans.reject', $loan->id) }}" class="mt-4">
            @csrf
            <div class="space-y-4">
                <div class="p-4 border border-red-200 rounded-lg bg-red-50">
                    <p class="text-sm text-red-800">
                        <i class="mr-2 fas fa-exclamation-triangle"></i>
                        <strong>Attention:</strong> Cette action est définitive. Le client sera notifié du rejet de sa demande.
                    </p>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        Motif du Rejet <span class="text-red-500">*</span>
                    </label>
                    <select name="rejection_category" required
                            class="w-full px-4 py-2 mb-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        <option value="">Sélectionner un motif...</option>
                        <option value="insufficient_savings">Épargne insuffisante</option>
                        <option value="poor_credit_history">Historique de crédit défavorable</option>
                        <option value="incomplete_documents">Documents incomplets</option>
                        <option value="high_risk">Profil à risque élevé</option>
                        <option value="unstable_income">Revenus instables</option>
                        <option value="existing_loans">Trop de prêts en cours</option>
                        <option value="other">Autre</option>
                    </select>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        Détails du Rejet <span class="text-red-500">*</span>
                    </label>
                    <textarea name="rejection_reason" required rows="4"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                              placeholder="Expliquez en détail les raisons du rejet..."></textarea>
                    <p class="mt-1 text-xs text-gray-500">Ces informations seront communiquées au client</p>
                </div>

                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="notify_client" value="1" checked
                               class="text-red-600 border-gray-300 rounded focus:ring-red-500">
                        <span class="ml-2 text-sm text-gray-700">Notifier le client par SMS</span>
                    </label>
                </div>
            </div>

            <div class="flex justify-end pt-4 mt-6 space-x-3 border-t border-gray-200">
                <button type="button" onclick="closeRejectionModal()"
                        class="px-6 py-2 text-gray-700 transition-colors border border-gray-300 rounded-lg hover:bg-gray-50">
                    Annuler
                </button>
                <button type="submit"
                        class="px-6 py-2 text-white transition-colors bg-red-600 rounded-lg hover:bg-red-700">
                    <i class="mr-2 fas fa-times"></i>Confirmer le Rejet
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Décaissement -->
<div id="disbursementModal" class="fixed inset-0 z-50 hidden w-full h-full overflow-y-auto bg-gray-600 bg-opacity-50">
    <div class="relative w-full max-w-xl p-5 mx-auto bg-white border rounded-lg shadow-lg top-20">
        <div class="flex items-center justify-between pb-3 border-b border-gray-200">
            <h3 class="text-xl font-semibold text-gray-900">Décaisser le Prêt</h3>
            <button onclick="closeDisbursementModal()" class="text-gray-400 hover:text-gray-600">
                <i class="text-xl fas fa-times"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.loans.disburse', $loan->id) }}" class="mt-4">
            @csrf
            <div class="space-y-4">
                <div class="p-4 border border-blue-200 rounded-lg bg-blue-50">
                    <div class="flex justify-between mb-2">
                        <span class="text-sm font-medium text-blue-900">Montant à décaisser:</span>
                        <span class="text-lg font-bold text-blue-900">
                            {{ number_format($loan->approved_amount, 0, ',', ' ') }} FCFA
                        </span>
                    </div>
                    <p class="text-xs text-blue-700">
                        L'échéancier de {{ $loan->duration_months }} paiements sera généré automatiquement
                    </p>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        Méthode de Décaissement <span class="text-red-500">*</span>
                    </label>
                    <select name="disbursement_method" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Sélectionner...</option>
                        <option value="cash">Espèces</option>
                        <option value="bank_transfer">Virement bancaire</option>
                        <option value="mobile_money">Mobile Money</option>
                    </select>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        Référence de Transaction
                    </label>
                    <input type="text" name="disbursement_reference"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="N° de transaction, référence bancaire...">
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        Notes de Décaissement
                    </label>
                    <textarea name="disbursement_notes" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="Commentaires ou informations supplémentaires..."></textarea>
                </div>

                <div class="p-4 border border-yellow-200 rounded-lg bg-yellow-50">
                    <p class="text-sm text-yellow-800">
                        <i class="mr-2 fas fa-exclamation-circle"></i>
                        <strong>Important:</strong> Assurez-vous que le montant a bien été remis au client avant de valider. Cette action ne peut pas être annulée.
                    </p>
                </div>
            </div>

            <div class="flex justify-end pt-4 mt-6 space-x-3 border-t border-gray-200">
                <button type="button" onclick="closeDisbursementModal()"
                        class="px-6 py-2 text-gray-700 transition-colors border border-gray-300 rounded-lg hover:bg-gray-50">
                    Annuler
                </button>
                <button type="submit"
                        class="px-6 py-2 text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700">
                    <i class="mr-2 fas fa-money-bill-wave"></i>Confirmer le Décaissement
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Paiement -->
<div id="paymentModal" class="fixed inset-0 z-50 hidden w-full h-full overflow-y-auto bg-gray-600 bg-opacity-50">
    <div class="relative w-full max-w-xl p-5 mx-auto bg-white border rounded-lg shadow-lg top-20">
        <div class="flex items-center justify-between pb-3 border-b border-gray-200">
            <h3 class="text-xl font-semibold text-gray-900">Enregistrer un Paiement</h3>
            <button onclick="closePaymentModal()" class="text-gray-400 hover:text-gray-600">
                <i class="text-xl fas fa-times"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.loans.record-payment', $loan->id) }}" class="mt-4" id="paymentForm">
            @csrf
            <input type="hidden" name="payment_id" id="payment_id">

            <div class="space-y-4">
                <div class="p-4 border border-gray-200 rounded-lg bg-gray-50">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Montant attendu:</span>
                        <span class="text-lg font-bold text-gray-900" id="expected_amount_display">0 FCFA</span>
                    </div>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        Montant Payé <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="paid_amount" id="paid_amount" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Montant reçu"
                           min="0" step="100">
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        Méthode de Paiement <span class="text-red-500">*</span>
                    </label>
                    <select name="payment_method" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Sélectionner...</option>
                        <option value="cash">Espèces</option>
                        <option value="bank_transfer">Virement bancaire</option>
                        <option value="mobile_money">Mobile Money</option>
                    </select>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        Référence de Paiement
                    </label>
                    <input type="text" name="payment_reference"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="N° de transaction...">
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        Notes
                    </label>
                    <textarea name="payment_notes" rows="2"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="Commentaires optionnels..."></textarea>
                </div>
            </div>

            <div class="flex justify-end pt-4 mt-6 space-x-3 border-t border-gray-200">
                <button type="button" onclick="closePaymentModal()"
                        class="px-6 py-2 text-gray-700 transition-colors border border-gray-300 rounded-lg hover:bg-gray-50">
                    Annuler
                </button>
                <button type="submit"
                        class="px-6 py-2 text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700">
                    <i class="mr-2 fas fa-check"></i>Enregistrer le Paiement
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openApprovalModal() {
    const modal = document.getElementById('approvalModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.style.display = 'block';
    }
}

function closeApprovalModal() {
    const modal = document.getElementById('approvalModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }
}

function openRejectionModal() {
    const modal = document.getElementById('rejectionModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.style.display = 'block';
    }
}

function closeRejectionModal() {
    const modal = document.getElementById('rejectionModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }
}

function openDisbursementModal() {
    const modal = document.getElementById('disbursementModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.style.display = 'block';
    }
}

function closeDisbursementModal() {
    const modal = document.getElementById('disbursementModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }
}

function openPaymentModal(paymentId, expectedAmount) {
    const modal = document.getElementById('paymentModal');
    const paymentIdInput = document.getElementById('payment_id');
    const paidAmountInput = document.getElementById('paid_amount');
    const expectedAmountDisplay = document.getElementById('expected_amount_display');

    if (modal && paymentIdInput && paidAmountInput && expectedAmountDisplay) {
        paymentIdInput.value = paymentId;
        paidAmountInput.value = expectedAmount;
        expectedAmountDisplay.textContent = expectedAmount.toLocaleString('fr-FR') + ' FCFA';
        modal.classList.remove('hidden');
        modal.style.display = 'block';
    }
}

function closePaymentModal() {
    const modal = document.getElementById('paymentModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }
}
</script>
@endsection
