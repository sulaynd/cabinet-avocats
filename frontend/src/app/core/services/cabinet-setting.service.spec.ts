import { TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { CabinetSettingService } from './cabinet-setting.service';
import { environment } from '../../../environments/environment';

describe('CabinetSettingService', () => {
  let service: CabinetSettingService;
  let httpMock: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
      providers: [CabinetSettingService],
    });
    service = TestBed.inject(CabinetSettingService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => httpMock.verify());

  it('appelle bien l\'endpoint public (sans authentification) pour les coordonnées', () => {
    service.public().subscribe((res) => {
      expect(res.nom).toBe('JCA');
    });

    const requete = httpMock.expectOne(`${environment.apiUrl}/parametres-cabinet/public`);
    expect(requete.request.method).toBe('GET');
    requete.flush({ nom: 'JCA', adresse: null, telephone: null, email: null });
  });

  it('appelle l\'endpoint admin pour la modification des coordonnées', () => {
    const donnees = { nom: 'JCA modifié', adresse: null, telephone: null, email: null };

    service.modifier(donnees).subscribe((res) => {
      expect(res.nom).toBe('JCA modifié');
    });

    const requete = httpMock.expectOne(`${environment.apiUrl}/parametres-cabinet`);
    expect(requete.request.method).toBe('PUT');
    requete.flush(donnees);
  });
});
