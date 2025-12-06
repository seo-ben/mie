<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /**
     * Hiérarchie des rôles
     * Les rôles listés sont ceux que chaque rôle supérieur peut gérer
     */
    private static array $roleHierarchy = [
        'administrateur_systeme' => ['administrateur_reglementaire', 'gestionnaire_superviseur', 'gestionnaire_credit', 'agent_terrain', 'agent_agence'],
        'administrateur_reglementaire' => ['gestionnaire_superviseur', 'gestionnaire_credit', 'agent_terrain', 'agent_agence'],
        'gestionnaire_superviseur' => ['gestionnaire_credit', 'agent_terrain', 'agent_agence'],
        'gestionnaire_credit' => ['agent_terrain', 'agent_agence'],
        'agent_terrain' => [],
        'agent_agence' => []
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  mixed ...$roles  Roles requis pour accéder à la route
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $userRole = auth()->user()->role;

        // Vérifie si l'utilisateur a le rôle requis ou un rôle supérieur
        foreach ($roles as $requiredRole) {
            if (self::canActAs($userRole, $requiredRole)) {
                return $next($request);
            }
        }

        return response()->json([
            'message' => 'Forbidden. Required roles: ' . implode(', ', $roles) . '. Your role: ' . $userRole
        ], 403);
    }

    /**
     * Vérifie si un rôle peut agir en tant qu'un autre rôle
     *
     * @param string $userRole
     * @param string $targetRole
     * @return bool
     */
    public static function canActAs(string $userRole, string $targetRole): bool
    {
        if ($userRole === $targetRole) {
            return true;
        }

        return isset(self::$roleHierarchy[$userRole]) && in_array($targetRole, self::$roleHierarchy[$userRole]);
    }
}
