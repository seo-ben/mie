<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Connexion pour les utilisateurs (Agents)
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email'    => 'required|string',
                'password' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors'  => $validator->errors()
                ], 422);
            }
            
            // On cherche par email ou username
            $user = User::where('email', $request->email)
                        ->orWhere('username', $request->email)
                        ->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                Log::warning("Tentative de connexion échouée pour : " . $request->email);
                return response()->json([
                    'success' => false,
                    'message' => 'Identifiants invalides (Email ou Mot de passe incorrect)'
                ], 401);
            }
    
            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Votre compte est désactivé. Veuillez contacter l\'administrateur.'
                ], 403);
            }
    
            // On supprime les anciens tokens pour éviter les conflits (Optionnel)
            $user->tokens()->delete();

            // Création du token Sanctum
            $token = $user->createToken('api_token_agent')->plainTextToken;

            Log::info("Agent connecté avec succès : " . $user->username . " (ID: " . $user->id . ")");
    
            return response()->json([
                'success' => true,
                'message' => 'Connexion réussie',
                'data' => [
                    'user' => [
                        'id'         => $user->id,
                        'username'   => $user->username,
                        'email'      => $user->email,
                        'name'       => $user->first_name . ' ' . $user->last_name,
                        'first_name' => $user->first_name,
                        'last_name'  => $user->last_name,
                        'role'       => $user->role,
                        'agency_id'  => $user->agency_id
                    ],
                    'token'      => $token,
                    'token_type' => 'Bearer'
                ]
            ]);
    
        } catch (\Exception $e) {
            Log::error('Erreur Critique Login: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur interne est survenue sur le serveur'
            ], 500);
        }
    }

    /**
     * Profil de l'utilisateur connecté
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Non authentifié'], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user
            ]
        ]);
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request)
    {
        try {
            if ($request->user()) {
                $request->user()->currentAccessToken()->delete();
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Déconnexion réussie'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur lors de la déconnexion'], 500);
        }
    }
}
