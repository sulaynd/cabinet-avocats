import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatButtonModule } from '@angular/material/button';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatDatepickerModule } from '@angular/material/datepicker';
import { MatNativeDateModule } from '@angular/material/core';
import { MatIconModule } from '@angular/material/icon';
import { DossierService } from '../../../core/services/dossier.service';
import { ClientService } from '../../../core/services/client.service';
import { UserService } from '../../../core/services/user.service';
import { AuthService } from '../../../core/services/auth.service';
import { NotificationService } from '../../../core/services/notification.service';
import { Client } from '../../../core/models/client.model';
import { Dossier } from '../../../core/models/dossier.model';
import { Utilisateur } from '../../../core/models/user.model';

@Component({
  selector: 'app-dossier-form',
  standalone: true,
  imports: [
    CommonModule, ReactiveFormsModule,
    MatFormFieldModule, MatInputModule, MatSelectModule, MatButtonModule,
    MatCheckboxModule, MatDatepickerModule, MatNativeDateModule, MatIconModule,
  ],
  templateUrl: './dossier-form.component.html',
})
export class DossierFormComponent implements OnInit {
  dossierId: number | null = null;
  clients: Client[] = [];
  avocats: Utilisateur[] = [];
  assistants: Utilisateur[] = [];
  enregistrement = false;

  readonly typesAffaire = ['civil', 'penal', 'commercial', 'famille', 'travail', 'immobilier', 'autre'];
  readonly statuts = ['ouvert', 'en_cours', 'en_attente', 'clos', 'archive'];

  // Déclaré via inject() (et non par constructeur) car utilisé dans l'initialiseur
  // de `form` juste en dessous : un paramètre de constructeur ne serait pas encore
  // assigné à ce stade (avec les sémantiques de champs de classe ES2022+), ce qui
  // provoquerait l'erreur "used before its initialization".
  private fb = inject(FormBuilder);

  form = this.fb.group({
    // client_id / avocat_id restent nullables : c'est l'état réel tant que rien
    // n'est sélectionné (Validators.required empêche la soumission avant ce choix).
    client_id: this.fb.control(null as number | null, Validators.required),
    avocat_id: this.fb.control(null as number | null, Validators.required),
    assistant_id: this.fb.control(null as number | null),
    titre: this.fb.nonNullable.control('', Validators.required),
    type_affaire: this.fb.nonNullable.control('autre', Validators.required),
    statut: this.fb.nonNullable.control('ouvert', Validators.required),
    mode_facturation: this.fb.nonNullable.control('horaire' as 'horaire' | 'forfait', Validators.required),
    taux_horaire: this.fb.control(null as number | null),
    montant_forfait: this.fb.control(null as number | null),
    facturation_periodique: this.fb.nonNullable.control(false),
    frequence_facturation: this.fb.control(null as 'hebdomadaire' | 'mensuelle' | null),
    facturer_a_cloture: this.fb.nonNullable.control(false),
    // MatDatepicker travaille avec des objets Date ; converti en chaîne ISO à l'envoi (voir soumettre()).
    date_ouverture: this.fb.control(null as Date | null),
    description: this.fb.nonNullable.control(''),
    envoyer_questionnaire_accueil: this.fb.nonNullable.control(true),
  });

  constructor(
    private dossierService: DossierService,
    private clientService: ClientService,
    private userService: UserService,
    private auth: AuthService,
    private notification: NotificationService,
    private route: ActivatedRoute,
    private router: Router
  ) {}

  /**
   * Seul un admin peut choisir/modifier librement l'avocat responsable et
   * l'assistant traitant. Pour un avocat/assistant, ces champs sont désactivés :
   * à la création le backend l'auto-assigne de toute façon, et en modification
   * le backend ignore silencieusement tout changement venant d'un non-admin —
   * autant ne pas laisser croire que la sélection a un effet.
   */
  get peutAssigner(): boolean {
    return this.auth.hasRole('admin');
  }

  ngOnInit(): void {
    this.clientService.liste({ per_page: 100 }).subscribe((res) => (this.clients = res.data));
    // Le champ "avocat responsable" ne propose que les utilisateurs de rôle avocat ;
    // le champ "assistant traitant" (facultatif) ne propose que le rôle assistant.
    this.userService.liste({ role: 'avocat', per_page: 100 }).subscribe((res) => (this.avocats = res.data));
    this.userService.liste({ role: 'assistant', per_page: 100 }).subscribe((res) => (this.assistants = res.data));

    const idParam = this.route.snapshot.paramMap.get('id');
    this.dossierId = idParam ? Number(idParam) : null;

    if (!this.peutAssigner) {
      if (this.dossierId) {
        // En modification, un non-admin ne peut de toute façon pas changer
        // l'assignation (ignoré côté serveur) : les deux champs sont désactivés.
        this.form.get('avocat_id')?.disable();
        this.form.get('assistant_id')?.disable();
      } else if (this.auth.hasRole('avocat')) {
        // À la création, un avocat s'auto-assigne (champ non pertinent) mais
        // peut librement choisir un assistant pour l'épauler sur ce dossier.
        this.form.get('avocat_id')?.disable();
      } else if (this.auth.hasRole('assistant')) {
        // Un assistant doit désigner l'avocat sous la responsabilité duquel le
        // dossier est ouvert ; il s'auto-assigne lui-même comme assistant traitant.
        this.form.get('assistant_id')?.disable();
      }
    }

    if (this.dossierId) {
      this.dossierService.obtenir(this.dossierId).subscribe((dossier) => {
        this.form.patchValue({
          client_id: dossier.client_id,
          avocat_id: dossier.avocat_id,
          assistant_id: dossier.assistant_id ?? null,
          titre: dossier.titre,
          type_affaire: dossier.type_affaire,
          statut: dossier.statut,
          mode_facturation: dossier.mode_facturation,
          taux_horaire: dossier.taux_horaire ?? null,
          montant_forfait: dossier.montant_forfait ?? null,
          facturation_periodique: dossier.facturation_periodique ?? false,
          frequence_facturation: dossier.frequence_facturation ?? null,
          facturer_a_cloture: dossier.facturer_a_cloture ?? false,
          date_ouverture: dossier.date_ouverture ? new Date(dossier.date_ouverture) : null,
          description: dossier.description ?? '',
        });
      });
    }
  }

  soumettre(): void {
    if (this.form.invalid) return;
    this.enregistrement = true;
    // getRawValue() pour inclure avocat_id/assistant_id même désactivés (valeur
    // affichée, ignorée côté serveur de toute façon pour un non-admin en édition).
    const valeurs = this.form.getRawValue();
    // Cast : client_id/avocat_id sont `number | null` côté formulaire (nuls tant
    // que rien n'est sélectionné), mais Validators.required + le garde ci-dessus
    // garantissent qu'ils sont bien renseignés à ce stade.
    const data = { ...valeurs, date_ouverture: this.versDateIso(valeurs.date_ouverture) } as unknown as Partial<Dossier>;

    const requete = this.dossierId
      ? this.dossierService.modifier(this.dossierId, data)
      : this.dossierService.creer(data);

    requete.subscribe({
      next: (dossier) => {
        this.enregistrement = false;
        this.notification.succes(this.dossierId ? 'Dossier modifié.' : `Dossier ${dossier.reference} créé.`);
        this.router.navigate(['/dossiers', dossier.id]);
      },
      error: (err) => {
        this.enregistrement = false;
        this.notification.erreur(err?.error?.message || "Impossible d'enregistrer ce dossier.");
      },
    });
  }

  private versDateIso(date: Date | null): string | null {
    if (!date) return null;
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
  }
}
