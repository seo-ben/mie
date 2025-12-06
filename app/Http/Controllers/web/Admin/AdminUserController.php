<?php
namespace App\Http\Controllers\Web\Admin;

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
        $query = User::query()
            ->when($request->get('role'), function($query, $role) {
                $query->where('role', $role);
            })
            ->when($request->has('is_active'), function($query) use ($request) {
                $query->where('is_active', $request->boolean('is_active'));
            })
            ->when($request->get('search'), function($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->select(['id', 'first_name', 'last_name', 'email', 'role', 'is_active']);

        $users = $query->get();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        }

        return view('admin.users.index', [
            'users' => $users,
            'search' => $request->get('search'),
            'role' => $request->get('role'),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : null
        ]);
    }

    /**
     * Afficher le formulaire de création d'un utilisateur
     */
    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Créer un nouvel utilisateur
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
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
            'username' => $validated['username'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'role' => $validated['role'],
            'agency_id' => $validated['agency_id'],
            'password' => Hash::make($validated['password']),
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

        return redirect()->route('admin.users.index')
            ->with('success', 'Utilisateur créé avec succès.');
    }

    /**
     * Afficher un utilisateur spécifique
     */
    public function show($userId)
    {
        $user = User::with(['agency', 'clients'])->findOrFail($userId);

        $recentActivities = AuditLog::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.users.show', [
            'user' => $user,
            'recent_activities' => $recentActivities
        ]);
    }

    /**
     * Afficher le formulaire d'édition d'un utilisateur
     */
    public function edit($userId)
    {
        $user = User::findOrFail($userId);
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        $validated = $request->validate([
            'username' => ['string', Rule::unique('users')->ignore($user->id)],
            'first_name' => 'string',
            'last_name' => 'string',
            'email' => ['email', Rule::unique('users')->ignore($user->id)],
            'phone' => ['string', Rule::unique('users')->ignore($user->id)],
            'role' => 'string',
            'agency_id' => 'nullable|exists:agencies,id',
            'is_active' => 'boolean',
        ]);

        $user->update(array_filter($validated));

        return redirect()->route('admin.users.index')
            ->with('success', 'Utilisateur mis à jour avec succès.');
    }

    /**
     * Supprimer (désactiver) un utilisateur
     */
    public function destroy($userId)
    {
        $user = User::findOrFail($userId);

        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Vous ne pouvez pas désactiver votre propre compte.');
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

        return redirect()->route('admin.users.index')
            ->with('success', 'Utilisateur désactivé avec succès.');
    }

    /**
     * Afficher le formulaire de réinitialisation du mot de passe
     */
    public function showResetPasswordForm($userId)
    {
        $user = User::findOrFail($userId);
        return view('admin.users.reset-password', compact('user'));
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
        $tempPassword = Str::random(12);
        $user->password = Hash::make($tempPassword);
        $user->save();

        // TODO: Implémenter l'envoi de notification si send_notification est true

        return redirect()->route('admin.users.index')
            ->with('success', 'Mot de passe réinitialisé avec succès.')
            ->with('temporary_password', $tempPassword);
    }

    /**
     * Afficher le formulaire pour activer/désactiver 2FA
     */
    public function showToggle2FAForm($userId)
    {
        $user = User::findOrFail($userId);
        return view('admin.users.toggle-2fa', compact('user'));
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

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $enable ? 'ENABLE_2FA' : 'DISABLE_2FA',
            'entity_type' => 'user',
            'entity_id' => $user->id
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', $enable ? '2FA activé avec succès.' : '2FA désactivé avec succès.');
    }

    public function toggleStatus(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')
                ->with('error', 'Vous ne pouvez pas modifier votre propre statut.');
        }
        $user->update(['is_active' => !$user->is_active]);
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $user->is_active ? 'ACTIVATE_USER' : 'DEACTIVATE_USER',
            'entity_type' => 'user',
            'entity_id' => $user->id,
            'additional_data' => [
                'user' => $user->first_name . ' ' . $user->last_name,
                'role' => $user->role
            ]
        ]);
        return redirect()->route('users.index')
            ->with('success', 'Statut de l\'utilisateur mis à jour avec succès.');
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

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $history
            ]);
        }

        return view('admin.users.login-history', [
            'user' => $user,
            'history' => $history,
            'limit' => $limit
        ]);
    }

    /**
     * Statistiques globales des utilisateurs
     */
    public function globalStats()
    {
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $statsByRole = User::groupBy('role')
            ->selectRaw('role, count(*) as count')
            ->pluck('count', 'role')
            ->toArray();

        return view('admin.users.stats', [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'stats_by_role' => $statsByRole
        ]);
    }
}
