import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { RendezVousService } from '../../../core/services/rendez-vous.service';
import { environment } from '../../../../environments/environment';

interface AvocatPublic {
  id: number;
  name: string;
}

@Component({
  selector: 'app-prise-rdv-publique',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './prise-rdv-publique.component.html',
})
export class PriseRdvPubliqueComponent implements OnInit {
  avocats: AvocatPublic[] = [];
  creneaux: string[] = [];
  creneauChoisi: string | null = null;
  chargementCreneaux = false;
  reservationEnCours = false;
  reservationConfirmee = false;
  erreur = '';

  private fb = inject(FormBuilder);

  form = this.fb.group({
    avocat_id: [null as number | null, Validators.required],
    nom: ['', Validators.required],
    email: ['', [Validators.required, Validators.email]],
    telephone: [''],
    motif: [''],
  });

  constructor(private rendezVousService: RendezVousService, private http: HttpClient) {}

  ngOnInit(): void {
    // Endpoint public listant les avocats du cabinet (à créer côté API si besoin ;
    // en attendant, on peut aussi coder en dur la liste des praticiens du cabinet).
    this.http.get<AvocatPublic[]>(`${environment.apiUrl}/public/avocats`).subscribe({
      next: (a) => (this.avocats = a),
      error: () => (this.avocats = []),
    });
  }

  chargerCreneaux(): void {
    const avocatId = this.form.value.avocat_id;
    if (!avocatId) return;
    this.chargementCreneaux = true;
    this.creneauChoisi = null;

    const debut = new Date().toISOString().slice(0, 10);
    const fin = new Date(Date.now() + 14 * 86400000).toISOString().slice(0, 10);

    this.rendezVousService.creneauxDisponibles(avocatId, debut, fin).subscribe({
      next: (c) => { this.creneaux = c; this.chargementCreneaux = false; },
      error: () => (this.chargementCreneaux = false),
    });
  }

  reserver(): void {
    if (this.form.invalid || !this.creneauChoisi) return;
    this.reservationEnCours = true;
    this.erreur = '';

    this.rendezVousService.reserver({ ...(this.form.value as any), date_heure: this.creneauChoisi }).subscribe({
      next: () => {
        this.reservationEnCours = false;
        this.reservationConfirmee = true;
      },
      error: (err) => {
        this.reservationEnCours = false;
        this.erreur = err?.error?.message || 'Impossible de réserver ce créneau, merci de réessayer.';
      },
    });
  }
}
