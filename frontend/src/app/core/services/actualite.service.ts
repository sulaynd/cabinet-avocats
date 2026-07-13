import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Actualite, ActualitePayload } from '../models/actualite.model';

@Injectable({ providedIn: 'root' })
export class ActualiteService {
  private apiUrl = `${environment.apiUrl}/actualites`;

  constructor(private http: HttpClient) {}

  public(): Observable<Actualite[]> {
    return this.http.get<Actualite[]>(`${environment.apiUrl}/actualites/public`);
  }

  liste(): Observable<Actualite[]> {
    return this.http.get<Actualite[]>(this.apiUrl);
  }

  creer(donnees: ActualitePayload): Observable<Actualite> {
    return this.http.post<Actualite>(this.apiUrl, donnees);
  }

  modifier(id: number, donnees: ActualitePayload): Observable<Actualite> {
    return this.http.put<Actualite>(`${this.apiUrl}/${id}`, donnees);
  }

  supprimer(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/${id}`);
  }
}
