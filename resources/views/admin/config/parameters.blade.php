@extends('layouts.app_admin')

@section('title', 'Configuration du Cœur Institutionnel')
@section('page-title', 'Protocole / Configuration Centrale')

@section('content')
<div class="space-y-8">
    <!-- En-tête Institutionnel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Paramètres de l'Infrastructure Centrale</h2>
            <p class="text-slate-500 text-sm font-medium">Gouvernance globale et gestion des seuils opérationnels</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.config.backups') }}" class="btn-bank btn-bank-secondary">
                <i class="fas fa-database mr-2 text-[10px]"></i> Sauvegardes
            </a>
            <a href="{{ route('admin.config.logs') }}" class="btn-bank btn-bank-primary">
                <i class="fas fa-history mr-2 text-[10px]"></i> Audit de Configuration
            </a>
        </div>
    </div>

    <!-- Mécanisme de Retour -->
    @if(session('success'))
    <div class="bank-card p-4 border-l-4 border-l-emerald-500 bg-emerald-50/50 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <i class="fas fa-check-circle text-emerald-500"></i>
            <p class="text-xs font-black text-emerald-800 uppercase tracking-tight">{{ session('success') }}</p>
        </div>
        @if(session('updated_count'))
        <span class="px-3 py-1 bg-emerald-100 text-emerald-700 text-[9px] font-black rounded-full uppercase">{{ session('updated_count') }} Paramètres Mis à Jour</span>
        @endif
    </div>
    @endif

    <!-- Recherche et Filtrage Intelligent -->
    <div class="bank-card p-6">
        <form method="GET" action="{{ route('admin.config.parameters') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <div class="md:col-span-6">
                <label class="text-[9px] font-extrabold text-slate-400 uppercase tracking-widest block mb-2">Recherche de Paramètre</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Clé système ou description..." class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-10 pr-4 py-2 text-xs focus:ring-1 focus:ring-blue-500 outline-none transition">
                    <i class="fas fa-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                </div>
            </div>
            <div class="md:col-span-4">
                <label class="text-[9px] font-extrabold text-slate-400 uppercase tracking-widest block mb-2">Catégorie de Gouvernance</label>
                <select name="category" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2 text-xs font-bold text-slate-600 focus:ring-1 focus:ring-blue-500 outline-none transition uppercase">
                    <option value="">Tous les Domaines Institutionnels</option>
                    @foreach($categories as $key => $label)
                    <option value="{{ $key }}" {{ $category == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2 flex items-end gap-2">
                <button type="submit" class="btn-bank btn-bank-primary w-full py-2.5">
                    <i class="fas fa-filter text-[10px]"></i>
                </button>
                <a href="{{ route('admin.config.parameters') }}" class="btn-bank btn-bank-secondary p-2.5">
                    <i class="fas fa-rotate-right text-[10px]"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Grand Livre des Paramètres - TABLEAU -->
    <form method="POST" action="{{ route('admin.config.parameters.update') }}" id="parametersForm" class="space-y-8">
        @csrf

        @forelse($parameters as $categoryKey => $categoryParams)
        <div class="bank-card overflow-hidden">
            <div class="px-8 py-5 border-b border-slate-100 bg-slate-50/80 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-slate-900 rounded-xl flex items-center justify-center text-white shadow-lg shadow-slate-900/10">
                        <i class="fas {{ getCategoryIcon($categoryKey) }} text-xs"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-tight">Domaine : {{ $categories[$categoryKey] ?? ucfirst($categoryKey) }}</h3>
                        <p class="text-[9px] font-bold text-slate-400 uppercase">{{ count($categoryParams) }} Paramètres de Gouvernance Actifs</p>
                    </div>
                </div>
                <button type="button" class="w-8 h-8 rounded-lg hover:bg-slate-200 transition flex items-center justify-center text-slate-400" onclick="toggleCategory('{{ $categoryKey }}')">
                    <i class="fas fa-chevron-down text-[10px]" id="icon-{{ $categoryKey }}"></i>
                </button>
            </div>

            <div id="category-{{ $categoryKey }}" class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-8 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest w-1/4">Paramètre / Description</th>
                            <th class="px-8 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest w-1/3">Valeur de Configuration</th>
                            <th class="px-8 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest w-1/6 text-right">Dernière Modifiction</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($categoryParams as $param)
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <td class="px-8 py-4 align-top">
                                <div class="flex flex-col gap-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-slate-800 font-mono">{{ $param->parameter_key }}</span>
                                        @if($param->is_required)
                                            <span class="text-[8px] font-black text-rose-500 uppercase bg-rose-50 px-1.5 py-0.5 rounded">Requis</span>
                                        @endif
                                    </div>
                                    <p class="text-[10px] text-slate-500 leading-snug">{{ $param->description }}</p>
                                </div>
                            </td>
                            <td class="px-8 py-4 align-top">
                                <div>
                                    @if($param->data_type === 'boolean')
                                    <div class="flex items-center gap-2">
                                        <label class="flex items-center cursor-pointer">
                                            <input type="radio" name="parameters[{{ $param->id }}]" value="1" {{ $param->parameter_value == '1' ? 'checked' : '' }} class="hidden peer">
                                            <div class="px-4 py-1.5 rounded-md border border-slate-200 text-[10px] font-bold uppercase text-slate-400 peer-checked:bg-emerald-600 peer-checked:text-white peer-checked:border-emerald-600 transition-all">Oui</div>
                                        </label>
                                        <label class="flex items-center cursor-pointer">
                                            <input type="radio" name="parameters[{{ $param->id }}]" value="0" {{ $param->parameter_value == '0' ? 'checked' : '' }} class="hidden peer">
                                            <div class="px-4 py-1.5 rounded-md border border-slate-200 text-[10px] font-bold uppercase text-slate-400 peer-checked:bg-rose-600 peer-checked:text-white peer-checked:border-rose-600 transition-all">Non</div>
                                        </label>
                                    </div>

                                    @elseif($param->data_type === 'select')
                                    <select name="parameters[{{ $param->id }}]" class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold text-slate-700 focus:ring-1 focus:ring-blue-500 outline-none uppercase transition">
                                        @foreach(json_decode($param->allowed_values ?? '[]', true) as $option)
                                        <option value="{{ $option }}" {{ $param->parameter_value == $option ? 'selected' : '' }}>{{ $option }}</option>
                                        @endforeach
                                    </select>

                                    @elseif($param->data_type === 'number')
                                    <input type="number" name="parameters[{{ $param->id }}]" value="{{ is_array($param->parameter_value) ? json_encode($param->parameter_value) : $param->parameter_value }}" step="0.01" class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm font-bold text-slate-900 focus:ring-1 focus:ring-blue-500 outline-none transition font-mono" {{ $param->is_required ? 'required' : '' }}>

                                    @elseif($param->data_type === 'text')
                                    <textarea name="parameters[{{ $param->id }}]" rows="2" class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs font-medium text-slate-900 focus:ring-1 focus:ring-blue-500 outline-none transition resize-y" {{ $param->is_required ? 'required' : '' }}>{{ is_array($param->parameter_value) ? json_encode($param->parameter_value, JSON_PRETTY_PRINT) : $param->parameter_value }}</textarea>

                                    @else
                                    <input type="text" name="parameters[{{ $param->id }}]" value="{{ is_array($param->parameter_value) ? json_encode($param->parameter_value) : $param->parameter_value }}" class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm font-bold text-slate-900 focus:ring-1 focus:ring-blue-500 outline-none transition" {{ $param->is_required ? 'required' : '' }}>
                                    @endif
                                </div>
                            </td>
                            <td class="px-8 py-4 align-top text-right">
                                <span class="text-[10px] font-bold text-slate-800 block">
                                    {{ $param->updated_at ? $param->updated_at->format('d/m/Y') : '—' }}
                                </span>
                                <span class="text-[9px] text-slate-400 block">
                                    {{ $param->updated_at ? $param->updated_at->format('H:i') : '' }}
                                </span>
                                @if($param->validation_rules)
                                <div class="mt-1 inline-flex items-center gap-1 text-blue-500" title="Règles : {{ $param->validation_rules }}">
                                    <i class="fas fa-shield-halved text-[8px]"></i>
                                    <span class="text-[8px] font-black uppercase tracking-tight">Vérifié</span>
                                </div>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @empty
        <div class="bank-card p-20 text-center">
            <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6 border border-slate-100">
                <i class="fas fa-gears text-slate-200 text-3xl"></i>
            </div>
            <h4 class="text-sm font-black text-slate-800 uppercase tracking-widest">Aucun Paramètre Protocolaire Trouvé</h4>
            <p class="text-xs text-slate-400 mt-2">Ajustez vos critères de recherche ou vos filtres de gouvernance.</p>
        </div>
        @endforelse

        @if($parameters->count() > 0)
        <!-- Barre flottante d'actions institutionnelles -->
        <div class="bank-card p-6 sticky bottom-6 shadow-2xl shadow-slate-900/20 border-blue-200 bg-white/95 backdrop-blur-md z-30">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-4 text-slate-500">
                    <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center text-slate-400 border border-slate-200">
                        <i class="fas fa-lock text-xs"></i>
                    </div>
                    <div class="text-left leading-tight">
                        <p class="text-[10px] font-black text-slate-800 uppercase tracking-widest">Exécution Autorisée Uniquement</p>
                        <p class="text-[8px] font-bold text-slate-400">Les mises à jour sont immédiates après validation du protocole</p>
                    </div>
                </div>
                <div class="flex gap-4 w-full md:w-auto">
                    <button type="button" onclick="showResetModal()" class="flex-1 md:flex-none px-8 py-3 bg-rose-50 border border-rose-100 text-rose-700 text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-rose-100 transition shadow-sm">
                        Réinitialisation des comptes
                    </button>
                    <button type="submit" class="flex-1 md:flex-none px-12 py-3 bg-slate-900 text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-blue-600 transition shadow-xl shadow-slate-900/10">
                        Appliquer les Modifications
                    </button>
                </div>
            </div>
        </div>
        @endif
    </form>

    <!-- Modal de Réinitialisation des Protocoles -->
    <div id="resetModal" class="hidden fixed inset-0 bg-slate-900/80 z-[100] flex items-center justify-center p-6 backdrop-blur-sm">
        <div class="bank-card max-w-md w-full p-8 shadow-2xl">
            <div class="w-16 h-16 bg-rose-100 text-rose-600 rounded-2xl flex items-center justify-center mb-6 border border-rose-200">
                <i class="fas fa-triangle-exclamation text-2xl"></i>
            </div>
            <h3 class="text-xl font-black text-slate-900 leading-tight">Réinitialisation Critique des Protocoles</h3>
            <p class="text-sm text-slate-500 mt-2 leading-relaxed">Êtes-vous absolument sûr de vouloir rétablir les paramètres à leurs valeurs institutionnelles par défaut ?</p>

            <form method="POST" action="{{ route('admin.config.reset-defaults') }}" class="mt-8 space-y-6">
                @csrf
                <div>
                    <label class="text-[9px] font-extrabold text-slate-400 uppercase tracking-widest block mb-2">Périmètre Cible</label>
                    <select name="category" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs font-bold text-slate-700 outline-none uppercase">
                        <option value="">Réinitialisation de l'Infrastructure Globale</option>
                        @foreach($categories as $key => $label)
                        <option value="{{ $key }}">Domaine : {{ $label }} uniquement</option>
                        @endforeach
                    </select>
                </div>

                <div class="bg-rose-50 border border-rose-100 p-4 rounded-xl">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="confirm" value="1" required class="w-4 h-4 text-rose-600 bg-white border-slate-300 rounded focus:ring-rose-500">
                        <span class="ml-3 text-[10px] font-black text-rose-700 uppercase tracking-tight">J'autorise l'inversion à l'état de base</span>
                    </label>
                </div>

                <div class="flex gap-4 pt-2">
                    <button type="button" onclick="hideResetModal()" class="flex-1 px-4 py-3 bg-slate-100 text-slate-600 text-[10px] font-black uppercase rounded-xl hover:bg-slate-200 transition">
                        Annuler
                    </button>
                    <button type="submit" class="flex-1 px-4 py-3 bg-rose-600 text-white text-[10px] font-black uppercase rounded-xl hover:bg-rose-700 transition">
                        Confirmer la Réinitialisation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@php
function getCategoryIcon($category) {
    return match($category) {
        'fees' => 'fa-coins',
        'rates' => 'fa-percent',
        'limits' => 'fa-ruler',
        'integrations' => 'fa-plug',
        'security' => 'fa-shield-alt',
        'notifications' => 'fa-bell',
        'loans' => 'fa-hand-holding-usd',
        'accounts' => 'fa-wallet',
        'tontine' => 'fa-hands-helping',
        'savings' => 'fa-piggy-bank',
        default => 'fa-cog'
    };
}
@endphp

<script>
function toggleCategory(categoryKey) {
    const content = document.getElementById('category-' + categoryKey);
    const icon = document.getElementById('icon-' + categoryKey);
    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.classList.replace('fa-chevron-right', 'fa-chevron-down');
    } else {
        content.style.display = 'none';
        icon.classList.replace('fa-chevron-down', 'fa-chevron-right');
    }
}
function showResetModal() { document.getElementById('resetModal').classList.remove('hidden'); }
function hideResetModal() { document.getElementById('resetModal').classList.add('hidden'); }
// Re-enable toggle animations for tables if needed, currently direct display toggle
</script>
@endsection
