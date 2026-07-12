import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { UserService } from '../../../core/services/user.service';
import { Utilisateur } from '../../../core/models/user.model';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-user-list',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule],
  templateUrl: './user-list.component.html',
})
export class UserListComponent implements OnInit {
  utilisateurs: Utilisateur[] = [];
  chargement = false;
  erreur = '';
  recherche = '';
  roleFiltre: Utilisateur['role'] | '' = '';
  pageCourante = 1;
  dernierePage = 1;

  readonly roles: Utilisateur['role'][] = ['admin', 'avocat', 'assistant'];

  constructor(private userService: UserService, private auth: AuthService) {}

  ngOnInit(): void {
    this.charger();
  }

  charger(): void {
    this.chargement = true;
    const params: Record<string, string | number> = { page: this.pageCourante };
    if (this.recherche) params['search'] = this.recherche;
    if (this.roleFiltre) params['role'] = this.roleFiltre;

    this.userService.liste(params).subscribe({
      next: (res) => {
        this.utilisateurs = res.data;
        this.dernierePage = res.last_page;
        this.chargement = false;
      },
      error: () => (this.chargement = false),
    });
  }

  changerPage(page: number): void {
    if (page < 1 || page > this.dernierePage) return;
    this.pageCourante = page;
    this.charger();
  }

  estMoi(utilisateur: Utilisateur): boolean {
    return this.auth.currentUser()?.id === utilisateur.id;
  }

  supprimer(utilisateur: Utilisateur): void {
    if (!confirm(`Supprimer l'utilisateur "${utilisateur.name}" ?`)) return;
    this.erreur = '';

    this.userService.supprimer(utilisateur.id).subscribe({
      next: () => this.charger(),
      error: (err) => {
        this.erreur = err?.error?.message || "Impossible de supprimer cet utilisateur.";
      },
    });
  }
}
