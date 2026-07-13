import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { UserService, UtilisateurPayload } from '../../../core/services/user.service';
import { NotificationService } from '../../../core/services/notification.service';
import { Role } from '../../../core/models/user.model';

@Component({
  selector: 'app-user-form',
  standalone: true,
  imports: [
    CommonModule, ReactiveFormsModule,
    MatFormFieldModule, MatInputModule, MatSelectModule, MatButtonModule, MatIconModule, MatCheckboxModule,
  ],
  templateUrl: './user-form.component.html',
})
export class UserFormComponent implements OnInit {
  userId: number | null = null;
  enregistrement = false;
  erreur = '';
  photoUrl: string | null = null;
  fichierSelectionne: File | null = null;
  apercuFichier: string | null = null;

  readonly roles: Role[] = ['admin', 'avocat', 'assistant', 'stagiaire'];

  private fb = inject(FormBuilder);

  form = this.fb.group({
    name: this.fb.nonNullable.control('', Validators.required),
    email: this.fb.nonNullable.control('', [Validators.required, Validators.email]),
    password: this.fb.nonNullable.control(''),
    role: this.fb.nonNullable.control('assistant' as Role, Validators.required),
    phone: this.fb.nonNullable.control(''),
    taux_horaire_defaut: this.fb.control(null as number | null),
    afficher_equipe_publique: this.fb.nonNullable.control(false),
    titre_public: this.fb.nonNullable.control(''),
    bio_publique: this.fb.nonNullable.control(''),
  });

  constructor(
    private userService: UserService,
    private notification: NotificationService,
    private route: ActivatedRoute,
    private router: Router
  ) {}

  ngOnInit(): void {
    const idParam = this.route.snapshot.paramMap.get('id');
    if (idParam) {
      this.userId = Number(idParam);
      this.form.get('password')?.clearValidators();
      this.form.get('password')?.updateValueAndValidity();

      this.userService.obtenir(this.userId).subscribe((utilisateur: any) => {
        this.form.patchValue({
          name: utilisateur.name,
          email: utilisateur.email,
          role: utilisateur.role,
          phone: utilisateur.phone ?? '',
          taux_horaire_defaut: utilisateur.taux_horaire_defaut ?? null,
          afficher_equipe_publique: utilisateur.afficher_equipe_publique ?? false,
          titre_public: utilisateur.titre_public ?? '',
          bio_publique: utilisateur.bio_publique ?? '',
        });
        this.photoUrl = utilisateur.photo_url ?? null;
      });
    } else {
      this.form.get('password')?.setValidators([Validators.required, Validators.minLength(8)]);
      this.form.get('password')?.updateValueAndValidity();
    }
  }

  annuler(): void {
    this.router.navigate(['/utilisateurs']);
  }

  surSelectionPhoto(event: Event): void {
    const input = event.target as HTMLInputElement;
    const fichier = input.files?.[0] ?? null;
    this.fichierSelectionne = fichier;

    if (fichier) {
      const lecteur = new FileReader();
      lecteur.onload = () => (this.apercuFichier = lecteur.result as string);
      lecteur.readAsDataURL(fichier);
    }
  }

  soumettre(): void {
    if (this.form.invalid) return;
    this.enregistrement = true;
    this.erreur = '';

    const data = { ...this.form.value } as unknown as Partial<UtilisateurPayload>;
    if (!data.password) delete data.password;

    const requete = this.userId
      ? this.userService.modifier(this.userId, data)
      : this.userService.creer(data as UtilisateurPayload);

    requete.subscribe({
      next: (u: any) => {
        this.enregistrement = false;
        const idReel = this.userId ?? u?.id;

        if (this.fichierSelectionne && idReel) {
          this.userService.televerserPhoto(idReel, this.fichierSelectionne).subscribe({
            next: () => {
              this.notification.succes('Membre et photo enregistrés.');
              this.router.navigate(['/utilisateurs']);
            },
            error: (err) => {
              this.notification.erreur(
                err?.error?.message || "Membre enregistré, mais la photo n'a pas pu être envoyée — réessayez depuis sa fiche."
              );
              this.router.navigate(['/utilisateurs']);
            },
          });
          return;
        }

        this.router.navigate(['/utilisateurs']);
      },
      error: (err) => {
        this.enregistrement = false;
        this.erreur = err?.error?.message || "Impossible d'enregistrer cet utilisateur.";
      },
    });
  }
}
