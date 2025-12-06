<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Client;
use App\Models\Account;
use App\Services\AuthService;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\ClientRegisterRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * Connexion utilisateur
     */
    public function login(LoginRequest $request)
    {
        try {
            $credentials = $request->only(['email', 'password']);
            $userType = $request->get('user_type', 'employee'); // employee, client
            $deviceInfo = $request->get('device_info', []);
            
            // Ici j'ignores $userType et $deviceInfo si mon AuthService ne les utilise pas encore 
            // $result = $this->authService->login($credentials, $userType, $deviceInfo);
            $result = $this->authService->attemptLogin($credentials);
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 401);
            }

            return response()->json([
                'success' => true,
                'message' => 'Connexion réussie',
                'data' => [
                    'user' => $result['user'],
                    'token' => $result['token'],
                    'permissions' => $result['permissions'],
                    'expires_at' => $result['expires_at']
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la connexion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request)
    {
        try {
            $user = auth()->user();
            
            // Révoquer le token actuel
            $request->user()->currentAccessToken()->delete();

            // Logger la déconnexion
            \App\Models\AuditLog::create([
                'user_id' => $user->id,
                'action' => 'LOGOUT',
                'entity_type' => 'authentication',
                'entity_id' => $user->id,
                'ip_address' => $request->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Déconnexion réussie'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la déconnexion'
            ], 500);
        }
    }

    /**
     * Informations utilisateur connecté
     */
    public function me()
    {
        $user = auth()->user();
        
        $userData = [
            'id' => $user->id,
            'name' => $user->full_name,
            'email' => $user->email,
            'role' => $user->role,
            'agency' => $user->agency ? [
                'id' => $user->agency->id,
                'name' => $user->agency->name,
                'code' => $user->agency->code
            ] : null,
            'last_login' => $user->last_login,
            'is_active' => $user->is_active,
            'mfa_enabled' => $user->mfa_enabled
        ];

        return response()->json([
            'success' => true,
            'data' => $userData
        ]);
    }

    /**
     * Rafraîchir le token
     */
    public function refresh(Request $request)
    {
        try {
            $user = auth()->user();
            
            // Créer un nouveau token
            $token = $user->createToken('auth_token', ['*'], now()->addHours(24));

            // Révoquer l'ancien token
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'data' => [
                    'token' => $token->plainTextToken,
                    'expires_at' => $token->accessToken->expires_at
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rafraîchissement du token'
            ], 500);
        }
    }

    /**
     * Changer le mot de passe
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed'
        ]);

        try {
            $user = auth()->user();

            if (!Hash::check($request->get('current_password'), $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mot de passe actuel incorrect'
                ], 422);
            }

            $user->update([
                'password' => Hash::make($request->get('new_password'))
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mot de passe modifié avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de mot de passe'
            ], 500);
        }
    }

    /**
     * Mot de passe oublié
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'user_type' => 'required|in:employee,client'
        ]);

        try {
            $result = $this->authService->forgotPassword(
                $request->get('email'),
                $request->get('user_type')
            );

            return response()->json([
                'success' => true,
                'message' => 'Instructions de réinitialisation envoyées par email'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi des instructions'
            ], 500);
        }
    }

    /**
     * Réinitialiser le mot de passe
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed'
        ]);

        try {
            $result = $this->authService->resetPassword(
                $request->get('token'),
                $request->get('email'),
                $request->get('password')
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Mot de passe réinitialisé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réinitialisation'
            ], 500);
        }
    }

    /**
     * Inscription utilisateur
     */
    public function register(Request $request)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'username' => 'required|string|unique:users,username',
                'email' => 'required|string|email|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'phone' => 'required|string|unique:users,phone',
                'role' => 'required|in:agent_terrain,agent_agence,gestionnaire_superviseur,gestionnaire_credit,administrateur_systeme,administrateur_reglementaire',
                'profile' => 'required|array',
                'profile.date_of_birth' => 'required|date',
                'profile.gender' => 'required|in:M,F',
                'profile.address' => 'required|string',
                'profile.city' => 'required|string',
                'profile.region' => 'required|string',
                'profile.profession' => 'required|string',
                'profile.monthly_income' => 'required|numeric',
                'profile.id_type' => 'required|in:CNI,PASSPORT,VOTER_CARD',
                'profile.id_number' => 'required|string',
                'profile.id_expiry_date' => 'required|date|after:today',
                'device_info' => 'required|array',
                'device_info.device_id' => 'required|string',
                'device_info.platform' => 'required|in:android,ios'
            ]);

            DB::beginTransaction();

            // Create user with validated role
            $user = User::create([
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'phone' => $validated['phone'],
                'role' => $validated['role'], // Use the validated role instead of hardcoding 'client'
                'is_active' => true
            ]);

            // Create client profile
            $client = Client::create([
                'user_id' => $user->id,
                'client_number' => $this->generateClientNumber(),
                'date_of_birth' => $validated['profile']['date_of_birth'],
                'gender' => $validated['profile']['gender'],
                'address' => $validated['profile']['address'],
                'city' => $validated['profile']['city'],
                'region' => $validated['profile']['region'],
                'profession' => $validated['profile']['profession'],
                'monthly_income' => $validated['profile']['monthly_income'],
                'id_type' => $validated['profile']['id_type'],
                'id_number' => $validated['profile']['id_number'],
                'id_expiry_date' => $validated['profile']['id_expiry_date'],
                'kyc_status' => 'pending'
            ]);

            // Create savings account
            Account::create([
                'client_id' => $client->id,
                'account_number' => $this->generateAccountNumber('SAV'),
                'account_type' => 'savings',
                'status' => 'pending_activation',
                'balance' => 0,
                'currency' => 'XOF'
            ]);

            // Generate token
            $token = $user->createToken('auth-token')->plainTextToken;

            DB::commit();

            return response()->json([
                'success' => true,
                'user' => $user,
                'token' => $token,
                'permissions' => $this->authService->getRolePermissions('client'),
                'expires_at' => now()->addHours(2)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTrace()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'errors' => [$e->getMessage()]
            ], 422);
        }
    }

    private function generateClientNumber(): string
    {
        $prefix = 'CLI';
        $year = date('y');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return $prefix . $year . $random;
    }

    private function generateAccountNumber(string $type): string
    {
        $prefix = $type;
        $year = date('y');
        $random = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        return $prefix . $year . $random;
    }
}