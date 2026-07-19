import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { CollaborateurAuthService } from '../services/collaborateur-auth.service';

export const collaborateurAuthGuard: CanActivateFn = () => {
  const collaborateurAuth = inject(CollaborateurAuthService);
  const router = inject(Router);

  if (!collaborateurAuth.isConnecte()) {
    router.navigate(['/collaborateur/connexion']);
    return false;
  }

  return true;
};
