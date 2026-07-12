import { Component, OnInit, ViewChild, AfterViewInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { MatTableModule, MatTableDataSource } from '@angular/material/table';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatChipsModule } from '@angular/material/chips';
import { MatSortModule, MatSort } from '@angular/material/sort';
import { QuestionnaireService } from '../../../core/services/questionnaire.service';
import { NotificationService } from '../../../core/services/notification.service';
import { ConfirmService } from '../../../core/services/confirm.service';
import { Questionnaire } from '../../../core/models/questionnaire.model';

@Component({
  selector: 'app-questionnaire-list',
  standalone: true,
  imports: [
    CommonModule, RouterLink,
    MatTableModule, MatButtonModule, MatIconModule, MatChipsModule, MatSortModule,
  ],
  templateUrl: './questionnaire-list.component.html',
})
export class QuestionnaireListComponent implements OnInit, AfterViewInit {
  dataSource = new MatTableDataSource<Questionnaire>([]);
  @ViewChild(MatSort) sort!: MatSort;

  readonly colonnes = ['nom', 'type_affaire', 'champs', 'statut', 'actions'];

  constructor(
    private questionnaireService: QuestionnaireService,
    private notification: NotificationService,
    private confirmService: ConfirmService
  ) {}

  ngOnInit(): void {
    this.charger();
  }

  ngAfterViewInit(): void {
    this.dataSource.sort = this.sort;
    this.dataSource.sortingDataAccessor = (questionnaire, colonne) => {
      switch (colonne) {
        case 'type_affaire': return questionnaire.type_affaire || 'zzz'; // "par défaut" trié en dernier
        case 'champs': return questionnaire.champs.length;
        default: return (questionnaire as any)[colonne] ?? '';
      }
    };
  }

  charger(): void {
    this.questionnaireService.liste().subscribe({
      next: (q) => (this.dataSource.data = q),
      error: () => this.notification.erreur('Impossible de charger les questionnaires.'),
    });
  }

  supprimer(q: Questionnaire): void {
    this.confirmService
      .demander({
        titre: 'Supprimer ce questionnaire ?',
        message: `"${q.nom}" sera définitivement supprimé.`,
        libelleConfirmer: 'Supprimer',
        destructif: true,
      })
      .subscribe((confirme) => {
        if (!confirme) return;
        this.questionnaireService.supprimer(q.id).subscribe({
          next: () => {
            this.notification.succes('Questionnaire supprimé.');
            this.charger();
          },
          error: (err) => this.notification.erreur(err?.error?.message || 'Impossible de supprimer ce questionnaire.'),
        });
      });
  }
}
