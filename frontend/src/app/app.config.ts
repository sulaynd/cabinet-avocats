import { ApplicationConfig, LOCALE_ID } from '@angular/core';
import { provideRouter } from '@angular/router';
import { provideHttpClient, withInterceptors } from '@angular/common/http';
import { provideAnimationsAsync } from '@angular/platform-browser/animations/async';
import { registerLocaleData } from '@angular/common';
import localeFrCA from '@angular/common/locales/fr-CA';
import { routes } from './app.routes';
import { authInterceptor } from './core/interceptors/auth.interceptor';
import { portailAuthInterceptor } from './core/interceptors/portail-auth.interceptor';
import { collaborateurAuthInterceptor } from './core/interceptors/collaborateur-auth.interceptor';
import { unauthorizedInterceptor } from './core/interceptors/unauthorized.interceptor';

// Locale fr-CA : virgule décimale, espace avant le symbole monétaire, dates en
// français canadien — cohérent avec le format déjà utilisé dans les PDF/emails
// de facture (number_format($x, 2, ',', ' ')).
registerLocaleData(localeFrCA);

export const appConfig: ApplicationConfig = {
  providers: [
    { provide: LOCALE_ID, useValue: 'fr-CA' },
    provideRouter(routes),
    provideHttpClient(withInterceptors([authInterceptor, portailAuthInterceptor, collaborateurAuthInterceptor, unauthorizedInterceptor])),
    provideAnimationsAsync(),
  ],
};
