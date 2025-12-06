<!-- Détails du Compte d'Épargne -->
<div class="bg-white rounded-lg shadow-sm mb-6">
    <div class="px-6 py-4 bg-cyan-600 text-white rounded-t-lg">
        <h5 class="text-lg font-semibold flex items-center">
            <i class="fas fa-piggy-bank mr-2"></i>Détails du Compte d'Épargne
        </h5>
    </div>

    <div class="p-6">
        @php
            $savings = $account->savingsAccount;
        @endphp

        @if($savings)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <table class="w-full">
                        <tr class="border-b border-gray-100">
                            <td class="py-3 text-gray-600">Taux d'intérêt:</td>
                            <td class="py-3 text-right font-bold text-gray-900">
                                {{ $savings->interest_rate ?? 0 }}% /an
                            </td>
                        </tr>
                        <tr class="border-b border-gray-100">
                            <td class="py-3 text-gray-600">Solde minimum:</td>
                            <td class="py-3 text-right font-bold text-gray-900">
                                {{ number_format($savings->minimum_balance ?? 0, 0, ',', ' ') }} FCFA
                            </td>
                        </tr>
                        <tr>
                            <td class="py-3 text-gray-600">Frais mensuels:</td>
                            <td class="py-3 text-right font-bold text-gray-900">
                                {{ number_format($savings->monthly_fee ?? 0, 0, ',', ' ') }} FCFA
                            </td>
                        </tr>
                    </table>
                </div>

                <div>
                    {{-- <table class="w-full">
                        <tr class="border-b border-gray-100">
                            <td class="py-3 text-gray-600">Total dépôts:</td>
                            <td class="py-3 text-right font-bold text-green-600">
                                {{ number_format($savings->total_deposits ?? 0, 0, ',', ' ') }} FCFA
                            </td>
                        </tr>
                        <tr class="border-b border-gray-100">
                            <td class="py-3 text-gray-600">Total retraits:</td>
                            <td class="py-3 text-right font-bold text-red-600">
                                {{ number_format($savings->total_withdrawals ?? 0, 0, ',', ' ') }} FCFA
                            </td>
                        </tr>
                        <tr>
                            <td class="py-3 text-gray-600">Dernier calcul d'intérêts:</td>
                            <td class="py-3 text-right text-gray-900">
                                @if(!empty($savings->last_interest_calculated))
                                    {{ \Carbon\Carbon::parse($savings->last_interest_calculated)->format('d/m/Y') }}
                                @else
                                    <span class="text-gray-400">Jamais</span>
                                @endif
                            </td>
                        </tr>
                    </table> --}}
                </div>
            </div>

            <!-- Indicateurs -->
            <div class="mt-6">
                <h6 class="text-base font-semibold text-gray-900 mb-4">Indicateurs</h6>

                <!-- Solde minimum respecté -->
                <div class="flex items-center mb-3">
                    @if($account->balance >= ($savings->minimum_balance ?? 0))
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-100 text-green-600 mr-3">
                            <i class="fas fa-check text-sm"></i>
                        </span>
                        <span class="text-gray-900">Solde minimum respecté</span>
                    @else
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-yellow-100 text-yellow-600 mr-3">
                            <i class="fas fa-exclamation-triangle text-sm"></i>
                        </span>
                        <span class="text-gray-900">Solde en dessous du minimum</span>
                    @endif
                </div>

                <!-- Intérêts estimés -->
                @php
                    $months = $account->activated_at ? $account->activated_at->diffInMonths(now()) : 0;
                    $interestRate = $savings->interest_rate ?? 0;
                    $estimatedInterest = ($account->balance * $interestRate / 100) / 12 * $months;
                @endphp
                <div class="mt-4 p-4 bg-cyan-50 border border-cyan-200 rounded-lg">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-cyan-600 mr-3 mt-0.5"></i>
                        <div class="flex-1">
                            <strong class="block text-cyan-900 mb-1">
                                Intérêts estimés accumulés: {{ number_format($estimatedInterest, 0, ',', ' ') }} FCFA
                            </strong>
                            <small class="text-cyan-700">Basé sur {{ $months }} mois depuis l'activation</small>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="text-center py-6 text-gray-600">
                <i class="fas fa-exclamation-circle text-3xl text-yellow-500 mb-2"></i>
                <p>Aucune information d'épargne disponible pour ce compte.</p>
            </div>
        @endif
    </div>
</div>

<!-- Graphique -->
<div class="bg-white rounded-lg shadow-sm mb-6">
    <div class="px-6 py-4 border-b border-gray-200">
        <h5 class="text-lg font-semibold text-gray-900">Évolution du Solde</h5>
    </div>
    <div class="p-6">
        <canvas id="savingsChart" class="w-full" style="height: 300px;"></canvas>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('savingsChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($stats['months'] ?? ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin']) !!},
                datasets: [{
                    label: 'Solde (FCFA)',
                    data: {!! json_encode($stats['balances'] ?? [0, 5000, 10000, 15000, 20000, $account->balance]) !!},
                    borderColor: '#0891b2',
                    backgroundColor: 'rgba(8, 145, 178, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: val => val.toLocaleString() + ' FCFA'
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
