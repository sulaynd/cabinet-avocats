import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { QuestionnairePublicPayload } from '../models/reponse-questionnaire.model';

/** Service PUBLIC (pas d'authentification) pour la page de questionnaire par jeton. */
@Injectable({ providedIn: 'root' })
export class QuestionnairePublicService {
  private readonly apiUrl = `${environment.apiUrl}/questionnaire`;

  constructor(private http: HttpClient) {}

  afficher(token: string): Observable<QuestionnairePublicPayload> {
    return this.http.get<QuestionnairePublicPayload>(`${this.apiUrl}/${token}`);
  }

  soumettre(token: string, reponses: Record<string, any>): Observable<{ message: string }> {
    return this.http.post<{ message: string }>(`${this.apiUrl}/${token}`, { reponses });
  }
}
