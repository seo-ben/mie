<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Afficher le formulaire de connexion
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Connexion des utilisateurs système (agents, gestionnaires, admins)
     */
    public function login(Request $request)
    {
        try {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required'
            ]);

            $user = User::where('email', $credentials['email'])->first();

            if (!$user) {
                return back()
                    ->withErrors(['email' => 'Utilisateur introuvable'])
                    ->withInput($request->only('email'));
            }

            if (!$user->is_active) {
                return back()
                    ->withErrors(['email' => 'Votre compte est désactivé. Contactez l\'administrateur.'])
                    ->withInput($request->only('email'));
            }

            if ($user->role === 'client') {
                return back()
                    ->withErrors(['email' => 'Veuillez utiliser l\'interface client pour vous connecter.'])
                    ->withInput($request->only('email'));
            }

            // Tentative de connexion
            if (Auth::attempt($credentials, $request->filled('remember'))) {
                $request->session()->regenerate();

                // Mettre à jour la dernière connexion
                $user->update(['last_login' => now()]);

                // Rediriger selon le rôle
                return $this->redirectToDashboard($user);
            }

            return back()
                ->withErrors(['password' => 'Mot de passe incorrect'])
                ->withInput($request->only('email'));

        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput($request->only('email'));
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Une erreur est survenue lors de la connexion'])
                ->withInput($request->only('email'));
        }
    }

    /**
     * Afficher le formulaire d'inscription (pour utilisateurs système)
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * Inscription d'un nouvel utilisateur système
     */
    public function register(Request $request)
    {
        try {
            $data = $request->validate([
                'username' => 'required|string|max:50|unique:users',
                'email' => 'required|string|email|max:100|unique:users',
                'password' => 'required|string|confirmed|min:8',
                'first_name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
                'phone' => 'nullable|string|max:20|unique:users',
                'role' => 'required|string|in:agent_terrain,agent_agence,gestionnaire_superviseur,gestionnaire_credit,administrateur_systeme,administrateur_reglementaire',
            ]);

            // Créer l'utilisateur
            $user = User::create([
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'] ?? null,
                'role' => $data['role'],
                'is_active' => true
            ]);

            // Connexion automatique après inscription
            Auth::login($user);

            return redirect()
                ->route('dashboard')
                ->with('success', 'Inscription réussie ! Bienvenue ' . $user->first_name);

        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput($request->except('password', 'password_confirmation'));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('User registration failed', [
                'error' => $e->getMessage()
            ]);

            return back()
                ->withErrors(['error' => 'Une erreur est survenue lors de l\'inscription'])
                ->withInput($request->except('password', 'password_confirmation'));
        }
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request)
    {
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()
            ->route('login')
            ->with('success', 'Vous avez été déconnecté avec succès');
    }

    /**
     * Afficher le profil de l'utilisateur connecté
     */
    public function profile()
    {
        $user = Auth::user();
        return view('auth.profile', compact('user'));
    }

    /**
     * Afficher le formulaire de mot de passe oublié
     */
    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Envoyer le lien de réinitialisation
     */
    public function forgotPassword(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email'
            ]);

            $user = User::where('email', $request->email)->first();
            if ($user->role === 'client') {
                return back()
                    ->withErrors(['email' => 'Veuillez utiliser l\'interface client pour réinitialiser votre mot de passe.'])
                    ->withInput($request->only('email'));
            }

            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status === Password::RESET_LINK_SENT) {
                return back()->with('success', 'Un lien de réinitialisation a été envoyé à votre adresse email');
            }

            return back()
                ->withErrors(['email' => __($status)])
                ->withInput($request->only('email'));

        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput($request->only('email'));
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Une erreur est survenue lors de l\'envoi du lien'])
                ->withInput($request->only('email'));
        }
    }

    /**
     * Afficher le formulaire de réinitialisation du mot de passe
     */
    public function showResetPasswordForm($token, Request $request)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email
        ]);
    }

    /**
     * Réinitialiser le mot de passe
     */
    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'token' => 'required',
                'email' => 'required|email|exists:users,email',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = User::where('email', $request->email)->first();
            if ($user->role === 'client') {
                return back()
                    ->withErrors(['email' => 'Veuillez utiliser l\'interface client pour réinitialiser votre mot de passe.'])
                    ->withInput($request->only('email'));
            }

            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    $user->forceFill([
                        'password' => Hash::make($password)
                    ])->save();
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return redirect()
                    ->route('login')
                    ->with('success', 'Votre mot de passe a été réinitialisé avec succès');
            }

            return back()
                ->withErrors(['email' => __($status)])
                ->withInput($request->only('email'));

        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput($request->only('email'));
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Une erreur est survenue lors de la réinitialisation'])
                ->withInput($request->only('email'));
        }
    }

    /**
     * Changer le mot de passe de l'utilisateur connecté
     */
    public function changePassword(Request $request)
    {
        try {
            $request->validate([
                'current_password' => 'required',
                'password' => 'required|string|min:8|confirmed'
            ]);

            $user = Auth::user();

            if ($user->role === 'client') {
                return back()
                    ->withErrors(['error' => 'Veuillez utiliser l\'interface client pour changer votre mot de passe.']);
            }

            if (!Hash::check($request->current_password, $user->password)) {
                return back()
                    ->withErrors(['current_password' => 'Le mot de passe actuel est incorrect']);
            }

            $user->update([
                'password' => Hash::make($request->password)
            ]);

            return back()->with('success', 'Votre mot de passe a été modifié avec succès');

        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Une erreur est survenue lors du changement de mot de passe']);
        }
    }

    /**
     * Obtenir les informations de l'utilisateur connecté (pour API AJAX)
     */
    public function me(Request $request)
    {
        $user = Auth::user();
        if ($user->role === 'client') {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez utiliser l\'interface client pour accéder à vos informations.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user
            ]
        ]);
    }

    /**
     * Rediriger vers le dashboard approprié selon le rôle
     */
    protected function redirectToDashboard($user)
    {
        $dashboardRoutes = [
            'agent_terrain' => 'agent.dashboard',
            'agent_agence' => 'agent.dashboard',
            'gestionnaire_superviseur' => 'manager.dashboard',
            'gestionnaire_credit' => 'manager.dashboard',
            'administrateur_systeme' => 'admin.dashboard',
            'administrateur_reglementaire' => 'admin.dashboard',
        ];

        $route = $dashboardRoutes[$user->role] ?? 'dashboard';

        return redirect()
            ->intended(route($route))
            ->with('success', 'Bienvenue ' . $user->first_name . ' !');
    }
}