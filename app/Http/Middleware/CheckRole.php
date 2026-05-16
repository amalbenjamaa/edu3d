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
        $wantsJson = $request->expectsJson() || $request->is('api/*');

        if (! $request->user()) {
            if ($wantsJson) {
                return response()->json(['message' => 'Non authentifié.'], 401);
            }

            return redirect()->guest(route('login'));
        }

        if (! in_array($request->user()->role, $roles, true)) {
            if ($wantsJson) {
                return response()->json([
                    'message' => 'Accès refusé. Vous n\'avez pas les droits nécessaires.',
                ], 403);
            }

            return redirect()->route('login');
        }

        return $next($request);
    }
}