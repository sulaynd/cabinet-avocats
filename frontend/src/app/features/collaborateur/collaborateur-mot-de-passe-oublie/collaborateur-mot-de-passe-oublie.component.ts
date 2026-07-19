import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { CollaborateurAuthService } from '../../../core/services/collaborateur-auth.service';

@Component({
  selector: 'app-collaborateur-mot-de-passe-oublie',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterLink, MatFormFieldModule, MatInputModule, MatButtonModule],
  templateUrl: './collaborateur-mot-de-passe-oublie.component.html',
})
export class CollaborateurMotDePasseOublieComponent {
  chargement = false;
  messageEnvoye = false;
  erreur = '';

  private fb = inject(FormBuilder);

  form = this.fb.group({
    email: this.fb.nonNullable.control('', [Validators.required, Validators.email]),
  });

  constructor(private collaborateurAuth: CollaborateurAuthService) {}

  soumettre(): void {
    if (this.form.invalid) return;
    this.chargement = true;
    this.erreur = '';

    this.collaborateurAuth.demanderReinitialisation(this.form.value.email!).subscribe({
      next: () => {
        this.chargement = false;
        this.messageEnvoye = true;
      },
      error: (err) => {
        this.chargement = false;
        this.erreur = err?.error?.message || 'Une erreur est survenue, merci de réessayer.';
      },
    });
  }
}
