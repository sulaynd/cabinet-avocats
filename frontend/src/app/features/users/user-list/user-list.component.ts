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
import { UserService } from '../../../core/services/user.service';
import { NotificationService } from '../../../core/services/notification.service';
import { ConfirmService } from '../../../core/services/confirm.service';
import { Utilisateur } from '../../../core/models/user.model';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-user-list',
  standalone: true,
  imports: [
    CommonModule, RouterLink, FormsModule,
    MatTableModule, MatFormFieldModule, MatInputModule, MatSelectModule,
    MatButtonModule, MatIconModule, MatPaginatorModule, MatChipsModule, MatSortModule,
  ],
  templateUrl: './user-list.component.html',
})
export class UserListComponent implements OnInit, AfterViewInit {
  dataSource = new MatTableDataSource<Utilisateur>([]);
  @ViewChild(MatSort) sort!: MatSort;

  chargement = false;
  recherche = '';
  roleFiltre: Utilisateur['role'] | '' = '';
  pageCourante = 1;
  dernierePage = 1;
  totalUtilisateurs = 0;
  taillePage = 20;

  readonly colonnes = ['nom', 'email', 'role', 'telephone', 'dossiers', 'actions'];
  readonly roles: Utilisateur['role'][] = ['admin', 'avocat', 'assistant', 'stagiaire'];

  constructor(
    private userService: UserService,
    private auth: AuthService,
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
    this.dataSource.sortingDataAccessor = (utilisateur, colonne) => {
      switch (colonne) {
        case 'nom': return utilisateur.name?.toLowerCase() ?? '';
        case 'telephone': return utilisateur.phone ?? '';
        case 'dossiers': return utilisateur.dossiers_count ?? -1;
        default: return (utilisateur as any)[colonne] ?? '';
      }
    };
  }

  charger(): void {
    this.chargement = true;
    const params: Record<string, string | number> = { page: this.pageCourante, per_page: this.taillePage };
    if (this.recherche) params['search'] = this.recherche;
    if (this.roleFiltre) params['role'] = this.roleFiltre;

    this.userService.liste(params).subscribe({
      next: (res) => {
        this.dataSource.data = res.data;
        this.dernierePage = res.last_page;
        this.totalUtilisateurs = res.total;
        this.chargement = false;
      },
      error: () => {
        this.chargement = false;
        this.notification.erreur('Impossible de charger les utilisateurs.');
      },
    });
  }

  changerPage(evenement: { pageIndex: number; pageSize: number }): void {
    this.pageCourante = evenement.pageIndex + 1;
    this.taillePage = evenement.pageSize;
    this.charger();
  }

  estMoi(utilisateur: Utilisateur): boolean {
    return this.auth.currentUser()?.id === utilisateur.id;
  }

  supprimer(utilisateur: Utilisateur): void {
    this.confirmService
      .demander({
        titre: 'Supprimer cet utilisateur ?',
        message: `"${utilisateur.name}" sera définitivement supprimé.`,
        libelleConfirmer: 'Supprimer',
        destructif: true,
      })
      .subscribe((confirme) => {
        if (!confirme) return;
        this.userService.supprimer(utilisateur.id).subscribe({
          next: () => {
            this.notification.succes('Utilisateur supprimé.');
            this.charger();
          },
          error: (err) => this.notification.erreur(err?.error?.message || 'Impossible de supprimer cet utilisateur.'),
        });
      });
  }
}
