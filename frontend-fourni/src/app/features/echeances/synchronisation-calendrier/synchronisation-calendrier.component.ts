import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { IcalService } from '../../../core/services/ical.service';
import { LiensIcal } from '../../../core/models/ical.model';
import { HasRoleDirective } from '../../../core/directives/has-role.directive';

@Component({
  selector: 'app-synchronisation-calendrier',
  standalone: true,
  imports: [CommonModule, HasRoleDirective],
  templateUrl: './synchronisation-calendrier.component.html',
})
export class SynchronisationCalendrierComponent implements OnInit {
  liens: LiensIcal | null = null;
  chargement = false;
  copie: 'personnel' | 'equipe' | null = null;

  constructor(private icalService: IcalService) {}

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
      error: () => (this.chargement = false),
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
    if (!confirm("Régénérer votre lien personnel ? L'ancien lien cessera immédiatement de fonctionner dans les agendas déjà abonnés.")) return;
    this.icalService.regenererPersonnel().subscribe(() => this.charger());
  }

  regenererEquipe(): void {
    if (!confirm("Régénérer le lien d'équipe ? Tous les agendas déjà abonnés (toute l'équipe) devront se réabonner avec le nouveau lien.")) return;
    this.icalService.regenererEquipe().subscribe(() => this.charger());
  }
}
