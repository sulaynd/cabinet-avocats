import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Dossier } from '../models/dossier.model';
import { Echeance } from '../models/echeance.model';
import { DocumentFile } from '../models/document.model';
import { Facture } from '../models/facture.model';

interface Paginated<T> {
  data: T[];
  total: number;
  current_page: number;
  last_page: number;
}

@Injectable({ providedIn: 'root' })
export class DossierService {
  private readonly apiUrl = `${environment.apiUrl}/dossiers`;

  constructor(private http: HttpClient) {}

  liste(params: Record<string, string | number> = {}): Observable<Paginated<Dossier>> {
    return this.http.get<Paginated<Dossier>>(this.apiUrl, { params: params as any });
  }

  obtenir(id: number): Observable<Dossier> {
    return this.http.get<Dossier>(`${this.apiUrl}/${id}`);
  }

  creer(dossier: Partial<Dossier>): Observable<Dossier> {
    return this.http.post<Dossier>(this.apiUrl, dossier);
  }

  modifier(id: number, dossier: Partial<Dossier>): Observable<Dossier> {
    return this.http.put<Dossier>(`${this.apiUrl}/${id}`, dossier);
  }

  supprimer(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/${id}`);
  }

  echeances(id: number): Observable<Echeance[]> {
    return this.http.get<Echeance[]>(`${this.apiUrl}/${id}/echeances`);
  }

  documents(id: number): Observable<DocumentFile[]> {
    return this.http.get<DocumentFile[]>(`${this.apiUrl}/${id}/documents`);
  }

  factures(id: number): Observable<Facture[]> {
    return this.http.get<Facture[]>(`${this.apiUrl}/${id}/factures`);
  }

  uploaderDocument(id: number, fichier: File, type: string, necessiteSignature = false): Observable<DocumentFile> {
    const formData = new FormData();
    formData.append('fichier', fichier);
    formData.append('type', type);
    formData.append('necessite_signature', necessiteSignature ? '1' : '0');

    return this.http.post<DocumentFile>(`${this.apiUrl}/${id}/documents`, formData);
  }

  /** Assigne/réassigne l'avocat responsable et l'assistant traitant — réservé admin. */
  assigner(id: number, payload: { avocat_id: number; assistant_id?: number | null; stagiaire_id?: number | null }): Observable<Dossier> {
    return this.http.post<Dossier>(`${this.apiUrl}/${id}/assigner`, payload);
  }

  /** Automatise la création d'un mémoire d'honoraires (forfait ou temps passé non facturé). */
  genererFactureDepuisTemps(id: number): Observable<Facture> {
    return this.http.post<Facture>(`${this.apiUrl}/${id}/factures/generer-depuis-temps`, {});
  }

  /** Suggère l'avocat à assigner selon la spécialité et la charge de travail — simple suggestion, jamais imposée. */
  suggererAvocat(typeAffaire?: string): Observable<{ avocat_id: number | null; nom?: string; raison?: string }> {
    const params: Record<string, string> = typeAffaire ? { type_affaire: typeAffaire } : {};
    return this.http.get<{ avocat_id: number | null; nom?: string; raison?: string }>(`${this.apiUrl}/suggerer-avocat`, { params });
  }
}
