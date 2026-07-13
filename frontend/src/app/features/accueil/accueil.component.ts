import { Component, OnInit, ViewChild, ElementRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { CabinetSettingService } from '../../core/services/cabinet-setting.service';
import { CoordonneesCabinet } from '../../core/models/cabinet-setting.model';
import { MembreEquipeService } from '../../core/services/membre-equipe.service';
import { MembreEquipe } from '../../core/models/membre-equipe.model';
import { TemoignageService } from '../../core/services/temoignage.service';
import { Temoignage } from '../../core/models/temoignage.model';
import { OffreEmploiService } from '../../core/services/offre-emploi.service';
import { OffreEmploi } from '../../core/models/offre-emploi.model';
import { ActualiteService } from '../../core/services/actualite.service';
import { Actualite } from '../../core/models/actualite.model';

@Component({
  selector: 'app-accueil',
  standalone: true,
  imports: [CommonModule, RouterLink, MatButtonModule, MatIconModule],
  templateUrl: './accueil.component.html',
})
export class AccueilComponent implements OnInit {
  @ViewChild('temoignagesScroll') temoignagesScroll?: ElementRef<HTMLDivElement>;
  @ViewChild('servicesScroll') servicesScroll?: ElementRef<HTMLDivElement>;

  cabinet: CoordonneesCabinet | null = null;
  equipe: MembreEquipe[] = [];
  temoignages: Temoignage[] = [];
  offresEmploi: OffreEmploi[] = [];
  actualites: Actualite[] = [];
  readonly anneeCourante = new Date().getFullYear();

  readonly services = [
    { icone: 'flight_takeoff', titre: 'Immigration & mobilité internationale', description: "Accompagnement complet dans vos démarches d'immigration, de résidence et de mobilité à l'international." },
    { icone: 'groups', titre: 'Recrutement international', description: 'Mise en relation entre employeurs et talents à travers le monde, conformité aux exigences légales.' },
    { icone: 'public', titre: 'Coopération internationale', description: 'Appui aux projets de coopération entre organisations, gouvernements et partenaires internationaux.' },
    { icone: 'trending_up', titre: 'Développement international', description: 'Conseil stratégique pour des projets de développement durable à l\'échelle internationale.' },
    { icone: 'volunteer_activism', titre: 'Action humanitaire', description: "Soutien juridique et opérationnel aux organisations humanitaires et à leurs missions." },
    { icone: 'business_center', titre: 'Services-conseils stratégiques', description: 'Conseils stratégiques sur mesure pour entreprises, gouvernements et organisations internationales.' },
  ];

  readonly publicsCibles = [
    'Particuliers', 'Employeurs', 'Entreprises', 'Étudiants', 'Travailleurs',
    'Investisseurs', 'Gouvernements', 'ONG', 'Organisations internationales', 'Partenaires',
  ];

  readonly valeurs = [
    { icone: 'military_tech', nom: 'Excellence' },
    { icone: 'shield', nom: 'Intégrité' },
    { icone: 'workspace_premium', nom: 'Professionnalisme' },
    { icone: 'lightbulb', nom: 'Innovation' },
    { icone: 'diversity_3', nom: 'Inclusion' },
    { icone: 'eco', nom: 'Responsabilité sociale' },
  ];

  constructor(
    private cabinetSettingService: CabinetSettingService,
    private membreEquipeService: MembreEquipeService,
    private temoignageService: TemoignageService,
    private offreEmploiService: OffreEmploiService,
    private actualiteService: ActualiteService
  ) {}

  ngOnInit(): void {
    this.cabinetSettingService.public().subscribe((c) => (this.cabinet = c));
    this.membreEquipeService.public().subscribe((m) => (this.equipe = m));
    this.temoignageService.public().subscribe((t) => (this.temoignages = t));
    this.offreEmploiService.public().subscribe((o) => (this.offresEmploi = o));
    this.actualiteService.public().subscribe((a) => (this.actualites = a));
  }

  initiales(nom: string | undefined): string {
    if (!nom) return '';
    const premierSegment = nom.split(/—|–|-/)[0].trim();
    if (premierSegment.length <= 6) return premierSegment;
    return nom
      .split(/\s+/)
      .filter((mot) => mot.length > 2)
      .slice(0, 3)
      .map((mot) => mot[0].toUpperCase())
      .join('');
  }

  /** Méthode générique de défilement, réutilisable pour n'importe quelle rangée
   * (services, témoignages, ou chaque bande de l'équipe) via une référence de
   * template locale passée directement en paramètre, sans @ViewChild dédié. */
  defilerVersLeBas(): void {
    document.getElementById('pied-de-page')?.scrollIntoView({ behavior: 'smooth', block: 'end' });
  }

  defilerVersLeHaut(): void {
    document.getElementById('haut-de-page')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  defilerRangee(conteneur: HTMLElement, direction: 'gauche' | 'droite'): void {
    const decalage = direction === 'gauche' ? -300 : 300;
    conteneur.scrollBy({ left: decalage, behavior: 'smooth' });
  }

  defilerServices(direction: 'gauche' | 'droite'): void {
    const conteneur = this.servicesScroll?.nativeElement;
    if (!conteneur) return;
    const decalage = direction === 'gauche' ? -320 : 320;
    conteneur.scrollBy({ left: decalage, behavior: 'smooth' });
  }

  /** Fait défiler la rangée de témoignages au clic des flèches, utile quand
   * leur nombre grandit avec le temps (au lieu d'un simple empilement vertical). */
  defilerTemoignages(direction: 'gauche' | 'droite'): void {
    const conteneur = this.temoignagesScroll?.nativeElement;
    if (!conteneur) return;
    const decalage = direction === 'gauche' ? -340 : 340;
    conteneur.scrollBy({ left: decalage, behavior: 'smooth' });
  }

  get equipeAvocats(): MembreEquipe[] {
    return this.equipe.filter((m) => m.role === 'admin' || m.role === 'avocat');
  }

  get equipeAssistants(): MembreEquipe[] {
    return this.equipe.filter((m) => m.role === 'assistant');
  }

  get equipeStagiaires(): MembreEquipe[] {
    return this.equipe.filter((m) => m.role === 'stagiaire');
  }

  initialesMembre(nom: string): string {
    return nom
      .split(/\s+/)
      .filter(Boolean)
      .slice(0, 2)
      .map((mot) => mot[0].toUpperCase())
      .join('');
  }
}
