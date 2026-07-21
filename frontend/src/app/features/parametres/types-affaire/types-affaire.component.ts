import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { TypeAffaireService } from '../../../core/services/type-affaire.service';
import { NotificationService } from '../../../core/services/notification.service';
import { ConfirmService } from '../../../core/services/confirm.service';
import { TypeAffaire, SousCategorieAffaire } from '../../../core/models/type-affaire.model';

@Component({
  selector: 'app-types-affaire',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink, MatButtonModule, MatIconModule],
  templateUrl: './types-affaire.component.html',
})
export class TypesAffaireComponent implements OnInit {
  types: TypeAffaire[] = [];
  chargement = true;
  typeDeplie: number | null = null;

  nouveauTypeLibelle = '';
  nouvelleSousCategorieLibelle: Record<number, string> = {};

  constructor(
    private typeAffaireService: TypeAffaireService,
    private notification: NotificationService,
    private confirmService: ConfirmService
  ) {}

  ngOnInit(): void {
    this.charger();
  }

  charger(): void {
    this.chargement = true;
    this.typeAffaireService.liste().subscribe({
      next: (t) => {
        this.types = t;
        this.chargement = false;
      },
      error: (err) => {
        this.chargement = false;
        this.notification.erreur(err?.error?.message || 'Impossible de charger les types d\'affaire.');
      },
    });
  }

  deplierType(id: number): void {
    this.typeDeplie = this.typeDeplie === id ? null : id;
  }

  ajouterType(): void {
    if (!this.nouveauTypeLibelle.trim()) return;
    this.typeAffaireService.creerType(this.nouveauTypeLibelle).subscribe({
      next: () => {
        this.notification.succes('Type d\'affaire ajouté.');
        this.nouveauTypeLibelle = '';
        this.charger();
      },
      error: (err) => this.notification.erreur(err?.error?.message || "Impossible d'ajouter ce type."),
    });
  }

  renommerType(type: TypeAffaire): void {
    const nouveauLibelle = prompt('Nouveau libellé :', type.libelle);
    if (!nouveauLibelle || !nouveauLibelle.trim() || nouveauLibelle === type.libelle) return;
    this.typeAffaireService.modifierType(type.id, { libelle: nouveauLibelle }).subscribe({
      next: () => {
        this.notification.succes('Type renommé.');
        this.charger();
      },
      error: (err) => this.notification.erreur(err?.error?.message || 'Impossible de renommer ce type.'),
    });
  }

  basculerActifType(type: TypeAffaire): void {
    this.typeAffaireService.modifierType(type.id, { actif: !type.actif }).subscribe({
      next: () => {
        this.notification.succes(type.actif ? 'Type désactivé.' : 'Type activé.');
        this.charger();
      },
      error: (err) => this.notification.erreur(err?.error?.message || 'Impossible de modifier ce type.'),
    });
  }

  supprimerType(type: TypeAffaire): void {
    this.confirmService
      .demander({
        titre: 'Supprimer ce type d\'affaire ?',
        message: `"${type.libelle}" et toutes ses sous-catégories seront définitivement supprimés. Les dossiers existants gardent leur type actuel (affiché tel quel), mais ce type ne sera plus proposé.`,
        libelleConfirmer: 'Supprimer',
        destructif: true,
      })
      .subscribe((confirme) => {
        if (!confirme) return;
        this.typeAffaireService.supprimerType(type.id).subscribe({
          next: () => {
            this.notification.succes('Type supprimé.');
            this.charger();
          },
          error: (err) => this.notification.erreur(err?.error?.message || 'Impossible de supprimer ce type.'),
        });
      });
  }

  ajouterSousCategorie(type: TypeAffaire): void {
    const libelle = this.nouvelleSousCategorieLibelle[type.id]?.trim();
    if (!libelle) return;
    this.typeAffaireService.creerSousCategorie(type.id, libelle).subscribe({
      next: () => {
        this.notification.succes('Sous-catégorie ajoutée.');
        this.nouvelleSousCategorieLibelle[type.id] = '';
        this.charger();
      },
      error: (err) => this.notification.erreur(err?.error?.message || "Impossible d'ajouter cette sous-catégorie."),
    });
  }

  renommerSousCategorie(sc: SousCategorieAffaire): void {
    const nouveauLibelle = prompt('Nouveau libellé :', sc.libelle);
    if (!nouveauLibelle || !nouveauLibelle.trim() || nouveauLibelle === sc.libelle) return;
    this.typeAffaireService.modifierSousCategorie(sc.id, { libelle: nouveauLibelle }).subscribe({
      next: () => {
        this.notification.succes('Sous-catégorie renommée.');
        this.charger();
      },
      error: (err) => this.notification.erreur(err?.error?.message || 'Impossible de renommer cette sous-catégorie.'),
    });
  }

  basculerActifSousCategorie(sc: SousCategorieAffaire): void {
    this.typeAffaireService.modifierSousCategorie(sc.id, { actif: !sc.actif }).subscribe({
      next: () => {
        this.notification.succes(sc.actif ? 'Sous-catégorie désactivée.' : 'Sous-catégorie activée.');
        this.charger();
      },
      error: (err) => this.notification.erreur(err?.error?.message || 'Impossible de modifier cette sous-catégorie.'),
    });
  }

  supprimerSousCategorie(sc: SousCategorieAffaire): void {
    this.confirmService
      .demander({ titre: 'Supprimer cette sous-catégorie ?', message: `"${sc.libelle}" sera définitivement supprimée.`, libelleConfirmer: 'Supprimer', destructif: true })
      .subscribe((confirme) => {
        if (!confirme) return;
        this.typeAffaireService.supprimerSousCategorie(sc.id).subscribe({
          next: () => {
            this.notification.succes('Sous-catégorie supprimée.');
            this.charger();
          },
          error: (err) => this.notification.erreur(err?.error?.message || 'Impossible de supprimer cette sous-catégorie.'),
        });
      });
  }
}
