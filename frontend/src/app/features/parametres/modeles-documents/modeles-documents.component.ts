import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { ModeleDocumentService } from '../../../core/services/modele-document.service';
import { NotificationService } from '../../../core/services/notification.service';
import { ConfirmService } from '../../../core/services/confirm.service';
import { ModeleDocument } from '../../../core/models/modele-document.model';

@Component({
  selector: 'app-modeles-documents',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink, MatButtonModule, MatIconModule],
  templateUrl: './modeles-documents.component.html',
})
export class ModelesDocumentsComponent implements OnInit {
  modeles: ModeleDocument[] = [];
  chargement = true;
  televersementEnCours = false;

  nomFormulaire = '';
  descriptionFormulaire = '';
  typeAffaireFormulaire: string | null = null;
  fichierSelectionne: File | null = null;

  readonly typesAffaire: { valeur: string; libelle: string }[] = [
    { valeur: 'immigration_mobilite', libelle: 'Immigration & mobilité internationale' },
    { valeur: 'recrutement_international', libelle: 'Recrutement international' },
    { valeur: 'cooperation_internationale', libelle: 'Coopération internationale' },
    { valeur: 'developpement_international', libelle: 'Développement international' },
    { valeur: 'action_humanitaire', libelle: 'Action humanitaire' },
    { valeur: 'conseils_strategiques', libelle: 'Services-conseils stratégiques' },
    { valeur: 'autre', libelle: 'Autre' },
  ];

  readonly variablesDisponibles = [
    'client_nom', 'client_type', 'client_adresse', 'client_telephone', 'client_email',
    'dossier_reference', 'dossier_titre', 'dossier_type_affaire', 'dossier_date_ouverture',
    'avocat_nom', 'avocat_adverse_nom', 'cabinet_nom', 'cabinet_adresse', 'cabinet_telephone', 'date_jour',
  ];

  constructor(
    private modeleDocumentService: ModeleDocumentService,
    private notification: NotificationService,
    private confirmService: ConfirmService
  ) {}

  ngOnInit(): void {
    this.charger();
  }

  charger(): void {
    this.chargement = true;
    this.modeleDocumentService.liste().subscribe({
      next: (m) => {
        this.modeles = m;
        this.chargement = false;
      },
      error: (err) => {
        this.chargement = false;
        this.notification.erreur(err?.error?.message || 'Impossible de charger les modèles.');
      },
    });
  }

  libelleTypeAffaire(type: string | null): string {
    if (!type) return 'Générique (tous types)';
    return this.typesAffaire.find((t) => t.valeur === type)?.libelle ?? type;
  }

  selectionnerFichier(event: Event): void {
    const input = event.target as HTMLInputElement;
    this.fichierSelectionne = input.files?.[0] ?? null;
  }

  televerser(): void {
    if (!this.nomFormulaire.trim() || !this.fichierSelectionne) {
      this.notification.erreur('Le nom et le fichier Word (.docx) sont obligatoires.');
      return;
    }

    this.televersementEnCours = true;
    this.modeleDocumentService
      .televerser(this.nomFormulaire, this.descriptionFormulaire, this.typeAffaireFormulaire, this.fichierSelectionne)
      .subscribe({
        next: () => {
          this.televersementEnCours = false;
          this.notification.succes('Modèle téléversé.');
          this.nomFormulaire = '';
          this.descriptionFormulaire = '';
          this.typeAffaireFormulaire = null;
          this.fichierSelectionne = null;
          this.charger();
        },
        error: (err) => {
          this.televersementEnCours = false;
          this.notification.erreur(err?.error?.message || 'Impossible de téléverser ce modèle.');
        },
      });
  }

  supprimer(modele: ModeleDocument): void {
    this.confirmService
      .demander({ titre: 'Supprimer ce modèle ?', message: `"${modele.nom}" ne sera plus disponible pour générer de nouveaux documents.`, libelleConfirmer: 'Supprimer', destructif: true })
      .subscribe((confirme) => {
        if (!confirme) return;
        this.modeleDocumentService.supprimer(modele.id).subscribe({
          next: () => {
            this.notification.succes('Modèle supprimé.');
            this.charger();
          },
          error: (err) => this.notification.erreur(err?.error?.message || 'Impossible de supprimer ce modèle.'),
        });
      });
  }
}
