import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-mot-de-passe-oublie',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterLink, MatFormFieldModule, MatInputModule, MatButtonModule],
  templateUrl: './mot-de-passe-oublie.component.html',
})
export class MotDePasseOublieComponent {
  chargement = false;
  messageEnvoye = false;
  erreur = '';

  private fb = inject(FormBuilder);

  form = this.fb.group({
    email: this.fb.nonNullable.control('', [Validators.required, Validators.email]),
  });

  constructor(private auth: AuthService) {}

  soumettre(): void {
    if (this.form.invalid) return;
    this.chargement = true;
    this.erreur = '';

    this.auth.demanderReinitialisation(this.form.value.email!).subscribe({
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
