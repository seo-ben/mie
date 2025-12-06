@extends('layouts.app_admin')
@section('title', 'Paramètres Système')
@section('content')

<!-- Messages Flash -->
@if(session('success'))
<div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
    <div class="flex items-center">
        <i class="fas fa-check-circle text-green-500 mr-3"></i>
        <p class="text-green-700">{{ session('success') }}</p>
        @if(session('updated_count'))
        <span class="ml-auto bg-green-100 text-green-800 text-sm px-3 py-1 rounded-full">{{ session('updated_count') }} paramètre(s) mis à jour</span>
        @endif
    </div>
</div>
@endif

@if(session('error'))
<div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
    <div class="flex items-center">
        <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
        <p class="text-red-700">{{ session('error') }}</p>
    </div>
</div>
@endif

<!-- Header -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Paramètres Système</h2>
        <p class="text-gray-600 mt-1">Configuration globale de la plateforme</p>
    </div>
    <div class="flex gap-3 mt-4 md:mt-0">
        <a href="{{ route('admin.config.backups') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
            <i class="fas fa-database mr-2"></i>Sauvegardes
        </a>
        <a href="{{ route('admin.config.logs') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
            <i class="fas fa-history mr-2"></i>Historique
        </a>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <form method="GET" action="{{ route('admin.config.parameters') }}" class="flex flex-col md:flex-row gap-4">
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label>
            <div class="relative">
                <input type="text" name="search" value="{{ $search }}" placeholder="Nom du paramètre ou description..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
            </div>
        </div>
        <div class="md:w-64">
            <label class="block text-sm font-medium text-gray-700 mb-2">Catégorie</label>
            <select name="category" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">Toutes les catégories</option>
                @foreach($categories as $key => $label)
                <option value="{{ $key }}" {{ $category == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-filter mr-2"></i>Filtrer
            </button>
            <a href="{{ route('admin.config.parameters') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                <i class="fas fa-redo"></i>
            </a>
        </div>
    </form>
</div>

<!-- Parameters Form -->
<form method="POST" action="{{ route('admin.config.parameters.update') }}" id="parametersForm">
    @csrf

    @forelse($parameters as $categoryKey => $categoryParams)
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-purple-50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                        <i class="fas {{ getCategoryIcon($categoryKey) }} text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">{{ $categories[$categoryKey] ?? ucfirst($categoryKey) }}</h3>
                        <p class="text-sm text-gray-600">{{ count($categoryParams) }} paramètre(s)</p>
                    </div>
                </div>
                <button type="button" class="text-gray-400 hover:text-gray-600" onclick="toggleCategory('{{ $categoryKey }}')">
                    <i class="fas fa-chevron-down" id="icon-{{ $categoryKey }}"></i>
                </button>
            </div>
        </div>

        <div id="category-{{ $categoryKey }}" class="p-6">
            <div class="space-y-4">
                @foreach($categoryParams as $param)
                <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex-1">
                            <label class="block font-semibold text-gray-800 mb-1">
                                {{ $param->parameter_key }}
                                @if($param->is_required)
                                <span class="text-red-500">*</span>
                                @endif
                            </label>
                            <p class="text-sm text-gray-600 mb-2">{{ $param->description }}</p>

                            @if($param->data_type === 'boolean')
                            <div class="flex items-center gap-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="parameters[{{ $param->id }}]" value="1" {{ $param->parameter_value == '1' ? 'checked' : '' }} class="form-radio text-blue-600">
                                    <span class="ml-2">Activé</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="parameters[{{ $param->id }}]" value="0" {{ $param->parameter_value == '0' ? 'checked' : '' }} class="form-radio text-blue-600">
                                    <span class="ml-2">Désactivé</span>
                                </label>
                            </div>

                            @elseif($param->data_type === 'select')
                            <select name="parameters[{{ $param->id }}]" class="w-full md:w-96 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                @foreach(json_decode($param->allowed_values ?? '[]', true) as $option)
                                <option value="{{ $option }}" {{ $param->parameter_value == $option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            </select>

                            @elseif($param->data_type === 'number')
                                <input type="number"
                                    name="parameters[{{ $param->id }}]"
                                    value="{{ is_array($param->parameter_value) ? json_encode($param->parameter_value) : $param->parameter_value }}"
                                    step="0.01"
                                    class="w-full md:w-96 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                    {{ $param->is_required ? 'required' : '' }}>

                            @elseif($param->data_type === 'text')
                                <textarea name="parameters[{{ $param->id }}]"
                                        rows="3"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                        {{ $param->is_required ? 'required' : '' }}>
                            {{ is_array($param->parameter_value) ? json_encode($param->parameter_value, JSON_PRETTY_PRINT) : $param->parameter_value }}
                                </textarea>

                            @else
                                <input type="text"
                                    name="parameters[{{ $param->id }}]"
                                    value="{{ is_array($param->parameter_value) ? json_encode($param->parameter_value) : $param->parameter_value }}"
                                    class="w-full md:w-96 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                    {{ $param->is_required ? 'required' : '' }}>
                            @endif


                            @if($param->validation_rules)
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-info-circle mr-1"></i>{{ $param->validation_rules }}
                            </p>
                            @endif
                        </div>

                        <div class="text-sm text-gray-500">
                            Mis à jour: {{ $param->updated_at->format('d/m/Y H:i') }}
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-lg shadow-sm p-12 text-center">
        <i class="fas fa-cog text-gray-300 text-6xl mb-4"></i>
        <p class="text-gray-500 text-lg">Aucun paramètre trouvé</p>
    </div>
    @endforelse

    @if($parameters->count() > 0)
    <div class="bg-white rounded-lg shadow-sm p-6 sticky bottom-0">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="text-sm text-gray-600">
                <i class="fas fa-info-circle mr-2"></i>
                Les modifications seront appliquées immédiatement après validation
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="showResetModal()" class="px-6 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition">
                    <i class="fas fa-undo mr-2"></i>Réinitialiser
                </button>
                <button type="submit" class="px-8 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-save mr-2"></i>Enregistrer les modifications
                </button>
            </div>
        </div>
    </div>
    @endif
</form>

<!-- Reset Modal -->
<div id="resetModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Réinitialiser les paramètres</h3>
        <p class="text-gray-600 mb-6">Êtes-vous sûr de vouloir réinitialiser les paramètres aux valeurs par défaut ?</p>

        <form method="POST" action="{{ route('admin.config.reset-defaults') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Catégorie (optionnel)</label>
                <select name="category" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">Toutes les catégories</option>
                    @foreach($categories as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-6">
                <label class="inline-flex items-center">
                    <input type="checkbox" name="confirm" value="1" required class="form-checkbox text-red-600">
                    <span class="ml-2 text-sm text-gray-700">Je confirme la réinitialisation</span>
                </label>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="hideResetModal()" class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                    Annuler
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                    Réinitialiser
                </button>
            </div>
        </form>
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
        icon.classList.remove('fa-chevron-right');
        icon.classList.add('fa-chevron-down');
    } else {
        content.style.display = 'none';
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-right');
    }
}

function showResetModal() {
    document.getElementById('resetModal').classList.remove('hidden');
}

function hideResetModal() {
    document.getElementById('resetModal').classList.add('hidden');
}
</script>

@endsection
