# Configuration complémentaire — Auth, rôles, PDF

## 0. ⚠️ Indispensable : activer routes/api.php et la table Sanctum

Un projet Laravel 11 neuf (`composer create-project laravel/laravel`) ne charge PAS
`routes/api.php` par défaut — sans cette étape, **toutes** les routes `/api/*` renvoient
404, y compris `/api/login`. Dans `bootstrap/app.php`, ajoutez la ligne `api:` :

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',   // ← à ajouter
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
)
```

Ensuite, Sanctum (utilisé pour générer les tokens de connexion) a besoin d'une table
`personal_access_tokens`, absente de nos migrations fournies (elle appartient au package
Sanctum lui-même) :

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --tag="sanctum-migrations"
php artisan migrate
```

Sans ces deux étapes, la connexion échoue soit en 404 (routes non chargées), soit en 500
au moment de générer le token (table absente).

## 1. Enregistrer le middleware `role`

Dans `bootstrap/app.php` (Laravel 11), ajouter l'alias dans `withMiddleware` :

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => \App\Http\Middleware\EnsureRole::class,
        'portail' => \App\Http\Middleware\EnsurePortailClient::class,
    ]);
})
```

## 2. Installer la génération de PDF

```bash
composer require barryvdh/laravel-dompdf
```

Rien d'autre à configurer : le contrôleur `FactureController::genererPdf()` et la vue
`resources/views/pdf/facture.blade.php` sont déjà prêts. Le PDF utilise la police
`DejaVu Sans` (incluse avec dompdf) pour un rendu correct des accents français.

## 3. Sanctum (API token pour Angular)

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

Dans `.env`, si le frontend Angular tourne sur un port différent (ex. `localhost:4200`) :

```
SANCTUM_STATEFUL_DOMAINS=localhost:4200
SESSION_DOMAIN=localhost
```

Ici l'authentification utilisée côté Angular est le **token Bearer classique**
(`Authorization: Bearer <token>`, stocké dans `localStorage` par `AuthService`),
plus simple à mettre en place qu'un flux SPA à cookies pour un premier déploiement.

## 4. Résumé des permissions par rôle

| Action                                          | admin | avocat | assistant |
|--------------------------------------------------|:-----:|:------:|:---------:|
| Voir/gérer les dossiers **qui lui sont assignés** |  ✅ (tous) |  ✅ (les siens) |  ✅ (les siens) |
| Voir les factures des dossiers assignés          |  ✅   |   ✅   |    ✅     |
| Créer / modifier une facture                     |  ✅   |   ✅   |    ❌     |
| Supprimer une facture                            |  ✅   |   ✅   |    ❌     |
| **Assigner/réassigner un dossier** (avocat/assistant) |  ✅   |   ❌   |    ❌     |
| Supprimer un dossier                             |  ✅   |   ❌   |    ❌     |
| Gérer les utilisateurs du cabinet                |  ✅   |   ❌   |    ❌     |
| Gérer les modèles de questionnaires d'accueil    |  ✅   |   ❌   |    ❌     |

Ce tableau est appliqué :
- **côté Angular** : via `roleGuard` sur les routes et `*appHasRole` dans les templates (confort d'UI, évite d'afficher des actions interdites) ;
- **côté Laravel** : via le middleware `role:...` sur les routes sensibles, ET via `DossierPolicy` + `Dossier::scopeVisiblePar()` pour le filtrage par assignation (sécurité réelle — ne jamais faire confiance au frontend seul).

Pour étendre la restriction création/modification de facture à `avocat` uniquement (pas `assistant`), il suffit d'ajouter le même middleware sur les routes `POST`/`PUT` de `factures`, sur le modèle de la route `DELETE` déjà protégée.

## 4bis. Accès restreint aux dossiers assignés (avocat/assistant)

Un avocat ou un assistant ne voit et ne peut agir QUE sur les dossiers dont il est
`avocat_id` ou `assistant_id` — jamais sur les dossiers d'un collègue. C'est appliqué à
deux niveaux complémentaires :

1. **Listes** (`GET /dossiers`, `/factures`, agenda...) : `Dossier::scopeVisiblePar()`
   filtre la requête SQL directement — un avocat non assigné ne reçoit même pas la ligne.
2. **Accès direct par ID** (`GET /dossiers/{id}`, `/factures/{id}`, upload de document,
   démarrage de chrono, etc.) : `DossierPolicy` (`view`/`update`/`delete`) vérifie
   explicitement `$user->estTraitantDe($dossier)` avant de répondre — empêche qu'un
   avocat devine l'ID d'un dossier qui ne lui appartient pas et y accède quand même.

Un **admin** n'est jamais concerné par ces restrictions (accès total).

**Assignation** : seul un admin peut définir/modifier `avocat_id`/`assistant_id` d'un
dossier existant — via l'endpoint dédié `POST /dossiers/{id}/assigner`, ou via `PUT
/dossiers/{id}` (les deux champs y sont silencieusement ignorés si l'appelant n'est pas
admin). À la **création** d'un dossier, un non-admin est automatiquement ajouté comme
avocat responsable (rôle avocat) ou assistant traitant (rôle assistant) du dossier qu'il
ouvre — il ne peut pas créer un dossier assigné uniquement à des tiers.

La policy Laravel est découverte automatiquement par convention de nommage
(`App\Models\Dossier` → `App\Policies\DossierPolicy`). Si ce n'est pas le cas dans votre
version, l'enregistrer explicitement :

```php
// bootstrap/app.php ou un service provider
use Illuminate\Support\Facades\Gate;

Gate::policy(\App\Models\Dossier::class, \App\Policies\DossierPolicy::class);
```

## 5. Feuille de style globale

Un fichier `frontend/src/styles.css` est fourni avec l'ensemble des classes utilisées par
tous les composants (tableaux, formulaires, badges de statut, grille de calendrier de
l'agenda, etc.). Après `ng new`, remplacez le `src/styles.css` généré par celui-ci — il
est déjà référencé par défaut dans `angular.json` via `"styles": ["src/styles.css"]`.

## 6. Lien dossier ↔ traitant (avocat / assistant)

Chaque dossier a un **avocat responsable** (`avocat_id`, obligatoire) et, en option, un
**assistant traitant** (`assistant_id`). Les deux sont sélectionnables dans le formulaire
dossier, filtrés par rôle (`avocat` / `assistant`) via `GET /api/users?role=...`.

Le paramètre `traitant_id` (sur `GET /api/dossiers` et `GET /api/echeances`) renvoie tout
ce qui concerne un utilisateur donné, qu'il soit avocat responsable ou assistant — c'est
ce que la vue Agenda utilise pour son filtre "avocat / assistant".

## 7. Temps passé et facturation automatisée

Chaque dossier a un `mode_facturation` (`horaire` ou `forfait`) :
- **Horaire** : les intervenants chronomètrent leur temps (`POST /temps/demarrer` puis
  `POST /temps/{id}/arreter`), ou saisissent une durée manuelle. Le bouton "Générer une
  facture" (`POST /dossiers/{id}/factures/generer-depuis-temps`) regroupe tout le temps
  facturable pas encore facturé, une ligne par intervenant, au taux du dossier ou à défaut
  au `taux_horaire_defaut` de l'utilisateur.
- **Forfait** : le même bouton crée directement une ligne unique au `montant_forfait` du dossier.

Un seul chronomètre à la fois par utilisateur (toutes dossiers confondus) : en démarrer un
second renvoie une erreur 422 explicite tant que le premier n'est pas arrêté.

## 8. Synchronisation des agendas (iCal)

Chaque utilisateur dispose d'une URL d'abonnement iCal personnelle (`ical_token` sur
`users`), régénérable à tout moment (`POST /ical/regenerer-personnel`). Un admin dispose
en plus d'une URL d'agenda **collectif** pour tout le cabinet (`cabinet_settings.ical_token_equipe`).

Ces deux routes (`GET /ical/perso/{token}.ics` et `GET /ical/equipe/{token}.ics`) sont
**volontairement hors du groupe `auth:sanctum`** dans `routes/api.php` : les logiciels
d'agenda externes (Google Calendar, Outlook, Apple Calendar) ne savent pas envoyer de
token Bearer — la sécurité repose sur le caractère secret de l'URL elle-même, exactement
comme les liens d'agenda partagé de Google ou Outlook. Si un lien fuite, il suffit de le
régénérer pour couper l'accès immédiatement.

## 9. Envoi d'emails (factures, confirmations de RDV)

Configurer un vrai transporteur SMTP dans `.env` (sinon les emails partent dans les logs en local) :

```
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=contact@jca.ca
MAIL_FROM_NAME="JCA"
```

`FactureMail` joint le PDF généré à la volée (dompdf) ; `ConfirmationRendezVousMail`
confirme une demande de rendez-vous en ligne. Les deux passent par la file d'attente
Laravel (`Queueable`) — lancer un worker (`php artisan queue:work`) en production pour
ne pas bloquer la requête HTTP pendant l'envoi.

## 10. Facturation périodique / à la clôture

- **À la clôture** : activer `facturer_a_cloture` sur un dossier. Dès qu'il passe au
  statut `clos`, une facture est générée (forfait ou temps non facturé) et **envoyée
  automatiquement** par email au client.
- **Périodique** : activer `facturation_periodique` + `frequence_facturation`
  (`hebdomadaire`/`mensuelle`). La commande `php artisan factures:generer-periodiques`
  parcourt les dossiers concernés et facture tout temps non facturé dont la période
  est écoulée. À planifier quotidiennement dans le scheduler Laravel :

```php
// routes/console.php (Laravel 11)
use Illuminate\Support\Facades\Schedule;

Schedule::command('factures:generer-periodiques')->dailyAt('07:00');
```

(Nécessite `php artisan schedule:work` en développement, ou une entrée cron
`* * * * * php artisan schedule:run` en production.)

## 11. Portail client sécurisé

Le modèle `Client` peut s'authentifier lui-même via Sanctum (`POST /api/portail/connexion`),
**totalement indépendamment** des comptes internes (`users`) du cabinet — deux tables,
deux guards logiques. Le middleware `portail` vérifie explicitement que le token
authentifié appartient à un `Client`, pour qu'un token client ne puisse jamais accéder
aux routes internes (et inversement).

Un client n'a par défaut **aucun accès** : le cabinet doit explicitement "activer" le
portail via `POST /api/clients/{id}/activer-portail` (définit un mot de passe). Dans
cette version simplifiée le mot de passe est transmis directement dans la requête ; en
production, préférer l'envoi d'un lien d'activation à usage unique par email plutôt
qu'un mot de passe en clair choisi par le cabinet.

Le client connecté ne voit que ses propres dossiers, documents et factures
(`GET /api/portail/mes-dossiers`, etc.) — jamais ceux d'un autre client.

## 12. Signature électronique — ⚠️ portée volontairement limitée

L'endpoint `POST /api/portail/documents/{id}/signer` enregistre le nom saisi par le
client, l'horodatage serveur et son adresse IP comme **piste d'audit de consentement**.

**Ce n'est pas une signature électronique qualifiée au sens du règlement eIDAS** : pas de
certificat d'identité, pas d'horodatage par un tiers de confiance, pas de scellement
cryptographique du document. C'est suffisant pour un accusé de réception ou un document à
faible enjeu, mais **insuffisant pour donner une pleine valeur probante à un acte
juridique important** (mandat, contrat d'honoraires engageant, etc.).

Pour une signature à valeur probante forte, intégrer un prestataire de services de
confiance certifié eIDAS (par ex. **Yousign**, **DocuSign**, **Universign** — tous ont des
API REST) plutôt que ce mécanisme interne. Le point d'intégration naturel est la table
`documents` : ajouter un `signature_provider_id` renvoyé par le prestataire, et un webhook
qui met à jour `signe_le` quand le prestataire notifie la signature effective.

## 13. Prise de rendez-vous en ligne

Routes publiques (pas d'auth) pour un widget de prise de RDV sur le site vitrine :

- `GET /api/public/creneaux?avocat_id=&date_debut=&date_fin=` : créneaux libres
  (horaires simplifiés 9h-12h30/14h-18h, pas de 30 min, jours ouvrés uniquement —
  à adapter aux disponibilités réelles de chaque avocat si besoin).
- `POST /api/public/rendez-vous` : crée automatiquement la fiche client (par email, via
  `firstOrCreate`) si elle n'existe pas encore, enregistre la demande, envoie un email
  de confirmation.

Côté cabinet, `GET /api/rendez-vous` liste les demandes reçues (statut `demande`), à
confirmer ou annuler manuellement — la demande n'est volontairement **pas** auto-confirmée
ni auto-transformée en dossier, pour garder un point de contrôle humain avant d'engager
le cabinet sur un rendez-vous.

## 14. Intégration client (onboarding) — questionnaire de pré-consultation automatique

Un admin définit un ou plusieurs **modèles de questionnaire** (`Questionnaire`, champ
`champs` en JSON : liste de questions avec type `texte`/`zone_texte`/`choix`/`case`),
chacun optionnellement ciblé sur un `type_affaire` (civil, pénal...) ou "par défaut" si
`type_affaire` est `null`.

Dès qu'un dossier est créé (`DossierController::store`), `EnvoiQuestionnaireAccueil`
sélectionne automatiquement le questionnaire applicable (`Questionnaire::pourTypeAffaire()`
— priorité au questionnaire ciblant ce type d'affaire, sinon repli sur le questionnaire
par défaut), crée une `ReponseQuestionnaire` avec un jeton secret, et envoie au client un
email contenant le lien public `/questionnaire/{token}` (page **sans authentification**,
comme les autres liens à jeton du projet). Ce comportement peut être désactivé au cas par
cas en envoyant `envoyer_questionnaire_accueil: false` à la création du dossier.

Le lien pointe vers le frontend Angular (variable `frontend_url`, à ajouter dans
`config/app.php` / `.env` — sinon repli sur `APP_URL`) :

```
// .env
FRONTEND_URL=https://cabinet-lambert.fr

// config/app.php
'frontend_url' => env('FRONTEND_URL', env('APP_URL')),
```

Le cabinet peut consulter les réponses (`GET /dossiers/{id}/reponses-questionnaires`,
même règle d'accès que le dossier) ou renvoyer manuellement le lien
(`POST /dossiers/{id}/renvoyer-questionnaire`, ex. si le client l'a perdu).

## 15. Premier compte admin (seeder)

Après `php artisan migrate`, aucun compte n'existe encore — impossible de se connecter.
`AdminUserSeeder` crée le premier compte admin, à partir des variables `.env` :

```
ADMIN_NAME="Administrateur du cabinet"
ADMIN_EMAIL=admin@jca.ca
ADMIN_PASSWORD=changez-moi-immediatement    # ⚠️ à changer avant tout usage réel
```

```bash
php artisan migrate --seed
# ou, si les tables existent déjà :
php artisan db:seed --class=AdminUserSeeder
```

Le seeder utilise `updateOrCreate()` : le relancer est sans danger (met à jour le même
compte au lieu d'en créer un doublon) — pratique pour réinitialiser le mot de passe admin
en local si besoin. Une fois connecté avec ce compte, créez les avocats/assistants du
cabinet directement depuis l'écran **Utilisateurs** de l'application (aucun autre seeder
n'est nécessaire).

## 16. AuthGuard renforcé (session expirée)

L'`AuthGuard` (`core/guards/auth.guard.ts`) protège déjà toutes les routes authentifiées
depuis le début du projet. Il est complété par `unauthorizedInterceptor`
(`core/interceptors/unauthorized.interceptor.ts`) : si l'API renvoie 401 en cours
d'utilisation (token expiré ou révoqué côté serveur), la session locale est nettoyée et
l'utilisateur est renvoyé vers `/connexion?session_expiree=1` immédiatement — l'AuthGuard
ne vérifiant qu'au moment de la navigation, sans cet intercepteur une session expirée en
plein milieu d'un écran laisserait l'interface dans un état incohérent (requêtes qui
échouent silencieusement). Cet intercepteur ignore volontairement les routes `/portail/`
et `/public/`, qui ont leur propre logique (ou aucune session à expirer).

## 17. Angular Material

`install-frontend.sh` installe Angular Material (`ng add @angular/material`, thème
personnalisé, typographie globale et animations activées) puis ajoute par-dessus
`src/theme-overrides.scss`, qui reteinte les composants Material aux couleurs du cabinet
(encre `#1B2430` / laiton `#A8802E`) via les variables CSS "system tokens"
(`--mat-sys-*`) — ces variables sont de simples propriétés CSS, donc elles s'appliquent
sans dépendre de la version exacte de l'API Sass que `ng add` a générée dans
`styles.scss` (thème M3 via `mat.theme()`, ou legacy M2 via `mat.define-theme()`).

**Si votre version de Material est antérieure aux system tokens** (peu probable avec un
`ng add` récent), remplacez plutôt directement la palette dans le bloc généré par `ng add`
en haut de `styles.scss`, avec l'équivalent M2 :

```scss
@use '@angular/material' as mat;

$cabinet-primary: mat.define-palette((
  50: #f7f5ef, 100: #efe2c4, 200: #d9c088, 300: #c2a057,
  400: #b08d3e, 500: #a8802e, 600: #8f6b26, 700: #6f531e,
  800: #4f3b16, 900: #241c08, contrast: (500: white, 900: white),
));
$cabinet-theme: mat.define-light-theme((
  color: (primary: $cabinet-primary, accent: $cabinet-primary),
));
@include mat.all-component-themes($cabinet-theme);
```

### Composants déjà convertis (référence à dupliquer)

`dossier-list` (table, champs de recherche/filtre, pagination) et `dossier-form`
(champs, select, datepicker, checkbox) sont convertis à Material et servent de patron.
Deux briques réutilisables évitent de réinjecter `MatSnackBar`/`MatDialog` partout :

- **`NotificationService`** (`core/services/notification.service.ts`) — remplace `alert()` :
  `this.notification.succes('...')` / `.erreur('...')` / `.info('...')`.
- **`ConfirmService`** (`core/services/confirm.service.ts`) — remplace `confirm()` :
  ```ts
  this.confirmService.demander({ titre: '...', message: '...', destructif: true })
    .subscribe(confirme => { if (confirme) { /* ... */ } });
  ```

### Recette pour convertir un écran restant (clients, échéances, factures, utilisateurs, questionnaires)

1. Importer dans le composant : `MatFormFieldModule`, `MatInputModule`, et selon le cas
   `MatSelectModule`, `MatButtonModule`, `MatIconModule`, `MatTableModule`,
   `MatCheckboxModule`, `MatDatepickerModule` + `MatNativeDateModule`.
2. `<input>`/`<select>`/`<textarea>` → les envelopper dans `<mat-form-field appearance="outline">`
   avec un `<mat-label>` ; `<select><option>` devient `<mat-select><mat-option>`.
3. `<table>` HTML brut → `<table mat-table [dataSource]="...">` avec un `matColumnDef` par
   colonne (voir `dossier-list.component.html`) ; remplacer la pagination "maison" par
   `<mat-paginator>`.
4. Remplacer chaque `confirm(...)` par `ConfirmService.demander(...)`, chaque `alert(...)`
   par `NotificationService.erreur(...)`.
5. Boutons : `<button class="btn-primaire">` → `<button mat-raised-button color="primary">` ;
   actions secondaires → `mat-icon-button` avec une `<mat-icon>` (police Material Icons,
   voir note dans `install-frontend.sh`).

Les anciennes classes CSS "maison" (`.btn-primaire`, `.badge`, etc., dans `styles.scss`)
restent utilisées par les écrans pas encore convertis — aucune urgence à tout migrer d'un
coup, les deux styles coexistent sans conflit pendant la transition.
