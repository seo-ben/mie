<?php
namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AdminActAsController extends Controller
{
    /**
     * L'admin peut se connecter en tant qu'agent
     */
    public function actAsAgent(Request $request, $agentId)
    {
        if (!$this->canImpersonate()) {
            return response()->json(['message' => 'Permission refusée'], 403);
        }

        $agent = User::where('role', 'LIKE', 'agent%')->findOrFail($agentId);

        return $this->startImpersonation($agent, 'agent');
    }

    /**
     * L'admin peut se connecter en tant que gestionnaire
     */
    public function actAsManager(Request $request, $managerId)
    {
        if (!$this->canImpersonate()) {
            return response()->json(['message' => 'Permission refusée'], 403);
        }

        $manager = User::where('role', 'LIKE', 'gestionnaire%')->findOrFail($managerId);

        return $this->startImpersonation($manager, 'manager');
    }

    /**
     * Arrêter l'impersonation
     */
    public function stopImpersonation()
    {
        $originalUserId = session('impersonating_original_user_id');

        if (!$originalUserId) {
            return response()->json(['message' => 'Aucune impersonation active'], 400);
        }

        $originalUser = User::findOrFail($originalUserId);

        // Logger la fin d'impersonation
        AuditLog::create([
            'user_id' => $originalUserId,
            'action' => 'STOP_IMPERSONATION',
            'entity_type' => 'user',
            'entity_id' => auth()->id(),
            'additional_data' => [
                'impersonated_user' => auth()->user()->full_name,
                'impersonated_role' => auth()->user()->role
            ]
        ]);

        // Remettre l'utilisateur original
        auth()->login($originalUser);
        session()->forget(['impersonating_original_user_id', 'impersonating_as']);

        return response()->json([
            'success' => true,
            'message' => 'Impersonation terminée',
            'user' => $originalUser
        ]);
    }

    private function startImpersonation($targetUser, $roleType)
    {
        $originalUser = auth()->user();

        // Sauvegarder l'utilisateur original en session
        session([
            'impersonating_original_user_id' => $originalUser->id,
            'impersonating_as' => $roleType
        ]);

        // Logger le début d'impersonation
        AuditLog::create([
            'user_id' => $originalUser->id,
            'action' => 'START_IMPERSONATION',
            'entity_type' => 'user',
            'entity_id' => $targetUser->id,
            'additional_data' => [
                'target_user' => $targetUser->full_name,
                'target_role' => $targetUser->role,
                'impersonation_type' => $roleType
            ]
        ]);

        // Connecter en tant que l'utilisateur cible
        auth()->login($targetUser);

        return response()->json([
            'success' => true,
            'message' => "Connecté en tant que {$targetUser->full_name}",
            'user' => $targetUser,
            'impersonating' => true,
            'original_admin' => $originalUser->full_name
        ]);
    }

    private function canImpersonate()
    {
        $user = auth()->user();
        return in_array($user->role, ['administrateur_systeme', 'administrateur_reglementaire']);
    }
}
