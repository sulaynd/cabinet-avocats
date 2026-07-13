import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { ActualiteService } from '../../../core/services/actualite.service';
import { NotificationService } from '../../../core/services/notification.service';
import { ConfirmService } from '../../../core/services/confirm.service';
import { Actualite } from '../../../core/models/actualite.model';

@Component({
  selector: 'app-actualites',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink, MatButtonModule, MatIconModule, MatCheckboxModule],
  templateUrl: './actualites.component.html',
})
export class ActualitesComponent implements OnInit {
  actualites: Actualite[] = [];
  chargement = true;
  enregistrementEnCours: Record<number, boolean> = {};

  constructor(
    private actualiteService: ActualiteService,
    private notification: NotificationService,
    private confirmService: ConfirmService
  ) {}

  ngOnInit(): void {
    this.charger();
  }

  charger(): void {
    this.chargement = true;
    this.actualiteService.liste().subscribe({
      next: (a) => {
        this.actualites = a;
        this.chargement = false;
      },
      error: (err) => {
        this.chargement = false;
        this.notification.erreur(err?.error?.message || 'Impossible de charger les actualités.');
      },
    });
  }

  ajouterActualite(): void {
    this.actualites.push({
      id: -Date.now(),
      titre: '',
      date: new Date().toISOString().slice(0, 10),
      extrait: '',
      ordre: this.actualites.length,
      actif: false,
    });
  }

  estNouvelle(actualite: Actualite): boolean {
    return actualite.id < 0;
  }

  enregistrer(actualite: Actualite): void {
    if (!actualite.titre.trim() || !actualite.extrait.trim()) {
      this.notification.erreur('Le titre et l\'extrait sont obligatoires.');
      return;
    }
    this.enregistrementEnCours[actualite.id] = true;

    const payload = {
      titre: actualite.titre,
      date: actualite.date,
      extrait: actualite.extrait,
      ordre: actualite.ordre,
      actif: actualite.actif,
    };
    const requete = this.estNouvelle(actualite)
      ? this.actualiteService.creer(payload)
      : this.actualiteService.modifier(actualite.id, payload);

    requete.subscribe({
      next: () => {
        delete this.enregistrementEnCours[actualite.id];
        this.notification.succes('Actualité enregistrée.');
        this.charger();
      },
      error: (err) => {
        delete this.enregistrementEnCours[actualite.id];
        this.notification.erreur(err?.error?.message || "Impossible d'enregistrer cette actualité.");
      },
    });
  }

  supprimer(actualite: Actualite): void {
    if (this.estNouvelle(actualite)) {
      this.actualites = this.actualites.filter((a) => a.id !== actualite.id);
      return;
    }
    this.confirmService
      .demander({ titre: 'Supprimer cette actualité ?', message: `"${actualite.titre}" sera retirée de la page d'accueil.`, libelleConfirmer: 'Supprimer', destructif: true })
      .subscribe((confirme) => {
        if (!confirme) return;
        this.actualiteService.supprimer(actualite.id).subscribe({
          next: () => {
            this.notification.succes('Actualité supprimée.');
            this.charger();
          },
          error: (err) => this.notification.erreur(err?.error?.message || 'Impossible de supprimer cette actualité.'),
        });
      });
  }
}
