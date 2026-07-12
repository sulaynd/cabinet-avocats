import { Component, OnInit, ViewChild, AfterViewInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { MatTableModule, MatTableDataSource } from '@angular/material/table';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatPaginatorModule } from '@angular/material/paginator';
import { MatChipsModule } from '@angular/material/chips';
import { MatSortModule, MatSort } from '@angular/material/sort';
import { MatTooltipModule } from '@angular/material/tooltip';
import { DossierService } from '../../../core/services/dossier.service';
import { NotificationService } from '../../../core/services/notification.service';
import { ConfirmService } from '../../../core/services/confirm.service';
import { Dossier, StatutDossier } from '../../../core/models/dossier.model';

@Component({
  selector: 'app-dossier-list',
  standalone: true,
  imports: [
    CommonModule, RouterLink, FormsModule,
    MatTableModule, MatFormFieldModule, MatInputModule, MatSelectModule,
    MatButtonModule, MatIconModule, MatPaginatorModule, MatChipsModule, MatSortModule, MatTooltipModule,
  ],
  templateUrl: './dossier-list.component.html',
})
export class DossierListComponent implements OnInit, AfterViewInit {
  // dataSource (au lieu d'un simple tableau) pour brancher le tri MatSort ;
  // le tri s'applique uniquement à la page actuellement chargée (la pagination
  // reste côté serveur), pas à l'ensemble des dossiers en base.
  dataSource = new MatTableDataSource<Dossier>([]);
  @ViewChild(MatSort) sort!: MatSort;

  chargement = false;
  recherche = '';
  statutFiltre: StatutDossier | '' = '';
  pageCourante = 1;
  dernierePage = 1;
  totalDossiers = 0;
  taillePage = 20;

  readonly colonnes = ['reference', 'titre', 'client', 'avocat', 'assistant', 'statut', 'actions'];
  readonly statuts: StatutDossier[] = ['ouvert', 'en_cours', 'en_attente', 'clos', 'archive'];

  constructor(
    private dossierService: DossierService,
    private notification: NotificationService,
    private confirmService: ConfirmService
  ) {}

  ngOnInit(): void {
    this.charger();
  }

  private rechercheTimeout?: ReturnType<typeof setTimeout>;
  onRechercheChange(): void {
    clearTimeout(this.rechercheTimeout);
    this.rechercheTimeout = setTimeout(() => {
      this.pageCourante = 1;
      this.charger();
    }, 350);
  }

  ngAfterViewInit(): void {
    this.dataSource.sort = this.sort;
    // Tri personnalisé pour les colonnes calculées (client/avocat/assistant ne
    // sont pas des propriétés directes de Dossier mais des relations imbriquées).
    this.dataSource.sortingDataAccessor = (dossier, colonne) => {
      switch (colonne) {
        case 'client': return this.nomClient(dossier).toLowerCase();
        case 'avocat': return dossier.avocat?.name?.toLowerCase() ?? '';
        case 'assistant': return dossier.assistant?.name?.toLowerCase() ?? '';
        default: return (dossier as any)[colonne] ?? '';
      }
    };
  }

  charger(): void {
    this.chargement = true;
    const params: Record<string, string | number> = { page: this.pageCourante, per_page: this.taillePage };
    if (this.recherche) params['search'] = this.recherche;
    if (this.statutFiltre) params['statut'] = this.statutFiltre;

    this.dossierService.liste(params).subscribe({
      next: (res) => {
        this.dataSource.data = res.data;
        this.dernierePage = res.last_page;
        this.totalDossiers = res.total;
        this.chargement = false;
      },
      error: () => {
        this.chargement = false;
        this.notification.erreur('Impossible de charger les dossiers.');
      },
    });
  }

  changerPage(evenement: { pageIndex: number; pageSize: number }): void {
    this.pageCourante = evenement.pageIndex + 1;
    this.taillePage = evenement.pageSize;
    this.charger();
  }

  nomClient(dossier: Dossier): string {
    const client = dossier.client;
    if (!client) return '';
    return client.type === 'entreprise' ? (client.raison_sociale ?? '') : `${client.prenom ?? ''} ${client.nom ?? ''}`.trim();
  }

  supprimer(dossier: Dossier): void {
    this.confirmService
      .demander({
        titre: 'Supprimer ce dossier ?',
        message: `Le dossier ${dossier.reference} — ${dossier.titre} sera définitivement supprimé.`,
        libelleConfirmer: 'Supprimer',
        destructif: true,
      })
      .subscribe((confirme) => {
        if (!confirme) return;
        this.dossierService.supprimer(dossier.id).subscribe({
          next: () => {
            this.notification.succes('Dossier supprimé.');
            this.charger();
          },
          error: (err) => this.notification.erreur(err?.error?.message || 'Impossible de supprimer ce dossier.'),
        });
      });
  }
}
