import { TestBed } from '@angular/core/testing';
import { HttpClientTestingModule } from '@angular/common/http/testing';
import { RouterTestingModule } from '@angular/router/testing';
import { AccueilComponent } from './accueil.component';

describe('AccueilComponent', () => {
  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [AccueilComponent, HttpClientTestingModule, RouterTestingModule],
    }).compileComponents();
  });

  function creerComposant(): AccueilComponent {
    const fixture = TestBed.createComponent(AccueilComponent);
    return fixture.componentInstance;
  }

  it('se crée correctement', () => {
    expect(creerComposant()).toBeTruthy();
  });

  it('dérive un court monogramme depuis un nom avec tiret (ex: "JCA — Juristyle...")', () => {
    const composant = creerComposant();
    expect(composant.initiales('JCA — Juristyle Conseil & Accompagnement')).toBe('JCA');
  });

  it('dérive des initiales depuis un nom sans segment court (ex: nom complet sans acronyme)', () => {
    const composant = creerComposant();
    expect(composant.initiales('Juristyle Conseil Accompagnement')).toBe('JCA');
  });

  it('renvoie une chaîne vide si aucun nom n\'est fourni', () => {
    const composant = creerComposant();
    expect(composant.initiales(undefined)).toBe('');
  });

  it('dérive les initiales à deux lettres d\'un membre de l\'équipe (ex: "Malick Ndiaye")', () => {
    const composant = creerComposant();
    expect(composant.initialesMembre('Malick Ndiaye')).toBe('MN');
  });
});
