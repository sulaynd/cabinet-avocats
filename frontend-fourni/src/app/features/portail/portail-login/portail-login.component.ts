import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { PortailAuthService } from '../../../core/services/portail-auth.service';

@Component({
  selector: 'app-portail-login',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './portail-login.component.html',
})
export class PortailLoginComponent {
  chargement = false;
  erreur = '';

  private fb = inject(FormBuilder);

  form = this.fb.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', Validators.required],
  });

  constructor(private portailAuth: PortailAuthService, private router: Router) {}

  soumettre(): void {
    if (this.form.invalid) return;
    this.chargement = true;
    this.erreur = '';

    const { email, password } = this.form.value;

    this.portailAuth.connexion(email!, password!).subscribe({
      next: () => {
        this.chargement = false;
        this.router.navigate(['/portail/mes-dossiers']);
      },
      error: () => {
        this.chargement = false;
        this.erreur = 'Email ou mot de passe incorrect.';
      },
    });
  }
}
