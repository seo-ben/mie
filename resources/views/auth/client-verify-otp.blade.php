<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification OTP - Client</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md">
            <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Vérification OTP</h2>

            @if ($errors->any())
                <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('client.verify-otp') }}">
                @csrf
                <input type="hidden" name="client_id" value="{{ $client_id ?? old('client_id') }}">
                <div class="mb-4">
                    <label for="otp" class="block text-gray-700 font-medium mb-2">Code OTP</label>
                    <input type="text" name="otp" id="otp" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <button type="submit" class="w-full bg-blue-500 text-white p-3 rounded-lg hover:bg-blue-600">Vérifier</button>
            </form>
            <p class="mt-4 text-center">
                <a href="{{ route('client.register') }}" class="text-blue-500 hover:underline">Retour à l'inscription</a>
            </p>
        </div>
    </div>
</body>
</html>
