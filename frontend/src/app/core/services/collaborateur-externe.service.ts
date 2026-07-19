import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { CollaborateurExterne } from '../models/collaborateur-externe.model';

@Injectable({ providedIn: 'root' })
export class CollaborateurExterneService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) {}

  rechercherRepertoire(recherche?: string): Observable<CollaborateurExterne[]> {
    const params: Record<string, string> = recherche ? { recherche } : {};
    return this.http.get<CollaborateurExterne[]>(`${this.apiUrl}/collaborateurs-externes`, { params });
  }

  modifier(id: number, donnees: { nom: string; email: string; organisation?: string; telephone?: string }): Observable<CollaborateurExterne> {
    return this.http.put<CollaborateurExterne>(`${this.apiUrl}/collaborateurs-externes/${id}`, donnees);
  }

  supprimerDuRepertoire(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/collaborateurs-externes/${id}`);
  }

  activer(id: number): Observable<{ message: string }> {
    return this.http.post<{ message: string }>(`${this.apiUrl}/collaborateurs-externes/${id}/activer`, {});
  }

  pourDossier(dossierId: number): Observable<CollaborateurExterne[]> {
    return this.http.get<CollaborateurExterne[]>(`${this.apiUrl}/dossiers/${dossierId}/collaborateurs-externes`);
  }

  creerEtLier(dossierId: number, donnees: { nom: string; email: string; organisation?: string; telephone?: string }): Observable<CollaborateurExterne> {
    return this.http.post<CollaborateurExterne>(`${this.apiUrl}/dossiers/${dossierId}/collaborateurs-externes`, donnees);
  }

  lier(dossierId: number, collaborateurId: number): Observable<CollaborateurExterne[]> {
    return this.http.post<CollaborateurExterne[]>(`${this.apiUrl}/dossiers/${dossierId}/collaborateurs-externes/${collaborateurId}/lier`, {});
  }

  delier(dossierId: number, collaborateurId: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/dossiers/${dossierId}/collaborateurs-externes/${collaborateurId}`);
  }
}
