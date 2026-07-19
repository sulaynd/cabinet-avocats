import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Intervenant, IntervenantPayload } from '../models/intervenant.model';

@Injectable({ providedIn: 'root' })
export class IntervenantService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) {}

  /** Répertoire complet du cabinet, avec recherche facultative — utilisé
   * pour retrouver un intervenant existant à lier à un nouveau dossier. */
  rechercherRepertoire(recherche?: string): Observable<Intervenant[]> {
    const params: Record<string, string> = recherche ? { recherche } : {};
    return this.http.get<Intervenant[]>(`${this.apiUrl}/intervenants`, { params });
  }

  modifier(id: number, donnees: IntervenantPayload): Observable<Intervenant> {
    return this.http.put<Intervenant>(`${this.apiUrl}/intervenants/${id}`, donnees);
  }

  /** Supprime définitivement du répertoire (détaché de tous les dossiers). */
  supprimerDuRepertoire(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/intervenants/${id}`);
  }

  pourDossier(dossierId: number): Observable<Intervenant[]> {
    return this.http.get<Intervenant[]>(`${this.apiUrl}/dossiers/${dossierId}/intervenants`);
  }

  /** Crée un nouvel intervenant et le lie directement à ce dossier. */
  creerEtLier(dossierId: number, donnees: IntervenantPayload): Observable<Intervenant> {
    return this.http.post<Intervenant>(`${this.apiUrl}/dossiers/${dossierId}/intervenants`, donnees);
  }

  /** Lie un intervenant déjà existant du répertoire à ce dossier. */
  lier(dossierId: number, intervenantId: number): Observable<Intervenant[]> {
    return this.http.post<Intervenant[]>(`${this.apiUrl}/dossiers/${dossierId}/intervenants/${intervenantId}/lier`, {});
  }

  /** Retire l'intervenant de ce dossier uniquement (ne le supprime pas du répertoire). */
  delier(dossierId: number, intervenantId: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/dossiers/${dossierId}/intervenants/${intervenantId}`);
  }
}
