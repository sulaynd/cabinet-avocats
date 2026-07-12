import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { IcalService } from '../../../core/services/ical.service';
import { NotificationService } from '../../../core/services/notification.service';
import { ConfirmService } from '../../../core/services/confirm.service';
import { LiensIcal } from '../../../core/models/ical.model';
import { HasRoleDirective } from '../../../core/directives/has-role.directive';

@Component({
  selector: 'app-synchronisation-calendrier',
  standalone: true,
  imports: [CommonModule, HasRoleDirective, MatButtonModule, MatIconModule],
  templateUrl: './synchronisation-calendrier.component.html',
})
export class SynchronisationCalendrierComponent implements OnInit {
  liens: LiensIcal | null = null;
  chargement = false;
  copie: 'personnel' | 'equipe' | null = null;

  constructor(
    private icalService: IcalService,
    private notification: NotificationService,
    private confirmService: ConfirmService
  ) {}

  ngOnInit(): void {
    this.charger();
  }

  charger(): void {
    this.chargement = true;
    this.icalService.mesLiens().subscribe({
      next: (liens) => {
        this.liens = liens;
        this.chargement = false;
      },
      error: () => {
        this.chargement = false;
        this.notification.erreur('Impossible de charger les liens de synchronisation.');
      },
    });
  }

  webcal(url: string): string {
    return this.icalService.versWebcal(url);
  }

  copier(url: string, cle: 'personnel' | 'equipe'): void {
    navigator.clipboard?.writeText(url);
    this.copie = cle;
    setTimeout(() => (this.copie = null), 2000);
  }

  regenererPersonnel(): void {
    this.confirmService
      .demander({ titre: 'Régénérer votre lien personnel ?', message: "L'ancien lien cessera immédiatement de fonctionner dans les agendas déjà abonnés.", libelleConfirmer: 'Régénérer', destructif: true })
      .subscribe((confirme) => {
        if (!confirme) return;
        this.icalService.regenererPersonnel().subscribe({
          next: () => {
            this.notification.succes('Lien personnel régénéré.');
            this.charger();
          },
          error: (err) => this.notification.erreur(err?.error?.message || 'Impossible de régénérer ce lien.'),
        });
      });
  }

  regenererEquipe(): void {
    this.confirmService
      .demander({ titre: "Régénérer le lien d'équipe ?", message: 'Tous les agendas déjà abonnés (toute l\'équipe) devront se réabonner avec le nouveau lien.', libelleConfirmer: 'Régénérer', destructif: true })
      .subscribe((confirme) => {
        if (!confirme) return;
        this.icalService.regenererEquipe().subscribe({
          next: () => {
            this.notification.succes("Lien d'équipe régénéré.");
            this.charger();
          },
          error: (err) => this.notification.erreur(err?.error?.message || 'Impossible de régénérer ce lien.'),
        });
      });
  }
}
