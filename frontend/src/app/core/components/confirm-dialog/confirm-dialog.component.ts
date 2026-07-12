import { Component, Inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MAT_DIALOG_DATA, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';

export interface DonneesConfirmDialog {
  titre: string;
  message: string;
  libelleConfirmer?: string;
  libelleAnnuler?: string;
  destructif?: boolean;
}

@Component({
  selector: 'app-confirm-dialog',
  standalone: true,
  imports: [CommonModule, MatDialogModule, MatButtonModule],
  templateUrl: './confirm-dialog.component.html',
})
export class ConfirmDialogComponent {
  constructor(
    private dialogRef: MatDialogRef<ConfirmDialogComponent, boolean>,
    @Inject(MAT_DIALOG_DATA) public data: DonneesConfirmDialog
  ) {}

  annuler(): void {
    this.dialogRef.close(false);
  }

  confirmer(): void {
    this.dialogRef.close(true);
  }
}
