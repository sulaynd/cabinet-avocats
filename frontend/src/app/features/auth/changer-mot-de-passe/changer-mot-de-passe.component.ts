import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, ReactiveFormsModule, Validators, AbstractControl, ValidationErrors } from '@angular/forms';
import { Router } from '@angular/router';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { AuthService } from '../../../core/services/auth.service';

function motsDePasseIdentiques(control: AbstractControl): ValidationErrors | null {
  const mdp = control.get('password')?.value;
  const confirmation = control.get('confirmation')?.value;
  return mdp && confirmation && mdp !== confirmation ? { motsDePasseDifferents: true } : null;
}

@Component({
  selector: 'app-changer-mot-de-passe',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, MatFormFieldModule, MatInputModule, MatButtonModule],
  templateUrl: './changer-mot-de-passe.component.html',
})
export class ChangerMotDePasseComponent {
  chargement = false;
  erreur = '';

  private fb = inject(FormBuilder);

  form = this.fb.group(
    {
      password: this.fb.nonNullable.control('', [Validators.required, Validators.minLength(8)]),
      confirmation: this.fb.nonNullable.control('', Validators.required),
    },
    { validators: motsDePasseIdentiques }
  );

  constructor(private auth: AuthService, private router: Router) {}

  annuler(): void {
    this.router.navigate(['/dossiers']);
  }

  soumettre(): void {
    if (this.form.invalid) return;
    this.chargement = true;
    this.erreur = '';

    this.auth.changerMotDePasse(this.form.value.password!).subscribe({
      next: () => {
        this.chargement = false;
        this.router.navigate(['/dossiers']);
      },
      error: (err) => {
        this.chargement = false;
        this.erreur = err?.error?.message || 'Impossible de changer le mot de passe, merci de réessayer.';
      },
    });
  }
}
