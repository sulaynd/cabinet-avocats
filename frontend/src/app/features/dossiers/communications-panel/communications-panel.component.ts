import { Component, Input, OnChanges, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatChipsModule } from '@angular/material/chips';
import { CommunicationService } from '../../../core/services/communication.service';
import { NotificationService } from '../../../core/services/notification.service';
import { ConfirmService } from '../../../core/services/confirm.service';
import { Communication, TypeCommunication } from '../../../core/models/communication.model';

@Component({
  selector: 'app-communications-panel',
  standalone: true,
  imports: [
    CommonModule, ReactiveFormsModule,
    MatFormFieldModule, MatInputModule, MatSelectModule, MatButtonModule, MatIconModule, MatChipsModule,
  ],
  templateUrl: './communications-panel.component.html',
})
export class CommunicationsPanelComponent implements OnChanges {
  @Input({ required: true }) dossierId!: number;

  communications: Communication[] = [];
  afficherFormulaire = false;

  readonly types: TypeCommunication[] = ['appel', 'email', 'courrier', 'reunion', 'note'];

  private fb = inject(FormBuilder);

  form = this.fb.group({
    type: this.fb.nonNullable.control('note' as TypeCommunication, Validators.required),
    objet: this.fb.nonNullable.control('', Validators.required),
    contenu: this.fb.control(''),
  });

  constructor(
    private communicationService: CommunicationService,
    private notification: NotificationService,
    private confirmService: ConfirmService
  ) {}

  ngOnChanges(): void {
    if (this.dossierId) this.charger();
  }

  charger(): void {
    this.communicationService.liste(this.dossierId).subscribe((c) => (this.communications = c));
  }

  enregistrer(): void {
    if (this.form.invalid) return;
    this.communicationService.creer(this.dossierId, this.form.value).subscribe({
      next: () => {
        this.form.reset({ type: 'note', objet: '', contenu: '' });
        this.afficherFormulaire = false;
        this.notification.succes('Communication ajoutée.');
        this.charger();
      },
      error: (err) => this.notification.erreur(err?.error?.message || "Impossible d'ajouter cette communication."),
    });
  }

  supprimer(id: number): void {
    this.confirmService
      .demander({ titre: 'Supprimer cette communication ?', libelleConfirmer: 'Supprimer', message: 'Cette action est définitive.', destructif: true })
      .subscribe((confirme) => {
        if (!confirme) return;
        this.communicationService.supprimer(id).subscribe({
          next: () => {
            this.notification.succes('Communication supprimée.');
            this.charger();
          },
          error: (err) => this.notification.erreur(err?.error?.message || 'Impossible de supprimer cette communication.'),
        });
      });
  }
}
