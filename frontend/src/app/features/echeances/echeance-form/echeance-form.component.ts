import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { EcheanceService } from '../../../core/services/echeance.service';
import { DossierService } from '../../../core/services/dossier.service';
import { NotificationService } from '../../../core/services/notification.service';
import { Dossier } from '../../../core/models/dossier.model';
import { Echeance } from '../../../core/models/echeance.model';

@Component({
  selector: 'app-echeance-form',
  standalone: true,
  imports: [
    CommonModule, ReactiveFormsModule,
    MatFormFieldModule, MatInputModule, MatSelectModule, MatButtonModule, MatIconModule,
  ],
  templateUrl: './echeance-form.component.html',
})
export class EcheanceFormComponent implements OnInit {
  echeanceId: number | null = null;
  dossiers: Dossier[] = [];
  enregistrement = false;
  conflitsDetectes: { id: number; titre: string; date_heure: string; dossier_reference: string; dossier_titre: string }[] = [];
  verificationConflitsEnCours = false;

  readonly types = ['audience', 'delai_procedural', 'rdv_client', 'autre'];
  readonly rappels = [
    { label: 'Aucun rappel', valeur: null },
    { label: '30 minutes avant', valeur: 30 },
    { label: '1 heure avant', valeur: 60 },
    { label: '1 jour avant', valeur: 1440 },
    { label: '2 jours avant', valeur: 2880 },
    { label: '3 jours avant', valeur: 4320 },
  ];

  private fb = inject(FormBuilder);

  form = this.fb.group({
    // dossier_id reste nullable : c'est l'état réel tant que rien n'est
    // sélectionné (Validators.required empêche la soumission avant ce choix).
    dossier_id: this.fb.control(null as number | null, Validators.required),
    titre: this.fb.nonNullable.control('', Validators.required),
    type: this.fb.nonNullable.control('audience', Validators.required),
    date_heure: this.fb.nonNullable.control('', Validators.required),
    lieu: this.fb.nonNullable.control(''),
    rappel_avant: [4320 as number | null],
  });

  constructor(
    private echeanceService: EcheanceService,
    private dossierService: DossierService,
    private notification: NotificationService,
    private route: ActivatedRoute,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.dossierService.liste({ per_page: 100 }).subscribe((res) => (this.dossiers = res.data));

    const dossierIdParam = this.route.snapshot.queryParamMap.get('dossier_id');
    if (dossierIdParam) {
      this.form.patchValue({ dossier_id: Number(dossierIdParam) });
    }

    const idParam = this.route.snapshot.paramMap.get('id');
    if (idParam) {
      this.echeanceId = Number(idParam);
      this.echeanceService.obtenir(this.echeanceId).subscribe((echeance) => {
        this.form.patchValue({
          dossier_id: echeance.dossier_id,
          titre: echeance.titre,
          type: echeance.type,
          date_heure: echeance.date_heure.substring(0, 16),
          lieu: echeance.lieu ?? '',
          rappel_avant: echeance.rappel_avant ?? null,
        });
      });
    }

    // Vérifie les conflits d'horaire dès que le dossier, la date/heure ou le
    // type change — avertit sans jamais bloquer l'enregistrement, au cas où
    // le double engagement serait volontaire (ex: un bref suivi téléphonique).
    ['dossier_id', 'date_heure', 'type'].forEach((champ) => {
      this.form.get(champ)?.valueChanges.subscribe(() => this.verifierConflits());
    });
  }

  verifierConflits(): void {
    const { dossier_id, date_heure, type } = this.form.value;
    this.conflitsDetectes = [];

    if (!dossier_id || !date_heure || (type !== 'audience' && type !== 'rdv_client')) {
      return;
    }

    this.verificationConflitsEnCours = true;
    this.echeanceService.verifierConflits(dossier_id, date_heure, this.echeanceId).subscribe({
      next: (conflits) => {
        this.verificationConflitsEnCours = false;
        this.conflitsDetectes = conflits;
      },
      error: () => (this.verificationConflitsEnCours = false),
    });
  }

  annuler(): void {
    this.router.navigate(['/echeances']);
  }

  soumettre(): void {
    if (this.form.invalid) return;
    this.enregistrement = true;
    // Cast : dossier_id est `number | null` côté formulaire (nul tant que rien
    // n'est sélectionné), mais Validators.required + le garde ci-dessus
    // garantissent qu'il est bien renseigné à ce stade.
    const data = this.form.value as Partial<Echeance>;

    const requete = this.echeanceId
      ? this.echeanceService.modifier(this.echeanceId, data)
      : this.echeanceService.creer(data);

    requete.subscribe({
      next: () => {
        this.enregistrement = false;
        this.notification.succes(this.echeanceId ? 'Échéance modifiée.' : 'Échéance créée.');
        this.router.navigate(['/echeances']);
      },
      error: (err) => {
        this.enregistrement = false;
        this.notification.erreur(err?.error?.message || "Impossible d'enregistrer cette échéance.");
      },
    });
  }
}
