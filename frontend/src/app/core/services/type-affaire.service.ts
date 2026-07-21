import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { TypeAffaire, SousCategorieAffaire } from '../models/type-affaire.model';

@Injectable({ providedIn: 'root' })
export class TypeAffaireService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) {}

  /** Utilisé partout dans l'app (formulaire dossier, rendez-vous public,
   * admin) — l'endpoint public/types-affaire ne nécessite pas d'authentification,
   * mais fonctionne aussi bien pour un usage interne authentifié. */
  liste(actifSeulement = false): Observable<TypeAffaire[]> {
    const params: Record<string, string> = actifSeulement ? { actif: '1' } : {};
    return this.http.get<TypeAffaire[]>(`${this.apiUrl}/public/types-affaire`, { params });
  }

  creerType(libelle: string): Observable<TypeAffaire> {
    return this.http.post<TypeAffaire>(`${this.apiUrl}/types-affaire`, { libelle });
  }

  modifierType(id: number, donnees: { libelle?: string; actif?: boolean; ordre?: number }): Observable<TypeAffaire> {
    return this.http.put<TypeAffaire>(`${this.apiUrl}/types-affaire/${id}`, donnees);
  }

  supprimerType(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/types-affaire/${id}`);
  }

  creerSousCategorie(typeAffaireId: number, libelle: string): Observable<SousCategorieAffaire> {
    return this.http.post<SousCategorieAffaire>(`${this.apiUrl}/types-affaire/${typeAffaireId}/sous-categories`, { libelle });
  }

  modifierSousCategorie(id: number, donnees: { libelle?: string; actif?: boolean; ordre?: number }): Observable<SousCategorieAffaire> {
    return this.http.put<SousCategorieAffaire>(`${this.apiUrl}/sous-categories-affaire/${id}`, donnees);
  }

  supprimerSousCategorie(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/sous-categories-affaire/${id}`);
  }
}
