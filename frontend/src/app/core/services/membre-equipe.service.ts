import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { MembreEquipe, MembreEquipePayload } from '../models/membre-equipe.model';

@Injectable({ providedIn: 'root' })
export class MembreEquipeService {
  private apiUrl = `${environment.apiUrl}/membres-equipe`;

  constructor(private http: HttpClient) {}

  /** Endpoint public (hors Sanctum) — pour la page d'accueil. */
  public(): Observable<MembreEquipe[]> {
    return this.http.get<MembreEquipe[]>(`${environment.apiUrl}/membres-equipe/public`);
  }

  liste(): Observable<MembreEquipe[]> {
    return this.http.get<MembreEquipe[]>(this.apiUrl);
  }

  creer(donnees: MembreEquipePayload): Observable<MembreEquipe> {
    return this.http.post<MembreEquipe>(this.apiUrl, donnees);
  }

  modifier(id: number, donnees: MembreEquipePayload): Observable<MembreEquipe> {
    return this.http.put<MembreEquipe>(`${this.apiUrl}/${id}`, donnees);
  }

  supprimer(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/${id}`);
  }

  televerserPhoto(id: number, fichier: File): Observable<MembreEquipe> {
    const formData = new FormData();
    formData.append('photo', fichier);
    return this.http.post<MembreEquipe>(`${this.apiUrl}/${id}/photo`, formData);
  }
}
