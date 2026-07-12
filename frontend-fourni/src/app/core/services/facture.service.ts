import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Facture } from '../models/facture.model';

interface Paginated<T> {
  data: T[];
  total: number;
  current_page: number;
  last_page: number;
}

@Injectable({ providedIn: 'root' })
export class FactureService {
  private readonly apiUrl = `${environment.apiUrl}/factures`;

  constructor(private http: HttpClient) {}

  liste(params: Record<string, string | number> = {}): Observable<Paginated<Facture>> {
    return this.http.get<Paginated<Facture>>(this.apiUrl, { params: params as any });
  }

  obtenir(id: number): Observable<Facture> {
    return this.http.get<Facture>(`${this.apiUrl}/${id}`);
  }

  creer(facture: Partial<Facture>): Observable<Facture> {
    return this.http.post<Facture>(this.apiUrl, facture);
  }

  marquerPayee(id: number): Observable<Facture> {
    return this.http.post<Facture>(`${this.apiUrl}/${id}/marquer-payee`, {});
  }

  envoyerParEmail(id: number): Observable<Facture> {
    return this.http.post<Facture>(`${this.apiUrl}/${id}/envoyer`, {});
  }

  supprimer(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/${id}`);
  }

  /** Télécharge le PDF de la facture et déclenche l'enregistrement dans le navigateur. */
  telechargerPdf(id: number, numero: string): void {
    this.http.get(`${this.apiUrl}/${id}/pdf`, { responseType: 'blob' }).subscribe((blob) => {
      const url = window.URL.createObjectURL(blob);
      const lien = document.createElement('a');
      lien.href = url;
      lien.download = `${numero}.pdf`;
      lien.click();
      window.URL.revokeObjectURL(url);
    });
  }
}
