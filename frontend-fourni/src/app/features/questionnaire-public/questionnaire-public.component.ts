import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { QuestionnairePublicService } from '../../core/services/questionnaire-public.service';
import { QuestionnairePublicPayload } from '../../core/models/reponse-questionnaire.model';

@Component({
  selector: 'app-questionnaire-public',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './questionnaire-public.component.html',
})
export class QuestionnairePublicComponent implements OnInit {
  token = '';
  donnees: QuestionnairePublicPayload | null = null;
  reponses: Record<string, any> = {};
  chargement = true;
  envoiEnCours = false;
  envoye = false;
  erreur = '';

  constructor(private route: ActivatedRoute, private questionnairePublicService: QuestionnairePublicService) {}

  ngOnInit(): void {
    this.token = this.route.snapshot.paramMap.get('token') || '';
    this.questionnairePublicService.afficher(this.token).subscribe({
      next: (d) => {
        this.donnees = d;
        this.reponses = d.reponses_existantes || {};
        this.chargement = false;
      },
      error: () => {
        this.erreur = 'Ce lien de questionnaire est invalide ou a expiré.';
        this.chargement = false;
      },
    });
  }

  soumettre(): void {
    if (!this.donnees) return;
    this.envoiEnCours = true;

    this.questionnairePublicService.soumettre(this.token, this.reponses).subscribe({
      next: () => {
        this.envoiEnCours = false;
        this.envoye = true;
      },
      error: (err) => {
        this.envoiEnCours = false;
        this.erreur = err?.error?.message || "Impossible d'enregistrer vos réponses.";
      },
    });
  }
}
