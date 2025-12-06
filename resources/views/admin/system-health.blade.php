@extends('layouts.app_admin')
@section('content')
    <div class="min-h-screen p-6">
        <div class="max-w-7xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">État de Santé du Système</h1>
            <div class="bg-white p-6 rounded-lg shadow-lg">
                @foreach ($systemHealth as $key => $value)
                    <p class="text-gray-700 mb-2"><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ is_array($value) ? json_encode($value) : $value }}</p>
                @endforeach
            </div>
        </div>
    </div>
@endsection
