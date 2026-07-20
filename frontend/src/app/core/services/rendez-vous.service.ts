import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { RendezVous } from '../models/rendez-vous.model';

interface Paginated<T> {
  data: T[];
  total: number;
  current_page: number;
  last_page: number;
}

@Injectable({ providedIn: 'root' })
export class RendezVousService {
  private readonly apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) {}

  // --- Côté cabinet (authentifié) ---
  liste(params: { statut?: string; page?: number; per_page?: number } = {}): Observable<Paginated<RendezVous>> {
    return this.http.get<Paginated<RendezVous>>(`${this.apiUrl}/rendez-vous`, { params: params as any });
  }

  confirmer(id: number, avocatId: number, montantConsultation?: number | null, lienRencontre?: string | null, dureeMinutes?: number | null): Observable<RendezVous> {
    return this.http.post<RendezVous>(`${this.apiUrl}/rendez-vous/${id}/confirmer`, {
      avocat_id: avocatId,
      montant_consultation: montantConsultation ?? null,
      lien_rencontre: lienRencontre ?? null,
      duree_minutes: dureeMinutes ?? 60,
    });
  }

  annuler(id: number): Observable<RendezVous> {
    return this.http.post<RendezVous>(`${this.apiUrl}/rendez-vous/${id}/annuler`, {});
  }

  // --- Widget public (site vitrine, sans authentification) ---
  creneauxDisponibles(dateDebut: string, dateFin: string): Observable<string[]> {
    return this.http.get<string[]>(`${this.apiUrl}/public/creneaux`, {
      params: { date_debut: dateDebut, date_fin: dateFin },
    });
  }

  reserver(payload: { nom: string; email: string; telephone?: string; motif: string; type_affaire: string; date_heure: string }): Observable<RendezVous> {
    return this.http.post<RendezVous>(`${this.apiUrl}/public/rendez-vous`, payload);
  }
}
