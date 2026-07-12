import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Communication } from '../models/communication.model';

@Injectable({ providedIn: 'root' })
export class CommunicationService {
  private readonly apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) {}

  liste(dossierId: number): Observable<Communication[]> {
    return this.http.get<Communication[]>(`${this.apiUrl}/dossiers/${dossierId}/communications`);
  }

  creer(dossierId: number, payload: Partial<Communication>): Observable<Communication> {
    return this.http.post<Communication>(`${this.apiUrl}/dossiers/${dossierId}/communications`, payload);
  }

  modifier(id: number, payload: Partial<Communication>): Observable<Communication> {
    return this.http.put<Communication>(`${this.apiUrl}/communications/${id}`, payload);
  }

  supprimer(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/communications/${id}`);
  }
}
