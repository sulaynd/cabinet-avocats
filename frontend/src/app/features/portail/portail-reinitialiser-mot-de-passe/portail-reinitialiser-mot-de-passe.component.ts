import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, ReactiveFormsModule, Validators, AbstractControl, ValidationErrors } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { PortailAuthService } from '../../../core/services/portail-auth.service';

function motsDePasseIdentiques(control: AbstractControl): ValidationErrors | null {
  const mdp = control.get('password')?.value;
  const confirmation = control.get('confirmation')?.value;
  return mdp && confirmation && mdp !== confirmation ? { motsDePasseDifferents: true } : null;
}

@Component({
  selector: 'app-portail-reinitialiser-mot-de-passe',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterLink, MatFormFieldModule, MatInputModule, MatButtonModule],
  templateUrl: './portail-reinitialiser-mot-de-passe.component.html',
})
export class PortailReinitialiserMotDePasseComponent implements OnInit {
  chargement = false;
  reussi = false;
  erreur = '';
  lienInvalide = false;

  private email = '';
  private token = '';
  private fb = inject(FormBuilder);

  form = this.fb.group(
    {
      password: this.fb.nonNullable.control('', [Validators.required, Validators.minLength(8)]),
      confirmation: this.fb.nonNullable.control('', Validators.required),
    },
    { validators: motsDePasseIdentiques }
  );

  constructor(private portailAuth: PortailAuthService, private route: ActivatedRoute, private router: Router) {}

  ngOnInit(): void {
    this.email = this.route.snapshot.queryParamMap.get('email') || '';
    this.token = this.route.snapshot.queryParamMap.get('token') || '';

    if (!this.email || !this.token) {
      this.lienInvalide = true;
    }
  }

  soumettre(): void {
    if (this.form.invalid) return;
    this.chargement = true;
    this.erreur = '';

    this.portailAuth.reinitialiserMotDePasse(this.email, this.token, this.form.value.password!).subscribe({
      next: () => {
        this.chargement = false;
        this.reussi = true;
      },
      error: (err) => {
        this.chargement = false;
        this.erreur = err?.error?.message || 'Impossible de réinitialiser le mot de passe.';
      },
    });
  }
}
