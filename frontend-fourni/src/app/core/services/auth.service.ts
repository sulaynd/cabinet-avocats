import { Injectable, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, tap } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Utilisateur } from '../models/user.model';

export type { Utilisateur };

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly apiUrl = environment.apiUrl;
  currentUser = signal<Utilisateur | null>(null);

  constructor(private http: HttpClient) {}

  login(email: string, password: string): Observable<{ user: Utilisateur; token: string }> {
    return this.http.post<{ user: Utilisateur; token: string }>(`${this.apiUrl}/login`, { email, password }).pipe(
      tap((res) => {
        localStorage.setItem('token', res.token);
        this.currentUser.set(res.user);
      })
    );
  }

  logout(): Observable<void> {
    return this.http.post<void>(`${this.apiUrl}/logout`, {}).pipe(
      tap(() => {
        localStorage.removeItem('token');
        this.currentUser.set(null);
      })
    );
  }

  chargerUtilisateurCourant(): Observable<Utilisateur> {
    return this.http.get<Utilisateur>(`${this.apiUrl}/me`).pipe(tap((user) => this.currentUser.set(user)));
  }

  getToken(): string | null {
    return localStorage.getItem('token');
  }

  isAuthentifie(): boolean {
    return !!this.getToken();
  }

  /** Vérifie si l'utilisateur courant a l'un des rôles donnés. */
  hasRole(...roles: Array<Utilisateur['role']>): boolean {
    const user = this.currentUser();
    return !!user && roles.includes(user.role);
  }

  /**
   * À appeler une fois au démarrage de l'app (ex. dans un APP_INITIALIZER
   * ou dans le AuthGuard) pour restaurer la session à partir du token stocké.
   */
  restaurerSession(): Observable<Utilisateur> {
    return this.chargerUtilisateurCourant();
  }
}
