import { Component, Input, OnChanges, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { CommunicationService } from '../../../core/services/communication.service';
import { Communication, TypeCommunication } from '../../../core/models/communication.model';

@Component({
  selector: 'app-communications-panel',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
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

  constructor(private communicationService: CommunicationService) {}

  ngOnChanges(): void {
    if (this.dossierId) this.charger();
  }

  charger(): void {
    this.communicationService.liste(this.dossierId).subscribe((c) => (this.communications = c));
  }

  enregistrer(): void {
    if (this.form.invalid) return;
    this.communicationService.creer(this.dossierId, this.form.value).subscribe(() => {
      this.form.reset({ type: 'note', objet: '', contenu: '' });
      this.afficherFormulaire = false;
      this.charger();
    });
  }

  supprimer(id: number): void {
    if (!confirm('Supprimer cette communication ?')) return;
    this.communicationService.supprimer(id).subscribe(() => this.charger());
  }
}
