# Cabinet d'Avocats — JCA (Juristyle Conseil & Accompagnement)

Application de gestion complète pour cabinet d'avocats : dossiers, clients, facturation, agenda, temps passé, documents, portail client et site vitrine public.

## Stack technique

- **Backend** : Laravel 13 (PHP 8.4+), Laravel Sanctum (authentification API), MySQL (SQLite en mémoire pour les tests)
- **Frontend** : Angular 20, Angular Material, locale `fr-CA`
- **CI/CD** : GitHub Actions (tests backend + frontend, build de production à chaque push)

## Fonctionnalités principales

- **Gestion des dossiers** : ouverture, suivi, statuts, assignation (avocat responsable + assistant et/ou stagiaire traitants simultanément)
- **Clients** : particuliers et entreprises, portail client en libre-service (dossiers, factures, documents, signature électronique)
- **Facturation** : horaire ou forfaitaire, calcul automatique TPS/TVQ (Québec), génération de factures depuis le temps passé, facturation périodique et à la clôture automatiques, export PDF
- **Temps passé** : chronomètre en direct ou saisie manuelle, lié à la facturation
- **Agenda / échéances** : audiences, délais procéduraux, RDV client, synchronisation iCal (personnelle et d'équipe), rappels automatiques par email
- **Documents** : téléversement, demande et suivi de signature électronique
- **Communications** : journal des échanges avec le client par dossier (appels, courriels, réunions, notes)
- **Questionnaires de pré-consultation** : envoyés par email avec lien à jeton, remplis par le client avant le premier rendez-vous
- **Prise de rendez-vous en ligne publique** : calendrier des disponibilités, confirmation avec montant et lien de visioconférence
- **Site vitrine public** : équipe, témoignages clients, offres d'emploi (carrières), actualités — tous gérables depuis l'admin
- **Tableau de bord** : chiffre d'affaires, factures impayées, répartition des dossiers, échéances à venir
- **Comptes** : rôles admin / avocat / assistant / stagiaire avec permissions différenciées ; récupération de mot de passe en libre-service (cabinet et portail client)

## Prérequis

- PHP 8.4 ou supérieur, Composer
- Node.js 20 (LTS), npm
- MySQL 8+
- (Optionnel) Un compte Mailtrap ou équivalent pour tester les emails en développement

## Installation — Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Configurer `.env` avec vos identifiants MySQL (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) et SMTP (`MAIL_*`), puis :

```bash
php artisan migrate
php artisan db:seed --class=AdminUserSeeder   # crée le premier compte admin
php artisan serve
```

L'API est alors disponible sur `http://localhost:8000`.

### Variables d'environnement clés

| Variable | Description |
|---|---|
| `DB_*` | Connexion MySQL |
| `MAIL_*` | Serveur SMTP (emails transactionnels) |
| `FRONTEND_URL` | URL du frontend, utilisée dans les liens des emails (ex: réinitialisation de mot de passe) |
| `ADMIN_EMAIL` | Email du premier compte admin créé par le seeder |

### Rappels automatiques (production)

Les rappels d'échéances nécessitent le planificateur de tâches Laravel. Ajouter cette ligne cron sur le serveur :

```
* * * * * cd /chemin/vers/backend && php artisan schedule:run >> /dev/null 2>&1
```

## Installation — Frontend

```bash
cd frontend
npm install
ng serve
```

L'application est alors disponible sur `http://localhost:4200`.

## Tests

**Backend** (123 tests) :
```bash
cd backend
php artisan test
```

**Frontend** (9 tests) :
```bash
cd frontend
npm test -- --no-watch --browsers=ChromeHeadless
```

## Build de production

```bash
cd frontend
npm run build -- --configuration=production
```

## CI/CD

Chaque push sur `main` déclenche automatiquement, via GitHub Actions :
- L'exécution de la suite de tests backend (PHP 8.4, SQLite en mémoire)
- L'exécution de la suite de tests frontend (Chrome Headless)
- Un build de production du frontend

Voir `.github/workflows/ci.yml`.

## Structure du projet

```
backend/    Laravel — API REST, migrations, tests
frontend/   Angular — interface cabinet, portail client, site vitrine public
```

## Comptes de rôles

| Rôle | Portée |
|---|---|
| **admin** | Accès complet, gestion des utilisateurs, paramètres du cabinet, contenu public |
| **avocat** | Dossiers dont il est responsable, facturation complète |
| **assistant** | Dossiers assignés, facturation complète sauf création/envoi/suppression de facture |
| **stagiaire** | Dossiers assignés en lecture/écriture limitée (pas de clôture, pas de facturation, pas de communications client, pas de suppression de documents/échéances) |
