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
import { CabinetSettingService } from '../../../core/services/cabinet-setting.service';
import { Client } from '../../../core/models/client.model';
import { Dossier } from '../../../core/models/dossier.model';
import { Utilisateur } from '../../../core/models/user.model';
import { TypeAffaireService } from '../../../core/services/type-affaire.service';
import { TypeAffaire } from '../../../core/models/type-affaire.model';

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
  nomClientOriginal = '';
  clients: Client[] = [];
  avocats: Utilisateur[] = [];
  assistants: Utilisateur[] = [];
  enregistrement = false;

  private tauxHoraireModifieManuellement = false;
  tauxHoraireObligatoire = false;
  private tauxHoraireCabinet: number | null = null;
  private avocatModifieManuellement = false;
  suggestionAvocat: { nom: string; raison: string } | null = null;

  /** Sous-catégories actives du type d'affaire actuellement sélectionné —
   * peut être n'importe quel type (pas seulement immigration), selon ce qui
   * est configuré dans "Types d'affaire" (menu admin). */
  get sousCategoriesDuTypeChoisi(): { valeur: string; libelle: string }[] {
    const type = this.typesAffaire.find((t) => t.slug === this.form.value.type_affaire);
    return (type?.sous_categories ?? []).filter((sc) => sc.actif).map((sc) => ({ valeur: sc.slug, libelle: sc.libelle }));
  }

  estSousCategorieCochee(valeur: string): boolean {
    return (this.form.value.sous_categories_affaire ?? []).includes(valeur);
  }

  basculerSousCategorie(valeur: string, coche: boolean): void {
    const actuelles: string[] = this.form.value.sous_categories_affaire ?? [];
    const nouvelles = coche ? [...actuelles, valeur] : actuelles.filter((v) => v !== valeur);
    this.form.get('sous_categories_affaire')?.setValue(nouvelles);
  }

  get estStagiaire(): boolean {
    return this.auth.currentUser()?.role === 'stagiaire';
  }

  get assistantsUniquement(): Utilisateur[] {
    return this.assistants.filter((a) => a.role === 'assistant');
  }

  get stagiairesUniquement(): Utilisateur[] {
    return this.assistants.filter((a) => a.role === 'stagiaire');
  }

  // Chargés dynamiquement depuis l'API (gérables dans "Types d'affaire",
  // menu admin) plutôt que codés en dur — voir chargerTypesAffaire().
  typesAffaire: TypeAffaire[] = [];
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
    stagiaire_id: this.fb.control(null as number | null),
    titre: this.fb.nonNullable.control('', Validators.required),
    type_affaire: this.fb.nonNullable.control('', Validators.required),
    sous_categories_affaire: this.fb.nonNullable.control([] as string[]),
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
    public auth: AuthService,
    private notification: NotificationService,
    private cabinetSettingService: CabinetSettingService,
    private typeAffaireService: TypeAffaireService,
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
    this.cabinetSettingService.obtenir().subscribe((c) => (this.tauxHoraireCabinet = c.taux_horaire_defaut ?? null));
    this.typeAffaireService.liste(true).subscribe((t) => (this.typesAffaire = t));

    this.clientService.liste({ per_page: 100 }).subscribe((res) => (this.clients = res.data));
    // Le champ "avocat responsable" ne propose que les utilisateurs de rôle avocat ;
    // le champ "assistant traitant" (facultatif) ne propose que le rôle assistant.
    this.userService.liste({ role: 'avocat', per_page: 100 }).subscribe((res) => (this.avocats = res.data));
    this.userService.liste({ role: 'assistant,stagiaire', per_page: 100 }).subscribe((res) => (this.assistants = res.data));

    this.form.get('taux_horaire')?.valueChanges.subscribe(() => {
      this.tauxHoraireModifieManuellement = true;
    });

    this.form.get('avocat_id')?.valueChanges.subscribe((avocatId) => {
      this.avocatModifieManuellement = true;
      this.suggestionAvocat = null;

      // Revérifie quand même la suggestion pour ce type d'affaire : si le
      // choix manuel correspond exactement à ce que le système aurait
      // suggéré, on réaffiche la bannière informative (sans jamais
      // ré-imposer/écraser une sélection différente).
      const typeAffaireActuel = this.form.value.type_affaire;
      if (typeAffaireActuel && avocatId) {
        this.dossierService.suggererAvocat(typeAffaireActuel).subscribe((suggestion) => {
          if (suggestion.avocat_id === avocatId) {
            this.suggestionAvocat = { nom: suggestion.nom ?? '', raison: suggestion.raison ?? '' };
          }
        });
      }

      const avocat = this.avocats.find((a) => a.id === avocatId);
      const tauxParDefaut = avocat?.taux_horaire_defaut;
      const controleTaux = this.form.get('taux_horaire');

      if (tauxParDefaut) {
        // L'avocat a un taux par défaut configuré : on le propose (sans écraser
        // une valeur déjà saisie manuellement), et le champ redevient facultatif.
        if (!this.tauxHoraireModifieManuellement) {
          controleTaux?.setValue(tauxParDefaut, { emitEvent: false });
        }
        controleTaux?.clearValidators();
        this.tauxHoraireObligatoire = false;
      } else if (this.tauxHoraireCabinet) {
        // Ni le dossier ni l'avocat n'ont de taux — mais le cabinet a un taux
        // par défaut en dernier recours (voir Paramètres) : la facture ne
        // sera donc jamais à 0$ même sans saisie ici, le champ reste facultatif.
        controleTaux?.clearValidators();
        this.tauxHoraireObligatoire = false;
      } else {
        // Aucun taux nulle part (ni dossier, ni avocat, ni cabinet) : la
        // saisie devient obligatoire, pour éviter une facture à 0$.
        controleTaux?.setValidators(Validators.required);
        this.tauxHoraireObligatoire = true;
      }
      controleTaux?.updateValueAndValidity({ emitEvent: false });
    });

    this.form.get('mode_facturation')?.valueChanges.subscribe((mode) => {
      const controleForfait = this.form.get('montant_forfait');
      if (mode === 'forfait') {
        controleForfait?.setValidators([Validators.required, Validators.min(0.01)]);
      } else {
        controleForfait?.clearValidators();
      }
      controleForfait?.updateValueAndValidity({ emitEvent: false });
    });

    // Sous-catégories : obligatoires (au moins une) uniquement quand le type
    // d'affaire choisi a des sous-catégories actives configurées (n'importe
    // quel type, pas seulement immigration — voir "Types d'affaire" en admin).
    // S'applique en création comme en modification, contrairement à la
    // suggestion d'assignation.
    this.form.get('type_affaire')?.valueChanges.subscribe((type) => {
      const controleSousCategories = this.form.get('sous_categories_affaire');
      const typeChoisi = this.typesAffaire.find((t) => t.slug === type);
      const possedeSousCategories = (typeChoisi?.sous_categories ?? []).some((sc) => sc.actif);
      if (possedeSousCategories) {
        controleSousCategories?.setValidators(Validators.required);
      } else {
        controleSousCategories?.clearValidators();
        controleSousCategories?.setValue([], { emitEvent: false });
      }
      controleSousCategories?.updateValueAndValidity({ emitEvent: false });
    });

    const idParam = this.route.snapshot.paramMap.get('id');
    this.dossierId = idParam ? Number(idParam) : null;

    if (!this.dossierId) {
      // Venant d'un rendez-vous confirmé : pré-remplit le client et l'avocat
      // souhaité par le client, pour éviter d'avoir à les rechercher à nouveau.
      const clientIdParam = this.route.snapshot.queryParamMap.get('client_id');
      const avocatIdParam = this.route.snapshot.queryParamMap.get('avocat_id');
      const typeAffaireParam = this.route.snapshot.queryParamMap.get('type_affaire');
      if (clientIdParam) this.form.patchValue({ client_id: Number(clientIdParam) });
      if (avocatIdParam) this.form.patchValue({ avocat_id: Number(avocatIdParam) });
      if (typeAffaireParam) this.form.patchValue({ type_affaire: typeAffaireParam });

      // Suggestion d'assignation automatique (spécialité, puis charge de
      // travail) — uniquement à la création, et seulement tant que l'admin
      // n'a pas lui-même choisi un avocat (ou qu'il n'a pas déjà été
      // pré-rempli depuis un rendez-vous confirmé ci-dessus). Ne fait jamais
      // qu'une suggestion : le champ reste modifiable librement.
      this.form.get('type_affaire')?.valueChanges.subscribe((typeAffaire) => {
        if (this.avocatModifieManuellement) return;
        this.dossierService.suggererAvocat(typeAffaire).subscribe((suggestion) => {
          if (suggestion.avocat_id && !this.avocatModifieManuellement) {
            this.form.get('avocat_id')?.setValue(suggestion.avocat_id, { emitEvent: false });
            this.suggestionAvocat = { nom: suggestion.nom ?? '', raison: suggestion.raison ?? '' };
          }
        });
      });
    }

    // Enveloppé dans chargerUtilisateurCourant() : après un rechargement de
    // page, l'utilisateur courant (nécessaire à hasRole()) peut ne pas encore
    // être chargé au moment où ngOnInit s'exécute — sans cette attente, la
    // désactivation/pré-remplissage ci-dessous pouvait silencieusement ne
    // jamais s'appliquer (le champ restait actif et vide).
    this.auth.chargerUtilisateurCourant().subscribe(() => {
      if (!this.peutAssigner) {
        if (this.dossierId) {
          this.form.get('avocat_id')?.disable();
          this.form.get('assistant_id')?.disable();
          this.form.get('stagiaire_id')?.disable();
        } else if (this.auth.hasRole('avocat')) {
          this.form.get('avocat_id')?.setValue(this.auth.currentUser()?.id ?? null, { emitEvent: false });
          this.form.get('avocat_id')?.disable();
          // setValue ci-dessus utilise emitEvent:false (pour ne pas marquer
          // avocatModifieManuellement) — donc la vérification du taux horaire
          // obligatoire (normalement déclenchée par ce changement) ne se
          // lance jamais : on la rejoue ici manuellement, une fois.
          const tauxDeSoiMeme = this.auth.currentUser()?.taux_horaire_defaut;
          const controleTauxAvocat = this.form.get('taux_horaire');
          if (tauxDeSoiMeme || this.tauxHoraireCabinet) {
            controleTauxAvocat?.clearValidators();
            this.tauxHoraireObligatoire = false;
          } else {
            controleTauxAvocat?.setValidators(Validators.required);
            this.tauxHoraireObligatoire = true;
          }
          controleTauxAvocat?.updateValueAndValidity({ emitEvent: false });
        } else if (this.auth.hasRole('assistant')) {
          this.form.get('assistant_id')?.disable();
        } else if (this.auth.hasRole('stagiaire')) {
          this.form.get('stagiaire_id')?.disable();
        }

        if (this.auth.hasRole('stagiaire')) {
          this.form.get('mode_facturation')?.disable();
          this.form.get('taux_horaire')?.disable();
          this.form.get('montant_forfait')?.disable();
          this.form.get('facturation_periodique')?.disable();
          this.form.get('frequence_facturation')?.disable();
          this.form.get('facturer_a_cloture')?.disable();
        }
      }
    });

    if (this.dossierId) {
      this.dossierService.obtenir(this.dossierId).subscribe((dossier) => {
        this.nomClientOriginal = dossier.client
          ? dossier.client.type === 'entreprise'
            ? dossier.client.raison_sociale || ''
            : `${dossier.client.prenom || ''} ${dossier.client.nom || ''}`.trim()
          : '';
        // Le client d'un dossier ne doit jamais changer par erreur : plutôt
        // qu'un menu déroulant listant tous les clients (risque de clic sur le
        // mauvais client), on affiche son nom en texte simple en modification.
        this.form.get('client_id')?.disable();

        // La date d'ouverture est figée dès la création, pour tout le monde
        // y compris l'admin (ignorée côté serveur de toute façon).
        this.form.get('date_ouverture')?.disable();

        this.form.patchValue({
          client_id: dossier.client_id,
          avocat_id: dossier.avocat_id,
          assistant_id: dossier.assistant_id ?? null,
          stagiaire_id: dossier.stagiaire_id ?? null,
          titre: dossier.titre,
          type_affaire: dossier.type_affaire,
          sous_categories_affaire: dossier.sous_categories_affaire ?? [],
          statut: dossier.statut,
          mode_facturation: dossier.mode_facturation,
          taux_horaire: dossier.taux_horaire ?? null,
          montant_forfait: dossier.montant_forfait ?? null,
          facturation_periodique: dossier.facturation_periodique ?? false,
          frequence_facturation: dossier.frequence_facturation ?? null,
          facturer_a_cloture: dossier.facturer_a_cloture ?? false,
          date_ouverture: dossier.date_ouverture ? this.parseISOLocale(dossier.date_ouverture) : null,
          description: dossier.description ?? '',
        });
      });
    }
  }

  annuler(): void {
    this.dossierId ? this.router.navigate(['/dossiers', this.dossierId]) : this.router.navigate(['/dossiers']);
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

  /**
   * Construit une Date en heure LOCALE à partir d'une chaîne "YYYY-MM-DD" (ou
   * "YYYY-MM-DDTHH:mm:ss..." — seule la partie date est utilisée). `new
   * Date("YYYY-MM-DD")` interprète la chaîne comme minuit UTC (spec ISO 8601),
   * ce qui décale d'un jour vers la veille dans tout fuseau horaire en retard
   * sur UTC (ex: Québec) — d'où le symétrique manuel ici, comme pour versDateIso().
   */
  private parseISOLocale(chaine: string): Date {
    const [annee, mois, jour] = chaine.substring(0, 10).split('-').map(Number);
    return new Date(annee, mois - 1, jour);
  }
}
