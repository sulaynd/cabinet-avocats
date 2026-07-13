import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { OffreEmploi, OffreEmploiPayload } from '../models/offre-emploi.model';

@Injectable({ providedIn: 'root' })
export class OffreEmploiService {
  private apiUrl = `${environment.apiUrl}/offres-emploi`;

  constructor(private http: HttpClient) {}

  public(): Observable<OffreEmploi[]> {
    return this.http.get<OffreEmploi[]>(`${environment.apiUrl}/offres-emploi/public`);
  }

  liste(): Observable<OffreEmploi[]> {
    return this.http.get<OffreEmploi[]>(this.apiUrl);
  }

  creer(donnees: OffreEmploiPayload): Observable<OffreEmploi> {
    return this.http.post<OffreEmploi>(this.apiUrl, donnees);
  }

  modifier(id: number, donnees: OffreEmploiPayload): Observable<OffreEmploi> {
    return this.http.put<OffreEmploi>(`${this.apiUrl}/${id}`, donnees);
  }

  supprimer(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/${id}`);
  }
}
