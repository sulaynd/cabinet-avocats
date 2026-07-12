import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from '../services/auth.service';

/**
 * Restreint l'accès à une route selon le(s) rôle(s) autorisé(s), déclarés
 * dans les `data` de la route :
 *
 *   { path: 'factures', component: FactureListComponent,
 *     canActivate: [authGuard, roleGuard], data: { roles: ['admin', 'avocat'] } }
 *
 * À utiliser TOUJOURS après `authGuard` dans le tableau `canActivate`,
 * car il suppose que l'utilisateur courant est déjà chargé.
 */
export const roleGuard: CanActivateFn = (route) => {
  const auth = inject(AuthService);
  const router = inject(Router);

  const rolesAutorises: string[] = route.data?.['roles'] ?? [];

  if (rolesAutorises.length === 0) {
    return true;
  }

  const user = auth.currentUser();

  if (user && rolesAutorises.includes(user.role)) {
    return true;
  }

  router.navigate(['/acces-refuse']);
  return false;
};
