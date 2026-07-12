import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { ClientService } from '../../../core/services/client.service';
import { NotificationService } from '../../../core/services/notification.service';

@Component({
  selector: 'app-client-form',
  standalone: true,
  imports: [
    CommonModule, ReactiveFormsModule,
    MatFormFieldModule, MatInputModule, MatSelectModule, MatButtonModule, MatIconModule,
  ],
  templateUrl: './client-form.component.html',
})
export class ClientFormComponent implements OnInit {
  clientId: number | null = null;
  enregistrement = false;

  // inject() plutôt qu'un paramètre de constructeur : voir le commentaire dans
  // dossier-form.component.ts pour l'explication de "used before its initialization".
  private fb = inject(FormBuilder);

  // fb.nonNullable.control(...) plutôt que ['', ...] : par défaut Angular type
  // les contrôles de fb.group() comme `T | null` (réinitialisables à null), ce
  // qui ne correspond pas à Client (champs `string`, jamais `null`).
  form = this.fb.group({
    type: this.fb.nonNullable.control('particulier' as 'particulier' | 'entreprise', Validators.required),
    nom: this.fb.nonNullable.control(''),
    prenom: this.fb.nonNullable.control(''),
    raison_sociale: this.fb.nonNullable.control(''),
    siret: this.fb.nonNullable.control(''),
    email: this.fb.nonNullable.control('', Validators.email),
    telephone: this.fb.nonNullable.control(''),
    adresse: this.fb.nonNullable.control(''),
    code_postal: this.fb.nonNullable.control(''),
    ville: this.fb.nonNullable.control(''),
    notes: this.fb.nonNullable.control(''),
  });

  constructor(
    private clientService: ClientService,
    private notification: NotificationService,
    private route: ActivatedRoute,
    private router: Router
  ) {}

  ngOnInit(): void {
    const idParam = this.route.snapshot.paramMap.get('id');
    if (idParam) {
      this.clientId = Number(idParam);
      this.clientService.obtenir(this.clientId).subscribe((client) => {
        this.form.patchValue(client);
      });
    }
  }

  get estEntreprise(): boolean {
    return this.form.value.type === 'entreprise';
  }

  soumettre(): void {
    if (this.form.invalid) return;
    this.enregistrement = true;
    const data = this.form.value;

    const requete = this.clientId
      ? this.clientService.modifier(this.clientId, data)
      : this.clientService.creer(data);

    requete.subscribe({
      next: () => {
        this.enregistrement = false;
        this.notification.succes(this.clientId ? 'Client modifié.' : 'Client créé.');
        this.router.navigate(['/clients']);
      },
      error: (err) => {
        this.enregistrement = false;
        this.notification.erreur(err?.error?.message || "Impossible d'enregistrer ce client.");
      },
    });
  }
}
