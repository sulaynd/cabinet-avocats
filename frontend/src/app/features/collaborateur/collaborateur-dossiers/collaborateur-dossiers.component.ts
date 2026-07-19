import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { CollaborateurPortailService } from '../../../core/services/collaborateur-portail.service';
import { CollaborateurAuthService } from '../../../core/services/collaborateur-auth.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-collaborateur-dossiers',
  standalone: true,
  imports: [CommonModule, RouterLink, MatButtonModule, MatIconModule],
  templateUrl: './collaborateur-dossiers.component.html',
})
export class CollaborateurDossiersComponent implements OnInit {
  dossiers: any[] = [];
  chargement = true;

  constructor(
    private portailService: CollaborateurPortailService,
    private collaborateurAuth: CollaborateurAuthService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.portailService.mesDossiers().subscribe({
      next: (d) => {
        this.dossiers = d;
        this.chargement = false;
      },
      error: () => (this.chargement = false),
    });
  }

  deconnexion(): void {
    this.collaborateurAuth.deconnexion().subscribe(() => this.router.navigate(['/collaborateur/connexion']));
  }
}
