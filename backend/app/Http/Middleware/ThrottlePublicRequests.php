<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Identique au middleware "throttle" standard de Laravel, sauf qu'il exempte
 * complètement les adresses IP listées dans TRUSTED_IPS (.env, séparées par
 * virgule) — typiquement l'IP du cabinet lui-même, pour ne jamais se faire
 * bloquer en testant/démontrant l'application, tout en gardant la protection
 * intacte pour les vrais visiteurs publics.
 *
 * Utilisation dans routes/api.php (référence directe à la classe, sans besoin
 * d'alias enregistré ailleurs) :
 *   ->middleware(\App\Http\Middleware\ThrottlePublicRequests::class . ':30,1')
 */
class ThrottlePublicRequests
{
    public function handle(Request $request, Closure $next, int $maxAttempts = 60, int $decayMinutes = 1): Response
    {
        $ipsDeConfiance = array_filter(array_map('trim', explode(',', env('TRUSTED_IPS', ''))));

        if (in_array($request->ip(), $ipsDeConfiance, true)) {
            return $next($request);
        }

        $cle = 'throttle_public:' . $request->ip() . ':' . $request->path();

        if (RateLimiter::tooManyAttempts($cle, $maxAttempts)) {
            return response()->json(['message' => 'Trop de tentatives, merci de réessayer dans quelques instants.'], 429);
        }

        RateLimiter::hit($cle, $decayMinutes * 60);

        return $next($request);
    }
}
