import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { StatistiquesTableauDeBord } from '../models/tableau-de-bord.model';

@Injectable({ providedIn: 'root' })
export class TableauDeBordService {
  constructor(private http: HttpClient) {}

  obtenir(): Observable<StatistiquesTableauDeBord> {
    return this.http.get<StatistiquesTableauDeBord>(`${environment.apiUrl}/tableau-de-bord`);
  }
}
