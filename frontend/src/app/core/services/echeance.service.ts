import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Echeance } from '../models/echeance.model';

@Injectable({ providedIn: 'root' })
export class EcheanceService {
  private readonly apiUrl = `${environment.apiUrl}/echeances`;

  constructor(private http: HttpClient) {}

  liste(params: Record<string, string | number> = {}): Observable<Echeance[]> {
    return this.http.get<Echeance[]>(this.apiUrl, { params: params as any });
  }

  obtenir(id: number): Observable<Echeance> {
    return this.http.get<Echeance>(`${this.apiUrl}/${id}`);
  }

  creer(echeance: Partial<Echeance>): Observable<Echeance> {
    return this.http.post<Echeance>(this.apiUrl, echeance);
  }

  modifier(id: number, echeance: Partial<Echeance>): Observable<Echeance> {
    return this.http.put<Echeance>(`${this.apiUrl}/${id}`, echeance);
  }

  supprimer(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/${id}`);
  }
}
