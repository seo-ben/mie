@extends('layouts.app_admin')

@section('title', 'Comparatif de Performance Réseau')
@section('page-title', 'Protocole / Rapport Global des Nœuds')

@section('content')
<div class="space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 no-print">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Analyse Comparative des Divisions</h2>
            <p class="text-slate-500 text-sm font-bold uppercase tracking-widest font-mono">Performance consolidée du réseau d'agences</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="btn-bank btn-bank-outline">
                <i class="fas fa-print mr-2 text-[10px]"></i> Exporter
            </button>
        </div>
    </div>

    <!-- Filtres de Fenêtre d'Audit -->
    <div class="bank-card p-6 no-print">
        <form method="GET" action="{{ route('admin.reports.agencies.index') }}" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-2">Début de Période</label>
                <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2 text-xs font-bold focus:ring-1 focus:ring-blue-500 outline-none">
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-2">Fin de Période</label>
                <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2 text-xs font-bold focus:ring-1 focus:ring-blue-500 outline-none">
            </div>
            <button type="submit" class="btn-bank btn-bank-primary px-8">Audit du Réseau</button>
        </form>
    </div>

    <!-- KPIs Consolidés -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bank-card p-6 border-l-4 border-blue-600">
            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-1">Unités de Division</span>
            <p class="text-2xl font-black text-slate-900">{{ $globalStats['total_agencies'] }}</p>
        </div>
        <div class="bank-card p-6 border-l-4 border-emerald-500">
            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-1">Adhérents Totaux</span>
            <p class="text-2xl font-black text-slate-900">{{ number_format($globalStats['total_clients']) }}</p>
        </div>
        <div class="bank-card p-6 border-l-4 border-purple-600">
            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-1">Volume Ops (Fenêtre)</span>
            <p class="text-2xl font-black text-slate-900">{{ number_format($globalStats['total_transactions']) }}</p>
        </div>
        <div class="bank-card p-6 border-l-4 border-amber-500">
            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-1">Flux d'Actifs (Fenêtre)</span>
            <p class="text-2xl font-black text-slate-900">{{ number_format($globalStats['total_amount'], 0, ',', ' ') }} <small class="text-xs font-bold">XOF</small></p>
        </div>
    </div>

    <!-- Registre Comparatif des Agences -->
    <div class="bank-card overflow-hidden">
        <div class="px-8 py-5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <h3 class="text-xs font-black text-slate-700 uppercase tracking-widest">Registre de Performance par Division</h3>
            <span class="text-[9px] font-black text-slate-400 uppercase px-2 py-1 bg-white border border-slate-200 rounded">Période du {{ $startDate->format('d/m/Y') }} au {{ $endDate->format('d/m/Y') }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="bank-table">
                <thead>
                    <tr>
                        <th>Nœud Institutionnel</th>
                        <th class="text-center">Effectif</th>
                        <th class="text-center">Adhérents</th>
                        <th class="text-center">Volume Ops</th>
                        <th>Flux Net (XOF)</th>
                        <th>Capital Sous Gestion</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($agenciesPerformance as $perf)
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center text-blue-600 border border-slate-200 group-hover:bg-blue-600 group-hover:text-white transition-all">
                                    <i class="fas fa-landmark text-xs"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-black text-slate-800 leading-none">{{ $perf['agency']->name }}</p>
                                    <p class="text-[9px] text-slate-400 font-bold mt-1 uppercase">{{ $perf['agency']->city }} ({{ $perf['agency']->code }})</p>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="text-[10px] font-black text-slate-600">{{ $perf['agency']->active_users_count }} / {{ $perf['agency']->users_count }}</span>
                            <p class="text-[8px] font-bold text-slate-400 uppercase mt-0.5">Officiers Actifs</p>
                        </td>
                        <td class="text-center">
                            <div class="flex flex-col">
                                <span class="text-xs font-black text-slate-800">{{ number_format($perf['clients_count']) }}</span>
                                <span class="text-[9px] font-black text-emerald-600 uppercase">+{{ $perf['clients_period'] }}</span>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="text-xs font-black text-slate-800">{{ number_format($perf['transactions_count']) }}</span>
                            <p class="text-[8px] font-bold text-slate-400 uppercase mt-0.5">Opérations Terminées</p>
                        </td>
                        <td>
                            <p class="text-sm font-black text-blue-600">{{ number_format($perf['transactions_amount'], 0, ',', ' ') }}</p>
                            <div class="w-full bg-slate-100 h-1 mt-1.5 rounded-full overflow-hidden">
                                <div class="bg-blue-500 h-full rounded-full" style="width: {{ $globalStats['total_amount'] > 0 ? min(100, ($perf['transactions_amount'] / $globalStats['total_amount']) * 500) : 0 }}%"></div>
                            </div>
                        </td>
                        <td>
                            <p class="text-sm font-black text-slate-900">{{ number_format($perf['total_balance'], 0, ',', ' ') }}</p>
                            <p class="text-[8px] font-bold text-slate-400 uppercase mt-0.5">Solde des Portefeuilles</p>
                        </td>
                        <td class="text-right">
                            <a href="{{ route('admin.reports.agencies.show', $perf['agency']->id) }}" class="p-2 text-slate-400 hover:text-blue-600 transition">
                                <i class="fas fa-chart-line text-xs"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    @media print {
        .no-print { display: none !important; }
        .bank-card { box-shadow: none !important; border: 1px solid #e2e8f0 !important; }
    }
</style>
@endsection
