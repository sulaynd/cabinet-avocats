import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RendezVousService } from '../../../core/services/rendez-vous.service';
import { RendezVous } from '../../../core/models/rendez-vous.model';

@Component({
  selector: 'app-rendez-vous-list',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './rendez-vous-list.component.html',
})
export class RendezVousListComponent implements OnInit {
  rendezVous: RendezVous[] = [];
  chargement = false;

  constructor(private rendezVousService: RendezVousService) {}

  ngOnInit(): void {
    this.charger();
  }

  charger(): void {
    this.chargement = true;
    this.rendezVousService.liste().subscribe({
      next: (r) => { this.rendezVous = r; this.chargement = false; },
      error: () => (this.chargement = false),
    });
  }

  confirmer(id: number): void {
    this.rendezVousService.confirmer(id).subscribe(() => this.charger());
  }

  annuler(id: number): void {
    if (!confirm('Annuler cette demande de rendez-vous ?')) return;
    this.rendezVousService.annuler(id).subscribe(() => this.charger());
  }
}
