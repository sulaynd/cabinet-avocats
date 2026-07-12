import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { PortailAuthService } from '../services/portail-auth.service';

export const portailAuthGuard: CanActivateFn = () => {
  const portailAuth = inject(PortailAuthService);
  const router = inject(Router);

  if (!portailAuth.isConnecte()) {
    router.navigate(['/portail/connexion']);
    return false;
  }

  return true;
};
