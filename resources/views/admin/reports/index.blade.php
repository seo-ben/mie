@extends('layouts.app_admin')

@section('title', 'Analyses Officiers - Rapports')
@section('page-title', 'Rapports & Analyses')

@section('content')
<div class="space-y-8">
    <!-- En-tête -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Centre de Rapports et d'Analyses</h2>
            <p class="text-slate-500 text-sm font-medium">Consultez les indicateurs clés et les rapports réglementaires.</p>
        </div>
    </div>

    <!-- Grille de Rapports -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        
        <!-- Rentabilité -->
        <a href="{{ route('admin.profitability.index') }}" class="group bank-card p-6 hovered-transform cursor-pointer border-l-4 border-l-emerald-500">
            <div class="flex justify-between items-start mb-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <div class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center text-slate-400">
                    <i class="fas fa-arrow-right text-xs -rotate-45 group-hover:rotate-0 transition-transform duration-300"></i>
                </div>
            </div>
            <h3 class="text-lg font-black text-slate-800 mb-2 group-hover:text-emerald-600 transition-colors">Rentabilité & Profits</h3>
            <p class="text-xs text-slate-500 leading-relaxed">Analyse détaillée des revenus, coûts, ROI et performance financière globale.</p>
        </a>

        <!-- Balance Agée -->
        <a href="{{ route('admin.reports.regulatory.aging') }}" class="group bank-card p-6 hovered-transform cursor-pointer border-l-4 border-l-amber-500">
            <div class="flex justify-between items-start mb-4">
                <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                    <i class="fas fa-file-shield"></i>
                </div>
                <div class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center text-slate-400">
                    <i class="fas fa-arrow-right text-xs -rotate-45 group-hover:rotate-0 transition-transform duration-300"></i>
                </div>
            </div>
            <h3 class="text-lg font-black text-slate-800 mb-2 group-hover:text-amber-600 transition-colors">Rapport Réglementaire</h3>
            <p class="text-xs text-slate-500 leading-relaxed">Balance âgée et conformité aux normes BCEAO/COBAC.</p>
        </a>

        <!-- Performance Réseau -->
        <a href="{{ route('admin.reports.agencies.index') }}" class="group bank-card p-6 hovered-transform cursor-pointer border-l-4 border-l-blue-500">
            <div class="flex justify-between items-start mb-4">
                <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                    <i class="fas fa-network-wired"></i>
                </div>
                <div class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center text-slate-400">
                    <i class="fas fa-arrow-right text-xs -rotate-45 group-hover:rotate-0 transition-transform duration-300"></i>
                </div>
            </div>
            <h3 class="text-lg font-black text-slate-800 mb-2 group-hover:text-blue-600 transition-colors">Performance Réseau</h3>
            <p class="text-xs text-slate-500 leading-relaxed">Métriques de performance par agence et consolidées.</p>
        </a>

         <!-- Audit du Grand Livre -->
         <a href="{{ route('admin.transactions.index') }}" class="group bank-card p-6 hovered-transform cursor-pointer border-l-4 border-l-purple-500">
            <div class="flex justify-between items-start mb-4">
                <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                    <i class="fas fa-book"></i>
                </div>
                <div class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center text-slate-400">
                    <i class="fas fa-arrow-right text-xs -rotate-45 group-hover:rotate-0 transition-transform duration-300"></i>
                </div>
            </div>
            <h3 class="text-lg font-black text-slate-800 mb-2 group-hover:text-purple-600 transition-colors">Grand Livre</h3>
            <p class="text-xs text-slate-500 leading-relaxed">Journal complet des transactions et audit financier.</p>
        </a>

    </div>
</div>
@endsection
