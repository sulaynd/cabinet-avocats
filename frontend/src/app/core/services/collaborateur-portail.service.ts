import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

@Injectable({ providedIn: 'root' })
export class CollaborateurPortailService {
  private readonly apiUrl = `${environment.apiUrl}/collaborateur`;

  constructor(private http: HttpClient) {}

  mesDossiers(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/mes-dossiers`);
  }

  documentsDuDossier(dossierId: number): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/dossiers/${dossierId}/documents`);
  }

  televerser(dossierId: number, fichier: File): Observable<any> {
    const formData = new FormData();
    formData.append('fichier', fichier);
    return this.http.post<any>(`${this.apiUrl}/dossiers/${dossierId}/documents`, formData);
  }

  telecharger(documentId: number): Observable<Blob> {
    return this.http.get(`${this.apiUrl}/documents/${documentId}/telecharger`, { responseType: 'blob' });
  }
}
