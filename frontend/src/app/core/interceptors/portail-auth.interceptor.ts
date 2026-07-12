import { HttpInterceptorFn } from '@angular/common/http';

/**
 * N'ajoute le token du portail client que sur les requêtes vers /portail/ —
 * jamais sur le reste de l'API (voir auth.interceptor.ts pour le token cabinet).
 * La route de connexion elle-même (/portail/connexion) ne doit pas recevoir de
 * token (il n'y en a pas encore à ce stade).
 */
export const portailAuthInterceptor: HttpInterceptorFn = (req, next) => {
  if (!req.url.includes('/portail/') || req.url.includes('/portail/connexion')) {
    return next(req);
  }

  const token = localStorage.getItem('portail_token');

  if (token) {
    req = req.clone({
      setHeaders: { Authorization: `Bearer ${token}` },
    });
  }

  return next(req);
};
