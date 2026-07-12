import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { MatTableModule } from '@angular/material/table';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatPaginatorModule } from '@angular/material/paginator';
import { MatChipsModule } from '@angular/material/chips';
import { ClientService } from '../../../core/services/client.service';
import { NotificationService } from '../../../core/services/notification.service';
import { ConfirmService } from '../../../core/services/confirm.service';
import { Client } from '../../../core/models/client.model';

@Component({
  selector: 'app-client-list',
  standalone: true,
  imports: [
    CommonModule, RouterLink, FormsModule,
    MatTableModule, MatFormFieldModule, MatInputModule,
    MatButtonModule, MatIconModule, MatPaginatorModule, MatChipsModule,
  ],
  templateUrl: './client-list.component.html',
})
export class ClientListComponent implements OnInit {
  clients: Client[] = [];
  chargement = false;
  recherche = '';
  pageCourante = 1;
  dernierePage = 1;
  totalClients = 0;
  taillePage = 20;

  readonly colonnes = ['nom', 'type', 'email', 'telephone', 'ville', 'actions'];

  constructor(
    private clientService: ClientService,
    private notification: NotificationService,
    private confirmService: ConfirmService
  ) {}

  ngOnInit(): void {
    this.charger();
  }

  charger(): void {
    this.chargement = true;
    const params: Record<string, string | number> = { page: this.pageCourante, per_page: this.taillePage };
    if (this.recherche) params['search'] = this.recherche;

    this.clientService.liste(params).subscribe({
      next: (res) => {
        this.clients = res.data;
        this.dernierePage = res.last_page;
        this.totalClients = res.total;
        this.chargement = false;
      },
      error: () => {
        this.chargement = false;
        this.notification.erreur('Impossible de charger les clients.');
      },
    });
  }

  changerPage(evenement: { pageIndex: number; pageSize: number }): void {
    this.pageCourante = evenement.pageIndex + 1;
    this.taillePage = evenement.pageSize;
    this.charger();
  }

  nomAffiche(client: Client): string {
    return client.type === 'entreprise'
      ? (client.raison_sociale ?? '')
      : `${client.prenom ?? ''} ${client.nom ?? ''}`.trim();
  }

  supprimer(client: Client): void {
    this.confirmService
      .demander({
        titre: 'Supprimer ce client ?',
        message: `"${this.nomAffiche(client)}" sera définitivement supprimé.`,
        libelleConfirmer: 'Supprimer',
        destructif: true,
      })
      .subscribe((confirme) => {
        if (!confirme) return;
        this.clientService.supprimer(client.id).subscribe({
          next: () => {
            this.notification.succes('Client supprimé.');
            this.charger();
          },
          error: (err) => this.notification.erreur(err?.error?.message || 'Impossible de supprimer ce client.'),
        });
      });
  }
}
