import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatDatepickerModule } from '@angular/material/datepicker';
import { MatNativeDateModule } from '@angular/material/core';
import { EcheanceService } from '../../../core/services/echeance.service';
import { merge, debounceTime, switchMap, of } from 'rxjs';
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
    MatDatepickerModule, MatNativeDateModule,
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
    // Date (calendrier) et heure (sélecteur natif avec flèches) séparés —
    // plutôt qu'un champ datetime-local unique, plus sujet aux erreurs de
    // frappe (jour/mois/heure mélangés en tapant vite).
    date_jour: this.fb.control(null as Date | null, Validators.required),
    heure: this.fb.nonNullable.control('', Validators.required),
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
        // "2026-07-21T10:30:00" → date_jour = Date(2026-07-21), heure = "10:30".
        const [partieDate, partieHeure] = echeance.date_heure.split('T');
        this.form.patchValue({
          dossier_id: echeance.dossier_id,
          titre: echeance.titre,
          type: echeance.type,
          date_jour: this.parseLocalDate(partieDate),
          heure: partieHeure.substring(0, 5),
          lieu: echeance.lieu ?? '',
          rappel_avant: echeance.rappel_avant ?? null,
        });
        // patchValue seul ne suffit pas à révéler un conflit déjà existant à
        // l'ouverture (les écouteurs ci-dessous ne réagissent qu'à un
        // changement ultérieur) — on vérifie donc aussi une fois ici.
        this.verifierConflits();
      });
    }

    // Vérifie les conflits d'horaire dès que le dossier, la date, l'heure ou
    // le type change — avertit sans jamais bloquer l'enregistrement, au cas
    // où le double engagement serait volontaire (ex: un bref suivi
    // téléphonique). debounceTime + switchMap : en tapant/choisissant vite,
    // plusieurs requêtes partiraient sinon en rafale, et une réponse plus
    // ancienne (avec une date encore incomplète) pourrait arriver après la
    // bonne et effacer le résultat correct à l'écran.
    merge(
      this.form.get('dossier_id')!.valueChanges,
      this.form.get('date_jour')!.valueChanges,
      this.form.get('heure')!.valueChanges,
      this.form.get('type')!.valueChanges
    )
      .pipe(
        debounceTime(300),
        switchMap(() => {
          const dateHeure = this.combinerDateHeure();
          const { dossier_id, type } = this.form.value;
          if (!dossier_id || !dateHeure || (type !== 'audience' && type !== 'rdv_client')) {
            this.conflitsDetectes = [];
            return of(null);
          }
          this.verificationConflitsEnCours = true;
          return this.echeanceService.verifierConflits(dossier_id, dateHeure, this.echeanceId);
        })
      )
      .subscribe((conflits) => {
        this.verificationConflitsEnCours = false;
        if (conflits) this.conflitsDetectes = conflits;
      });
  }

  /** Combine date_jour (Date) + heure ("HH:mm") en chaîne "YYYY-MM-DDTHH:mm",
   * sans jamais passer par toISOString() (qui convertirait en UTC). */
  private combinerDateHeure(): string | null {
    const { date_jour, heure } = this.form.value;
    if (!date_jour || !heure) return null;

    const d = date_jour as Date;
    const jour = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    return `${jour}T${heure}`;
  }

  private parseLocalDate(chaineISO: string): Date {
    const [annee, mois, jour] = chaineISO.split('-').map(Number);
    return new Date(annee, mois - 1, jour);
  }

  verifierConflits(): void {
    const dateHeure = this.combinerDateHeure();
    const { dossier_id, type } = this.form.value;
    this.conflitsDetectes = [];

    if (!dossier_id || !dateHeure || (type !== 'audience' && type !== 'rdv_client')) {
      return;
    }

    this.verificationConflitsEnCours = true;
    this.echeanceService.verifierConflits(dossier_id, dateHeure, this.echeanceId).subscribe({
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

    const dateHeure = this.combinerDateHeure();
    const { dossier_id, titre, type, lieu, rappel_avant } = this.form.value;
    const data = { dossier_id, titre, type, date_heure: dateHeure, lieu, rappel_avant } as unknown as Partial<Echeance>;

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
