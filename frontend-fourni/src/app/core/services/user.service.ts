import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Utilisateur } from '../models/user.model';

interface Paginated<T> {
  data: T[];
  total: number;
  current_page: number;
  last_page: number;
}

export interface UtilisateurPayload {
  name: string;
  email: string;
  password?: string;
  role: Utilisateur['role'];
  phone?: string;
  taux_horaire_defaut?: number | null;
}

@Injectable({ providedIn: 'root' })
export class UserService {
  private readonly apiUrl = `${environment.apiUrl}/users`;

  constructor(private http: HttpClient) {}

  liste(params: Record<string, string | number> = {}): Observable<Paginated<Utilisateur>> {
    return this.http.get<Paginated<Utilisateur>>(this.apiUrl, { params: params as any });
  }

  obtenir(id: number): Observable<Utilisateur> {
    return this.http.get<Utilisateur>(`${this.apiUrl}/${id}`);
  }

  creer(utilisateur: UtilisateurPayload): Observable<Utilisateur> {
    return this.http.post<Utilisateur>(this.apiUrl, utilisateur);
  }

  modifier(id: number, utilisateur: Partial<UtilisateurPayload>): Observable<Utilisateur> {
    return this.http.put<Utilisateur>(`${this.apiUrl}/${id}`, utilisateur);
  }

  supprimer(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/${id}`);
  }
}
