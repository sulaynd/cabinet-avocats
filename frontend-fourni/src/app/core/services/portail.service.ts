import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Dossier } from '../models/dossier.model';
import { Facture } from '../models/facture.model';

@Injectable({ providedIn: 'root' })
export class PortailService {
  private readonly apiUrl = `${environment.apiUrl}/portail`;

  constructor(private http: HttpClient) {}

  mesDossiers(): Observable<Dossier[]> {
    return this.http.get<Dossier[]>(`${this.apiUrl}/mes-dossiers`);
  }

  monDossier(id: number): Observable<Dossier> {
    return this.http.get<Dossier>(`${this.apiUrl}/dossiers/${id}`);
  }

  mesFactures(): Observable<Facture[]> {
    return this.http.get<Facture[]>(`${this.apiUrl}/mes-factures`);
  }

  telechargerDocument(id: number, nomOriginal: string): void {
    this.http.get(`${this.apiUrl}/documents/${id}/telecharger`, { responseType: 'blob' }).subscribe((blob) => {
      const url = window.URL.createObjectURL(blob);
      const lien = document.createElement('a');
      lien.href = url;
      lien.download = nomOriginal;
      lien.click();
      window.URL.revokeObjectURL(url);
    });
  }

  signerDocument(id: number, nomSignataire: string): Observable<any> {
    return this.http.post(`${this.apiUrl}/documents/${id}/signer`, { nom_signataire: nomSignataire });
  }
}
