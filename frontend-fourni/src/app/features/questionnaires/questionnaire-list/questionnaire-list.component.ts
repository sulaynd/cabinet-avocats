import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { QuestionnaireService } from '../../../core/services/questionnaire.service';
import { Questionnaire } from '../../../core/models/questionnaire.model';

@Component({
  selector: 'app-questionnaire-list',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './questionnaire-list.component.html',
})
export class QuestionnaireListComponent implements OnInit {
  questionnaires: Questionnaire[] = [];

  constructor(private questionnaireService: QuestionnaireService) {}

  ngOnInit(): void {
    this.charger();
  }

  charger(): void {
    this.questionnaireService.liste().subscribe((q) => (this.questionnaires = q));
  }

  supprimer(q: Questionnaire): void {
    if (!confirm(`Supprimer le questionnaire "${q.nom}" ?`)) return;
    this.questionnaireService.supprimer(q.id).subscribe(() => this.charger());
  }
}
