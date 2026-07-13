import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatSelectModule } from '@angular/material/select';
import { OffreEmploiService } from '../../../core/services/offre-emploi.service';
import { NotificationService } from '../../../core/services/notification.service';
import { ConfirmService } from '../../../core/services/confirm.service';
import { OffreEmploi, TypeContrat } from '../../../core/models/offre-emploi.model';

@Component({
  selector: 'app-carrieres',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink, MatButtonModule, MatIconModule, MatCheckboxModule, MatSelectModule],
  templateUrl: './carrieres.component.html',
})
export class CarrieresComponent implements OnInit {
  offres: OffreEmploi[] = [];
  chargement = true;
  enregistrementEnCours: Record<number, boolean> = {};

  readonly typesContrat: { valeur: TypeContrat; libelle: string }[] = [
    { valeur: 'cdi', libelle: 'CDI' },
    { valeur: 'cdd', libelle: 'CDD' },
    { valeur: 'stage', libelle: 'Stage' },
    { valeur: 'temps_partiel', libelle: 'Temps partiel' },
    { valeur: 'contractuel', libelle: 'Contractuel' },
    { valeur: 'autre', libelle: 'Autre' },
  ];

  constructor(
    private offreEmploiService: OffreEmploiService,
    private notification: NotificationService,
    private confirmService: ConfirmService
  ) {}

  ngOnInit(): void {
    this.charger();
  }

  charger(): void {
    this.chargement = true;
    this.offreEmploiService.liste().subscribe({
      next: (o) => {
        this.offres = o;
        this.chargement = false;
      },
      error: (err) => {
        this.chargement = false;
        this.notification.erreur(err?.error?.message || 'Impossible de charger les offres d\'emploi.');
      },
    });
  }

  ajouterOffre(): void {
    this.offres.push({
      id: -Date.now(),
      titre: '',
      description: '',
      type_contrat: 'cdi',
      lieu: null,
      date_limite: null,
      ordre: this.offres.length,
      actif: false,
    });
  }

  estNouvelle(offre: OffreEmploi): boolean {
    return offre.id < 0;
  }

  enregistrer(offre: OffreEmploi): void {
    if (!offre.titre.trim() || !offre.description.trim()) {
      this.notification.erreur('Le titre et la description sont obligatoires.');
      return;
    }
    this.enregistrementEnCours[offre.id] = true;

    const payload = {
      titre: offre.titre,
      description: offre.description,
      type_contrat: offre.type_contrat,
      lieu: offre.lieu,
      date_limite: offre.date_limite,
      ordre: offre.ordre,
      actif: offre.actif,
    };
    const requete = this.estNouvelle(offre)
      ? this.offreEmploiService.creer(payload)
      : this.offreEmploiService.modifier(offre.id, payload);

    requete.subscribe({
      next: () => {
        delete this.enregistrementEnCours[offre.id];
        this.notification.succes('Offre enregistrée.');
        this.charger();
      },
      error: (err) => {
        delete this.enregistrementEnCours[offre.id];
        this.notification.erreur(err?.error?.message || "Impossible d'enregistrer cette offre.");
      },
    });
  }

  supprimer(offre: OffreEmploi): void {
    if (this.estNouvelle(offre)) {
      this.offres = this.offres.filter((o) => o.id !== offre.id);
      return;
    }
    this.confirmService
      .demander({ titre: 'Supprimer cette offre ?', message: `"${offre.titre}" sera retirée de la page d'accueil.`, libelleConfirmer: 'Supprimer', destructif: true })
      .subscribe((confirme) => {
        if (!confirme) return;
        this.offreEmploiService.supprimer(offre.id).subscribe({
          next: () => {
            this.notification.succes('Offre supprimée.');
            this.charger();
          },
          error: (err) => this.notification.erreur(err?.error?.message || 'Impossible de supprimer cette offre.'),
        });
      });
  }
}
