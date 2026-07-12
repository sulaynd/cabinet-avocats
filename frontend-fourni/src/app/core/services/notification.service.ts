import { Injectable } from '@angular/core';
import { MatSnackBar } from '@angular/material/snack-bar';

/**
 * Remplace les `alert()` du navigateur par des notifications Material
 * cohérentes avec le reste de l'interface. Point d'entrée unique : les
 * composants n'injectent plus MatSnackBar directement.
 */
@Injectable({ providedIn: 'root' })
export class NotificationService {
  constructor(private snackBar: MatSnackBar) {}

  succes(message: string): void {
    this.snackBar.open(message, 'OK', { duration: 3000, panelClass: 'snackbar-succes' });
  }

  erreur(message: string): void {
    this.snackBar.open(message, 'Fermer', { duration: 5000, panelClass: 'snackbar-erreur' });
  }

  info(message: string): void {
    this.snackBar.open(message, undefined, { duration: 3000 });
  }
}
