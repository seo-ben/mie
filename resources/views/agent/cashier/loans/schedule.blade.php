@extends('layouts.cashier')

@section('title', 'Échéancier de Remboursement - ' . $loan->loan_number)
@section('page-title', 'Échéancier')
@section('page-subtitle', 'Détails du prêt ' . $loan->loan_number)

@push('styles')
<style>
.bank-card {
    background: #1a2332;
    border: 1px solid rgba(0, 209, 178, 0.15);
    border-radius: 16px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
}
.table-dark-custom {
    color: white;
}
.table-dark-custom th {
    background: rgba(0, 209, 178, 0.1);
    color: #00d1b2;
    border-bottom: 1px solid rgba(0, 209, 178, 0.2);
    font-size: 0.8rem;
    text-transform: uppercase;
}
.table-dark-custom td {
    border-bottom: 1px solid rgba(225, 232, 237, 0.05);
    vertical-align: middle;
}
.stat-card {
    background: rgba(0, 209, 178, 0.05);
    border: 1px solid rgba(0, 209, 178, 0.1);
    border-radius: 12px;
    padding: 15px;
    height: 100%;
}
</style>
@endpush

@section('content')
<div class="row align-items-center mb-4">
    <div class="col">
        <a href="{{ route('caissier.depot') }}" class="btn btn-outline-light btn-sm rounded-pill px-3">
            <i class="fas fa-arrow-left me-1"></i> Retour aux encaissements
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="bank-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="text-white mb-0">Remboursements <span style="color: #00d1b2;">{{ $loan->loan_number }}</span></h4>
                <a href="{{ route('caissier.depot') }}" class="btn btn-sm" style="background: #00d1b2; color: #0f1923; font-weight: 600;">
                    <i class="fas fa-coins me-1"></i> Faire un versement
                </a>
            </div>
            
            <div class="row mb-5 text-white g-3">
                <div class="col-md-3">
                    <div class="stat-card">
                        <small class=" d-block text-white text-uppercase mb-1">Client Emprunteur</small>
                        <strong class="fs-6 d-block text-truncate">{{ $loan->client->full_name }}</strong>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <small class=" d-block text-uppercase text-white mb-1">Capital Engagé</small>
                        <strong class="fs-5 text-white">{{ number_format($loan->approved_amount, 0, ',', ' ') }} <small class="fs-6 ">FCFA</small></strong>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card" style="background: rgba(255, 193, 7, 0.05); border-color: rgba(255, 193, 7, 0.2);">
                        <small class=" d-block text-white text-uppercase mb-1">Reste à Payer (Total)</small>
                        <strong class="fs-5 text-warning">{{ number_format($loan->remaining_amount, 0, ',', ' ') }} <small class="fs-6 ">FCFA</small></strong>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card d-flex flex-column justify-content-center">
                        <small class=" d-block text-white text-uppercase mb-1">Progression</small>
                        @php
                            $totalPaid = $loan->payments->sum('paid_amount');
                            $totalDue = $loan->payments->sum('expected_amount');
                            $progress = $totalDue > 0 ? ($totalPaid / $totalDue) * 100 : 0;
                        @endphp
                        <div class="progress mt-2" style="height: 10px; background: rgba(255,255,255,0.1);">
                            <div class="progress-bar" role="progressbar" style="width: {{ $progress }}%; background: #00d1b2;" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="text-end mt-1"><small style="color: #00d1b2;">{{ round($progress, 1) }}%</small></div>
                    </div>
                </div>
            </div>

            <h6 class="text-uppercase  mb-3">Tableau d'amortissement</h6>
            <div class="table-responsive">
                <table class="table table-borderless table-dark-custom mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 50px;">N°</th>
                            <th>Échéance</th>
                            <th class="text-end">Attendu</th>
                            <th class="text-end">Principal</th>
                            <th class="text-end">Payé</th>
                            <th class="text-end">Pénalités</th>
                            <th class="text-center">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="text-white">
                        @forelse($loan->payments->sortBy('payment_number') as $payment)
                        <tr class="{{ $payment->status === 'overdue' ? 'bg-danger bg-opacity-10' : '' }}">
                            <td class="text-center">
                                <span class="badge bg-secondary rounded-circle" style="width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center;">{{ $payment->payment_number }}</span>
                            </td>
                            <td>
                                {{ $payment->due_date->format('d/m/Y') }}
                                @if($payment->status === 'pending' && $payment->due_date->isPast())
                                    <br><small class="text-danger"><i class="fas fa-exclamation-circle text-xs"></i> Retard</small>
                                @endif
                            </td>
                            <td class="text-end fw-bold">{{ number_format($payment->expected_amount, 0, ',', ' ') }}</td>
                            <td class="text-end  small">{{ number_format($payment->principal_amount, 0, ',', ' ') }}</td>
                            <td class="text-end">
                                @if($payment->paid_amount > 0)
                                    <span class="text-success fw-bold">{{ number_format($payment->paid_amount, 0, ',', ' ') }}</span>
                                @else
                                    <span class="">-</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($payment->penalty_amount > 0)
                                    <span class="text-danger fw-bold">+{{ number_format($payment->penalty_amount, 0, ',', ' ') }}</span>
                                @else
                                    <span class="">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @php
                                    $badge = 'secondary';
                                    $label = 'Attente';
                                    if($payment->status === 'paid') { $badge = 'success'; $label = 'Soldé'; }
                                    if($payment->status === 'overdue') { $badge = 'danger'; $label = 'En retard'; }
                                    if($payment->status === 'partial') { $badge = 'warning text-dark'; $label = 'Partiel'; }
                                @endphp
                                <span class="badge bg-{{ $badge }} px-2 py-1">{{ $label }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 ">Auncun échéancier généré.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>
@endsection
