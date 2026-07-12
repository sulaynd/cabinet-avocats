<?php

namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sanctum authentifie un token quel que soit le modèle auquel il appartient
 * (User ou Client). Ce middleware vérifie explicitement que l'entité
 * authentifiée est bien un Client, pour empêcher un token "portail" d'être
 * réutilisé sur les routes internes du cabinet, et vice-versa.
 */
class EnsurePortailClient
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() instanceof Client) {
            return response()->json(['message' => 'Accès réservé au portail client.'], 403);
        }

        return $next($request);
    }
}
