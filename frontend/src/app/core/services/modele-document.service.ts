import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ModeleDocument } from '../models/modele-document.model';

@Injectable({ providedIn: 'root' })
export class ModeleDocumentService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) {}

  liste(): Observable<ModeleDocument[]> {
    return this.http.get<ModeleDocument[]>(`${this.apiUrl}/modeles-documents`);
  }

  pourDossier(dossierId: number): Observable<ModeleDocument[]> {
    return this.http.get<ModeleDocument[]>(`${this.apiUrl}/dossiers/${dossierId}/modeles-documents`);
  }

  televerser(nom: string, description: string, typeAffaire: string | null, fichier: File): Observable<ModeleDocument> {
    const formData = new FormData();
    formData.append('nom', nom);
    if (description) formData.append('description', description);
    if (typeAffaire) formData.append('type_affaire', typeAffaire);
    formData.append('fichier', fichier);
    return this.http.post<ModeleDocument>(`${this.apiUrl}/modeles-documents`, formData);
  }

  supprimer(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/modeles-documents/${id}`);
  }

  /** Génère le document fusionné — renvoie directement le fichier binaire à télécharger. */
  generer(dossierId: number, modeleId: number): Observable<Blob> {
    return this.http.post(`${this.apiUrl}/dossiers/${dossierId}/generer-document`, { modele_id: modeleId }, { responseType: 'blob' });
  }
}
