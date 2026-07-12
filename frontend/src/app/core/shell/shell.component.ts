import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterOutlet, RouterLink, RouterLinkActive, Router } from '@angular/router';
import { MatSidenavModule } from '@angular/material/sidenav';
import { MatIconModule } from '@angular/material/icon';
import { MatButtonModule } from '@angular/material/button';
import { MatToolbarModule } from '@angular/material/toolbar';
import { AuthService } from '../services/auth.service';
import { CabinetSettingService } from '../services/cabinet-setting.service';
import { CoordonneesCabinet } from '../models/cabinet-setting.model';
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
export class ShellComponent implements OnInit {
  cabinet: CoordonneesCabinet | null = null;

  constructor(
    public auth: AuthService,
    private router: Router,
    private cabinetSettingService: CabinetSettingService
  ) {}

  ngOnInit(): void {
    // Endpoint public (pas l'endpoint admin réservé) : la barre latérale est
    // visible par tous les rôles connectés, pas seulement l'administrateur.
    this.cabinetSettingService.public().subscribe((c) => (this.cabinet = c));

    // Filet de sécurité : si la page a été rechargée (le signal en mémoire
    // currentUser a été réinitialisé) ou si l'utilisateur a navigué
    // directement ici en contournant la redirection post-connexion.
    this.auth.chargerUtilisateurCourant().subscribe((user) => {
      if (user.doit_changer_mot_de_passe && !this.router.url.includes('changer-mot-de-passe')) {
        this.router.navigate(['/changer-mot-de-passe']);
      }
    });
  }

  get utilisateur() {
    return this.auth.currentUser();
  }

  initiales(nom: string | undefined): string {
    if (!nom) return '';
    const premierSegment = nom.split(/—|–|-/)[0].trim();
    if (premierSegment.length <= 6) return premierSegment;
    return nom
      .split(/\s+/)
      .filter((mot) => mot.length > 2)
      .slice(0, 3)
      .map((mot) => mot[0].toUpperCase())
      .join('');
  }

  deconnexion(): void {
    this.auth.logout().subscribe({
      next: () => this.router.navigate(['/connexion']),
      error: () => this.router.navigate(['/connexion']),
    });
  }
}
