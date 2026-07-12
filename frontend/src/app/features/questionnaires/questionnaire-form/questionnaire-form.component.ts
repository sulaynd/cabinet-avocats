import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormArray, FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { QuestionnaireService } from '../../../core/services/questionnaire.service';
import { NotificationService } from '../../../core/services/notification.service';
import { TypeChampQuestionnaire } from '../../../core/models/questionnaire.model';

@Component({
  selector: 'app-questionnaire-form',
  standalone: true,
  imports: [
    CommonModule, ReactiveFormsModule,
    MatFormFieldModule, MatInputModule, MatSelectModule, MatButtonModule, MatIconModule, MatCheckboxModule,
  ],
  templateUrl: './questionnaire-form.component.html',
})
export class QuestionnaireFormComponent implements OnInit {
  questionnaireId: number | null = null;
  enregistrement = false;

  readonly typesChamp: TypeChampQuestionnaire[] = ['texte', 'zone_texte', 'choix', 'case'];
  readonly typesAffaire = ['civil', 'penal', 'commercial', 'famille', 'travail', 'immobilier', 'autre'];

  private fb = inject(FormBuilder);

  form = this.fb.group({
    nom: this.fb.nonNullable.control('', Validators.required),
    description: this.fb.control(''),
    type_affaire: this.fb.control(null as string | null),
    actif: this.fb.nonNullable.control(true),
    champs: this.fb.array([this.creerChamp()]),
  });

  constructor(
    private questionnaireService: QuestionnaireService,
    private notification: NotificationService,
    private route: ActivatedRoute,
    private router: Router
  ) {}

  ngOnInit(): void {
    const idParam = this.route.snapshot.paramMap.get('id');
    if (idParam) {
      this.questionnaireId = Number(idParam);
      this.questionnaireService.obtenir(this.questionnaireId).subscribe((q) => {
        this.form.patchValue({ nom: q.nom, description: q.description ?? '', type_affaire: q.type_affaire ?? null, actif: q.actif });
        this.champs.clear();
        q.champs.forEach((c) => this.champs.push(this.creerChamp(c.cle, c.label, c.type, (c.options || []).join(', '), c.requis ?? false)));
      });
    }
  }

  get champs(): FormArray {
    return this.form.get('champs') as FormArray;
  }

  private creerChamp(cle = '', label = '', type: TypeChampQuestionnaire = 'texte', options = '', requis = false) {
    return this.fb.group({
      cle: [cle, Validators.required],
      label: [label, Validators.required],
      type: [type, Validators.required],
      options: [options],
      requis: [requis],
    });
  }

  ajouterChamp(): void {
    this.champs.push(this.creerChamp());
  }

  supprimerChamp(index: number): void {
    if (this.champs.length > 1) this.champs.removeAt(index);
  }

  annuler(): void {
    this.router.navigate(['/questionnaires']);
  }

  soumettre(): void {
    if (this.form.invalid) return;
    this.enregistrement = true;

    const valeurs = this.form.value;
    const payload = {
      ...valeurs,
      champs: (valeurs.champs || []).map((c: any) => ({
        cle: c.cle,
        label: c.label,
        type: c.type,
        requis: c.requis,
        options: c.type === 'choix' ? String(c.options || '').split(',').map((s: string) => s.trim()).filter(Boolean) : [],
      })),
    };

    const requete = this.questionnaireId
      ? this.questionnaireService.modifier(this.questionnaireId, payload)
      : this.questionnaireService.creer(payload);

    requete.subscribe({
      next: () => {
        this.enregistrement = false;
        this.notification.succes(this.questionnaireId ? 'Questionnaire modifié.' : 'Questionnaire créé.');
        this.router.navigate(['/questionnaires']);
      },
      error: (err) => {
        this.enregistrement = false;
        this.notification.erreur(err?.error?.message || "Impossible d'enregistrer ce questionnaire.");
      },
    });
  }
}
