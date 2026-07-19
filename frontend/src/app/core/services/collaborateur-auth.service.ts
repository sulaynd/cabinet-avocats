import { Injectable, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, tap } from 'rxjs';
import { environment } from '../../../environments/environment';
import { CollaborateurExterne } from '../models/collaborateur-externe.model';

/**
 * Service d'authentification du PORTAIL COLLABORATEUR EXTERNE — distinct des
 * comptes internes (AuthService) et du portail client (PortailAuthService).
 * Stocke son token sous une clé localStorage propre, pour ne jamais mélanger
 * les sessions dans le même navigateur.
 */
@Injectable({ providedIn: 'root' })
export class CollaborateurAuthService {
  private readonly apiUrl = `${environment.apiUrl}/collaborateur`;
  collaborateurCourant = signal<CollaborateurExterne | null>(null);

  constructor(private http: HttpClient) {}

  connexion(email: string, password: string): Observable<{ collaborateur: CollaborateurExterne; token: string }> {
    return this.http.post<{ collaborateur: CollaborateurExterne; token: string }>(`${environment.apiUrl}/collaborateur/connexion`, { email, password }).pipe(
      tap((res) => {
        localStorage.setItem('collaborateur_token', res.token);
        this.collaborateurCourant.set(res.collaborateur);
      })
    );
  }

  deconnexion(): Observable<void> {
    return this.http.post<void>(`${this.apiUrl}/deconnexion`, {}).pipe(
      tap(() => {
        localStorage.removeItem('collaborateur_token');
        this.collaborateurCourant.set(null);
      })
    );
  }

  getToken(): string | null {
    return localStorage.getItem('collaborateur_token');
  }

  moi(): Observable<CollaborateurExterne> {
    return this.http.get<CollaborateurExterne>(`${this.apiUrl}/moi`).pipe(tap((c) => this.collaborateurCourant.set(c)));
  }

  changerMotDePasse(password: string): Observable<{ message: string }> {
    return this.http.post<{ message: string }>(`${this.apiUrl}/changer-mot-de-passe`, { password }).pipe(
      tap(() => {
        const c = this.collaborateurCourant();
        if (c) this.collaborateurCourant.set({ ...c, doit_changer_mot_de_passe: false });
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
