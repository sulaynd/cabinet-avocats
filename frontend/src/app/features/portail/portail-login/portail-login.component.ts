import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { PortailAuthService } from '../../../core/services/portail-auth.service';
import { CabinetSettingService } from '../../../core/services/cabinet-setting.service';
import { CoordonneesCabinet } from '../../../core/models/cabinet-setting.model';

@Component({
  selector: 'app-portail-login',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterLink, MatFormFieldModule, MatInputModule, MatButtonModule],
  templateUrl: './portail-login.component.html',
})
export class PortailLoginComponent implements OnInit {
  chargement = false;
  erreur = '';
  cabinet: CoordonneesCabinet | null = null;

  private fb = inject(FormBuilder);

  form = this.fb.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', Validators.required],
  });

  constructor(
    private portailAuth: PortailAuthService,
    private router: Router,
    private cabinetSettingService: CabinetSettingService
  ) {}

  ngOnInit(): void {
    this.cabinetSettingService.public().subscribe((c) => (this.cabinet = c));
  }

  initiales(nom: string | undefined): string {
    if (!nom) return '';
    const premierSegment = nom.split(/—|–|-/)[0].trim();
    if (premierSegment.length <= 6) return premierSegment;
    return nom
      .split(/\s+/)
      .filter((mot) => mot.length > 2)
      .slice(0, 3)
      .map((mot) => mot[0].toUpperCase())
      .join('');
  }

  soumettre(): void {
    if (this.form.invalid) return;
    this.chargement = true;
    this.erreur = '';

    const { email, password } = this.form.value;

    this.portailAuth.connexion(email!, password!).subscribe({
      next: (res) => {
        this.chargement = false;
        if (res.client.doit_changer_mot_de_passe) {
          this.router.navigate(['/portail/changer-mot-de-passe']);
        } else {
          this.router.navigate(['/portail/mes-dossiers']);
        }
      },
      error: () => {
        this.chargement = false;
        this.erreur = 'Email ou mot de passe incorrect.';
      },
    });
  }
}
