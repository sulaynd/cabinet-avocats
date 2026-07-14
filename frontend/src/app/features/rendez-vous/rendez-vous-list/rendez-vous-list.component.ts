import { Component, OnInit, ViewChild, AfterViewInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { MatTableModule, MatTableDataSource } from '@angular/material/table';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatChipsModule } from '@angular/material/chips';
import { MatPaginatorModule } from '@angular/material/paginator';
import { MatSortModule, MatSort } from '@angular/material/sort';
import { RendezVousService } from '../../../core/services/rendez-vous.service';
import { NotificationService } from '../../../core/services/notification.service';
import { ConfirmService } from '../../../core/services/confirm.service';
import { RendezVous } from '../../../core/models/rendez-vous.model';
import { HasRoleDirective } from '../../../core/directives/has-role.directive';

@Component({
  selector: 'app-rendez-vous-list',
  standalone: true,
  imports: [
    CommonModule, FormsModule,
    MatTableModule, MatButtonModule, MatIconModule, MatChipsModule, MatPaginatorModule, MatSortModule, HasRoleDirective,
  ],
  templateUrl: './rendez-vous-list.component.html',
})
export class RendezVousListComponent implements OnInit, AfterViewInit {
  dataSource = new MatTableDataSource<RendezVous>([]);
  @ViewChild(MatSort) sort!: MatSort;

  chargement = false;
  readonly colonnes = ['date_heure', 'nom', 'email', 'avocat', 'motif', 'statut', 'actions'];

  pageCourante = 1;
  taillePage = 20;
  totalRendezVous = 0;

  constructor(
    private rendezVousService: RendezVousService,
    private notification: NotificationService,
    private confirmService: ConfirmService
  ) {}

  ngOnInit(): void {
    this.charger();
  }

  ngAfterViewInit(): void {
    this.dataSource.sort = this.sort;
    this.dataSource.sortingDataAccessor = (rdv, colonne) => {
      if (colonne === 'avocat') return rdv.avocat?.name?.toLowerCase() ?? '';
      return (rdv as any)[colonne] ?? '';
    };
  }

  charger(): void {
    this.chargement = true;
    this.rendezVousService.liste({ page: this.pageCourante, per_page: this.taillePage }).subscribe({
      next: (res) => {
        this.dataSource.data = res.data;
        this.totalRendezVous = res.total;
        this.chargement = false;
      },
      error: () => {
        this.chargement = false;
        this.notification.erreur('Impossible de charger les demandes de rendez-vous.');
      },
    });
  }

  changerPage(evenement: { pageIndex: number; pageSize: number }): void {
    this.pageCourante = evenement.pageIndex + 1;
    this.taillePage = evenement.pageSize;
    this.charger();
  }

  rendezVousAConfirmer: RendezVous | null = null;
  montantPopup: number | null = null;
  lienPopup = '';
  confirmationEnCours = false;

  ouvrirPopupConfirmation(r: RendezVous): void {
    this.rendezVousAConfirmer = r;
    this.montantPopup = null;
    this.lienPopup = '';
  }

  fermerPopupConfirmation(): void {
    this.rendezVousAConfirmer = null;
  }

  envoyerConfirmation(): void {
    if (!this.rendezVousAConfirmer || !this.montantPopup) return;
    this.confirmationEnCours = true;

    this.rendezVousService.confirmer(this.rendezVousAConfirmer.id, this.montantPopup, this.lienPopup).subscribe({
      next: () => {
        this.confirmationEnCours = false;
        this.notification.succes('Rendez-vous confirmé.');
        this.fermerPopupConfirmation();
        this.charger();
      },
      error: (err) => {
        this.confirmationEnCours = false;
        this.notification.erreur(err?.error?.message || 'Impossible de confirmer ce rendez-vous.');
      },
    });
  }

  annuler(id: number): void {
    this.confirmService
      .demander({ titre: 'Annuler ce rendez-vous ?', message: 'Le demandeur ne sera pas notifié automatiquement.', libelleAnnuler: 'Retour', libelleConfirmer: "Oui, annuler", destructif: true })
      .subscribe((confirme) => {
        if (!confirme) return;
        this.rendezVousService.annuler(id).subscribe({
          next: () => {
            this.notification.succes('Rendez-vous annulé.');
            this.charger();
          },
          error: (err) => this.notification.erreur(err?.error?.message || 'Impossible d\'annuler ce rendez-vous.'),
        });
      });
  }
}
