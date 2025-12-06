<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClientAuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Afficher le formulaire de connexion client
     */
    public function showClientLoginForm()
    {
        return view('auth.client-login');
    }

    /**
     * Connexion client avec code compte et mot de passe (Web)
     */
    public function clientLogin(Request $request)
    {
        try {
            $credentials = $request->validate([
                'client_number' => 'required|string',
                'password' => 'required|string'
            ]);

            $client = Client::where('client_number', $credentials['client_number'])->first();

            if (!$client) {
                return back()
                    ->withErrors(['client_number' => 'Code client introuvable'])
                    ->withInput($request->only('client_number'));
            }

            if (!$client->is_active) {
                return back()
                    ->withErrors(['client_number' => 'Votre compte est désactivé. Veuillez contacter votre agence.'])
                    ->withInput($request->only('client_number'));
            }

            if (Auth::guard('client')->attempt($credentials, $request->filled('remember'))) {
                $request->session()->regenerate();

                $client->update(['last_login' => now()]);

                return redirect()
                    ->intended(route('client.dashboard'))
                    ->with('success', 'Bienvenue ' . $client->first_name . ' !');
            }

            return back()
                ->withErrors(['password' => 'Mot de passe incorrect'])
                ->withInput($request->only('client_number'));

        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput($request->only('client_number'));
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Une erreur est survenue lors de la connexion'])
                ->withInput($request->only('client_number'));
        }
    }

    /**
     * Connexion client avec code compte et mot de passe (API)
     */
    public function login(Request $request)
    {
        $request->validate([
            'client_number' => 'required|string',
            'password' => 'required|string'
        ]);

        try {
            $client = Client::where('client_number', $request->client_number)->first();

            if (!$client || !Hash::check($request->password, $client->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Code client ou mot de passe incorrect'
                ], 401);
            }

            if (!$client->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Votre compte est désactivé. Veuillez contacter votre agence.'
                ], 401);
            }

            // Générer le token
            $token = $client->createToken('client-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'token' => $token,
                'user' => [
                    'client_number' => $client->client_number,
                    'first_name' => $client->first_name,
                    'last_name' => $client->last_name,
                    'phone' => $client->phone
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
     * Déconnexion client (Web)
     */
    public function clientLogout(Request $request)
    {
        Auth::guard('client')->logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()
            ->route('client.login')
            ->with('success', 'Vous avez été déconnecté avec succès');
    }

    /**
     * Afficher le formulaire de mot de passe oublié client
     */
    public function showClientForgotPasswordForm()
    {
        return view('auth.client-forgot-password');
    }

    /**
     * Envoyer le lien de réinitialisation pour client
     */
    public function clientForgotPassword(Request $request)
    {
        try {
            $request->validate([
                'client_number' => 'required|string'
            ]);

            $client = Client::where('client_number', $request->client_number)->first();

            if (!$client) {
                return back()
                    ->withErrors(['client_number' => 'Code client introuvable'])
                    ->withInput($request->only('client_number'));
            }

            $status = Password::broker('clients')->sendResetLink([
                'email' => $client->email
            ]);

            if ($status === Password::RESET_LINK_SENT) {
                return back()->with('success', 'Un lien de réinitialisation a été envoyé à votre adresse email');
            }

            return back()
                ->withErrors(['client_number' => __($status)])
                ->withInput($request->only('client_number'));

        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput($request->only('client_number'));
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Une erreur est survenue lors de l\'envoi du lien'])
                ->withInput($request->only('client_number'));
        }
    }

    /**
     * Afficher le formulaire de réinitialisation du mot de passe client
     */
    public function showClientResetPasswordForm($token, Request $request)
    {
        return view('auth.client-reset-password', [
            'token' => $token,
            'email' => $request->email,
            'client_number' => $request->client_number
        ]);
    }

    /**
     * Réinitialiser le mot de passe client
     */
    public function clientResetPassword(Request $request)
    {
        try {
            $request->validate([
                'token' => 'required',
                'client_number' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $client = Client::where('client_number', $request->client_number)->first();

            if (!$client) {
                return back()
                    ->withErrors(['client_number' => 'Code client introuvable'])
                    ->withInput($request->only('client_number'));
            }

            $status = Password::broker('clients')->reset(
                [
                    'email' => $client->email,
                    'password' => $request->password,
                    'password_confirmation' => $request->password_confirmation,
                    'token' => $request->token
                ],
                function ($client, $password) {
                    $client->forceFill([
                        'password' => Hash::make($password)
                    ])->save();
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return redirect()
                    ->route('client.login')
                    ->with('success', 'Votre mot de passe a été réinitialisé avec succès');
            }

            return back()
                ->withErrors(['client_number' => __($status)])
                ->withInput($request->only('client_number'));

        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput($request->only('client_number'));
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Une erreur est survenue lors de la réinitialisation'])
                ->withInput($request->only('client_number'));
        }
    }

    /**
     * Inscription par un agent uniquement
     */
    public function register(Request $request)
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
     * Afficher le formulaire d'inscription client
     */
    public function showClientRegistrationForm()
    {
        return view('auth.client-register');
    }
    /**
     * Afficher le formulaire de vérification OTP
     */
    public function showVerifyOtpForm(Request $request)
    {
        return view('auth.client-verify-otp', [
            'client_id' => $request->query('client_id')
        ]);
    }

    /**
     * Afficher le formulaire pour définir le mot de passe
     */
    public function showSetPasswordForm($token, Request $request)
    {
        return view('auth.client-set-password', [
            'token' => $token,
            'client_number' => $request->query('client_number')
        ]);
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
                'phone' => 'nullable|string|unique:clients,phone',
                'password' => 'required|string|min:6|confirmed',
                'first_name' => 'required|string',
                'last_name' => 'required|string'
            ]);

            DB::beginTransaction();

            // Créer le client
            $client = Client::create([
                'client_number' => 'CLI' . date('y') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT),
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'registration_type' => 'self',
                'registration_status' => 'pending',
                'is_active' => false
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
            'client_id' => 'required|exists:clients,id',
            'otp' => 'required|string'
        ]);

        $client = Client::find($request->client_id);

        if (!$client || !Hash::check($request->otp, $client->otp)) {
            return response()->json([
                'success' => false,
                'message' => 'Code OTP invalide'
            ], 422);
        }

        if (now()->gt($client->otp_expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Code OTP expiré'
            ], 422);
        }

        // Activer le compte
        $client->otp = null;
        $client->otp_expires_at = null;
        $client->phone_verified_at = now();
        $client->is_active = true;
        $client->save();

        // Générer le token
        $token = $client->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Compte vérifié avec succès',
            'token' => $token,
            'client' => [
                'client_number' => $client->client_number,
                'first_name' => $client->first_name,
                'last_name' => $client->last_name,
                'phone' => $client->phone
            ]
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

        $client = Auth::guard('client')->user();
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
            $referrer = Auth::guard('client')->user();
            if (!$referrer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorisé'
                ], 403);
            }

            $validated = $request->validate([
                'phone' => 'nullable|string|unique:clients,phone',
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'relationship' => 'required|string'
            ]);

            DB::beginTransaction();

            // Générer un mot de passe temporaire
            $tempPassword = Str::random(8);

            // Créer le client
            $client = Client::create([
                'client_number' => 'CLI' . date('y') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT),
                'phone' => $validated['phone'],
                'password' => Hash::make($tempPassword),
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'registration_type' => 'referral',
                'registration_status' => 'pending',
                'referred_by' => $referrer->id,
                'relationship' => $validated['relationship'],
                'is_active' => false
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Client parrainé avec succès',
                'client_number' => $client->client_number,
                'temp_password' => $tempPassword
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