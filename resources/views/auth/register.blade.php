<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Système</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md">
            <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Inscription Système</h2>

            @if ($errors->any())
                <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf
                <div class="mb-4">
                    <label for="username" class="block text-gray-700 font-medium mb-2">Nom d'utilisateur</label>
                    <input type="text" name="username" id="username" value="{{ old('username') }}" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div class="mb-4">
                    <label for="email" class="block text-gray-700 font-medium mb-2">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div class="mb-4">
                    <label for="first_name" class="block text-gray-700 font-medium mb-2">Prénom</label>
                    <input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div class="mb-4">
                    <label for="last_name" class="block text-gray-700 font-medium mb-2">Nom</label>
                    <input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div class="mb-4">
                    <label for="phone" class="block text-gray-700 font-medium mb-2">Téléphone</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone') }}" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label for="role" class="block text-gray-700 font-medium mb-2">Rôle</label>
                    <select name="role" id="role" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="agent_terrain" {{ old('role') == 'agent_terrain' ? 'selected' : '' }}>Agent Terrain</option>
                        <option value="agent_agence" {{ old('role') == 'agent_agence' ? 'selected' : '' }}>Agent Agence</option>
                        <option value="gestionnaire_superviseur" {{ old('role') == 'gestionnaire_superviseur' ? 'selected' : '' }}>Gestionnaire Superviseur</option>
                        <option value="gestionnaire_credit" {{ old('role') == 'gestionnaire_credit' ? 'selected' : '' }}>Gestionnaire Crédit</option>
                        <option value="administrateur_systeme" {{ old('role') == 'administrateur_systeme' ? 'selected' : '' }}>Administrateur Système</option>
                        <option value="administrateur_reglementaire" {{ old('role') == 'administrateur_reglementaire' ? 'selected' : '' }}>Administrateur Réglementaire</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="password" class="block text-gray-700 font-medium mb-2">Mot de passe</label>
                    <input type="password" name="password" id="password" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div class="mb-4">
                    <label for="password_confirmation" class="block text-gray-700 font-medium mb-2">Confirmer le mot de passe</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <button type="submit" class="w-full bg-blue-500 text-white p-3 rounded-lg hover:bg-blue-600">S'inscrire</button>
            </form>
            <p class="mt-4 text-center">
                Déjà inscrit ? <a href="{{ route('login') }}" class="text-blue-500 hover:underline">Se connecter</a>
            </p>
        </div>
    </div>
</body>
</html>
