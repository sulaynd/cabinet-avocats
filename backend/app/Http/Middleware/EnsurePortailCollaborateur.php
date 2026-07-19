<?php

namespace App\Http\Middleware;

use App\Models\CollaborateurExterne;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vérifie explicitement que l'entité authentifiée est bien un
 * CollaborateurExterne, pour empêcher un token "portail collaborateur"
 * d'être réutilisé sur les routes internes du cabinet ou du portail client,
 * et vice-versa (voir EnsurePortailClient pour l'explication complète).
 */
class EnsurePortailCollaborateur
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() instanceof CollaborateurExterne) {
            return response()->json(['message' => 'Accès réservé au portail collaborateur.'], 403);
        }

        return $next($request);
    }
}
