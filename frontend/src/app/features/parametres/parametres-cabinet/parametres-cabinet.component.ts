import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { CabinetSettingService } from '../../../core/services/cabinet-setting.service';
import { NotificationService } from '../../../core/services/notification.service';

@Component({
  selector: 'app-parametres-cabinet',
  standalone: true,
  imports: [
    CommonModule, ReactiveFormsModule,
    MatFormFieldModule, MatInputModule, MatButtonModule, MatIconModule,
  ],
  templateUrl: './parametres-cabinet.component.html',
})
export class ParametresCabinetComponent implements OnInit {
  enregistrement = false;
  chargement = true;
  photoUrl: string | null = null;
  fichierSelectionne: File | null = null;
  apercuFichier: string | null = null;
  televersementEnCours = false;

  private fb = inject(FormBuilder);

  form = this.fb.group({
    nom: this.fb.nonNullable.control('', Validators.required),
    adresse: this.fb.control(''),
    telephone: this.fb.control(''),
    email: this.fb.control('', Validators.email),
  });

  constructor(
    private cabinetSettingService: CabinetSettingService,
    private notification: NotificationService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.cabinetSettingService.obtenir().subscribe({
      next: (c) => {
        this.form.patchValue({
          nom: c.nom,
          adresse: c.adresse ?? '',
          telephone: c.telephone ?? '',
          email: c.email ?? '',
        });
        this.photoUrl = c.photo_fondateur_url ?? null;
        this.chargement = false;
      },
      error: (err) => {
        this.chargement = false;
        this.notification.erreur(err?.error?.message || 'Impossible de charger les paramètres du cabinet.');
      },
    });
  }

  surSelectionPhoto(event: Event): void {
    const input = event.target as HTMLInputElement;
    const fichier = input.files?.[0] ?? null;
    this.fichierSelectionne = fichier;

    if (fichier) {
      const lecteur = new FileReader();
      lecteur.onload = () => (this.apercuFichier = lecteur.result as string);
      lecteur.readAsDataURL(fichier);
    } else {
      this.apercuFichier = null;
    }
  }

  televerserPhoto(): void {
    if (!this.fichierSelectionne) return;
    this.televersementEnCours = true;

    this.cabinetSettingService.televerserPhoto(this.fichierSelectionne).subscribe({
      next: (c) => {
        this.televersementEnCours = false;
        this.photoUrl = c.photo_fondateur_url ?? null;
        this.fichierSelectionne = null;
        this.apercuFichier = null;
        this.notification.succes('Photo mise à jour — visible immédiatement sur la page d\'accueil.');
      },
      error: (err) => {
        this.televersementEnCours = false;
        this.notification.erreur(err?.error?.message || 'Impossible de téléverser cette photo.');
      },
    });
  }

  soumettre(): void {
    if (this.form.invalid) return;
    this.enregistrement = true;

    this.cabinetSettingService.modifier(this.form.getRawValue()).subscribe({
      next: () => {
        this.enregistrement = false;
        this.notification.succes('Coordonnées du cabinet mises à jour. Elles s\'appliquent immédiatement (connexion, factures, emails...).');
      },
      error: (err) => {
        this.enregistrement = false;
        this.notification.erreur(err?.error?.message || "Impossible d'enregistrer ces coordonnées.");
      },
    });
  }

  annuler(): void {
    this.router.navigate(['/dossiers']);
  }
}
