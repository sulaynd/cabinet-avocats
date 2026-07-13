import { Component, OnInit, ViewChild, ElementRef, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Location } from '@angular/common';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { RendezVousService } from '../../../core/services/rendez-vous.service';
import { environment } from '../../../../environments/environment';

interface AvocatPublic {
  id: number;
  name: string;
}

@Component({
  selector: 'app-prise-rdv-publique',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, MatButtonModule, MatIconModule],
  templateUrl: './prise-rdv-publique.component.html',
})
export class PriseRdvPubliqueComponent implements OnInit {
  @ViewChild('joursScroll') joursScroll?: ElementRef<HTMLDivElement>;

  avocats: AvocatPublic[] = [];
  creneaux: string[] = [];
  creneauChoisi: string | null = null;
  jourChoisi: string | null = null;
  chargementCreneaux = false;
  reservationEnCours = false;
  reservationConfirmee = false;
  erreur = '';

  private fb = inject(FormBuilder);
  private location = inject(Location);

  form = this.fb.group({
    avocat_id: [null as number | null, Validators.required],
    nom: ['', Validators.required],
    email: ['', [Validators.required, Validators.email]],
    telephone: ['', Validators.required],
    adresse: [''],
    code_postal: [''],
    ville: [''],
    motif: [''],
  });

  constructor(private rendezVousService: RendezVousService, private http: HttpClient) {}

  /** Ce widget est une vraie page (pas une modale) : "fermer" revient simplement
   * à la page précédente (typiquement l'écran de connexion, d'où vient l'aperçu). */
  fermer(): void {
    this.location.back();
  }

  ngOnInit(): void {
    // Endpoint public listant les avocats du cabinet (à créer côté API si besoin ;
    // en attendant, on peut aussi coder en dur la liste des praticiens du cabinet).
    this.http.get<AvocatPublic[]>(`${environment.apiUrl}/public/avocats`).subscribe({
      next: (a) => (this.avocats = a),
      error: () => (this.avocats = []),
    });
  }

  /**
   * Regroupe les créneaux (liste plate) par jour, pour un sélecteur en deux
   * étapes (jour, puis heure) plutôt qu'une longue liste plate mélangeant
   * dates et heures — plus lisible, surtout sur 14 jours de disponibilités.
   */
  get joursDisponibles(): { cle: string; libelle: string; creneaux: string[] }[] {
    const groupes = new Map<string, string[]>();
    for (const c of this.creneaux) {
      const d = new Date(c);
      const cle = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
      if (!groupes.has(cle)) groupes.set(cle, []);
      groupes.get(cle)!.push(c);
    }
    return Array.from(groupes.entries()).map(([cle, creneaux]) => ({
      cle,
      libelle: new Date(creneaux[0]).toLocaleDateString('fr-CA', { weekday: 'short', day: 'numeric', month: 'short' }),
      creneaux,
    }));
  }

  get creneauxDuJour(): string[] {
    return this.joursDisponibles.find((j) => j.cle === this.jourChoisi)?.creneaux ?? [];
  }

  choisirJour(cle: string): void {
    this.jourChoisi = cle;
    this.creneauChoisi = null;
  }

  /** Fait défiler la rangée de jours au clic des flèches (utile sur ordinateur,
   * où le défilement horizontal à la souris/trackpad n'est pas toujours intuitif). */
  defilerJours(direction: 'gauche' | 'droite'): void {
    const conteneur = this.joursScroll?.nativeElement;
    if (!conteneur) return;
    const decalage = direction === 'gauche' ? -200 : 200;
    conteneur.scrollBy({ left: decalage, behavior: 'smooth' });
  }

  chargerCreneaux(): void {
    const avocatId = this.form.value.avocat_id;
    if (!avocatId) return;
    this.chargementCreneaux = true;
    this.creneauChoisi = null;
    this.jourChoisi = null;

    // Pas de toISOString() : elle convertit en UTC, ce qui peut exclure les
    // créneaux d'aujourd'hui en soirée dans un fuseau en retard sur UTC (ex: Québec).
    const versDateLocale = (d: Date) =>
      `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    const debut = versDateLocale(new Date());
    const fin = versDateLocale(new Date(Date.now() + 30 * 86400000));

    this.rendezVousService.creneauxDisponibles(avocatId, debut, fin).subscribe({
      next: (c) => {
        this.creneaux = c;
        this.chargementCreneaux = false;
        // Sélectionne automatiquement le premier jour disponible pour éviter
        // une étape inutile quand il n'y a qu'une poignée de jours à choisir.
        this.jourChoisi = this.joursDisponibles[0]?.cle ?? null;
      },
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
