import { HttpInterceptorFn } from '@angular/common/http';

/**
 * N'ajoute le token du portail collaborateur que sur les requêtes vers
 * /collaborateur/ — jamais sur le reste de l'API. La route de connexion
 * elle-même (/collaborateur/connexion) ne reçoit pas de token.
 */
export const collaborateurAuthInterceptor: HttpInterceptorFn = (req, next) => {
  if (!req.url.includes('/collaborateur/') || req.url.includes('/collaborateur/connexion')) {
    return next(req);
  }

  const token = localStorage.getItem('collaborateur_token');

  if (token) {
    req = req.clone({
      setHeaders: { Authorization: `Bearer ${token}` },
    });
  }

  return next(req);
};
