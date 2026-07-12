import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatTableModule } from '@angular/material/table';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatChipsModule } from '@angular/material/chips';
import { PortailService } from '../../../core/services/portail.service';
import { PortailAuthService } from '../../../core/services/portail-auth.service';
import { Router, RouterLink } from '@angular/router';
import { Dossier } from '../../../core/models/dossier.model';
import { Facture } from '../../../core/models/facture.model';

@Component({
  selector: 'app-portail-dossiers',
  standalone: true,
  imports: [CommonModule, RouterLink, MatTableModule, MatButtonModule, MatIconModule, MatChipsModule],
  templateUrl: './portail-dossiers.component.html',
})
export class PortailDossiersComponent implements OnInit {
  dossiers: Dossier[] = [];
  factures: Facture[] = [];
  dossierOuvert: Dossier | null = null;

  readonly colonnesDossiers = ['reference', 'titre', 'avocat', 'statut', 'actions'];
  readonly colonnesFactures = ['numero', 'emission', 'montant', 'statut'];
  readonly colonnesDocuments = ['nom', 'signature', 'actions'];

  constructor(private portailService: PortailService, private portailAuth: PortailAuthService, private router: Router) {}

  ngOnInit(): void {
    // Filet de sécurité : si le client a rechargé la page (le signal en mémoire
    // clientCourant a été réinitialisé) ou a navigué directement ici en
    // contournant la redirection post-connexion, on revérifie depuis l'API.
    this.portailAuth.moi().subscribe((client) => {
      if (client.doit_changer_mot_de_passe) {
        this.router.navigate(['/portail/changer-mot-de-passe']);
      }
    });

    this.portailService.mesDossiers().subscribe((d) => (this.dossiers = d));
    this.portailService.mesFactures().subscribe((f) => (this.factures = f));
  }

  ouvrirDossier(id: number): void {
    this.portailService.monDossier(id).subscribe((d) => (this.dossierOuvert = d));
  }

  fermerDossier(): void {
    this.dossierOuvert = null;
  }

  telechargerDocument(id: number, nom: string): void {
    this.portailService.telechargerDocument(id, nom);
  }

  signer(documentId: number): void {
    const nom = prompt('Merci de taper votre nom complet pour valoriser votre signature :');
    if (!nom) return;
    this.portailService.signerDocument(documentId, nom).subscribe(() => this.ouvrirDossier(this.dossierOuvert!.id));
  }

  deconnexion(): void {
    this.portailAuth.deconnexion().subscribe(() => this.router.navigate(['/portail/connexion']));
  }
}
