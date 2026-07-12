import { Component } from '@angular/core';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-acces-refuse',
  standalone: true,
  imports: [RouterLink],
  template: `
    <div class="acces-refuse">
      <h2>Accès refusé</h2>
      <p>Vous n'avez pas les droits nécessaires pour accéder à cette page.</p>
      <a routerLink="/dossiers">Retour à l'accueil</a>
    </div>
  `,
})
export class AccesRefuseComponent {}
