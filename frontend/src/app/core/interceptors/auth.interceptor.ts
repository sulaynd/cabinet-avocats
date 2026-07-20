import { HttpInterceptorFn } from '@angular/common/http';

export const authInterceptor: HttpInterceptorFn = (req, next) => {
  // Les routes du portail client et du portail collaborateur utilisent leur
  // propre token (voir portail-auth.interceptor.ts et
  // collaborateur-auth.interceptor.ts) : on ne doit jamais y injecter le
  // token du cabinet, au risque d'authentifier la requête comme le mauvais
  // type de compte (ex: un membre du cabinet à la place du collaborateur).
  if (req.url.includes('/portail/') || req.url.includes('/collaborateur/')) {
    return next(req);
  }

  const token = localStorage.getItem('token');

  if (token) {
    req = req.clone({
      setHeaders: { Authorization: `Bearer ${token}` },
    });
  }

  return next(req);
};
