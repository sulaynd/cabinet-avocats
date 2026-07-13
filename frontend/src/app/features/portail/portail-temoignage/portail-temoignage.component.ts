import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatChipsModule } from '@angular/material/chips';
import { TemoignageService } from '../../../core/services/temoignage.service';
import { NotificationService } from '../../../core/services/notification.service';
import { TemoignageAdmin } from '../../../core/models/temoignage.model';

@Component({
  selector: 'app-portail-temoignage',
  standalone: true,
  imports: [
    CommonModule, ReactiveFormsModule, RouterLink,
    MatFormFieldModule, MatInputModule, MatButtonModule, MatIconModule, MatChipsModule,
  ],
  templateUrl: './portail-temoignage.component.html',
})
export class PortailTemoignageComponent implements OnInit {
  chargement = true;
  enregistrement = false;
  temoignageExistant: TemoignageAdmin | null = null;

  private fb = inject(FormBuilder);

  form = this.fb.group({
    texte: this.fb.nonNullable.control('', [Validators.required, Validators.maxLength(2000)]),
  });

  constructor(
    private temoignageService: TemoignageService,
    private notification: NotificationService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.temoignageService.monTemoignage().subscribe({
      next: (t) => {
        this.temoignageExistant = t;
        if (t) this.form.patchValue({ texte: t.texte });
        this.chargement = false;
      },
      error: () => (this.chargement = false),
    });
  }

  soumettre(): void {
    if (this.form.invalid) return;
    this.enregistrement = true;

    this.temoignageService.soumettreDepuisPortail(this.form.value.texte!).subscribe({
      next: (t) => {
        this.enregistrement = false;
        this.temoignageExistant = t;
        this.notification.succes(
          "Merci ! Votre témoignage a été envoyé et sera visible sur la page d'accueil après validation par le cabinet."
        );
      },
      error: (err) => {
        this.enregistrement = false;
        this.notification.erreur(err?.error?.message || "Impossible d'envoyer votre témoignage, merci de réessayer.");
      },
    });
  }

  annuler(): void {
    this.router.navigate(['/portail/mes-dossiers']);
  }
}
