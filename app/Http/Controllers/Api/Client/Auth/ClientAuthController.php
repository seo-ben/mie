<?php

namespace App\Http\Controllers\Api\Client\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ClientAuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'phone' => 'required|unique:clients,phone',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed'
        ]);

        $clientNumber = 'CLI' . date('y') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $client = Client::create([
            'client_number' => $clientNumber,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'registration_type' => 'self',
            'registration_status' => 'pending',
            'registration_channel' => 'mobile_app'
        ]);

        return response()->json([
            'message' => 'Inscription réussie',
            'client_number' => $client->client_number
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'client_number' => 'required',
            'password' => 'required'
        ]);

        $client = Client::where('client_number', $request->client_number)->first();

        if (!$client || !Hash::check($request->password, $client->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Numéro client ou code incorrect'
            ], 401);
        }

        if (!$client->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Compte en attente de validation'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'client' => [
                    'client_number' => $client->client_number,
                    'name' => $client->first_name . ' ' . $client->last_name,
                    'phone' => $client->phone
                ],
                'token' => $client->createToken('mobile-app')->plainTextToken
            ]
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'client_number' => 'required',
            'otp' => 'required|string|size:6'
        ]);

        $client = Client::where('client_number', $request->client_number)
                       ->where('otp', $request->otp)
                       ->first();

        if (!$client) {
            throw ValidationException::withMessages([
                'otp' => ['Code OTP invalide'],
            ]);
        }

        $client->phone_verified_at = now();
        $client->otp = null;
        $client->save();

        return response()->json(['message' => 'Numéro vérifié avec succès']);
    }
}