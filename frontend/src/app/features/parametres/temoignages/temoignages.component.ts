import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatChipsModule } from '@angular/material/chips';
import { MatSlideToggleModule } from '@angular/material/slide-toggle';
import { TemoignageService } from '../../../core/services/temoignage.service';
import { NotificationService } from '../../../core/services/notification.service';
import { ConfirmService } from '../../../core/services/confirm.service';
import { TemoignageAdmin } from '../../../core/models/temoignage.model';

@Component({
  selector: 'app-temoignages',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink, MatButtonModule, MatIconModule, MatChipsModule, MatSlideToggleModule],
  templateUrl: './temoignages.component.html',
})
export class TemoignagesComponent implements OnInit {
  temoignages: TemoignageAdmin[] = [];
  chargement = true;
  enregistrementEnCours: Record<number, boolean> = {};

  constructor(
    private temoignageService: TemoignageService,
    private notification: NotificationService,
    private confirmService: ConfirmService
  ) {}

  ngOnInit(): void {
    this.charger();
  }

  charger(): void {
    this.chargement = true;
    this.temoignageService.liste().subscribe({
      next: (t) => {
        this.temoignages = t;
        this.chargement = false;
      },
      error: (err) => {
        this.chargement = false;
        this.notification.erreur(err?.error?.message || 'Impossible de charger les témoignages.');
      },
    });
  }

  basculerActif(temoignage: TemoignageAdmin): void {
    this.enregistrementEnCours[temoignage.id] = true;
    this.temoignageService.modifier(temoignage.id, temoignage.actif, temoignage.ordre).subscribe({
      next: () => {
        delete this.enregistrementEnCours[temoignage.id];
        this.notification.succes(temoignage.actif ? 'Témoignage publié sur la page d\'accueil.' : 'Témoignage masqué.');
      },
      error: (err) => {
        delete this.enregistrementEnCours[temoignage.id];
        temoignage.actif = !temoignage.actif; // annule le changement visuel si l'appel échoue
        this.notification.erreur(err?.error?.message || 'Impossible de mettre à jour ce témoignage.');
      },
    });
  }

  supprimer(temoignage: TemoignageAdmin): void {
    this.confirmService
      .demander({
        titre: 'Supprimer ce témoignage ?',
        message: `Le témoignage de "${temoignage.client?.nom_complet}" sera définitivement supprimé.`,
        libelleConfirmer: 'Supprimer',
        destructif: true,
      })
      .subscribe((confirme) => {
        if (!confirme) return;
        this.temoignageService.supprimer(temoignage.id).subscribe({
          next: () => {
            this.notification.succes('Témoignage supprimé.');
            this.charger();
          },
          error: (err) => this.notification.erreur(err?.error?.message || 'Impossible de supprimer ce témoignage.'),
        });
      });
  }
}
