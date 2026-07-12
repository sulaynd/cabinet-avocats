import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { LiensIcal } from '../models/ical.model';

@Injectable({ providedIn: 'root' })
export class IcalService {
  private readonly apiUrl = `${environment.apiUrl}/ical`;

  constructor(private http: HttpClient) {}

  mesLiens(): Observable<LiensIcal> {
    return this.http.get<LiensIcal>(`${this.apiUrl}/mes-liens`);
  }

  regenererPersonnel(): Observable<{ personnel: string }> {
    return this.http.post<{ personnel: string }>(`${this.apiUrl}/regenerer-personnel`, {});
  }

  regenererEquipe(): Observable<{ equipe: string }> {
    return this.http.post<{ equipe: string }>(`${this.apiUrl}/regenerer-equipe`, {});
  }

  /** Convertit une URL https://... en lien webcal:// pour ouverture directe par le logiciel d'agenda. */
  versWebcal(url: string): string {
    return url.replace(/^https?:\/\//, 'webcal://');
  }
}
