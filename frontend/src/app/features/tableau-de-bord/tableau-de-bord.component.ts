import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatIconModule } from '@angular/material/icon';
import { MatTooltipModule } from '@angular/material/tooltip';
import { TableauDeBordService } from '../../core/services/tableau-de-bord.service';
import { StatistiquesTableauDeBord } from '../../core/models/tableau-de-bord.model';

@Component({
  selector: 'app-tableau-de-bord',
  standalone: true,
  imports: [CommonModule, MatIconModule, MatTooltipModule],
  templateUrl: './tableau-de-bord.component.html',
})
export class TableauDeBordComponent implements OnInit {
  stats: StatistiquesTableauDeBord | null = null;
  chargement = true;

  readonly libellesStatut: Record<string, string> = {
    ouvert: 'Ouvert',
    en_cours: 'En cours',
    en_attente: 'En attente',
    clos: 'Clos',
    archive: 'Archivé',
  };

  readonly libellesTypeAffaire: Record<string, string> = {
    immigration_mobilite: 'Immigration & mobilité internationale',
    recrutement_international: 'Recrutement international',
    cooperation_internationale: 'Coopération internationale',
    developpement_international: 'Développement international',
    action_humanitaire: 'Action humanitaire',
    conseils_strategiques: 'Services-conseils stratégiques',
    autre: 'Autre',
  };

  constructor(private tableauDeBordService: TableauDeBordService) {}

  ngOnInit(): void {
    this.tableauDeBordService.obtenir().subscribe({
      next: (s) => {
        this.stats = s;
        this.chargement = false;
      },
      error: () => (this.chargement = false),
    });
  }

  get maxEvolutionMensuelle(): number {
    if (!this.stats?.evolution_mensuelle.length) return 1;
    return Math.max(...this.stats.evolution_mensuelle.map((m) => m.total), 1);
  }

  get maxCaParAvocat(): number {
    if (!this.stats?.ca_par_avocat.length) return 1;
    return Math.max(...this.stats.ca_par_avocat.map((a) => a.total), 1);
  }

  get totalDossiers(): number {
    if (!this.stats) return 0;
    return Object.values(this.stats.dossiers_par_statut).reduce((s, n) => s + n, 0);
  }

  get maxDossiersParType(): number {
    if (!this.stats?.dossiers_par_type.length) return 1;
    return Math.max(...this.stats.dossiers_par_type.map((t) => t.total), 1);
  }

  dossiersStatutEntries(): [string, number][] {
    return this.stats ? Object.entries(this.stats.dossiers_par_statut) : [];
  }
}
