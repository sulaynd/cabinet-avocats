import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Questionnaire } from '../models/questionnaire.model';
import { ReponseQuestionnaire } from '../models/reponse-questionnaire.model';

@Injectable({ providedIn: 'root' })
export class QuestionnaireService {
  private readonly apiUrl = `${environment.apiUrl}/questionnaires`;

  constructor(private http: HttpClient) {}

  liste(): Observable<Questionnaire[]> {
    return this.http.get<Questionnaire[]>(this.apiUrl);
  }

  obtenir(id: number): Observable<Questionnaire> {
    return this.http.get<Questionnaire>(`${this.apiUrl}/${id}`);
  }

  creer(payload: Partial<Questionnaire>): Observable<Questionnaire> {
    return this.http.post<Questionnaire>(this.apiUrl, payload);
  }

  modifier(id: number, payload: Partial<Questionnaire>): Observable<Questionnaire> {
    return this.http.put<Questionnaire>(`${this.apiUrl}/${id}`, payload);
  }

  supprimer(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/${id}`);
  }

  // --- Réponses par dossier ---
  reponsesDuDossier(dossierId: number): Observable<ReponseQuestionnaire[]> {
    return this.http.get<ReponseQuestionnaire[]>(`${environment.apiUrl}/dossiers/${dossierId}/reponses-questionnaires`);
  }

  renvoyer(dossierId: number): Observable<ReponseQuestionnaire> {
    return this.http.post<ReponseQuestionnaire>(`${environment.apiUrl}/dossiers/${dossierId}/renvoyer-questionnaire`, {});
  }
}
