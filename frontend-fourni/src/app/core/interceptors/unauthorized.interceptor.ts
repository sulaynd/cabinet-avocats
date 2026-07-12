import { HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { catchError, throwError } from 'rxjs';

/**
 * Complète l'AuthGuard (qui ne vérifie qu'au moment de la navigation) : si
 * l'API renvoie 401 en cours d'utilisation (token expiré/révoqué), on nettoie
 * la session locale et on renvoie immédiatement vers /connexion, plutôt que
 * de laisser l'écran dans un état incohérent (données à moitié chargées).
 * Ne s'applique pas aux routes /portail/ (gérées par leur propre intercepteur/guard)
 * ni à /public/ ou aux liens à jeton, qui n'ont pas de notion de session à expirer.
 */
export const unauthorizedInterceptor: HttpInterceptorFn = (req, next) => {
  const router = inject(Router);

  return next(req).pipe(
    catchError((erreur) => {
      const estRoutePortailOuPublique = req.url.includes('/portail/') || req.url.includes('/public/');

      if (erreur.status === 401 && !estRoutePortailOuPublique && !req.url.includes('/login')) {
        localStorage.removeItem('token');
        router.navigate(['/connexion'], { queryParams: { session_expiree: '1' } });
      }

      return throwError(() => erreur);
    })
  );
};
