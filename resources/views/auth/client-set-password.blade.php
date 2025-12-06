<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Définir le mot de passe - Client</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md">
            <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Définir le mot de passe</h2>

            @if ($errors->any())
                <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('client.set-password') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="mb-4">
                    <label for="client_number" class="block text-gray-700 font-medium mb-2">Numéro de client</label>
                    <input type="text" name="client_number" id="client_number" value="{{ $client_number ?? old('client_number') }}" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div class="mb-4">
                    <label for="temporary_password" class="block text-gray-700 font-medium mb-2">Mot de passe temporaire</label>
                    <input type="password" name="temporary_password" id="temporary_password" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div class="mb-4">
                    <label for="new_password" class="block text-gray-700 font-medium mb-2">Nouveau mot de passe</label>
                    <input type="password" name="new_password" id="new_password" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div class="mb-4">
                    <label for="new_password_confirmation" class="block text-gray-700 font-medium mb-2">Confirmer le nouveau mot de passe</label>
                    <input type="password" name="new_password_confirmation" id="new_password_confirmation" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <button type="submit" class="w-full bg-blue-500 text-white p-3 rounded-lg hover:bg-blue-600">Définir</button>
            </form>
            <p class="mt-4 text-center">
                <a href="{{ route('client.login') }}" class="text-blue-500 hover:underline">Retour à la connexion</a>
            </p>
        </div>
    </div>
</body>
</html>
