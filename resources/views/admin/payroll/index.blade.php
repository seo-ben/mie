@extends('layouts.app_admin')

@section('title', 'Gestion de la Paie')
@section('page-title', 'Administration du Personnel & Flux Salariaux')

@section('content')
<div class="space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Registre du Personnel & Rémunérations</h2>
            <p class="text-slate-500 text-sm font-medium">Suivi des décaissements salariaux et conformité de caisse</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.payroll.report') }}" class="btn-bank btn-bank-outline">
                <i class="fas fa-file-invoice-dollar mr-2 text-[10px]"></i> Historique des Paiements
            </a>
        </div>
    </div>

    <!-- Matrice de Performance Salariale -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bank-card p-6 border-l-4 border-blue-600">
            <span class="kpi-label">Effectif Total</span>
            <div class="kpi-value !text-2xl mt-1">{{ number_format($stats['total_staff']) }}</div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">Contrats Internes Actifs</p>
        </div>
        <div class="bank-card p-6 border-l-4 border-emerald-600">
            <span class="kpi-label">Décaissements (Mois en cours)</span>
            <div class="kpi-value !text-2xl mt-1 text-emerald-600">{{ number_format($stats['total_paid_this_month'], 0, ',', ' ') }} <small class="text-xs">XOF</small></div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">Masse Salariale Libérée</p>
        </div>
        <div class="bank-card p-6 border-l-4 border-amber-600">
            <span class="kpi-label">Paiements en Attente</span>
            <div class="kpi-value !text-2xl mt-1 text-amber-600">{{ number_format($stats['pending_salaries']) }}</div>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-2">Postes à Regulariser ce Cycle</p>
        </div>
    </div>

    <!-- Registre du Personnel -->
    <div class="bank-card p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xs font-black text-slate-700 uppercase tracking-widest">Registre Permanent du Personnel</h3>
            <form action="{{ route('admin.payroll.index') }}" method="GET" class="relative w-64">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom, Prénom ou ID..." class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-9 pr-4 py-2 text-xs focus:ring-1 focus:ring-blue-500 outline-none">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="bank-table">
                <thead>
                    <tr>
                        <th>Membre du Personnel</th>
                        <th>Rôle / Agence</th>
                        <th class="text-right">Salaire de Base</th>
                        <th class="text-center">Mode Défaut</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($staffMembers as $staff)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 font-bold border border-slate-200 text-xs">
                                    {{ substr($staff->first_name, 0, 1) }}{{ substr($staff->last_name, 0, 1) }}
                                </div>
                                <div>
                                    <p class="font-bold text-slate-800 leading-tight uppercase">{{ $staff->full_name }}</p>
                                    <p class="text-[9px] font-mono font-bold text-blue-600 lowercase tracking-tighter mt-0.5">{{ $staff->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="flex flex-col">
                                <span class="text-[10px] font-black text-slate-500 uppercase">{{ str_replace('_', ' ', $staff->role) }}</span>
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">{{ $staff->agency->name ?? 'Siège Central' }}</span>
                            </div>
                        </td>
                        <td class="text-right">
                            <span class="text-sm font-extrabold text-slate-800 font-numeric">
                                {{ $staff->base_salary ? number_format($staff->base_salary, 0, ',', ' ') . ' XOF' : 'Non Défini' }}
                            </span>
                        </td>
                        <td class="text-center">
                            @php
                                $methodClass = match($staff->payment_method) {
                                    'cash' => 'bg-emerald-50 text-emerald-600',
                                    'bank' => 'bg-blue-50 text-blue-600',
                                    'mobile_money' => 'bg-purple-50 text-purple-600',
                                    default => 'bg-slate-50 text-slate-600'
                                };
                            @endphp
                            <span class="text-[8px] font-black uppercase px-2 py-1 rounded-md {{ $methodClass }}">
                                {{ $staff->payment_method }}
                            </span>
                        </td>
                        <td class="text-right">
                            <a href="{{ route('admin.payroll.create-payment', $staff->id) }}" class="btn-bank btn-bank-primary py-1.5 px-3 text-[10px]">
                                <i class="fas fa-wallet mr-1.5"></i> Payer
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center text-slate-400">Aucun personnel trouvé</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $staffMembers->links() }}
        </div>
    </div>
</div>
@endsection
