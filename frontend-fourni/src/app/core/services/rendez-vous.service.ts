import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { RendezVous } from '../models/rendez-vous.model';

@Injectable({ providedIn: 'root' })
export class RendezVousService {
  private readonly apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) {}

  // --- Côté cabinet (authentifié) ---
  liste(statut?: string): Observable<RendezVous[]> {
    return this.http.get<RendezVous[]>(`${this.apiUrl}/rendez-vous`, { params: statut ? { statut } : {} });
  }

  confirmer(id: number): Observable<RendezVous> {
    return this.http.post<RendezVous>(`${this.apiUrl}/rendez-vous/${id}/confirmer`, {});
  }

  annuler(id: number): Observable<RendezVous> {
    return this.http.post<RendezVous>(`${this.apiUrl}/rendez-vous/${id}/annuler`, {});
  }

  // --- Widget public (site vitrine, sans authentification) ---
  creneauxDisponibles(avocatId: number, dateDebut: string, dateFin: string): Observable<string[]> {
    return this.http.get<string[]>(`${this.apiUrl}/public/creneaux`, {
      params: { avocat_id: avocatId, date_debut: dateDebut, date_fin: dateFin },
    });
  }

  reserver(payload: { nom: string; email: string; telephone?: string; motif?: string; avocat_id: number; date_heure: string }): Observable<RendezVous> {
    return this.http.post<RendezVous>(`${this.apiUrl}/public/rendez-vous`, payload);
  }
}
