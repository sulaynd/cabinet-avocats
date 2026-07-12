<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restreint l'accès à une route selon le rôle de l'utilisateur authentifié.
 *
 * Utilisation dans routes/api.php :
 *   Route::delete('factures/{facture}', [...])->middleware('role:admin,avocat');
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            return response()->json(['message' => 'Action non autorisée pour votre rôle.'], 403);
        }

        return $next($request);
    }
}
