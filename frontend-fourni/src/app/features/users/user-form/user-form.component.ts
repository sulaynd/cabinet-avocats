import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { UserService, UtilisateurPayload } from '../../../core/services/user.service';
import { Role } from '../../../core/models/user.model';

@Component({
  selector: 'app-user-form',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './user-form.component.html',
})
export class UserFormComponent implements OnInit {
  userId: number | null = null;
  enregistrement = false;
  erreur = '';

  readonly roles: Role[] = ['admin', 'avocat', 'assistant'];

  private fb = inject(FormBuilder);

  form = this.fb.group({
    // fb.nonNullable.control(...) plutôt que la syntaxe raccourcie ['', ...] :
    // par défaut, Angular type les contrôles de fb.group() comme `T | null`
    // (un contrôle peut toujours être réinitialisé à null), ce qui ne correspond
    // pas à `UtilisateurPayload` (name/email/role: string, jamais null). Seul
    // taux_horaire_defaut reste volontairement nullable (champ optionnel).
    name: this.fb.nonNullable.control('', Validators.required),
    email: this.fb.nonNullable.control('', [Validators.required, Validators.email]),
    password: this.fb.nonNullable.control(''),
    role: this.fb.nonNullable.control('assistant' as Role, Validators.required),
    phone: this.fb.nonNullable.control(''),
    taux_horaire_defaut: this.fb.control(null as number | null),
  });

  constructor(
    private userService: UserService,
    private route: ActivatedRoute,
    private router: Router
  ) {}

  ngOnInit(): void {
    const idParam = this.route.snapshot.paramMap.get('id');
    if (idParam) {
      this.userId = Number(idParam);
      // En modification, le mot de passe devient optionnel (laisser vide = inchangé).
      this.form.get('password')?.clearValidators();
      this.form.get('password')?.updateValueAndValidity();

      this.userService.obtenir(this.userId).subscribe((utilisateur) => {
        this.form.patchValue({
          name: utilisateur.name,
          email: utilisateur.email,
          role: utilisateur.role,
          phone: utilisateur.phone ?? '',
          taux_horaire_defaut: utilisateur.taux_horaire_defaut ?? null,
        });
      });
    } else {
      this.form.get('password')?.setValidators([Validators.required, Validators.minLength(8)]);
      this.form.get('password')?.updateValueAndValidity();
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
      next: () => {
        this.enregistrement = false;
        this.router.navigate(['/utilisateurs']);
      },
      error: (err) => {
        this.enregistrement = false;
        this.erreur = err?.error?.message || "Impossible d'enregistrer cet utilisateur.";
      },
    });
  }
}
