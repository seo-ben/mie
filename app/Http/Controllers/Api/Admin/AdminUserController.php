<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class AdminUserController extends Controller
{
    /**
     * Liste de tous les utilisateurs
     */
    public function index(Request $request)
    {
        $users = User::with(['agency'])
            ->when($request->get('role'), fn($q, $role) => $q->where('role', $role))
            ->when($request->get('agency_id'), fn($q, $id) => $q->where('agency_id', $id))
            ->when($request->get('search'), function($q, $search) {
                $q->where(fn($sub) =>
                    $sub->where('first_name', 'like', "%$search%")
                        ->orWhere('last_name', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%")
                        ->orWhere('username', 'like', "%$search%")
                );
            })
            ->when($request->has('is_active'), fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Créer un nouvel utilisateur
     */
    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|unique:users,username',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|unique:users,phone',
            'role' => 'required|string',
            'agency_id' => 'nullable|exists:agencies,id',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'username' => $request->username,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->role,
            'agency_id' => $request->agency_id,
            'password' => Hash::make($request->password),
            'is_active' => true,
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'CREATE_USER',
            'entity_type' => 'user',
            'entity_id' => $user->id,
            'additional_data' => [
                'created_user_role' => $user->role,
                'created_user_agency' => $user->agency_id
            ]
        ]);

        return response()->json([
            'success' => true,
            'data' => $user
        ], 201);
    }

    /**
     * Afficher un utilisateur spécifique
     */
    public function show($userId)
    {
        $user = User::with(['agency', 'clients'])->findOrFail($userId);

        // Historique d'activités (dernières 10 actions)
        $recentActivities = AuditLog::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'recent_activities' => $recentActivities
            ]
        ]);
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        $request->validate([
            'username' => ['string', Rule::unique('users')->ignore($user->id)],
            'first_name' => 'string',
            'last_name' => 'string',
            'email' => ['email', Rule::unique('users')->ignore($user->id)],
            'phone' => ['string', Rule::unique('users')->ignore($user->id)],
            'role' => 'string',
            'agency_id' => 'nullable|exists:agencies,id',
            'is_active' => 'boolean',
        ]);

        $user->update($request->only([
            'username', 'first_name', 'last_name', 'email', 'phone', 'role', 'agency_id', 'is_active'
        ]));

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Supprimer (désactiver) un utilisateur
     */
    public function destroy($userId)
    {
        $user = User::findOrFail($userId);

        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas supprimer votre propre compte'
            ], 422);
        }

        $user->update(['is_active' => false]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'DEACTIVATE_USER',
            'entity_type' => 'user',
            'entity_id' => $user->id,
            'additional_data' => [
                'deactivated_user' => $user->first_name . ' ' . $user->last_name,
                'deactivated_role' => $user->role
            ]
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur désactivé avec succès'
        ]);
    }

    /**
     * Réinitialiser le mot de passe d'un utilisateur
     */
    public function resetPassword(Request $request, $userId)
    {
        $request->validate([
            'send_notification' => 'boolean'
        ]);

        $user = User::findOrFail($userId);
        $tempPassword = Str::random(10);
        $user->password = Hash::make($tempPassword);
        $user->save();

        // TODO: envoyer notification si besoin

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe réinitialisé avec succès',
            'temporary_password' => $tempPassword
        ]);
    }

    /**
     * Activer/Désactiver 2FA pour un utilisateur
     */
    public function toggle2FA(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $enable = $request->boolean('enable');

        $user->mfa_enabled = $enable;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => $enable ? '2FA activé' : '2FA désactivé',
            'data' => ['mfa_enabled' => $user->mfa_enabled]
        ]);
    }

    /**
     * Historique des connexions d'un utilisateur
     */
    public function loginHistory($userId, Request $request)
    {
        $limit = $request->get('limit', 20);

        $user = User::findOrFail($userId);

        $history = AuditLog::where('user_id', $userId)
            ->where('action', 'LOGIN')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }

    /**
     * Statistiques globales des utilisateurs
     */
    public function globalStats()
    {
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $roles = User::select('role')->groupBy('role')->get()->pluck('role');

        $statsByRole = [];
        foreach ($roles as $role) {
            $statsByRole[$role] = User::where('role', $role)->count();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'stats_by_role' => $statsByRole
            ]
        ]);
    }
}
