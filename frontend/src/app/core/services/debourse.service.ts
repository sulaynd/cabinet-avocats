import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Debourse, DeboursePayload } from '../models/debourse.model';

@Injectable({ providedIn: 'root' })
export class DebourseService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) {}

  liste(dossierId: number): Observable<Debourse[]> {
    return this.http.get<Debourse[]>(`${this.apiUrl}/dossiers/${dossierId}/debourses`);
  }

  creer(dossierId: number, donnees: DeboursePayload): Observable<Debourse> {
    return this.http.post<Debourse>(`${this.apiUrl}/dossiers/${dossierId}/debourses`, donnees);
  }

  modifier(id: number, donnees: DeboursePayload): Observable<Debourse> {
    return this.http.put<Debourse>(`${this.apiUrl}/debourses/${id}`, donnees);
  }

  supprimer(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/debourses/${id}`);
  }
}
