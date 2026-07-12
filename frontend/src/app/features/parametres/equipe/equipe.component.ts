import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MembreEquipeService } from '../../../core/services/membre-equipe.service';
import { NotificationService } from '../../../core/services/notification.service';
import { ConfirmService } from '../../../core/services/confirm.service';
import { MembreEquipe } from '../../../core/models/membre-equipe.model';

@Component({
  selector: 'app-equipe',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink, MatButtonModule, MatIconModule, MatCheckboxModule],
  templateUrl: './equipe.component.html',
})
export class EquipeComponent implements OnInit {
  membres: MembreEquipe[] = [];
  chargement = true;
  enregistrementEnCours: Record<number, boolean> = {};
  apercusFichiers: Record<number, string> = {};
  fichiersSelectionnes: Record<number, File> = {};

  constructor(
    private membreEquipeService: MembreEquipeService,
    private notification: NotificationService,
    private confirmService: ConfirmService
  ) {}

  ngOnInit(): void {
    this.charger();
  }

  charger(): void {
    this.chargement = true;
    this.membreEquipeService.liste().subscribe({
      next: (m) => {
        this.membres = m;
        this.chargement = false;
      },
      error: (err) => {
        this.chargement = false;
        this.notification.erreur(err?.error?.message || "Impossible de charger l'équipe.");
      },
    });
  }

  ajouterMembre(): void {
    // Membre temporaire (id négatif, unique via Date.now()) tant qu'il n'est pas
    // enregistré côté serveur — évite de créer en base avant que l'admin ait
    // vraiment saisi quelque chose.
    this.membres.push({
      id: -Date.now(),
      nom: '',
      titre: '',
      bio: '',
      photo_url: null,
      ordre: this.membres.length,
      actif: true,
    });
  }

  estNouveau(membre: MembreEquipe): boolean {
    return membre.id < 0;
  }

  enregistrer(membre: MembreEquipe): void {
    if (!membre.nom.trim()) {
      this.notification.erreur('Le nom est obligatoire.');
      return;
    }
    this.enregistrementEnCours[membre.id] = true;

    const payload = { nom: membre.nom, titre: membre.titre, bio: membre.bio, ordre: membre.ordre, actif: membre.actif };
    const requete = this.estNouveau(membre)
      ? this.membreEquipeService.creer(payload)
      : this.membreEquipeService.modifier(membre.id, payload);

    requete.subscribe({
      next: (m) => {
        delete this.enregistrementEnCours[membre.id];
        this.notification.succes('Membre enregistré.');
        this.charger();

        // Si une photo était déjà sélectionnée avant la toute première
        // sauvegarde (membre pas encore créé, donc pas encore d'id valide),
        // on la téléverse maintenant que l'id réel existe.
        const ancienId = membre.id;
        if (this.fichiersSelectionnes[ancienId]) {
          const fichier = this.fichiersSelectionnes[ancienId];
          delete this.fichiersSelectionnes[ancienId];
          this.membreEquipeService.televerserPhoto(m.id, fichier).subscribe(() => this.charger());
        }
      },
      error: (err) => {
        delete this.enregistrementEnCours[membre.id];
        this.notification.erreur(err?.error?.message || "Impossible d'enregistrer ce membre.");
      },
    });
  }

  supprimer(membre: MembreEquipe): void {
    if (this.estNouveau(membre)) {
      this.membres = this.membres.filter((m) => m.id !== membre.id);
      return;
    }
    this.confirmService
      .demander({ titre: 'Supprimer ce membre ?', message: `"${membre.nom}" sera retiré de la page d'accueil.`, libelleConfirmer: 'Supprimer', destructif: true })
      .subscribe((confirme) => {
        if (!confirme) return;
        this.membreEquipeService.supprimer(membre.id).subscribe({
          next: () => {
            this.notification.succes('Membre supprimé.');
            this.charger();
          },
          error: (err) => this.notification.erreur(err?.error?.message || 'Impossible de supprimer ce membre.'),
        });
      });
  }

  surSelectionPhoto(event: Event, membre: MembreEquipe): void {
    const input = event.target as HTMLInputElement;
    const fichier = input.files?.[0];
    if (!fichier) return;

    const lecteur = new FileReader();
    lecteur.onload = () => (this.apercusFichiers[membre.id] = lecteur.result as string);
    lecteur.readAsDataURL(fichier);

    if (this.estNouveau(membre)) {
      // Pas encore d'id réel : on garde le fichier de côté, il sera téléversé
      // juste après la création (voir enregistrer()).
      this.fichiersSelectionnes[membre.id] = fichier;
      this.notification.succes("Photo prête — elle sera envoyée à l'enregistrement du membre.");
      return;
    }

    this.membreEquipeService.televerserPhoto(membre.id, fichier).subscribe({
      next: () => {
        this.notification.succes('Photo mise à jour.');
        this.charger();
      },
      error: (err) => this.notification.erreur(err?.error?.message || 'Impossible de téléverser cette photo.'),
    });
  }
}
