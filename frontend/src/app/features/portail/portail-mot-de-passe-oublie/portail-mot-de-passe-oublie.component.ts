import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { PortailAuthService } from '../../../core/services/portail-auth.service';

@Component({
  selector: 'app-portail-mot-de-passe-oublie',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterLink, MatFormFieldModule, MatInputModule, MatButtonModule],
  templateUrl: './portail-mot-de-passe-oublie.component.html',
})
export class PortailMotDePasseOublieComponent {
  chargement = false;
  messageEnvoye = false;
  erreur = '';

  private fb = inject(FormBuilder);

  form = this.fb.group({
    email: this.fb.nonNullable.control('', [Validators.required, Validators.email]),
  });

  constructor(private portailAuth: PortailAuthService) {}

  soumettre(): void {
    if (this.form.invalid) return;
    this.chargement = true;
    this.erreur = '';

    this.portailAuth.demanderReinitialisation(this.form.value.email!).subscribe({
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
