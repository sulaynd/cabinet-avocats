import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormArray, FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, ActivatedRoute } from '@angular/router';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { FactureService } from '../../../core/services/facture.service';
import { DossierService } from '../../../core/services/dossier.service';
import { NotificationService } from '../../../core/services/notification.service';
import { Dossier } from '../../../core/models/dossier.model';
import { Facture } from '../../../core/models/facture.model';

@Component({
  selector: 'app-facture-form',
  standalone: true,
  imports: [
    CommonModule, ReactiveFormsModule,
    MatFormFieldModule, MatInputModule, MatSelectModule, MatButtonModule, MatIconModule,
  ],
  templateUrl: './facture-form.component.html',
})
export class FactureFormComponent implements OnInit {
  dossiers: Dossier[] = [];
  enregistrement = false;

  private fb = inject(FormBuilder);

  form = this.fb.group({
    // dossier_id reste nullable : c'est l'état réel tant que rien n'est
    // sélectionné (Validators.required empêche la soumission avant ce choix).
    dossier_id: this.fb.control(null as number | null, Validators.required),
    date_emission: this.fb.nonNullable.control(this.aujourdhui(), Validators.required),
    date_echeance: this.fb.control(''),
    // Taux par défaut du Québec, calculés indépendamment (pas de taxe sur
    // taxe depuis la réforme de 2013) — modifiables si autre province.
    taux_tps: this.fb.nonNullable.control(5, [Validators.required, Validators.min(0), Validators.max(100)]),
    taux_tvq: this.fb.nonNullable.control(9.975, [Validators.required, Validators.min(0), Validators.max(100)]),
    lignes: this.fb.array([this.creerLigne()]),
  });

  constructor(
    private factureService: FactureService,
    private dossierService: DossierService,
    private notification: NotificationService,
    private router: Router,
    private route: ActivatedRoute
  ) {}

  ngOnInit(): void {
    this.dossierService.liste({ per_page: 100 }).subscribe((res) => {
      this.dossiers = res.data;

      const dossierIdParam = this.route.snapshot.queryParamMap.get('dossier_id');
      if (dossierIdParam) {
        this.form.patchValue({ dossier_id: Number(dossierIdParam) });
      }
    });
  }

  get lignes(): FormArray {
    return this.form.get('lignes') as FormArray;
  }

  private creerLigne() {
    return this.fb.group({
      description: this.fb.nonNullable.control('', Validators.required),
      quantite: this.fb.nonNullable.control(1, [Validators.required, Validators.min(0)]),
      prix_unitaire: this.fb.nonNullable.control(0, [Validators.required, Validators.min(0)]),
    });
  }

  ajouterLigne(): void {
    this.lignes.push(this.creerLigne());
  }

  supprimerLigne(index: number): void {
    if (this.lignes.length > 1) this.lignes.removeAt(index);
  }

  montantLigne(index: number): number {
    const ligne = this.lignes.at(index).value;
    return (ligne.quantite || 0) * (ligne.prix_unitaire || 0);
  }

  get totalHt(): number {
    return this.lignes.controls.reduce((total, _, i) => total + this.montantLigne(i), 0);
  }

  get montantTps(): number {
    return this.totalHt * ((this.form.value.taux_tps || 0) / 100);
  }

  get montantTvq(): number {
    return this.totalHt * ((this.form.value.taux_tvq || 0) / 100);
  }

  get totalTtc(): number {
    return this.totalHt + this.montantTps + this.montantTvq;
  }

  private aujourdhui(): string {
    // Pas de toISOString() : elle convertit en UTC, ce qui peut donner la date
    // de demain en soirée dans un fuseau en retard sur UTC (ex: Québec).
    const maintenant = new Date();
    return `${maintenant.getFullYear()}-${String(maintenant.getMonth() + 1).padStart(2, '0')}-${String(maintenant.getDate()).padStart(2, '0')}`;
  }

  annuler(): void {
    this.router.navigate(['/factures']);
  }

  soumettre(): void {
    if (this.form.invalid) return;
    this.enregistrement = true;

    const dossier = this.dossiers.find((d) => d.id === this.form.value.dossier_id);

    // Cast : dossier_id est `number | null` côté formulaire (nul tant que rien
    // n'est sélectionné), mais Validators.required + le garde ci-dessus
    // garantissent qu'il est bien renseigné à ce stade.
    const payload = {
      ...this.form.value,
      client_id: dossier?.client_id,
    } as unknown as Partial<Facture>;

    this.factureService.creer(payload).subscribe({
      next: () => {
        this.enregistrement = false;
        this.notification.succes('Facture créée.');
        this.router.navigate(['/factures']);
      },
      error: (err) => {
        this.enregistrement = false;
        this.notification.erreur(err?.error?.message || "Impossible de créer cette facture.");
      },
    });
  }
}
