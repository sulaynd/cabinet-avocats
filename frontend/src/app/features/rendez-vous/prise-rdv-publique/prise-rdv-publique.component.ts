import { Component, OnInit, ViewChild, ElementRef, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Location } from '@angular/common';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { RendezVousService } from '../../../core/services/rendez-vous.service';
import { TypeAffaireService } from '../../../core/services/type-affaire.service';
import { TypeAffaire } from '../../../core/models/type-affaire.model';

@Component({
  selector: 'app-prise-rdv-publique',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, MatButtonModule, MatIconModule],
  templateUrl: './prise-rdv-publique.component.html',
})
export class PriseRdvPubliqueComponent implements OnInit {
  @ViewChild('joursScroll') joursScroll?: ElementRef<HTMLDivElement>;

  creneaux: string[] = [];
  creneauChoisi: string | null = null;
  jourChoisi: string | null = null;
  chargementCreneaux = false;
  reservationEnCours = false;
  reservationConfirmee = false;
  erreur = '';

  private fb = inject(FormBuilder);
  private location = inject(Location);

  // Chargés dynamiquement depuis l'API (gérables dans "Types d'affaire",
  // menu admin) plutôt que codés en dur.
  typesAffaire: TypeAffaire[] = [];

  // Le client ne choisit plus l'avocat lui-même : il décrit son besoin (type
  // de dossier et motif, tous deux obligatoires), et le cabinet assigne
  // l'avocat le plus approprié à la confirmation, selon les disponibilités
  // réelles de chacun.
  form = this.fb.group({
    type_affaire: ['', Validators.required],
    sous_categories_affaire: this.fb.nonNullable.control([] as string[]),
    nom: ['', Validators.required],
    email: ['', [Validators.required, Validators.email]],
    telephone: ['', Validators.required],
    adresse: [''],
    code_postal: [''],
    ville: [''],
    motif: ['', Validators.required],
  });

  constructor(private rendezVousService: RendezVousService, private typeAffaireService: TypeAffaireService) {}

  /** Ce widget est une vraie page (pas une modale) : "fermer" revient simplement
   * à la page précédente (typiquement l'écran de connexion, d'où vient l'aperçu). */
  fermer(): void {
    this.location.back();
  }

  ngOnInit(): void {
    // Les créneaux sont désormais génériques (horaires du cabinet), plus
    // besoin d'attendre le choix d'un avocat pour les charger.
    this.chargerCreneaux();
    this.typeAffaireService.liste(true).subscribe((t) => (this.typesAffaire = t));

    this.form.get('type_affaire')?.valueChanges.subscribe((type) => {
      const controle = this.form.get('sous_categories_affaire');
      const typeChoisi = this.typesAffaire.find((t) => t.slug === type);
      const possedeSousCategories = (typeChoisi?.sous_categories ?? []).some((sc) => sc.actif);
      if (possedeSousCategories) {
        controle?.setValidators(Validators.required);
      } else {
        controle?.clearValidators();
        controle?.setValue([], { emitEvent: false });
      }
      controle?.updateValueAndValidity({ emitEvent: false });
    });
  }

  /** Sous-catégories actives du type d'affaire actuellement sélectionné. */
  get sousCategoriesDuTypeChoisi(): { valeur: string; libelle: string }[] {
    const type = this.typesAffaire.find((t) => t.slug === this.form.value.type_affaire);
    return (type?.sous_categories ?? []).filter((sc) => sc.actif).map((sc) => ({ valeur: sc.slug, libelle: sc.libelle }));
  }

  estSousCategorieCochee(valeur: string): boolean {
    return (this.form.value.sous_categories_affaire ?? []).includes(valeur);
  }

  basculerSousCategorie(valeur: string, coche: boolean): void {
    const actuelles: string[] = this.form.value.sous_categories_affaire ?? [];
    const nouvelles = coche ? [...actuelles, valeur] : actuelles.filter((v) => v !== valeur);
    this.form.get('sous_categories_affaire')?.setValue(nouvelles);
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
    this.chargementCreneaux = true;
    this.creneauChoisi = null;
    this.jourChoisi = null;

    // Pas de toISOString() : elle convertit en UTC, ce qui peut exclure les
    // créneaux d'aujourd'hui en soirée dans un fuseau en retard sur UTC (ex: Québec).
    const versDateLocale = (d: Date) =>
      `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    const debut = versDateLocale(new Date());
    const fin = versDateLocale(new Date(Date.now() + 30 * 86400000));

    this.rendezVousService.creneauxDisponibles(debut, fin).subscribe({
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
