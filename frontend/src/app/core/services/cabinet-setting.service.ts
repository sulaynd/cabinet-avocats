import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { CoordonneesCabinet } from '../models/cabinet-setting.model';

@Injectable({ providedIn: 'root' })
export class CabinetSettingService {
  constructor(private http: HttpClient) {}

  /** Endpoint public (hors Sanctum) — utilisable avant authentification. */
  public(): Observable<CoordonneesCabinet> {
    return this.http.get<CoordonneesCabinet>(`${environment.apiUrl}/parametres-cabinet/public`);
  }

  /** Réservé à l'admin — coordonnées complètes pour l'écran de paramètres. */
  obtenir(): Observable<CoordonneesCabinet> {
    return this.http.get<CoordonneesCabinet>(`${environment.apiUrl}/parametres-cabinet`);
  }

  modifier(donnees: CoordonneesCabinet): Observable<CoordonneesCabinet> {
    return this.http.put<CoordonneesCabinet>(`${environment.apiUrl}/parametres-cabinet`, donnees);
  }

  televerserPhoto(fichier: File): Observable<CoordonneesCabinet> {
    const formData = new FormData();
    formData.append('photo', fichier);
    return this.http.post<CoordonneesCabinet>(`${environment.apiUrl}/parametres-cabinet/photo`, formData);
  }
}
