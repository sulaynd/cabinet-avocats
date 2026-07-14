import { Component, OnInit, ViewChild, AfterViewInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { MatTableModule, MatTableDataSource } from '@angular/material/table';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatSelectModule } from '@angular/material/select';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatPaginatorModule } from '@angular/material/paginator';
import { MatChipsModule } from '@angular/material/chips';
import { MatMenuModule } from '@angular/material/menu';
import { MatTooltipModule } from '@angular/material/tooltip';
import { MatSortModule, MatSort } from '@angular/material/sort';
import { FactureService } from '../../../core/services/facture.service';
import { NotificationService } from '../../../core/services/notification.service';
import { ConfirmService } from '../../../core/services/confirm.service';
import { Facture, StatutFacture } from '../../../core/models/facture.model';
import { HasRoleDirective } from '../../../core/directives/has-role.directive';

@Component({
  selector: 'app-facture-list',
  standalone: true,
  imports: [
    CommonModule, RouterLink, FormsModule, HasRoleDirective,
    MatTableModule, MatFormFieldModule, MatSelectModule, MatButtonModule,
    MatIconModule, MatPaginatorModule, MatChipsModule, MatMenuModule, MatSortModule, MatTooltipModule,
  ],
  templateUrl: './facture-list.component.html',
})
export class FactureListComponent implements OnInit, AfterViewInit {
  dataSource = new MatTableDataSource<Facture>([]);
  @ViewChild(MatSort) sort!: MatSort;

  chargement = false;
  statutFiltre: StatutFacture | '' = '';
  pageCourante = 1;
  dernierePage = 1;
  totalFactures = 0;
  taillePage = 20;

  readonly colonnes = ['numero', 'client', 'dossier', 'emission', 'echeance', 'montant', 'statut', 'actions'];
  readonly statuts: StatutFacture[] = ['brouillon', 'envoyee', 'payee', 'en_retard', 'annulee'];

  readonly libellesStatut: Record<string, string> = {
    brouillon: 'Facture non envoyée et non payée',
    envoyee: 'Facture envoyée et non payée',
    payee: 'Facture payée',
    en_retard: 'Facture en retard',
    annulee: 'Facture annulée',
  };

  readonly libellesStatutCourts: Record<string, string> = {
    brouillon: 'Non envoyée',
    envoyee: 'Envoyée',
    payee: 'Payée',
    en_retard: 'En retard',
    annulee: 'Annulée',
  };

  constructor(
    private factureService: FactureService,
    private notification: NotificationService,
    private confirmService: ConfirmService
  ) {}

  ngOnInit(): void {
    this.charger();
  }

  ngAfterViewInit(): void {
    this.dataSource.sort = this.sort;
    this.dataSource.sortingDataAccessor = (facture, colonne) => {
      switch (colonne) {
        case 'client': return this.nomClient(facture).toLowerCase();
        case 'dossier': return facture.dossier?.reference?.toLowerCase() ?? '';
        case 'emission': return facture.date_emission;
        case 'echeance': return facture.date_echeance ?? '';
        case 'montant': return facture.montant_ttc;
        default: return (facture as any)[colonne] ?? '';
      }
    };
  }

  charger(): void {
    this.chargement = true;
    const params: Record<string, string | number> = { page: this.pageCourante, per_page: this.taillePage };
    if (this.statutFiltre) params['statut'] = this.statutFiltre;

    this.factureService.liste(params).subscribe({
      next: (res) => {
        this.dataSource.data = res.data;
        this.dernierePage = res.last_page;
        this.totalFactures = res.total;
        this.chargement = false;
      },
      error: () => {
        this.chargement = false;
        this.notification.erreur('Impossible de charger les factures.');
      },
    });
  }

  changerPage(evenement: { pageIndex: number; pageSize: number }): void {
    this.pageCourante = evenement.pageIndex + 1;
    this.taillePage = evenement.pageSize;
    this.charger();
  }

  telechargerPdf(facture: Facture): void {
    this.factureService.telechargerPdf(facture.id, facture.numero);
  }

  marquerPayee(facture: Facture): void {
    this.factureService.marquerPayee(facture.id).subscribe({
      next: () => {
        this.notification.succes('Facture marquée payée.');
        this.charger();
      },
      error: (err) => this.notification.erreur(err?.error?.message || 'Impossible de mettre à jour cette facture.'),
    });
  }

  envoyerParEmail(facture: Facture): void {
    this.confirmService
      .demander({ titre: 'Envoyer par email ?', message: `La facture ${facture.numero} sera envoyée au client, PDF joint.`, libelleConfirmer: 'Envoyer' })
      .subscribe((confirme) => {
        if (!confirme) return;
        this.factureService.envoyerParEmail(facture.id).subscribe({
          next: () => {
            this.notification.succes('Facture envoyée par email.');
            this.charger();
          },
          error: (err) => this.notification.erreur(err?.error?.message || "Échec de l'envoi de l'email."),
        });
      });
  }

  nomClient(facture: Facture): string {
    const client = facture.client;
    if (!client) return '';
    return client.type === 'entreprise'
      ? (client.raison_sociale ?? '')
      : `${client.prenom ?? ''} ${client.nom ?? ''}`.trim();
  }

  supprimer(facture: Facture): void {
    this.confirmService
      .demander({ titre: 'Supprimer cette facture ?', message: `La facture ${facture.numero} sera définitivement supprimée.`, libelleConfirmer: 'Supprimer', destructif: true })
      .subscribe((confirme) => {
        if (!confirme) return;
        this.factureService.supprimer(facture.id).subscribe({
          next: () => {
            this.notification.succes('Facture supprimée.');
            this.charger();
          },
          error: (err) => this.notification.erreur(err?.error?.message || 'Impossible de supprimer cette facture.'),
        });
      });
  }
}
