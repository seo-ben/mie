<!-- Paramètres Spécifiques : Épargne Institutionnelle -->
<div class="bank-card overflow-hidden mb-8">
    <div class="px-8 py-5 bg-blue-600 text-white flex items-center justify-between">
        <h3 class="text-xs font-black uppercase tracking-[0.2em] flex items-center gap-2">
            <i class="fas fa-piggy-bank"></i> Paramètres du compte d'Épargne
        </h3>
        <span class="text-[10px] font-bold text-white/70 uppercase">Code de Produit : SAV-CORE</span>
    </div>

    <div class="p-8">
        @php
            $savings = $account->savingsAccount;
        @endphp

        @if($savings)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <!-- Détails Contractuels -->
                <div class="space-y-6">
                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 pb-2">Clauses Contractuelles</h4>
                    <div class="space-y-4">
                        <div class="flex justify-between items-end">
                            <span class="text-[10px] font-bold text-slate-500 uppercase">Rémunération Alpha (Taux)</span>
                            <span class="text-sm font-black text-slate-800">{{ $savings->interest_rate ?? 0 }}% <small class="text-[9px] text-slate-400 font-bold">/ AN</small></span>
                        </div>
                        <div class="flex justify-between items-end">
                            <span class="text-[10px] font-bold text-slate-500 uppercase">Seuil de Maintien Minimum</span>
                            <span class="text-sm font-black text-slate-800">{{ number_format($savings->minimum_balance ?? 0, 0, ',', ' ') }} <small class="text-[9px] text-slate-400 font-bold uppercase">XOF</small></span>
                        </div>
                        <div class="flex justify-between items-end">
                            <span class="text-[10px] font-bold text-slate-500 uppercase">Redevance de Maintenance</span>
                            <span class="text-sm font-black text-slate-800">{{ number_format($savings->monthly_fee ?? 0, 0, ',', ' ') }} <small class="text-[9px] text-slate-400 font-bold uppercase">XOF</small></span>
                        </div>
                    </div>
                </div>

                <!-- Indicateurs de Stabilité -->
                <div class="space-y-6">
                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 pb-2">Indicateurs de Stabilité</h4>
                    
                    <div class="p-5 rounded-2xl border {{ $account->balance >= ($savings->minimum_balance ?? 0) ? 'bg-emerald-50 border-emerald-100' : 'bg-rose-50 border-rose-100' }}">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $account->balance >= ($savings->minimum_balance ?? 0) ? 'bg-emerald-500/10 text-emerald-600' : 'bg-rose-500/10 text-rose-600' }}">
                                <i class="fas {{ $account->balance >= ($savings->minimum_balance ?? 0) ? 'fa-check-circle' : 'fa-triangle-exclamation' }}"></i>
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase {{ $account->balance >= ($savings->minimum_balance ?? 0) ? 'text-emerald-700' : 'text-rose-700' }}">
                                    {{ $account->balance >= ($savings->minimum_balance ?? 0) ? 'Conformité du Seuil Validée' : 'Violation du Seuil de Maintenance' }}
                                </p>
                                <p class="text-[9px] font-bold {{ $account->balance >= ($savings->minimum_balance ?? 0) ? 'text-emerald-600/70' : 'text-rose-600/70' }} uppercase">
                                    {{ $account->balance >= ($savings->minimum_balance ?? 0) ? 'Le solde actuel excède le minimum contractuel.' : 'Le solde est inférieur au minimum requis par le protocole.' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    @php
                        $months = $account->activated_at ? $account->activated_at->diffInMonths(now()) : 0;
                        $interestRate = $savings->interest_rate ?? 0;
                        $estimatedInterest = ($account->balance * $interestRate / 100) / 12 * $months;
                    @endphp
                    <div class="p-5 bg-blue-50 rounded-2xl border border-blue-100">
                        <div class="flex items-start gap-4">
                            <i class="fas fa-microchip text-blue-500 mt-1"></i>
                            <div>
                                <p class="text-[10px] font-black text-blue-900 uppercase tracking-widest leading-tight mb-1">Projection des Intérêts (IA)</p>
                                <p class="text-lg font-black text-blue-700">{{ number_format($estimatedInterest, 0, ',', ' ') }} <small class="text-[10px] uppercase">XOF</small></p>
                                <p class="text-[9px] font-bold text-blue-600/60 uppercase mt-1 italic">Accumulation estimée sur {{ $months }} cycles opérationnels</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="py-12 text-center border-2 border-dashed border-slate-100 rounded-3xl">
                <i class="fas fa-skull-crossbones text-4xl text-slate-100 mb-4 block"></i>
                <p class="text-[10px] font-black text-slate-300 uppercase tracking-[0.2em]">Données de Configuration du compte Corrompues ou Manquantes</p>
            </div>
        @endif
    </div>
</div>

<!-- Graphique de Vélocité du Solde -->
<div class="bank-card overflow-hidden">
    <div class="px-8 py-5 border-b border-slate-100 flex items-center justify-between">
        <h3 class="text-[10px] font-black text-slate-800 uppercase tracking-widest">Trajectoire de Vélocité du Solde</h3>
        <i class="fas fa-wave-square text-slate-200"></i>
    </div>
    <div class="p-8">
        <div class="h-[300px] w-full bg-slate-50/50 rounded-2xl relative overflow-hidden">
            <canvas id="savingsChart" class="relative z-10"></canvas>
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-20">
                <i class="fas fa-shield-halved text-[150px] text-slate-200"></i>
            </div>
        </div>
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
                labels: {!! json_encode($stats['months'] ?? ['Initial', 'M2', 'M3', 'M4', 'M5', 'Actuel']) !!},
                datasets: [{
                    label: 'Solde Consolidé (XOF)',
                    data: {!! json_encode($stats['balances'] ?? [0, 5000, 10000, 15000, 20000, $account->balance]) !!},
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.05)',
                    borderWidth: 3,
                    pointBackgroundColor: '#2563eb',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { size: 10, weight: 'bold' },
                        bodyFont: { size: 12, weight: 'black' },
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.03)' },
                        ticks: {
                            font: { size: 9, weight: 'bold' },
                            color: '#94a3b8',
                            callback: val => val.toLocaleString() + ' XOF'
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { size: 9, weight: 'bold' },
                            color: '#94a3b8'
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
