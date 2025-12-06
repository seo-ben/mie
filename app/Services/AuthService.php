<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuthService
{
    /**
     * Attempt to authenticate a user
     * 
     * @param array $credentials
     * @return array
     */
    public function attemptLogin(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return [
                'success' => false,
                'message' => 'Invalid credentials'
            ];
        }

        if (!$user->is_active) {
            return [
                'success' => false,
                'message' => 'Account is deactivated'
            ];
        }

        // Generate token
        $token = $user->createToken('auth-token')->plainTextToken;

        // Update last login
        $user->last_login = now();
        $user->save();

        // Define permissions based on role
        $permissions = $this->getRolePermissions($user->role);

        return [
            'success' => true,
            'token' => $token,
            'user' => $user,
            'permissions' => $permissions,
            'expires_at' => now()->addHours(2)
        ];
    }

    /**
     * Register a new user
     * 
     * @param array $data
     * @return array
     */
    public function register(array $data): array
    {
        try {
            DB::beginTransaction();

            $user = User::create([
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'] ?? null,
                'role' => $data['role'] ?? 'client',
                'is_active' => true
            ]);

            // Générer un token directement après inscription
            $token = $user->createToken('auth-token')->plainTextToken;

            DB::commit();

            return [
                'success' => true,
                'user' => $user,
                'token' => $token,
                'permissions' => [],
                'expires_at' => now()->addHours(2)
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User registration failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            return [
                'success' => false,
                'message' => 'Registration failed'
            ];
        }
    }

    /**
     * Register a new user as self-client
     * 
     * @param array $data
     * @return array
     */
    public function registerSelfClient(array $data): array
    {
        try {
            DB::beginTransaction();

            // Créer l'utilisateur avec un statut inactif
            $user = User::create([
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'],
                'role' => 'client',
                'is_active' => false // Compte inactif jusqu'à validation
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Inscription réussie. Un agent vous contactera pour finaliser votre inscription.',
                'reference_number' => $user->id // Assuming client number is the user ID for self-registration
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Self registration failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            return [
                'success' => false,
                'message' => 'L\'inscription a échoué. Veuillez réessayer.'
            ];
        }
    }

    /**
     * Send password reset link
     * 
     * @param string $email
     * @return array
     */
    public function sendPasswordResetLink(string $email): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found'
            ];
        }

        // Generate unique token
        $token = Str::random(60);
        $user->password_reset_token = $token;
        $user->password_reset_expires = Carbon::now()->addHours(24);
        $user->save();

        // Send email with reset link
        // TODO: Implement email sending logic

        return [
            'success' => true,
            'message' => 'Password reset link sent'
        ];
    }

    /**
     * Reset password
     * 
     * @param array $data
     * @return array
     */
    public function resetPassword(array $data): array
    {
        $user = User::where('password_reset_token', $data['token'])
            ->where('password_reset_expires', '>', Carbon::now())
            ->first();

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Invalid or expired token'
            ];
        }

        $user->password = Hash::make($data['password']);
        $user->password_reset_token = null;
        $user->password_reset_expires = null;
        $user->save();

        event(new PasswordReset($user));

        return [
            'success' => true,
            'message' => 'Password reset successfully'
        ];
    }

    /**
     * Change password
     * 
     * @param User $user
     * @param array $data
     * @return array
     */
    public function changePassword(User $user, array $data): array
    {
        if (!Hash::check($data['current_password'], $user->password)) {
            return [
                'success' => false,
                'message' => 'Current password is incorrect'
            ];
        }

        $user->password = Hash::make($data['new_password']);
        $user->save();

        return [
            'success' => true,
            'message' => 'Password changed successfully'
        ];
    }

    /**
     * Handle biometric authentication
     * 
     * @param string $biometricToken
     * @param string $deviceId
     * @return array
     */
    public function handleBiometricAuth(string $biometricToken, string $deviceId): array
    {
        // TODO: Implement biometric verification logic
        return [
            'success' => false,
            'message' => 'Biometric authentication not implemented'
        ];
    }

    /**
     * Get permissions for a specific role
     */
    private function getRolePermissions(string $role): array
    {
        return match ($role) {
            'administrateur_systeme' => [
                'users.*',
                'agencies.*',
                'clients.*',
                'accounts.*',
                'transactions.*',
                'loans.*',
                'reports.*',
                'settings.*'
            ],
            'gestionnaire_superviseur' => [
                'clients.view',
                'clients.create',
                'clients.update',
                'accounts.view',
                'accounts.create',
                'transactions.view',
                'transactions.create',
                'loans.view',
                'loans.approve',
                'reports.view'
            ],
            'agent_terrain' => [
                'clients.view',
                'clients.create',
                'accounts.view',
                'transactions.create',
                'transactions.view'
            ],
            'agent_agence' => [
                'clients.view',
                'clients.create',
                'accounts.view',
                'transactions.create',
                'transactions.view',
                'loans.view'
            ],
            'gestionnaire_credit' => [
                'loans.*',
                'clients.view',
                'accounts.view',
                'transactions.view'
            ],
            'administrateur_reglementaire' => [
                'reports.*',
                'audit.*',
                'compliance.*',
                'settings.view'
            ],
            default => []
        };
    }
}