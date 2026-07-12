import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { CabinetSettingService } from '../../core/services/cabinet-setting.service';
import { CoordonneesCabinet } from '../../core/models/cabinet-setting.model';
import { MembreEquipeService } from '../../core/services/membre-equipe.service';
import { MembreEquipe } from '../../core/models/membre-equipe.model';

@Component({
  selector: 'app-accueil',
  standalone: true,
  imports: [CommonModule, RouterLink, MatButtonModule, MatIconModule],
  templateUrl: './accueil.component.html',
})
export class AccueilComponent implements OnInit {
  cabinet: CoordonneesCabinet | null = null;
  equipe: MembreEquipe[] = [];
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

  // Témoignages et actualités : contenu d'exemple à remplacer par vos vrais
  // témoignages clients et actualités une fois disponibles.
  readonly temoignages = [
    { texte: "Grâce à JCA, notre dossier d'immigration a été traité avec une rigueur et un professionnalisme remarquables. Un accompagnement humain du début à la fin.", auteur: 'Un client accompagné en immigration' },
    { texte: "Une équipe qui comprend les enjeux internationaux et sait naviguer entre les exigences réglementaires de plusieurs pays. Recommandé sans hésiter.", auteur: 'Une entreprise partenaire' },
    { texte: "Le suivi en ligne de notre dossier nous a permis de rester informés à chaque étape, où que nous soyons dans le monde.", auteur: 'Un client à l\'international' },
  ];

  readonly actualites = [
    { date: 'Juillet 2026', titre: 'JCA élargit son accompagnement en mobilité internationale', extrait: "De nouveaux partenariats pour mieux servir nos clients à travers le monde." },
    { date: 'Juin 2026', titre: 'Webinaire : les nouvelles règles d\'immigration', extrait: 'Un survol des changements récents et de leurs impacts pour les employeurs et travailleurs.' },
    { date: 'Mai 2026', titre: 'JCA renforce son pôle action humanitaire', extrait: "Un accompagnement dédié aux organisations humanitaires et à leurs missions sur le terrain." },
  ];

  constructor(
    private cabinetSettingService: CabinetSettingService,
    private membreEquipeService: MembreEquipeService
  ) {}

  ngOnInit(): void {
    this.cabinetSettingService.public().subscribe((c) => (this.cabinet = c));
    this.membreEquipeService.public().subscribe((m) => (this.equipe = m));
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

  initialesMembre(nom: string): string {
    return nom
      .split(/\s+/)
      .filter(Boolean)
      .slice(0, 2)
      .map((mot) => mot[0].toUpperCase())
      .join('');
  }
}
