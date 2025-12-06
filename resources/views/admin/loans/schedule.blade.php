@extends('layouts.app_admin')

@section('title', 'Échéancier de Paiement')

@section('content')
<div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
    <!-- En-tête -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center">
            <a href="{{ route('admin.loans.show', $loan->id) }}" class="mr-4 text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Échéancier de Paiement</h1>
                <p class="text-gray-600">{{ $loan->loan_number }} - {{ $loan->client->first_name }} {{ $loan->client->last_name }}</p>
            </div>
        </div>

        <div class="flex space-x-3">
            <button onclick="window.print()" class="px-4 py-2 text-gray-700 transition-colors border border-gray-300 rounded-lg hover:bg-gray-50">
                <i class="mr-2 fas fa-print"></i>Imprimer
            </button>
            <a href="{{ route('admin.loans.show', $loan->id) }}" class="px-4 py-2 text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700">
                <i class="mr-2 fas fa-arrow-left"></i>Retour au Prêt
            </a>
        </div>
    </div>

    <!-- Résumé du prêt -->
    <div class="p-6 mb-6 bg-white rounded-lg shadow-sm">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
            <div>
                <p class="text-sm text-gray-600">Montant Prêt</p>
                <p class="text-xl font-bold text-gray-900">{{ number_format($loan->approved_amount, 0, ',', ' ') }} FCFA</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Taux d'Intérêt</p>
                <p class="text-xl font-bold text-gray-900">{{ $loan->interest_rate }}%</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Durée</p>
                <p class="text-xl font-bold text-gray-900">{{ $loan->duration_months }} mois</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Paiement Mensuel</p>
                <p class="text-xl font-bold text-blue-600">{{ number_format($loan->monthly_payment, 0, ',', ' ') }} FCFA</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Total à Rembourser</p>
                <p class="text-xl font-bold text-gray-900">{{ number_format($loan->total_amount_due, 0, ',', ' ') }} FCFA</p>
            </div>
        </div>
    </div>

    <!-- Statistiques de progression -->
    <div class="p-6 mb-6 bg-white rounded-lg shadow-sm">
        <h3 class="mb-4 text-lg font-semibold text-gray-900">Progression des Remboursements</h3>

        @php
            $totalPaid = $loan->payments->where('status', 'paid')->sum('paid_amount');
            $totalRemaining = $loan->total_amount_due - $totalPaid;
            $progressPercent = $loan->total_amount_due > 0 ? ($totalPaid / $loan->total_amount_due) * 100 : 0;

            $paidCount = $loan->payments->where('status', 'paid')->count();
            $pendingCount = $loan->payments->where('status', 'pending')->count();
            $overdueCount = $loan->payments->where('status', 'overdue')->count();
        @endphp

        <div class="mb-4">
            <div class="flex justify-between mb-2 text-sm">
                <span class="text-gray-600">Payé: {{ number_format($totalPaid, 0, ',', ' ') }} FCFA</span>
                <span class="text-gray-600">Restant: {{ number_format($totalRemaining, 0, ',', ' ') }} FCFA</span>
            </div>
            <div class="w-full h-4 bg-gray-200 rounded-full">
                <div class="h-4 transition-all duration-300 bg-green-600 rounded-full" style="width: {{ min($progressPercent, 100) }}%"></div>
            </div>
            <p class="mt-2 text-sm text-center text-gray-600">{{ round($progressPercent, 1) }}% remboursé</p>
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div class="p-4 text-center rounded-lg bg-green-50">
                <p class="text-3xl font-bold text-green-600">{{ $paidCount }}</p>
                <p class="text-sm text-gray-600">Paiements Effectués</p>
            </div>
            <div class="p-4 text-center rounded-lg bg-yellow-50">
                <p class="text-3xl font-bold text-yellow-600">{{ $pendingCount }}</p>
                <p class="text-sm text-gray-600">En Attente</p>
            </div>
            <div class="p-4 text-center rounded-lg bg-red-50">
                <p class="text-3xl font-bold text-red-600">{{ $overdueCount }}</p>
                <p class="text-sm text-gray-600">En Retard</p>
            </div>
        </div>
    </div>

    <!-- Échéancier détaillé -->
    <div class="overflow-hidden bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Détail des Échéances</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">N°</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Date d'Échéance</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Montant Attendu</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Capital</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Intérêts</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Montant Payé</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Date de Paiement</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Pénalités</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Statut</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($loan->payments->sortBy('payment_number') as $payment)
                    <tr class="hover:bg-gray-50 {{ $payment->status === 'overdue' ? 'bg-red-50' : '' }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full
                                {{ $payment->status === 'paid' ? 'bg-green-100 text-green-800' :
                                   ($payment->status === 'overdue' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') }}">
                                {{ $payment->payment_number }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $payment->due_date->format('d/m/Y') }}</div>
                            @if($payment->status === 'pending' && $payment->due_date->isPast())
                            <div class="text-xs text-red-600">
                                <i class="fas fa-exclamation-triangle"></i>
                                Retard: {{ $payment->due_date->diffInDays(now()) }} jour(s)
                            </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-semibold text-gray-900">{{ number_format($payment->expected_amount, 0, ',', ' ') }}</span>
                            <span class="text-xs text-gray-500">FCFA</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">
                            {{ number_format($payment->principal_amount, 0, ',', ' ') }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">
                            {{ number_format($payment->interest_amount, 0, ',', ' ') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($payment->paid_amount)
                            <span class="font-semibold text-green-600">{{ number_format($payment->paid_amount, 0, ',', ' ') }}</span>
                            <span class="text-xs text-gray-500">FCFA</span>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">
                            @if($payment->paid_date)
                            {{ $payment->paid_date->format('d/m/Y') }}
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($payment->penalty_amount > 0)
                            <span class="font-semibold text-red-600">{{ number_format($payment->penalty_amount, 0, ',', ' ') }}</span>
                            <span class="text-xs text-gray-500">FCFA</span>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusConfig = [
                                    'pending' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'icon' => 'fa-clock', 'label' => 'En Attente'],
                                    'paid' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'icon' => 'fa-check-circle', 'label' => 'Payé'],
                                    'overdue' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'icon' => 'fa-exclamation-triangle', 'label' => 'En Retard'],
                                    'partial' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'icon' => 'fa-minus-circle', 'label' => 'Partiel'],
                                ];
                                $status = $statusConfig[$payment->status] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'icon' => 'fa-question', 'label' => $payment->status];
                            @endphp

                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $status['bg'] }} {{ $status['text'] }}">
                                <i class="fas {{ $status['icon'] }} mr-1"></i>
                                {{ $status['label'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if(in_array($payment->status, ['pending', 'overdue']))
                            <button onclick="openPaymentModal({{ $payment->id }}, {{ $payment->expected_amount }})"
                                    class="px-3 py-1 text-sm text-white transition-colors bg-blue-600 rounded hover:bg-blue-700">
                                <i class="mr-1 fas fa-plus"></i>Payer
                            </button>
                            @elseif($payment->status === 'paid')
                            <button class="px-3 py-1 text-sm text-green-600 transition-colors border border-green-600 rounded hover:bg-green-50"
                                    title="Paiement effectué le {{ $payment->paid_date->format('d/m/Y') }}">
                                <i class="fas fa-check"></i> Payé
                            </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-6 py-12 text-center">
                            <i class="mb-3 text-5xl text-gray-400 fas fa-inbox"></i>
                            <p class="text-gray-500">Aucun échéancier généré</p>
                            <p class="text-sm text-gray-400">L'échéancier sera créé lors du décaissement</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>

                @if($loan->payments->count() > 0)
                <tfoot class="font-semibold bg-gray-50">
                    <tr>
                        <td colspan="2" class="px-6 py-4 text-right">TOTAL:</td>
                        <td class="px-6 py-4">
                            <span class="font-bold text-gray-900">{{ number_format($loan->payments->sum('expected_amount'), 0, ',', ' ') }}</span>
                            <span class="text-xs text-gray-500">FCFA</span>
                        </td>
                        <td class="px-6 py-4">
                            {{ number_format($loan->payments->sum('principal_amount'), 0, ',', ' ') }}
                        </td>
                        <td class="px-6 py-4">
                            {{ number_format($loan->payments->sum('interest_amount'), 0, ',', ' ') }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-bold text-green-600">{{ number_format($loan->payments->sum('paid_amount'), 0, ',', ' ') }}</span>
                            <span class="text-xs text-gray-500">FCFA</span>
                        </td>
                        <td class="px-6 py-4">-</td>
                        <td class="px-6 py-4">
                            <span class="font-bold text-red-600">{{ number_format($loan->payments->sum('penalty_amount'), 0, ',', ' ') }}</span>
                            <span class="text-xs text-gray-500">FCFA</span>
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    <!-- Légende -->
    <div class="p-6 mt-6 bg-white rounded-lg shadow-sm">
        <h3 class="mb-3 text-sm font-semibold text-gray-900">Légende</h3>
        <div class="grid grid-cols-2 gap-4 text-sm md:grid-cols-4">
            <div class="flex items-center">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 mr-2">
                    <i class="mr-1 fas fa-clock"></i>En Attente
                </span>
                <span class="text-gray-600">Non encore payé</span>
            </div>
            <div class="flex items-center">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 mr-2">
                    <i class="mr-1 fas fa-check-circle"></i>Payé
                </span>
                <span class="text-gray-600">Paiement complet</span>
            </div>
            <div class="flex items-center">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 mr-2">
                    <i class="mr-1 fas fa-exclamation-triangle"></i>En Retard
                </span>
                <span class="text-gray-600">Échéance dépassée</span>
            </div>
            <div class="flex items-center">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-2">
                    <i class="mr-1 fas fa-minus-circle"></i>Partiel
                </span>
                <span class="text-gray-600">Paiement incomplet</span>
            </div>
        </div>
    </div>

    <!-- Notes importantes -->
    @if($loan->payments->where('status', 'overdue')->count() > 0)
    <div class="p-4 mt-6 border border-red-200 rounded-lg bg-red-50">
        <div class="flex">
            <i class="mr-3 text-xl text-red-600 fas fa-exclamation-triangle"></i>
            <div>
                <h4 class="mb-1 font-semibold text-red-900">Attention: Paiements en Retard</h4>
                <p class="text-sm text-red-700">
                    Ce prêt a {{ $loan->payments->where('status', 'overdue')->count() }} paiement(s) en retard.
                    Des pénalités de 1% par jour s'appliquent. Veuillez contacter le client rapidement.
                </p>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Modal Paiement (réutilisé du show.blade.php) -->
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
function openPaymentModal(paymentId, expectedAmount) {
    document.getElementById('payment_id').value = paymentId;
    document.getElementById('paid_amount').value = expectedAmount;
    document.getElementById('expected_amount_display').textContent = expectedAmount.toLocaleString('fr-FR') + ' FCFA';
    document.getElementById('paymentModal').classList.remove('hidden');
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.add('hidden');
    document.getElementById('paymentForm').reset();
}

// Fermer le modal en cliquant à l'extérieur
window.onclick = function(event) {
    const modal = document.getElementById('paymentModal');
    if (event.target === modal) {
        modal.classList.add('hidden');
    }
}

// Style d'impression
window.onbeforeprint = function() {
    document.querySelectorAll('button').forEach(btn => btn.style.display = 'none');
    document.querySelectorAll('.no-print').forEach(el => el.style.display = 'none');
}

window.onafterprint = function() {
    document.querySelectorAll('button').forEach(btn => btn.style.display = '');
    document.querySelectorAll('.no-print').forEach(el => el.style.display = '');
}
</script>

<style>
@media print {
    body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
    button, .no-print { display: none !important; }
    .bg-gray-50 { background-color: #f9fafb !important; }
}
</style>
@endsection
