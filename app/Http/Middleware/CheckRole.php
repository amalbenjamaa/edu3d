<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Vérifie que l'utilisateur connecté possède l'un des rôles autorisés.
     *
     * Usage dans les routes :
     *   ->middleware('role:admin')
     *   ->middleware('role:admin,teacher')
     *   ->middleware('role:admin,teacher,student')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // L'utilisateur doit être authentifié
        if (! $request->user()) {
            return response()->json([
                'message' => 'Non authentifié.',
            ], 401);
        }

        // Vérifier si le rôle de l'utilisateur est dans la liste des rôles autorisés
        if (! in_array($request->user()->role, $roles)) {
            return response()->json([
                'message' => 'Accès refusé. Vous n\'avez pas les droits nécessaires.',
                'your_role'     => $request->user()->role,
                'required_roles' => $roles,
            ], 403);
        }

        return $next($request);
    }
}