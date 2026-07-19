import { Routes } from '@angular/router';
import { DossierListComponent } from './features/dossiers/dossier-list/dossier-list.component';
import { DossierFormComponent } from './features/dossiers/dossier-form/dossier-form.component';
import { DossierDetailComponent } from './features/dossiers/dossier-detail/dossier-detail.component';
import { ClientListComponent } from './features/clients/client-list/client-list.component';
import { ClientFormComponent } from './features/clients/client-form/client-form.component';
import { EcheanceAgendaComponent } from './features/echeances/echeance-agenda/echeance-agenda.component';
import { EcheanceFormComponent } from './features/echeances/echeance-form/echeance-form.component';
import { FactureListComponent } from './features/factures/facture-list/facture-list.component';
import { FactureFormComponent } from './features/factures/facture-form/facture-form.component';
import { UserListComponent } from './features/users/user-list/user-list.component';
import { UserFormComponent } from './features/users/user-form/user-form.component';
import { RendezVousListComponent } from './features/rendez-vous/rendez-vous-list/rendez-vous-list.component';
import { PriseRdvPubliqueComponent } from './features/rendez-vous/prise-rdv-publique/prise-rdv-publique.component';
import { PortailLoginComponent } from './features/portail/portail-login/portail-login.component';
import { PortailDossiersComponent } from './features/portail/portail-dossiers/portail-dossiers.component';
import { QuestionnaireListComponent } from './features/questionnaires/questionnaire-list/questionnaire-list.component';
import { QuestionnaireFormComponent } from './features/questionnaires/questionnaire-form/questionnaire-form.component';
import { QuestionnairePublicComponent } from './features/questionnaire-public/questionnaire-public.component';
import { LoginComponent } from './features/auth/login/login.component';
import { ShellComponent } from './core/shell/shell.component';
import { authGuard } from './core/guards/auth.guard';
import { roleGuard } from './core/guards/role.guard';
import { portailAuthGuard } from './core/guards/portail-auth.guard';

export const routes: Routes = [
  // Page d'accueil publique (site vitrine du cabinet) — remplace la racine "/".
  // Placée en premier et en pathMatch:'full' : ne capte QUE l'URL exacte "/",
  // laisse donc /dossiers, /clients... gérés plus bas par ShellComponent intacts.
  { path: '', pathMatch: 'full', loadComponent: () => import('./features/accueil/accueil.component').then(m => m.AccueilComponent) },

  { path: 'connexion', component: LoginComponent },
  {
    path: 'mot-de-passe-oublie',
    loadComponent: () =>
      import('./features/auth/mot-de-passe-oublie/mot-de-passe-oublie.component').then(
        (m) => m.MotDePasseOublieComponent
      ),
  },
  {
    path: 'reinitialiser-mot-de-passe',
    loadComponent: () =>
      import('./features/auth/reinitialiser-mot-de-passe/reinitialiser-mot-de-passe.component').then(
        (m) => m.ReinitialiserMotDePasseComponent
      ),
  },
  {
    path: 'changer-mot-de-passe',
    canActivate: [authGuard],
    loadComponent: () =>
      import('./features/auth/changer-mot-de-passe/changer-mot-de-passe.component').then(
        (m) => m.ChangerMotDePasseComponent
      ),
  },
  { path: 'acces-refuse', loadComponent: () => import('./features/auth/acces-refuse.component').then(m => m.AccesRefuseComponent) },

  // Widget public de prise de rendez-vous (site vitrine, aucune authentification).
  { path: 'prendre-rendez-vous', component: PriseRdvPubliqueComponent },

  // Questionnaire de pré-consultation — page publique ouverte depuis le lien reçu par email.
  { path: 'questionnaire/:token', component: QuestionnairePublicComponent },

  // Portail client — authentification et espace totalement séparés du cabinet.
  { path: 'portail/connexion', component: PortailLoginComponent },
  {
    path: 'portail/mot-de-passe-oublie',
    loadComponent: () =>
      import('./features/portail/portail-mot-de-passe-oublie/portail-mot-de-passe-oublie.component').then(
        (m) => m.PortailMotDePasseOublieComponent
      ),
  },
  {
    path: 'portail/reinitialiser-mot-de-passe',
    loadComponent: () =>
      import(
        './features/portail/portail-reinitialiser-mot-de-passe/portail-reinitialiser-mot-de-passe.component'
      ).then((m) => m.PortailReinitialiserMotDePasseComponent),
  },
  {
    path: 'portail/changer-mot-de-passe',
    canActivate: [portailAuthGuard],
    loadComponent: () =>
      import('./features/portail/portail-changer-mot-de-passe/portail-changer-mot-de-passe.component').then(
        (m) => m.PortailChangerMotDePasseComponent
      ),
  },
  { path: 'portail/mes-dossiers', component: PortailDossiersComponent, canActivate: [portailAuthGuard] },
  {
    path: 'portail/temoignage',
    canActivate: [portailAuthGuard],
    loadComponent: () =>
      import('./features/portail/portail-temoignage/portail-temoignage.component').then(
        (m) => m.PortailTemoignageComponent
      ),
  },

  {
    path: '',
    component: ShellComponent,
    canActivate: [authGuard],
    children: [
      // Tableau de bord (statistiques et état financier) — admin uniquement
      {
        path: 'tableau-de-bord',
        canActivate: [roleGuard],
        data: { roles: ['admin'] },
        loadComponent: () =>
          import('./features/tableau-de-bord/tableau-de-bord.component').then((m) => m.TableauDeBordComponent),
      },

      // Dossiers — accessibles à tous les rôles authentifiés
      { path: 'dossiers', component: DossierListComponent },
      { path: 'dossiers/nouveau', component: DossierFormComponent },
      { path: 'dossiers/:id/modifier', component: DossierFormComponent },
      { path: 'dossiers/:id', component: DossierDetailComponent },

      // Clients — accessibles à tous les rôles authentifiés
      { path: 'clients', component: ClientListComponent },
      { path: 'clients/nouveau', component: ClientFormComponent },
      { path: 'clients/:id/modifier', component: ClientFormComponent },

      // Agenda / Échéances — accessibles à tous les rôles authentifiés
      { path: 'echeances', component: EcheanceAgendaComponent },
      { path: 'echeances/nouvelle', component: EcheanceFormComponent },
      { path: 'echeances/:id/modifier', component: EcheanceFormComponent },

      // Demandes de rendez-vous en ligne reçues — accessibles à tous les rôles authentifiés
      { path: 'rendez-vous', component: RendezVousListComponent },

      // Factures — création/modification/suppression réservées à admin + avocat
      { path: 'factures', component: FactureListComponent },
      {
        path: 'factures/nouvelle',
        component: FactureFormComponent,
        canActivate: [roleGuard],
        data: { roles: ['admin', 'avocat'] },
      },

      // Utilisateurs du cabinet — entièrement réservé au rôle admin
      {
        path: 'utilisateurs',
        canActivate: [roleGuard],
        data: { roles: ['admin'] },
        children: [
          { path: '', component: UserListComponent },
          { path: 'nouveau', component: UserFormComponent },
          { path: ':id/modifier', component: UserFormComponent },
        ],
      },

      // Modèles de questionnaires d'accueil (onboarding) — entièrement réservé au rôle admin
      {
        path: 'questionnaires',
        canActivate: [roleGuard],
        data: { roles: ['admin'] },
        children: [
          { path: '', component: QuestionnaireListComponent },
          { path: 'nouveau', component: QuestionnaireFormComponent },
          { path: ':id/modifier', component: QuestionnaireFormComponent },
        ],
      },

      // Coordonnées du cabinet (nom, adresse, téléphone, email) — admin uniquement
      {
        path: 'parametres-cabinet',
        canActivate: [roleGuard],
        data: { roles: ['admin'] },
        loadComponent: () =>
          import('./features/parametres/parametres-cabinet/parametres-cabinet.component').then(
            (m) => m.ParametresCabinetComponent
          ),
      },

      // Témoignages (affichés sur la page d'accueil publique) — admin uniquement
      {
        path: 'temoignages',
        canActivate: [roleGuard],
        data: { roles: ['admin'] },
        loadComponent: () =>
          import('./features/parametres/temoignages/temoignages.component').then((m) => m.TemoignagesComponent),
      },

      // Offres d'emploi (Carrières, affichées sur la page d'accueil publique) — admin uniquement
      {
        path: 'carrieres',
        canActivate: [roleGuard],
        data: { roles: ['admin'] },
        loadComponent: () =>
          import('./features/parametres/carrieres/carrieres.component').then((m) => m.CarrieresComponent),
      },

      // Actualités (affichées sur la page d'accueil publique) — admin uniquement
      {
        path: 'actualites',
        canActivate: [roleGuard],
        data: { roles: ['admin'] },
        loadComponent: () =>
          import('./features/parametres/actualites/actualites.component').then((m) => m.ActualitesComponent),
      },

      // Modèles de documents (fusion documentaire) — admin uniquement
      {
        path: 'modeles-documents',
        canActivate: [roleGuard],
        data: { roles: ['admin'] },
        loadComponent: () =>
          import('./features/parametres/modeles-documents/modeles-documents.component').then((m) => m.ModelesDocumentsComponent),
      },
    ],
  },

  { path: '**', redirectTo: 'dossiers' },
];
