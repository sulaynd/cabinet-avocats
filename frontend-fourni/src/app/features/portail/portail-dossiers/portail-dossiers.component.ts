import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { PortailService } from '../../../core/services/portail.service';
import { PortailAuthService } from '../../../core/services/portail-auth.service';
import { Router } from '@angular/router';
import { Dossier } from '../../../core/models/dossier.model';
import { Facture } from '../../../core/models/facture.model';

@Component({
  selector: 'app-portail-dossiers',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './portail-dossiers.component.html',
})
export class PortailDossiersComponent implements OnInit {
  dossiers: Dossier[] = [];
  factures: Facture[] = [];
  dossierOuvert: Dossier | null = null;

  constructor(private portailService: PortailService, private portailAuth: PortailAuthService, private router: Router) {}

  ngOnInit(): void {
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
