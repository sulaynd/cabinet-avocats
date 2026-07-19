import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { CollaborateurPortailService } from '../../../core/services/collaborateur-portail.service';

@Component({
  selector: 'app-collaborateur-dossier-detail',
  standalone: true,
  imports: [CommonModule, RouterLink, MatButtonModule, MatIconModule],
  templateUrl: './collaborateur-dossier-detail.component.html',
})
export class CollaborateurDossierDetailComponent implements OnInit {
  dossierId!: number;
  documents: any[] = [];
  chargement = true;
  televersementEnCours = false;
  fichierSelectionne: File | null = null;
  erreur = '';

  constructor(private route: ActivatedRoute, private portailService: CollaborateurPortailService) {}

  ngOnInit(): void {
    this.dossierId = Number(this.route.snapshot.paramMap.get('id'));
    this.charger();
  }

  charger(): void {
    this.chargement = true;
    this.portailService.documentsDuDossier(this.dossierId).subscribe({
      next: (d) => {
        this.documents = d;
        this.chargement = false;
      },
      error: (err) => {
        this.chargement = false;
        this.erreur = err?.error?.message || 'Impossible de charger ce dossier.';
      },
    });
  }

  selectionnerFichier(event: Event): void {
    const input = event.target as HTMLInputElement;
    this.fichierSelectionne = input.files?.[0] ?? null;
  }

  televerser(): void {
    if (!this.fichierSelectionne) return;
    this.televersementEnCours = true;
    this.portailService.televerser(this.dossierId, this.fichierSelectionne).subscribe({
      next: () => {
        this.televersementEnCours = false;
        this.fichierSelectionne = null;
        this.charger();
      },
      error: (err) => {
        this.televersementEnCours = false;
        this.erreur = err?.error?.message || 'Impossible de téléverser ce document.';
      },
    });
  }

  telecharger(doc: any): void {
    this.portailService.telecharger(doc.id).subscribe((blob) => {
      const lien = document.createElement('a');
      lien.href = URL.createObjectURL(blob);
      lien.download = doc.nom_original;
      lien.click();
      URL.revokeObjectURL(lien.href);
    });
  }
}
