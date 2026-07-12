import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterOutlet, RouterLink, RouterLinkActive, Router } from '@angular/router';
import { MatSidenavModule } from '@angular/material/sidenav';
import { MatIconModule } from '@angular/material/icon';
import { MatButtonModule } from '@angular/material/button';
import { MatToolbarModule } from '@angular/material/toolbar';
import { AuthService } from '../services/auth.service';
import { HasRoleDirective } from '../directives/has-role.directive';

/**
 * Structure générale de l'application authentifiée : barre latérale de
 * navigation + zone de contenu (<router-outlet>). Sans ce composant, les
 * écrans (dossiers, clients...) s'affichaient bien via leurs routes mais
 * sans aucun moyen de naviguer entre eux dans l'interface elle-même.
 */
@Component({
  selector: 'app-shell',
  standalone: true,
  imports: [
    CommonModule, RouterOutlet, RouterLink, RouterLinkActive,
    MatSidenavModule, MatIconModule, MatButtonModule, MatToolbarModule,
    HasRoleDirective,
  ],
  templateUrl: './shell.component.html',
  styleUrl: './shell.component.scss',
})
export class ShellComponent {
  constructor(public auth: AuthService, private router: Router) {}

  get utilisateur() {
    return this.auth.currentUser();
  }

  deconnexion(): void {
    this.auth.logout().subscribe({
      next: () => this.router.navigate(['/connexion']),
      error: () => this.router.navigate(['/connexion']),
    });
  }
}
