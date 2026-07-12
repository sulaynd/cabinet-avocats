import { Injectable } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { Observable } from 'rxjs';
import { ConfirmDialogComponent, DonneesConfirmDialog } from '../components/confirm-dialog/confirm-dialog.component';

/**
 * Remplace le `confirm()` du navigateur par une boîte de dialogue Material.
 * Usage : this.confirmService.demander({...}).subscribe(confirme => { if (confirme) ... })
 */
@Injectable({ providedIn: 'root' })
export class ConfirmService {
  constructor(private dialog: MatDialog) {}

  demander(data: DonneesConfirmDialog): Observable<boolean> {
    const ref = this.dialog.open(ConfirmDialogComponent, { data, width: '420px' });

    return new Observable<boolean>((observer) => {
      ref.afterClosed().subscribe((resultat) => {
        observer.next(!!resultat);
        observer.complete();
      });
    });
  }
}
