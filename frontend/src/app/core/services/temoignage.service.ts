import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Temoignage, TemoignageAdmin } from '../models/temoignage.model';

@Injectable({ providedIn: 'root' })
export class TemoignageService {
  private apiUrl = `${environment.apiUrl}/temoignages`;

  constructor(private http: HttpClient) {}

  // --- Page d'accueil publique ---
  public(): Observable<Temoignage[]> {
    return this.http.get<Temoignage[]>(`${environment.apiUrl}/temoignages/public`);
  }

  // --- Admin (cabinet) ---
  liste(): Observable<TemoignageAdmin[]> {
    return this.http.get<TemoignageAdmin[]>(this.apiUrl);
  }

  /** Approuve/masque un témoignage — n'en modifie jamais le texte (ce sont les mots du client). */
  modifier(id: number, actif: boolean, ordre?: number): Observable<TemoignageAdmin> {
    return this.http.put<TemoignageAdmin>(`${this.apiUrl}/${id}`, { actif, ordre });
  }

  supprimer(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/${id}`);
  }

  // --- Portail client ---
  soumettreDepuisPortail(texte: string): Observable<TemoignageAdmin> {
    return this.http.post<TemoignageAdmin>(`${environment.apiUrl}/portail/temoignage`, { texte });
  }

  monTemoignage(): Observable<TemoignageAdmin | null> {
    return this.http.get<TemoignageAdmin | null>(`${environment.apiUrl}/portail/mon-temoignage`);
  }
}
