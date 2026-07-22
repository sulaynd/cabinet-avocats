import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { ClientService } from '../../../core/services/client.service';
import { NotificationService } from '../../../core/services/notification.service';
import { ConfirmService } from '../../../core/services/confirm.service';
import { Client } from '../../../core/models/client.model';

@Component({
  selector: 'app-client-form',
  standalone: true,
  imports: [
    CommonModule, ReactiveFormsModule,
    MatFormFieldModule, MatInputModule, MatSelectModule, MatButtonModule, MatIconModule,
  ],
  templateUrl: './client-form.component.html',
})
export class ClientFormComponent implements OnInit {
  clientId: number | null = null;
  enregistrement = false;
  activationPortailEnCours = false;
  portailActiveLe: string | null = null;

  // inject() plutôt qu'un paramètre de constructeur : voir le commentaire dans
  // dossier-form.component.ts pour l'explication de "used before its initialization".
  private fb = inject(FormBuilder);

  // fb.nonNullable.control(...) plutôt que ['', ...] : par défaut Angular type
  // les contrôles de fb.group() comme `T | null` (réinitialisables à null), ce
  // qui ne correspond pas à Client (champs `string`, jamais `null`).
  form = this.fb.group({
    type: this.fb.nonNullable.control('particulier' as 'particulier' | 'entreprise', Validators.required),
    nom: this.fb.nonNullable.control(''),
    prenom: this.fb.nonNullable.control(''),
    raison_sociale: this.fb.nonNullable.control(''),
    siret: this.fb.nonNullable.control(''),
    email: this.fb.nonNullable.control('', Validators.email),
    telephone: this.fb.nonNullable.control(''),
    adresse: this.fb.nonNullable.control(''),
    code_postal: this.fb.nonNullable.control(''),
    ville: this.fb.nonNullable.control(''),
    notes: this.fb.nonNullable.control(''),
  });

  constructor(
    private clientService: ClientService,
    private notification: NotificationService,
    private confirmService: ConfirmService,
    private route: ActivatedRoute,
    private router: Router
  ) {}

  ngOnInit(): void {
    // Validation conditionnelle : nom obligatoire pour un particulier, raison
    // sociale obligatoire pour une entreprise — pas les deux à la fois, et pas
    // aucun des deux (ce qui était possible avant ce correctif).
    this.form.get('type')!.valueChanges.subscribe((type) => this.appliquerValidationSelonType(type));
    this.appliquerValidationSelonType(this.form.value.type!);

    const idParam = this.route.snapshot.paramMap.get('id');
    if (idParam) {
      this.clientId = Number(idParam);
      this.clientService.obtenir(this.clientId).subscribe((client: Client) => {
        this.form.patchValue(client);
        this.appliquerValidationSelonType(client.type);
        this.portailActiveLe = client.portail_active_le ?? null;
        // L'email sert d'identifiant de connexion au portail client — le
        // modifier après coup pourrait rompre l'accès existant sans que
        // l'admin s'en rende compte. On le verrouille donc en modification
        // (toujours modifiable librement à la création).
        this.form.get('email')?.disable();
      });
    }
  }

  private appliquerValidationSelonType(type: 'particulier' | 'entreprise'): void {
    const nom = this.form.get('nom')!;
    const prenom = this.form.get('prenom')!;
    const email = this.form.get('email')!;
    const telephone = this.form.get('telephone')!;
    const raisonSociale = this.form.get('raison_sociale')!;

    if (type === 'entreprise') {
      nom.clearValidators();
      prenom.clearValidators();
      telephone.clearValidators();
      email.setValidators(Validators.email);
      raisonSociale.setValidators(Validators.required);
    } else {
      raisonSociale.clearValidators();
      nom.setValidators(Validators.required);
      prenom.setValidators(Validators.required);
      telephone.setValidators(Validators.required);
      email.setValidators([Validators.required, Validators.email]);
    }
    nom.updateValueAndValidity();
    prenom.updateValueAndValidity();
    email.updateValueAndValidity();
    telephone.updateValueAndValidity();
    raisonSociale.updateValueAndValidity();
  }

  get estEntreprise(): boolean {
    return this.form.value.type === 'entreprise';
  }

  annuler(): void {
    this.router.navigate(['/clients']);
  }

  activerPortail(): void {
    if (!this.clientId) return;
    const email = this.form.get('email')?.value;

    this.confirmService
      .demander({
        titre: this.portailActiveLe ? "Réinitialiser l'accès au portail ?" : "Activer l'accès au portail ?",
        message: `Un nouveau mot de passe sera généré et envoyé par email à ${email}. ${this.portailActiveLe ? "L'ancien mot de passe cessera de fonctionner." : ''}`,
        libelleConfirmer: this.portailActiveLe ? 'Réinitialiser' : 'Activer',
      })
      .subscribe((confirme) => {
        if (!confirme || !this.clientId) return;
        this.activationPortailEnCours = true;

        this.clientService.activerPortail(this.clientId).subscribe({
          next: () => {
            this.activationPortailEnCours = false;
            this.portailActiveLe = new Date().toISOString();
            this.notification.succes(`Identifiants envoyés par email à ${email}.`);
          },
          error: (err) => {
            this.activationPortailEnCours = false;
            this.notification.erreur(err?.error?.message || "Impossible d'activer le portail pour ce client.");
          },
        });
      });
  }

  soumettre(): void {
    if (this.form.invalid) return;
    this.enregistrement = true;
    const data = this.form.value;

    const requete = this.clientId
      ? this.clientService.modifier(this.clientId, data)
      : this.clientService.creer(data);

    requete.subscribe({
      next: () => {
        this.enregistrement = false;
        this.notification.succes(this.clientId ? 'Client modifié.' : 'Client créé.');
        this.router.navigate(['/clients']);
      },
      error: (err) => {
        this.enregistrement = false;
        this.notification.erreur(err?.error?.message || "Impossible d'enregistrer ce client.");
      },
    });
  }
}
