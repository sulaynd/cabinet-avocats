import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink, ActivatedRoute } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { DossierService } from '../../../core/services/dossier.service';
import { DocumentService } from '../../../core/services/document.service';
import { IntervenantService } from '../../../core/services/intervenant.service';
import { Intervenant, IntervenantPayload, FonctionIntervenant } from '../../../core/models/intervenant.model';
import { DebourseService } from '../../../core/services/debourse.service';
import { Debourse, DeboursePayload, CategorieDebourse } from '../../../core/models/debourse.model';
import { ModeleDocumentService } from '../../../core/services/modele-document.service';
import { ModeleDocument } from '../../../core/models/modele-document.model';
import { CollaborateurExterneService } from '../../../core/services/collaborateur-externe.service';
import { CollaborateurExterne } from '../../../core/models/collaborateur-externe.model';
import { FactureService } from '../../../core/services/facture.service';
import { EcheanceService } from '../../../core/services/echeance.service';
import { TempsPasseService } from '../../../core/services/temps-passe.service';
import { UserService } from '../../../core/services/user.service';
import { QuestionnaireService } from '../../../core/services/questionnaire.service';
import { NotificationService } from '../../../core/services/notification.service';
import { ConfirmService } from '../../../core/services/confirm.service';
import { AuthService } from '../../../core/services/auth.service';
import { Utilisateur } from '../../../core/models/user.model';
import { TypeAffaireService } from '../../../core/services/type-affaire.service';
import { TypeAffaire } from '../../../core/models/type-affaire.model';
import { ReponseQuestionnaire } from '../../../core/models/reponse-questionnaire.model';
import { Dossier } from '../../../core/models/dossier.model';
import { TypeDocument } from '../../../core/models/document.model';
import { TempsPasse } from '../../../core/models/temps-passe.model';
import { HasRoleDirective } from '../../../core/directives/has-role.directive';
import { CommunicationsPanelComponent } from '../communications-panel/communications-panel.component';

@Component({
  selector: 'app-dossier-detail',
  standalone: true,
  imports: [
    CommonModule, RouterLink, FormsModule, HasRoleDirective, CommunicationsPanelComponent,
    MatButtonModule, MatIconModule,
  ],
  templateUrl: './dossier-detail.component.html',
})
export class DossierDetailComponent implements OnInit {
  dossier: Dossier | null = null;
  chargement = false;

  // Chargés dynamiquement depuis l'API (gérables dans "Types d'affaire",
  // menu admin) — évite d'afficher un libellé obsolète si un type est
  // renommé après la création du dossier.
  typesAffaire: TypeAffaire[] = [];

  get libelleTypeAffaireActuel(): string {
    const type = this.typesAffaire.find((t) => t.slug === this.dossier?.type_affaire);
    return type?.libelle ?? this.dossier?.type_affaire ?? '';
  }

  get libellesSousCategoriesActuelles(): string[] {
    const type = this.typesAffaire.find((t) => t.slug === this.dossier?.type_affaire);
    const slugs = this.dossier?.sous_categories_affaire ?? [];
    return slugs.map((slug) => type?.sous_categories?.find((sc) => sc.slug === slug)?.libelle ?? slug);
  }

  readonly libellesStatutFacture: Record<string, string> = {
    brouillon: 'Non envoyée',
    envoyee: 'Envoyée',
    payee: 'Payée',
    en_retard: 'En retard',
    annulee: 'Annulée',
  };

  fichierSelectionne: File | null = null;
  typeDocumentSelectionne: TypeDocument = 'autre';
  necessiteSignatureSelectionne = false;
  televersement = false;
  erreurTeleversement = '';

  readonly typesDocument: TypeDocument[] = ['contrat', 'piece_procedure', 'correspondance', 'autre'];

  tempsPasses: TempsPasse[] = [];
  chronoEnCours: TempsPasse | null = null;
  descriptionChrono = '';
  minutesManuelles: number | null = null;
  descriptionManuelle = '';
  generationFacture = false;

  // --- Assignation (admin uniquement) ---
  modaleAssignationOuverte = false;
  avocats: Utilisateur[] = [];
  assistants: Utilisateur[] = [];
  avocatIdChoisi: number | null = null;
  assistantIdChoisi: number | null = null;
  stagiaireIdChoisi: number | null = null;
  assignationEnCours = false;

  // --- Questionnaire d'accueil ---
  reponsesQuestionnaires: ReponseQuestionnaire[] = [];
  renvoiQuestionnaireEnCours = false;

  constructor(
    private route: ActivatedRoute,
    private dossierService: DossierService,
    private typeAffaireService: TypeAffaireService,
    private documentService: DocumentService,
    private intervenantService: IntervenantService,
    private debourseService: DebourseService,
    private modeleDocumentService: ModeleDocumentService,
    private collaborateurExterneService: CollaborateurExterneService,
    private factureService: FactureService,
    private echeanceService: EcheanceService,
    private tempsPasseService: TempsPasseService,
    private userService: UserService,
    private questionnaireService: QuestionnaireService,
    private notification: NotificationService,
    private confirmService: ConfirmService,
    public auth: AuthService
  ) {}

  ngOnInit(): void {
    this.charger();
    this.typeAffaireService.liste().subscribe((t) => (this.typesAffaire = t));
    this.chargerTemps();
    this.tempsPasseService.enCours().subscribe((t) => (this.chronoEnCours = t));
    this.chargerReponsesQuestionnaires();
  }

  chargerReponsesQuestionnaires(): void {
    this.questionnaireService.reponsesDuDossier(this.dossierId).subscribe((r) => (this.reponsesQuestionnaires = r));
  }

  renvoyerQuestionnaire(): void {
    this.renvoiQuestionnaireEnCours = true;
    this.questionnaireService.renvoyer(this.dossierId).subscribe({
      next: () => {
        this.renvoiQuestionnaireEnCours = false;
        this.notification.succes('Questionnaire renvoyé au client.');
        this.chargerReponsesQuestionnaires();
      },
      error: (err) => {
        this.renvoiQuestionnaireEnCours = false;
        this.notification.erreur(err?.error?.message || "Impossible d'envoyer le questionnaire.");
      },
    });
  }

  private get dossierId(): number {
    return Number(this.route.snapshot.paramMap.get('id'));
  }

  charger(): void {
    this.chargement = true;
    // Le endpoint show() renvoie déjà client, avocat, echeances, documents et factures.
    this.dossierService.obtenir(this.dossierId).subscribe({
      next: (dossier) => {
        this.dossier = dossier;
        this.chargement = false;
      },
      error: () => (this.chargement = false),
    });
    this.modeleDocumentService.pourDossier(this.dossierId).subscribe((m) => (this.modelesDocuments = m));
    this.collaborateurExterneService.pourDossier(this.dossierId).subscribe((c) => (this.collaborateursExternes = c));
  }

  nomClient(): string {
    const client = this.dossier?.client;
    if (!client) return '';
    return client.type === 'entreprise' ? (client.raison_sociale ?? '') : `${client.prenom ?? ''} ${client.nom ?? ''}`.trim();
  }

  // --- Échéances ---

  marquerEcheanceRealisee(echeanceId: number): void {
    this.echeanceService.modifier(echeanceId, { statut: 'realisee' }).subscribe({
      next: () => this.charger(),
      error: (err) => this.notification.erreur(err?.error?.message || 'Impossible de mettre à jour cette échéance.'),
    });
  }

  supprimerEcheance(echeanceId: number): void {
    this.confirmService
      .demander({ titre: 'Supprimer cette échéance ?', message: 'Cette action est définitive.', libelleConfirmer: 'Supprimer', destructif: true })
      .subscribe((confirme) => {
        if (!confirme) return;
        this.echeanceService.supprimer(echeanceId).subscribe({
          next: () => {
            this.notification.succes('Échéance supprimée.');
            this.charger();
          },
          error: (err) => this.notification.erreur(err?.error?.message || 'Impossible de supprimer cette échéance.'),
        });
      });
  }

  // --- Documents ---

  surSelectionFichier(event: Event): void {
    const input = event.target as HTMLInputElement;
    this.fichierSelectionne = input.files?.[0] ?? null;
    this.erreurTeleversement = '';
  }

  televerserDocument(): void {
    if (!this.fichierSelectionne) return;
    this.televersement = true;
    this.erreurTeleversement = '';

    this.dossierService.uploaderDocument(this.dossierId, this.fichierSelectionne, this.typeDocumentSelectionne, this.necessiteSignatureSelectionne).subscribe({
      next: () => {
        this.televersement = false;
        this.fichierSelectionne = null;
        this.necessiteSignatureSelectionne = false;
        this.notification.succes('Document ajouté.');
        this.charger();
      },
      error: () => {
        this.televersement = false;
        this.erreurTeleversement = "Échec de l'envoi du document (taille max 20 Mo).";
      },
    });
  }

  telechargerDocument(id: number, nomOriginal: string): void {
    this.documentService.telecharger(id, nomOriginal);
  }

  supprimerDocument(id: number): void {
    this.confirmService
      .demander({ titre: 'Supprimer ce document ?', message: 'Cette action est définitive.', libelleConfirmer: 'Supprimer', destructif: true })
      .subscribe((confirme) => {
        if (!confirme) return;
        this.documentService.supprimer(id).subscribe({
          next: () => {
            this.notification.succes('Document supprimé.');
            this.charger();
          },
          error: (err) => this.notification.erreur(err?.error?.message || 'Impossible de supprimer ce document.'),
        });
      });
  }

  // --- Intervenants externes (carnet d'adresses partagé du cabinet) ---
  readonly fonctionsIntervenant: { valeur: FonctionIntervenant; libelle: string }[] = [
    { valeur: 'avocat_adverse', libelle: 'Avocat adverse' },
    { valeur: 'expert', libelle: 'Expert' },
    { valeur: 'greffier', libelle: 'Greffier/Greffe' },
    { valeur: 'huissier', libelle: 'Huissier' },
    { valeur: 'mediateur_arbitre', libelle: 'Médiateur/Arbitre' },
    { valeur: 'notaire', libelle: 'Notaire' },
    { valeur: 'autre', libelle: 'Autre' },
  ];

  formulaireIntervenantOuvert = false;
  intervenantEnEdition: Intervenant | null = null;
  intervenantForm: IntervenantPayload = { nom: '', fonction: 'autre', organisation: '', email: '', telephone: '', notes: '' };

  // Recherche dans le répertoire partagé, pour lier un intervenant déjà
  // existant plutôt que d'en recréer un doublon (ex: le même avocat adverse
  // revient sur un autre dossier).
  rechercheIntervenant = '';
  resultatsRechercheIntervenant: Intervenant[] = [];
  rechercheIntervenantEnCours = false;

  libelleFonctionIntervenant(fonction: FonctionIntervenant): string {
    return this.fonctionsIntervenant.find((f) => f.valeur === fonction)?.libelle ?? fonction;
  }

  ouvrirFormulaireIntervenant(intervenant?: Intervenant): void {
    this.intervenantEnEdition = intervenant ?? null;
    this.intervenantForm = intervenant
      ? { nom: intervenant.nom, fonction: intervenant.fonction, organisation: intervenant.organisation, email: intervenant.email, telephone: intervenant.telephone, notes: intervenant.notes }
      : { nom: '', fonction: 'autre', organisation: '', email: '', telephone: '', notes: '' };
    this.rechercheIntervenant = '';
    this.resultatsRechercheIntervenant = [];
    this.formulaireIntervenantOuvert = true;
  }

  fermerFormulaireIntervenant(): void {
    this.formulaireIntervenantOuvert = false;
    this.intervenantEnEdition = null;
  }

  rechercherIntervenant(): void {
    if (this.rechercheIntervenant.trim().length < 2) {
      this.resultatsRechercheIntervenant = [];
      return;
    }
    this.rechercheIntervenantEnCours = true;
    this.intervenantService.rechercherRepertoire(this.rechercheIntervenant).subscribe({
      next: (resultats) => {
        this.rechercheIntervenantEnCours = false;
        this.resultatsRechercheIntervenant = resultats;
      },
      error: () => (this.rechercheIntervenantEnCours = false),
    });
  }

  lierIntervenantExistant(intervenant: Intervenant): void {
    this.intervenantService.lier(this.dossierId, intervenant.id).subscribe({
      next: () => {
        this.notification.succes(`${intervenant.nom} ajouté au dossier.`);
        this.fermerFormulaireIntervenant();
        this.charger();
      },
      error: (err) => this.notification.erreur(err?.error?.message || "Impossible de lier cet intervenant."),
    });
  }

  enregistrerIntervenant(): void {
    if (!this.intervenantForm.nom.trim()) {
      this.notification.erreur('Le nom est obligatoire.');
      return;
    }

    // En modification, la fiche est partagée : le changement s'appliquera à
    // tous les dossiers où cet intervenant est déjà lié.
    const requete = this.intervenantEnEdition
      ? this.intervenantService.modifier(this.intervenantEnEdition.id, this.intervenantForm)
      : this.intervenantService.creerEtLier(this.dossierId, this.intervenantForm);

    requete.subscribe({
      next: () => {
        this.notification.succes('Intervenant enregistré.');
        this.fermerFormulaireIntervenant();
        this.charger();
      },
      error: (err) => this.notification.erreur(err?.error?.message || "Impossible d'enregistrer cet intervenant."),
    });
  }

  delierIntervenant(intervenant: Intervenant): void {
    this.confirmService
      .demander({
        titre: 'Retirer cet intervenant du dossier ?',
        message: `"${intervenant.nom}" sera retiré de ce dossier uniquement — il reste dans le répertoire du cabinet et sur ses autres dossiers éventuels.`,
        libelleConfirmer: 'Retirer',
        destructif: true,
      })
      .subscribe((confirme) => {
        if (!confirme) return;
        this.intervenantService.delier(this.dossierId, intervenant.id).subscribe({
          next: () => {
            this.notification.succes('Intervenant retiré du dossier.');
            this.charger();
          },
          error: (err) => this.notification.erreur(err?.error?.message || 'Impossible de retirer cet intervenant.'),
        });
      });
  }

  // --- Déboursés (frais de cour, déplacements, photocopies...) ---
  readonly categoriesDebourse: { valeur: CategorieDebourse; libelle: string }[] = [
    { valeur: 'frais_cour', libelle: 'Frais de cour' },
    { valeur: 'deplacement', libelle: 'Déplacement' },
    { valeur: 'photocopie', libelle: 'Photocopie' },
    { valeur: 'autre', libelle: 'Autre' },
  ];

  formulaireDebourseOuvert = false;
  debourseEnEdition: Debourse | null = null;
  debourseForm: DeboursePayload = { categorie: 'autre', description: '', montant: 0, date_debourse: this.aujourdhuiIso() };

  private aujourdhuiIso(): string {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
  }

  libelleCategorieDebourse(categorie: CategorieDebourse): string {
    return this.categoriesDebourse.find((c) => c.valeur === categorie)?.libelle ?? categorie;
  }

  get totalDebourseNonFactures(): number {
    return (this.dossier?.debourses ?? []).filter((d) => !d.facture_id).reduce((s, d) => s + Number(d.montant), 0);
  }

  ouvrirFormulaireDebourse(debourse?: Debourse): void {
    this.debourseEnEdition = debourse ?? null;
    this.debourseForm = debourse
      ? { categorie: debourse.categorie, description: debourse.description, montant: Number(debourse.montant), date_debourse: debourse.date_debourse }
      : { categorie: 'autre', description: '', montant: 0, date_debourse: this.aujourdhuiIso() };
    this.formulaireDebourseOuvert = true;
  }

  fermerFormulaireDebourse(): void {
    this.formulaireDebourseOuvert = false;
    this.debourseEnEdition = null;
  }

  enregistrerDebourse(): void {
    if (!this.debourseForm.description.trim() || !this.debourseForm.montant) {
      this.notification.erreur('La description et le montant sont obligatoires.');
      return;
    }

    const requete = this.debourseEnEdition
      ? this.debourseService.modifier(this.debourseEnEdition.id, this.debourseForm)
      : this.debourseService.creer(this.dossierId, this.debourseForm);

    requete.subscribe({
      next: () => {
        this.notification.succes('Déboursé enregistré.');
        this.fermerFormulaireDebourse();
        this.charger();
      },
      error: (err) => this.notification.erreur(err?.error?.message || "Impossible d'enregistrer ce déboursé."),
    });
  }

  supprimerDebourse(debourse: Debourse): void {
    this.confirmService
      .demander({ titre: 'Supprimer ce déboursé ?', message: `"${debourse.description}" sera retiré du dossier.`, libelleConfirmer: 'Supprimer', destructif: true })
      .subscribe((confirme) => {
        if (!confirme) return;
        this.debourseService.supprimer(debourse.id).subscribe({
          next: () => {
            this.notification.succes('Déboursé supprimé.');
            this.charger();
          },
          error: (err) => this.notification.erreur(err?.error?.message || 'Impossible de supprimer ce déboursé.'),
        });
      });
  }

  // --- Fusion documentaire (génération depuis un modèle Word) ---
  modelesDocuments: ModeleDocument[] = [];
  modeleSelectionne: number | null = null;
  generationDocumentEnCours = false;

  genererDocument(): void {
    if (!this.modeleSelectionne) {
      this.notification.erreur('Choisissez un modèle de document.');
      return;
    }

    this.generationDocumentEnCours = true;
    this.modeleDocumentService.generer(this.dossierId, this.modeleSelectionne).subscribe({
      next: (blob) => {
        this.generationDocumentEnCours = false;
        const modele = this.modelesDocuments.find((m) => m.id === this.modeleSelectionne);
        const lien = document.createElement('a');
        lien.href = URL.createObjectURL(blob);
        lien.download = `${modele?.nom ?? 'document'}.docx`;
        lien.click();
        URL.revokeObjectURL(lien.href);
        this.notification.succes('Document généré et ajouté aux documents du dossier.');
        this.charger();
      },
      error: (err) => {
        this.generationDocumentEnCours = false;
        this.notification.erreur(err?.error?.message || 'Impossible de générer ce document.');
      },
    });
  }

  // --- Collaborateurs externes (co-conseil...) ---
  collaborateursExternes: CollaborateurExterne[] = [];
  formulaireCollaborateurOuvert = false;
  collaborateurForm = { nom: '', email: '', organisation: '', telephone: '' };
  rechercheCollaborateur = '';
  resultatsRechercheCollaborateur: CollaborateurExterne[] = [];

  ouvrirFormulaireCollaborateur(): void {
    this.collaborateurForm = { nom: '', email: '', organisation: '', telephone: '' };
    this.rechercheCollaborateur = '';
    this.resultatsRechercheCollaborateur = [];
    this.formulaireCollaborateurOuvert = true;
  }

  fermerFormulaireCollaborateur(): void {
    this.formulaireCollaborateurOuvert = false;
  }

  rechercherCollaborateur(): void {
    if (this.rechercheCollaborateur.trim().length < 2) {
      this.resultatsRechercheCollaborateur = [];
      return;
    }
    this.collaborateurExterneService.rechercherRepertoire(this.rechercheCollaborateur).subscribe((r) => (this.resultatsRechercheCollaborateur = r));
  }

  lierCollaborateurExistant(collaborateur: CollaborateurExterne): void {
    this.collaborateurExterneService.lier(this.dossierId, collaborateur.id).subscribe({
      next: () => {
        this.notification.succes(`${collaborateur.nom} ajouté au dossier.`);
        this.fermerFormulaireCollaborateur();
        this.charger();
      },
      error: (err) => this.notification.erreur(err?.error?.message || 'Impossible de lier ce collaborateur.'),
    });
  }

  creerEtLierCollaborateur(): void {
    if (!this.collaborateurForm.nom.trim() || !this.collaborateurForm.email.trim()) {
      this.notification.erreur('Le nom et l\'email sont obligatoires.');
      return;
    }
    this.collaborateurExterneService.creerEtLier(this.dossierId, this.collaborateurForm).subscribe({
      next: () => {
        this.notification.succes('Collaborateur ajouté au dossier.');
        this.fermerFormulaireCollaborateur();
        this.charger();
      },
      error: (err) => this.notification.erreur(err?.error?.message || "Impossible d'ajouter ce collaborateur."),
    });
  }

  activerAccesCollaborateur(collaborateur: CollaborateurExterne): void {
    this.collaborateurExterneService.activer(collaborateur.id).subscribe({
      next: () => this.notification.succes(`Accès envoyé par email à ${collaborateur.nom}.`),
      error: (err) => this.notification.erreur(err?.error?.message || "Impossible d'activer cet accès."),
    });
  }

  delierCollaborateur(collaborateur: CollaborateurExterne): void {
    this.confirmService
      .demander({
        titre: 'Retirer ce collaborateur du dossier ?',
        message: `"${collaborateur.nom}" ne verra plus les documents partagés de ce dossier.`,
        libelleConfirmer: 'Retirer',
        destructif: true,
      })
      .subscribe((confirme) => {
        if (!confirme) return;
        this.collaborateurExterneService.delier(this.dossierId, collaborateur.id).subscribe({
          next: () => {
            this.notification.succes('Collaborateur retiré du dossier.');
            this.charger();
          },
          error: (err) => this.notification.erreur(err?.error?.message || 'Impossible de retirer ce collaborateur.'),
        });
      });
  }

  basculerPartageExterne(doc: any, partage: boolean): void {
    this.documentService.partagerExterne(doc.id, partage).subscribe({
      next: () => {
        this.notification.succes(partage ? 'Document partagé avec les collaborateurs.' : 'Partage retiré.');
        this.charger();
      },
      error: (err) => this.notification.erreur(err?.error?.message || 'Impossible de modifier le partage.'),
    });
  }

  formaterTaille(octets?: number): string {
    if (!octets) return '';
    const ko = octets / 1024;
    return ko < 1024 ? `${ko.toFixed(0)} Ko` : `${(ko / 1024).toFixed(1)} Mo`;
  }

  // --- Factures ---

  telechargerFacturePdf(id: number, numero: string): void {
    this.factureService.telechargerPdf(id, numero);
  }

  // --- Temps passé / chronomètre ---

  chargerTemps(): void {
    this.tempsPasseService.liste(this.dossierId).subscribe((temps) => (this.tempsPasses = temps));
  }

  get estStagiaire(): boolean {
    return this.auth.currentUser()?.role === 'stagiaire';
  }

  get chronoActifSurCeDossier(): boolean {
    return !!this.chronoEnCours && this.chronoEnCours.dossier_id === this.dossierId;
  }

  demarrerChrono(): void {
    this.tempsPasseService.demarrer(this.dossierId, this.descriptionChrono || undefined).subscribe({
      next: (t) => {
        this.chronoEnCours = t;
        this.descriptionChrono = '';
        this.chargerTemps();
      },
      error: (err) => this.notification.erreur(err?.error?.message || 'Impossible de démarrer le chronomètre.'),
    });
  }

  arreterChrono(): void {
    if (!this.chronoEnCours) return;
    this.tempsPasseService.arreter(this.chronoEnCours.id).subscribe({
      next: () => {
        this.chronoEnCours = null;
        this.notification.succes('Chronomètre arrêté.');
        this.chargerTemps();
      },
      error: (err) => this.notification.erreur(err?.error?.message || "Impossible d'arrêter le chronomètre."),
    });
  }

  ajouterTempsManuel(): void {
    if (!this.minutesManuelles || this.minutesManuelles < 1) return;
    this.tempsPasseService
      .ajouterManuel(this.dossierId, { description: this.descriptionManuelle || undefined, duree_minutes: this.minutesManuelles })
      .subscribe({
        next: () => {
          this.minutesManuelles = null;
          this.descriptionManuelle = '';
          this.notification.succes('Temps ajouté.');
          this.chargerTemps();
        },
        error: (err) => this.notification.erreur(err?.error?.message || "Impossible d'ajouter ce temps."),
      });
  }

  supprimerTemps(id: number): void {
    this.confirmService
      .demander({ titre: 'Supprimer cette entrée de temps ?', message: 'Cette action est définitive.', libelleConfirmer: 'Supprimer', destructif: true })
      .subscribe((confirme) => {
        if (!confirme) return;
        this.tempsPasseService.supprimer(id).subscribe({
          next: () => {
            this.notification.succes('Entrée de temps supprimée.');
            this.chargerTemps();
          },
          error: (err) => this.notification.erreur(err?.error?.message || 'Impossible de supprimer cette entrée.'),
        });
      });
  }

  formaterDuree(secondes: number): string {
    const h = Math.floor(secondes / 3600);
    const m = Math.round((secondes % 3600) / 60);
    return h > 0 ? `${h} h ${m.toString().padStart(2, '0')}` : `${m} min`;
  }

  get totalHeuresNonFacturees(): number {
    return this.tempsPasses
      .filter((t) => t.facturable && !t.facture_id && t.termine_a)
      .reduce((s, t) => s + t.duree_secondes, 0) / 3600;
  }

  genererFactureDepuisTemps(): void {
    this.confirmService
      .demander({ titre: 'Générer une facture ?', message: 'À partir du temps passé (ou du forfait) de ce dossier.', libelleConfirmer: 'Générer' })
      .subscribe((confirme) => {
        if (!confirme) return;
        this.generationFacture = true;

        this.dossierService.genererFactureDepuisTemps(this.dossierId).subscribe({
          next: () => {
            this.generationFacture = false;
            this.notification.succes('Facture générée.');
            this.charger();
            this.chargerTemps();
          },
          error: (err) => {
            this.generationFacture = false;
            this.notification.erreur(err?.error?.message || 'Impossible de générer la facture.');
          },
        });
      });
  }

  // --- Assignation (admin uniquement) ---

  ouvrirModaleAssignation(): void {
    this.avocatIdChoisi = this.dossier?.avocat_id ?? null;
    this.assistantIdChoisi = this.dossier?.assistant_id ?? null;
    this.stagiaireIdChoisi = this.dossier?.stagiaire_id ?? null;
    this.modaleAssignationOuverte = true;

    if (!this.avocats.length) {
      this.userService.liste({ role: 'avocat', per_page: 100 }).subscribe((res) => (this.avocats = res.data));
    }
    if (!this.assistants.length) {
      this.userService.liste({ role: 'assistant,stagiaire', per_page: 100 }).subscribe((res) => (this.assistants = res.data));
    }
  }

  get assistantsUniquement(): Utilisateur[] {
    return this.assistants.filter((a) => a.role === 'assistant');
  }

  get stagiairesUniquement(): Utilisateur[] {
    return this.assistants.filter((a) => a.role === 'stagiaire');
  }

  fermerModaleAssignation(): void {
    this.modaleAssignationOuverte = false;
  }

  confirmerAssignation(): void {
    if (!this.avocatIdChoisi) return;
    this.assignationEnCours = true;

    this.dossierService.assigner(this.dossierId, { avocat_id: this.avocatIdChoisi, assistant_id: this.assistantIdChoisi, stagiaire_id: this.stagiaireIdChoisi }).subscribe({
      next: () => {
        this.assignationEnCours = false;
        this.modaleAssignationOuverte = false;
        this.notification.succes('Dossier réassigné.');
        this.charger();
      },
      error: (err) => {
        this.assignationEnCours = false;
        this.notification.erreur(err?.error?.message || "Impossible d'assigner ce dossier.");
      },
    });
  }
}
