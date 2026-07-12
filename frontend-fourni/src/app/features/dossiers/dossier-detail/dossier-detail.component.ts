import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink, ActivatedRoute } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { DossierService } from '../../../core/services/dossier.service';
import { DocumentService } from '../../../core/services/document.service';
import { FactureService } from '../../../core/services/facture.service';
import { EcheanceService } from '../../../core/services/echeance.service';
import { TempsPasseService } from '../../../core/services/temps-passe.service';
import { UserService } from '../../../core/services/user.service';
import { QuestionnaireService } from '../../../core/services/questionnaire.service';
import { Utilisateur } from '../../../core/models/user.model';
import { ReponseQuestionnaire } from '../../../core/models/reponse-questionnaire.model';
import { Dossier } from '../../../core/models/dossier.model';
import { TypeDocument } from '../../../core/models/document.model';
import { TempsPasse } from '../../../core/models/temps-passe.model';
import { HasRoleDirective } from '../../../core/directives/has-role.directive';
import { CommunicationsPanelComponent } from '../communications-panel/communications-panel.component';

@Component({
  selector: 'app-dossier-detail',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule, HasRoleDirective, CommunicationsPanelComponent],
  templateUrl: './dossier-detail.component.html',
})
export class DossierDetailComponent implements OnInit {
  dossier: Dossier | null = null;
  chargement = false;

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
  assignationEnCours = false;

  // --- Questionnaire d'accueil ---
  reponsesQuestionnaires: ReponseQuestionnaire[] = [];
  renvoiQuestionnaireEnCours = false;

  constructor(
    private route: ActivatedRoute,
    private dossierService: DossierService,
    private documentService: DocumentService,
    private factureService: FactureService,
    private echeanceService: EcheanceService,
    private tempsPasseService: TempsPasseService,
    private userService: UserService,
    private questionnaireService: QuestionnaireService
  ) {}

  ngOnInit(): void {
    this.charger();
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
        this.chargerReponsesQuestionnaires();
      },
      error: (err) => {
        this.renvoiQuestionnaireEnCours = false;
        alert(err?.error?.message || "Impossible d'envoyer le questionnaire.");
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
  }

  nomClient(): string {
    const client = this.dossier?.client;
    if (!client) return '';
    return client.type === 'entreprise' ? (client.raison_sociale ?? '') : `${client.prenom ?? ''} ${client.nom ?? ''}`.trim();
  }

  // --- Échéances ---

  marquerEcheanceRealisee(echeanceId: number): void {
    this.echeanceService.modifier(echeanceId, { statut: 'realisee' }).subscribe(() => this.charger());
  }

  supprimerEcheance(echeanceId: number): void {
    if (!confirm('Supprimer cette échéance ?')) return;
    this.echeanceService.supprimer(echeanceId).subscribe(() => this.charger());
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
    if (!confirm('Supprimer ce document ?')) return;
    this.documentService.supprimer(id).subscribe(() => this.charger());
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
      error: (err) => alert(err?.error?.message || 'Impossible de démarrer le chronomètre.'),
    });
  }

  arreterChrono(): void {
    if (!this.chronoEnCours) return;
    this.tempsPasseService.arreter(this.chronoEnCours.id).subscribe(() => {
      this.chronoEnCours = null;
      this.chargerTemps();
    });
  }

  ajouterTempsManuel(): void {
    if (!this.minutesManuelles || this.minutesManuelles < 1) return;
    this.tempsPasseService
      .ajouterManuel(this.dossierId, { description: this.descriptionManuelle || undefined, duree_minutes: this.minutesManuelles })
      .subscribe(() => {
        this.minutesManuelles = null;
        this.descriptionManuelle = '';
        this.chargerTemps();
      });
  }

  supprimerTemps(id: number): void {
    if (!confirm('Supprimer cette entrée de temps ?')) return;
    this.tempsPasseService.supprimer(id).subscribe(() => this.chargerTemps());
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
    if (!confirm("Générer une facture à partir du temps passé (ou du forfait) de ce dossier ?")) return;
    this.generationFacture = true;

    this.dossierService.genererFactureDepuisTemps(this.dossierId).subscribe({
      next: () => {
        this.generationFacture = false;
        this.charger();
        this.chargerTemps();
      },
      error: (err) => {
        this.generationFacture = false;
        alert(err?.error?.message || 'Impossible de générer la facture.');
      },
    });
  }

  // --- Assignation (admin uniquement) ---

  ouvrirModaleAssignation(): void {
    this.avocatIdChoisi = this.dossier?.avocat_id ?? null;
    this.assistantIdChoisi = this.dossier?.assistant_id ?? null;
    this.modaleAssignationOuverte = true;

    if (!this.avocats.length) {
      this.userService.liste({ role: 'avocat', per_page: 100 }).subscribe((res) => (this.avocats = res.data));
    }
    if (!this.assistants.length) {
      this.userService.liste({ role: 'assistant', per_page: 100 }).subscribe((res) => (this.assistants = res.data));
    }
  }

  fermerModaleAssignation(): void {
    this.modaleAssignationOuverte = false;
  }

  confirmerAssignation(): void {
    if (!this.avocatIdChoisi) return;
    this.assignationEnCours = true;

    this.dossierService.assigner(this.dossierId, { avocat_id: this.avocatIdChoisi, assistant_id: this.assistantIdChoisi }).subscribe({
      next: () => {
        this.assignationEnCours = false;
        this.modaleAssignationOuverte = false;
        this.charger();
      },
      error: (err) => {
        this.assignationEnCours = false;
        alert(err?.error?.message || "Impossible d'assigner ce dossier.");
      },
    });
  }
}
