import { Directive, Input, TemplateRef, ViewContainerRef, effect } from '@angular/core';
import { AuthService, Utilisateur } from '../services/auth.service';

/**
 * Affiche le contenu uniquement si l'utilisateur courant a l'un des rôles donnés.
 *
 * Exemple :
 *   <button *appHasRole="['admin', 'avocat']">Supprimer la facture</button>
 */
@Directive({
  selector: '[appHasRole]',
  standalone: true,
})
export class HasRoleDirective {
  private rolesAutorises: Array<Utilisateur['role']> = [];
  private affiche = false;

  constructor(
    private templateRef: TemplateRef<unknown>,
    private viewContainer: ViewContainerRef,
    private auth: AuthService
  ) {
    effect(() => {
      // Se réévalue automatiquement quand `currentUser` (signal) change.
      this.auth.currentUser();
      this.mettreAJour();
    });
  }

  @Input() set appHasRole(roles: Array<Utilisateur['role']> | Utilisateur['role']) {
    this.rolesAutorises = Array.isArray(roles) ? roles : [roles];
    this.mettreAJour();
  }

  private mettreAJour(): void {
    const autorise = this.auth.hasRole(...this.rolesAutorises);

    if (autorise && !this.affiche) {
      this.viewContainer.createEmbeddedView(this.templateRef);
      this.affiche = true;
    } else if (!autorise && this.affiche) {
      this.viewContainer.clear();
      this.affiche = false;
    }
  }
}
