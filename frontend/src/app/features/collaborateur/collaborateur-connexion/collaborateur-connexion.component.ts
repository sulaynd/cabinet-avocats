import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { CollaborateurAuthService } from '../../../core/services/collaborateur-auth.service';
import { CabinetSettingService } from '../../../core/services/cabinet-setting.service';
import { CoordonneesCabinet } from '../../../core/models/cabinet-setting.model';

@Component({
  selector: 'app-collaborateur-connexion',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterLink, MatFormFieldModule, MatInputModule, MatButtonModule],
  templateUrl: './collaborateur-connexion.component.html',
})
export class CollaborateurConnexionComponent implements OnInit {
  chargement = false;
  erreur = '';
  cabinet: CoordonneesCabinet | null = null;

  private fb = inject(FormBuilder);

  form = this.fb.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', Validators.required],
  });

  constructor(
    private collaborateurAuth: CollaborateurAuthService,
    private router: Router,
    private cabinetSettingService: CabinetSettingService
  ) {}

  ngOnInit(): void {
    this.cabinetSettingService.public().subscribe((c) => (this.cabinet = c));
  }

  soumettre(): void {
    if (this.form.invalid) return;
    this.chargement = true;
    this.erreur = '';

    const { email, password } = this.form.value;

    this.collaborateurAuth.connexion(email!, password!).subscribe({
      next: (res) => {
        this.chargement = false;
        if (res.collaborateur.doit_changer_mot_de_passe) {
          this.router.navigate(['/collaborateur/changer-mot-de-passe']);
        } else {
          this.router.navigate(['/collaborateur/mes-dossiers']);
        }
      },
      error: () => {
        this.chargement = false;
        this.erreur = 'Email ou mot de passe incorrect.';
      },
    });
  }
}
