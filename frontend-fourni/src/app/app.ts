import { Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';

/**
 * Composant racine minimal : uniquement le <router-outlet>, qui affiche soit
 * l'écran de connexion, soit le ShellComponent (barre latérale + contenu)
 * selon la route. `ng new` génère par défaut une grande page de bienvenue
 * ("Hello, {{title}}" + logo Angular) qu'il faut vider — sinon elle
 * s'affiche au-dessus de tout le reste de l'application.
 */
@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet],
  templateUrl: './app.html',
  styleUrl: './app.scss',
})
export class App {}
