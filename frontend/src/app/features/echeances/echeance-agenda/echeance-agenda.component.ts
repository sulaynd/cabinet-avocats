import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatSelectModule } from '@angular/material/select';
import { MatButtonModule } from '@angular/material/button';
import { MatButtonToggleModule } from '@angular/material/button-toggle';
import { MatIconModule } from '@angular/material/icon';
import { EcheanceService } from '../../../core/services/echeance.service';
import { UserService } from '../../../core/services/user.service';
import { NotificationService } from '../../../core/services/notification.service';
import { ConfirmService } from '../../../core/services/confirm.service';
import { Echeance, TypeEcheance } from '../../../core/models/echeance.model';
import { Utilisateur } from '../../../core/models/user.model';
import { SynchronisationCalendrierComponent } from '../synchronisation-calendrier/synchronisation-calendrier.component';

interface JourCalendrier {
  date: Date;
  cle: string;
  horsMois: boolean;
  aujourdhui: boolean;
  echeances: Echeance[];
}

@Component({
  selector: 'app-echeance-agenda',
  standalone: true,
  imports: [
    CommonModule, RouterLink, FormsModule,
    MatFormFieldModule, MatSelectModule, MatButtonModule, MatButtonToggleModule, MatIconModule,
    SynchronisationCalendrierComponent,
  ],
  templateUrl: './echeance-agenda.component.html',
})
export class EcheanceAgendaComponent implements OnInit {
  vue: 'calendrier' | 'liste' = 'calendrier';
  toutesEcheances: Echeance[] = [];
  traitants: Utilisateur[] = [];
  chargement = false;
  afficherSync = false;

  typeFiltre: TypeEcheance | '' = '';
  traitantFiltre: number | null = null;

  moisCourant = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
  semaines: JourCalendrier[][] = [];
  jourSelectionne: string | null = null;

  readonly types: TypeEcheance[] = ['audience', 'delai_procedural', 'rdv_client', 'autre'];
  readonly nomsJours = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];

  constructor(
    private echeanceService: EcheanceService,
    private userService: UserService,
    private notification: NotificationService,
    private confirmService: ConfirmService
  ) {}

  ngOnInit(): void {
    // Le filtre "traitant" ne propose que les avocats et assistants (les personnes
    // réellement assignables à un dossier), pas les comptes admin.
    this.userService.liste({ per_page: 100 }).subscribe((res) => {
      this.traitants = res.data.filter((u) => u.role === 'avocat' || u.role === 'assistant');
    });
    this.charger();
  }

  charger(): void {
    this.chargement = true;
    const params: Record<string, string | number> = {};
    if (this.typeFiltre) params['type'] = this.typeFiltre;
    if (this.traitantFiltre) params['traitant_id'] = this.traitantFiltre;

    this.echeanceService.liste(params).subscribe({
      next: (echeances) => {
        this.toutesEcheances = echeances;
        this.construireCalendrier();
        this.chargement = false;
      },
      error: () => (this.chargement = false),
    });
  }

  changerVue(v: 'calendrier' | 'liste'): void {
    this.vue = v;
  }

  moisPrecedent(): void {
    this.moisCourant = new Date(this.moisCourant.getFullYear(), this.moisCourant.getMonth() - 1, 1);
    this.construireCalendrier();
  }
  moisSuivant(): void {
    this.moisCourant = new Date(this.moisCourant.getFullYear(), this.moisCourant.getMonth() + 1, 1);
    this.construireCalendrier();
  }
  aujourdHui(): void {
    this.moisCourant = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
    this.construireCalendrier();
  }

  private cleJour(d: Date): string {
    // Pas de toISOString() : elle convertit en UTC, ce qui décale la clé d'un
    // jour dans un fuseau en retard sur UTC (ex: Québec) — les échéances
    // atterriraient alors dans la mauvaise case du calendrier.
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
  }

  private construireCalendrier(): void {
    const parJour = new Map<string, Echeance[]>();
    for (const e of this.toutesEcheances) {
      const cle = e.date_heure.slice(0, 10);
      if (!parJour.has(cle)) parJour.set(cle, []);
      parJour.get(cle)!.push(e);
    }

    const premierDuMois = new Date(this.moisCourant.getFullYear(), this.moisCourant.getMonth(), 1);
    const decalage = (premierDuMois.getDay() + 6) % 7;
    const debutGrille = new Date(premierDuMois);
    debutGrille.setDate(debutGrille.getDate() - decalage);

    const aujourdhui = this.cleJour(new Date());
    const jours: JourCalendrier[] = [];

    for (let i = 0; i < 42; i++) {
      const date = new Date(debutGrille);
      date.setDate(date.getDate() + i);
      const cle = this.cleJour(date);
      jours.push({
        date,
        cle,
        horsMois: date.getMonth() !== this.moisCourant.getMonth(),
        aujourdhui: cle === aujourdhui,
        echeances: (parJour.get(cle) || []).sort((a, b) => a.date_heure.localeCompare(b.date_heure)),
      });
    }

    this.semaines = [];
    for (let i = 0; i < 6; i++) this.semaines.push(jours.slice(i * 7, i * 7 + 7));
  }

  selectionnerJour(cle: string): void {
    this.jourSelectionne = this.jourSelectionne === cle ? null : cle;
  }

  get echeancesDuJourSelectionne(): Echeance[] {
    if (!this.jourSelectionne) return [];
    return this.toutesEcheances
      .filter((e) => e.date_heure.slice(0, 10) === this.jourSelectionne)
      .sort((a, b) => a.date_heure.localeCompare(b.date_heure));
  }

  get groupesParJour(): { date: string; echeances: Echeance[] }[] {
    const map = new Map<string, Echeance[]>();
    for (const e of this.toutesEcheances) {
      const jour = e.date_heure.slice(0, 10);
      if (!map.has(jour)) map.set(jour, []);
      map.get(jour)!.push(e);
    }
    return Array.from(map.entries())
      .sort(([a], [b]) => a.localeCompare(b))
      .map(([date, echeances]) => ({ date, echeances: echeances.sort((a, b) => a.date_heure.localeCompare(b.date_heure)) }));
  }

  supprimer(echeance: Echeance): void {
    this.confirmService
      .demander({ titre: 'Supprimer cette échéance ?', message: `"${echeance.titre}" sera définitivement supprimée.`, libelleConfirmer: 'Supprimer', destructif: true })
      .subscribe((confirme) => {
        if (!confirme) return;
        this.echeanceService.supprimer(echeance.id).subscribe({
          next: () => {
            this.notification.succes('Échéance supprimée.');
            this.charger();
          },
          error: (err) => this.notification.erreur(err?.error?.message || 'Impossible de supprimer cette échéance.'),
        });
      });
  }

  marquerRealisee(echeance: Echeance): void {
    this.echeanceService.modifier(echeance.id, { statut: 'realisee' }).subscribe({
      next: () => this.charger(),
      error: (err) => this.notification.erreur(err?.error?.message || 'Impossible de mettre à jour cette échéance.'),
    });
  }
}
