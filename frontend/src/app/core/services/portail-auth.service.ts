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

  moi(): Observable<ClientPortail> {
    return this.http.get<ClientPortail>(`${this.apiUrl}/moi`).pipe(tap((client) => this.clientCourant.set(client)));
  }

  changerMotDePasse(password: string): Observable<{ message: string }> {
    return this.http.post<{ message: string }>(`${this.apiUrl}/changer-mot-de-passe`, { password }).pipe(
      tap(() => {
        const client = this.clientCourant();
        if (client) this.clientCourant.set({ ...client, doit_changer_mot_de_passe: false });
      })
    );
  }

  demanderReinitialisation(email: string): Observable<{ message: string }> {
    return this.http.post<{ message: string }>(`${this.apiUrl}/mot-de-passe-oublie`, { email });
  }

  reinitialiserMotDePasse(email: string, token: string, password: string): Observable<{ message: string }> {
    return this.http.post<{ message: string }>(`${this.apiUrl}/reinitialiser-mot-de-passe`, { email, token, password });
  }

  isConnecte(): boolean {
    return !!this.getToken();
  }
}
