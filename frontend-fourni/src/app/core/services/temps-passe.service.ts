import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { TempsPasse } from '../models/temps-passe.model';

@Injectable({ providedIn: 'root' })
export class TempsPasseService {
  private readonly apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) {}

  liste(dossierId: number): Observable<TempsPasse[]> {
    return this.http.get<TempsPasse[]>(`${this.apiUrl}/dossiers/${dossierId}/temps`);
  }

  demarrer(dossierId: number, description?: string): Observable<TempsPasse> {
    return this.http.post<TempsPasse>(`${this.apiUrl}/dossiers/${dossierId}/temps/demarrer`, { description });
  }

  arreter(id: number): Observable<TempsPasse> {
    return this.http.post<TempsPasse>(`${this.apiUrl}/temps/${id}/arreter`, {});
  }

  ajouterManuel(dossierId: number, payload: { description?: string; duree_minutes: number; facturable?: boolean }): Observable<TempsPasse> {
    return this.http.post<TempsPasse>(`${this.apiUrl}/dossiers/${dossierId}/temps`, payload);
  }

  supprimer(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/temps/${id}`);
  }

  /** Chronomètre actuellement en cours pour l'utilisateur connecté, tous dossiers confondus (ou null). */
  enCours(): Observable<TempsPasse | null> {
    return this.http.get<TempsPasse | null>(`${this.apiUrl}/temps/en-cours`);
  }
}
