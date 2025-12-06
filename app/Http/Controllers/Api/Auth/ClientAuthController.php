<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClientAuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Login client avec code compte et mot de passe
     */
    public function login(Request $request)
    {
        $request->validate([
            'client_number' => 'required|string',
            'password' => 'required|string'
        ]);

        try {
            $client = Client::where('client_number', $request->client_number)
                          ->with('user')
                          ->first();

            if (!$client || !$client->user || !Hash::check($request->password, $client->user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Code client ou mot de passe incorrect'
                ], 401);
            }

            if (!$client->user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Votre compte est désactivé. Veuillez contacter votre agence.'
                ], 401);
            }

            // Générer le token
            $token = $client->user->createToken('client-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'token' => $token,
                'user' => [
                    'client_number' => $client->client_number,
                    'first_name' => $client->user->first_name,
                    'last_name' => $client->user->last_name,
                    'phone' => $client->user->phone
                ],
                'permissions' => ['client.access'],
                'expires_at' => now()->addDays(30)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la connexion'
            ], 500);
        }
    }

    /**
     * Inscription par un agent uniquement
     */
    public function register(ClientRegisterRequest $request)
    {
        try {
            $result = $this->authService->registerClient($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Client enregistré avec succès',
                'data' => [
                    'client_number' => $result['client_number'],
                    'temporary_password' => $result['temporary_password']
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement du client'
            ], 500);
        }
    }

    /**
     * Changement du mot de passe temporaire
     */
    public function setPassword(Request $request)
    {
        $request->validate([
            'client_number' => 'required|string',
            'temporary_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed'
        ]);

        try {
            $result = $this->authService->setClientPassword(
                $request->client_number,
                $request->temporary_password,
                $request->new_password
            );

            return response()->json([
                'success' => true,
                'message' => 'Mot de passe modifié avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de mot de passe'
            ], 422);
        }
    }

    /**
     * Auto-inscription d'un client
     */
    public function selfRegister(Request $request)
    {
        try {
            $validated = $request->validate([
                'phone' => 'nullable|string|unique:users,phone',
                'password' => 'required|string|min:6|confirmed',
                'first_name' => 'required|string',
                'last_name' => 'required|string'
            ]);

            DB::beginTransaction();

            // Créer l'utilisateur (authentification)
            $user = User::create([
                'username' => strtolower($validated['first_name']) . '_' . time(),
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'role' => 'client',
                'is_active' => false
            ]);

            // Créer le profil client (informations personnelles)
            $client = Client::create([
                'user_id' => $user->id,
                'client_number' => 'CLI' . date('y') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT),
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'registration_type' => 'self',
                'registration_status' => 'pending'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inscription réussie, en attente de validation',
                'client_number' => $client->client_number
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'inscription',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Vérification du code OTP
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'otp' => 'required|string'
        ]);

        $user = User::find($request->user_id);

        if (!$user || !Hash::check($request->otp, $user->otp)) {
            return response()->json([
                'success' => false,
                'message' => 'Code OTP invalide'
            ], 422);
        }

        if (now()->gt($user->otp_expires_at)) {
            return response()->json([
                'success' => false,  
                'message' => 'Code OTP expiré'
            ], 422);
        }

        // Activer le compte
        $user->otp = null;
        $user->otp_expires_at = null;
        $user->phone_verified_at = now();
        $user->save();

        // Créer le profil client initial
        $clientNumber = 'CLI' . date('y') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $client = Client::create([
            'user_id' => $user->id,
            'client_number' => $clientNumber,
            'registration_type' => 'self',
            'registration_status' => 'pending' // En attente de validation par l'agence
        ]);

        // Générer le token
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Compte vérifié avec succès',
            'token' => $token,
            'user' => $user,
            'client_number' => $client->client_number
        ]);
    }

    /**
     * Compléter le profil client
     */
    public function completeProfile(Request $request)
    {
        $validated = $request->validate([
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:M,F',
            'address' => 'required|string',
            'city' => 'required|string',
            'region' => 'required|string',
            'profession' => 'required|string',
            'monthly_income' => 'required|numeric',
            'id_type' => 'required|in:CNI,PASSPORT,VOTER_CARD',
            'id_number' => 'required|string',
            'id_expiry_date' => 'required|date|after:today'
        ]);

        $client = auth()->user()->client;
        $client->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profil complété avec succès',
            'client' => $client
        ]);
    }

    /**
     * Inscription par un autre client
     */
    public function referralRegister(Request $request)
    {
        try {
            // Vérifier que le client qui parraine est authentifié
            $referrer = auth()->user();
            if (!$referrer || $referrer->role !== 'client') {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorisé'
                ], 403);
            }

            $validated = $request->validate([
                'phone' => 'nullable|string|unique:users,phone',
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'relationship' => 'required|string'
            ]);

            DB::beginTransaction();

            // Générer un mot de passe temporaire
            $tempPassword = Str::random(8);

            // Créer l'utilisateur
            $user = User::create([
                'username' => strtolower($validated['first_name']) . '_' . time(),
                'phone' => $validated['phone'],
                'password' => Hash::make($tempPassword),
                'role' => 'client',
                'is_active' => false
            ]);

            // Créer le profil client
            $client = Client::create([
                'user_id' => $user->id,
                'client_number' => 'CLI' . date('y') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT),
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'registration_type' => 'referral',
                'registration_status' => 'pending',
                'referred_by' => $referrer->client->id,
                'relationship' => $validated['relationship']
            ]);

            DB::commit();

            // Envoyer le mot de passe temporaire par SMS
            // sendPasswordViaSms($validated['phone'], $tempPassword);

            return response()->json([
                'success' => true,
                'message' => 'Client parrainé avec succès',
                'client_number' => $client->client_number,
                'temp_password' => $tempPassword // À retirer en production
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du parrainage',
                'error' => $e->getMessage()
            ], 422);
        }
    }
}