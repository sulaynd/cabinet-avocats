import { Injectable, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, tap } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ClientPortail } from '../models/portail-client.model';

/**
 * Service d'authentification du PORTAIL CLIENT — volontairement distinct
 * d'AuthService (comptes internes du cabinet). Stocke son token sous une clé
 * localStorage différente pour ne jamais mélanger les deux sessions dans le
 * même navigateur (utile si un poste du cabinet consulte aussi le portail).
 */
@Injectable({ providedIn: 'root' })
export class PortailAuthService {
  private readonly apiUrl = `${environment.apiUrl}/portail`;
  clientCourant = signal<ClientPortail | null>(null);

  constructor(private http: HttpClient) {}

  connexion(email: string, password: string): Observable<{ client: ClientPortail; token: string }> {
    return this.http.post<{ client: ClientPortail; token: string }>(`${environment.apiUrl}/portail/connexion`, { email, password }).pipe(
      tap((res) => {
        localStorage.setItem('portail_token', res.token);
        this.clientCourant.set(res.client);
      })
    );
  }

  deconnexion(): Observable<void> {
    return this.http.post<void>(`${this.apiUrl}/deconnexion`, {}).pipe(
      tap(() => {
        localStorage.removeItem('portail_token');
        this.clientCourant.set(null);
      })
    );
  }

  getToken(): string | null {
    return localStorage.getItem('portail_token');
  }

  isConnecte(): boolean {
    return !!this.getToken();
  }
}
