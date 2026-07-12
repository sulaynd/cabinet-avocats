import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { CabinetSettingService } from '../../../core/services/cabinet-setting.service';
import { CoordonneesCabinet } from '../../../core/models/cabinet-setting.model';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterLink],
  templateUrl: './login.component.html',
})
export class LoginComponent implements OnInit {
  chargement = false;
  erreur = '';
  sessionExpiree = false;
  cabinet: CoordonneesCabinet | null = null;

  private fb = inject(FormBuilder);

  form = this.fb.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', Validators.required],
  });

  constructor(
    private auth: AuthService,
    private router: Router,
    private route: ActivatedRoute,
    private cabinetSettingService: CabinetSettingService
  ) {
    this.sessionExpiree = this.route.snapshot.queryParamMap.get('session_expiree') === '1';
  }

  ngOnInit(): void {
    this.cabinetSettingService.public().subscribe((c) => (this.cabinet = c));
  }

  /**
   * Dérive un court monogramme pour le sceau à partir du nom du cabinet — par
   * exemple "JCA" depuis "JCA — Juristyle Conseil & Accompagnement". Si le nom
   * change un jour, le sceau reste cohérent sans qu'on ait à retoucher le code.
   */
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

    this.auth.login(email!, password!).subscribe({
      next: (res) => {
        this.chargement = false;
        if (res.user.doit_changer_mot_de_passe) {
          this.router.navigate(['/changer-mot-de-passe']);
          return;
        }
        const redirect = this.route.snapshot.queryParamMap.get('redirect') || '/dossiers';
        this.router.navigateByUrl(redirect);
      },
      error: () => {
        this.chargement = false;
        this.erreur = 'Email ou mot de passe incorrect.';
      },
    });
  }
}
