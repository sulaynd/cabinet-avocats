import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { catchError, map, of } from 'rxjs';
import { AuthService } from '../services/auth.service';

/**
 * Bloque l'accès aux routes protégées si l'utilisateur n'est pas authentifié.
 * Si un token existe mais que l'utilisateur courant n'est pas encore chargé
 * en mémoire (ex. rechargement de page), on le recharge via /api/me avant
 * de trancher.
 */
export const authGuard: CanActivateFn = (route, state) => {
  const auth = inject(AuthService);
  const router = inject(Router);

  if (!auth.isAuthentifie()) {
    router.navigate(['/connexion'], { queryParams: { redirect: state.url } });
    return false;
  }

  if (auth.currentUser()) {
    return true;
  }

  return auth.restaurerSession().pipe(
    map(() => true),
    catchError(() => {
      router.navigate(['/connexion'], { queryParams: { redirect: state.url } });
      return of(false);
    })
  );
};
