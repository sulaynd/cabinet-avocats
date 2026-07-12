# Cabinet d'Avocats — Architecture

Application de gestion de cabinet d'avocats : dossiers clients, suivi, facturation, agenda/échéances, gestion documentaire.

## Stack technique

- **Backend** : Laravel 11 (API REST), Laravel Sanctum (auth SPA par token/cookie), MySQL/PostgreSQL, Laravel Storage (S3 ou disque local) pour les documents.
- **Frontend** : Angular 18+ (standalone components), Angular Router, HttpClient, RxJS, Angular Material (ou PrimeNG) pour l'UI.
- **Communication** : API JSON REST, authentification par token Sanctum (Bearer) — adapté pour un frontend Angular séparé du backend.

## Rôles utilisateurs

- `admin` : accès complet, gestion des utilisateurs et facturation.
- `avocat` : gère ses dossiers, clients, échéances, factures, documents.
- `assistant` : saisie et suivi (dossiers, échéances, documents), accès facturation limité en lecture.

La gestion fine des permissions peut être affinée avec un package comme `spatie/laravel-permission`.

## Modèle de données

```
users
 ├─ id, name, email, password, role (enum: admin, avocat, assistant), phone, timestamps

clients
 ├─ id
 ├─ type (enum: particulier, entreprise)
 ├─ nom, prenom            (si particulier)
 ├─ raison_sociale, siret  (si entreprise)
 ├─ email, telephone, adresse, code_postal, ville
 ├─ notes
 └─ timestamps

dossiers
 ├─ id
 ├─ reference (unique, ex: DOS-2026-0001)
 ├─ client_id        -> clients.id
 ├─ avocat_id        -> users.id (avocat responsable)
 ├─ assistant_id     -> users.id, nullable (assistant traitant, en plus de l'avocat)
 ├─ titre
 ├─ type_affaire (civil, pénal, commercial, famille, travail, immobilier, autre)
 ├─ statut (ouvert, en_cours, en_attente, clos, archive)
 ├─ mode_facturation (horaire, forfait)
 ├─ taux_horaire       (€/h propre au dossier ; sinon taux par défaut de l'intervenant)
 ├─ montant_forfait    (utilisé si mode_facturation = forfait)
 ├─ date_ouverture, date_cloture
 ├─ description
 └─ timestamps

temps_passes
 ├─ id
 ├─ dossier_id      -> dossiers.id
 ├─ user_id         -> users.id (qui a travaillé)
 ├─ description
 ├─ demarre_a, termine_a   (chrono en cours si termine_a est null)
 ├─ duree_secondes
 ├─ facturable (bool)
 ├─ taux_horaire_applique (snapshot au moment de la facturation)
 ├─ facture_id      -> factures.id, nullable (renseigné une fois facturé)
 └─ timestamps

cabinet_settings (singleton, une seule ligne)
 └─ ical_token_equipe   (jeton secret de l'agenda collectif iCal)

echeances
 ├─ id
 ├─ dossier_id      -> dossiers.id
 ├─ titre
 ├─ type (audience, delai_procedural, rdv_client, autre)
 ├─ date_heure
 ├─ lieu
 ├─ statut (a_venir, realisee, annulee)
 ├─ rappel_avant (minutes, pour notification)
 └─ timestamps

factures
 ├─ id
 ├─ numero (unique, ex: FAC-2026-0001)
 ├─ dossier_id      -> dossiers.id
 ├─ client_id       -> clients.id
 ├─ date_emission, date_echeance
 ├─ montant_ht, taux_tva, montant_ttc
 ├─ statut (brouillon, envoyee, payee, en_retard, annulee)
 └─ timestamps

facture_lignes
 ├─ id
 ├─ facture_id      -> factures.id
 ├─ description
 ├─ quantite, prix_unitaire, montant
 └─ timestamps

documents
 ├─ id
 ├─ dossier_id      -> dossiers.id
 ├─ nom_original
 ├─ chemin (storage path)
 ├─ type (contrat, piece_procedure, correspondance, autre)
 ├─ taille (octets)
 ├─ uploaded_by     -> users.id
 └─ timestamps
```

Relations clés :
- Un `client` a plusieurs `dossiers`.
- Un `dossier` a plusieurs `echeances`, plusieurs `documents`, plusieurs `factures`.
- Une `facture` a plusieurs `facture_lignes`.
- Un `avocat` (user) a plusieurs `dossiers`.

## Endpoints API (extrait)

```
POST   /api/login
POST   /api/logout
GET    /api/me

GET    /api/clients
POST   /api/clients
GET    /api/clients/{id}
PUT    /api/clients/{id}
DELETE /api/clients/{id}
GET    /api/clients/{id}/dossiers

GET    /api/dossiers
POST   /api/dossiers
GET    /api/dossiers/{id}
PUT    /api/dossiers/{id}
DELETE /api/dossiers/{id}
GET    /api/dossiers/{id}/echeances
GET    /api/dossiers/{id}/documents
GET    /api/dossiers/{id}/factures
```
Filtres disponibles sur `GET /api/dossiers` : `statut`, `avocat_id`, `client_id`, `search`,
et `traitant_id` (renvoie les dossiers où l'utilisateur est **avocat responsable OU
assistant traitant** — pratique pour un tableau de bord "mes dossiers").

```
GET    /api/echeances?from=&to=&dossier_id=&traitant_id=
POST   /api/echeances
PUT    /api/echeances/{id}
DELETE /api/echeances/{id}

GET    /api/factures
POST   /api/factures
GET    /api/factures/{id}
PUT    /api/factures/{id}
DELETE /api/factures/{id}
POST   /api/factures/{id}/marquer-payee

POST   /api/dossiers/{id}/documents   (upload multipart)
GET    /api/documents/{id}/telecharger
DELETE /api/documents/{id}

GET    /api/users                (réservé admin)
POST   /api/users                (réservé admin)
GET    /api/users/{id}            (réservé admin)
PUT    /api/users/{id}            (réservé admin)
DELETE /api/users/{id}            (réservé admin)

GET    /api/dossiers/{id}/temps              (temps passé sur le dossier)
POST   /api/dossiers/{id}/temps/demarrer     (démarre un chronomètre)
POST   /api/dossiers/{id}/temps              (entrée de temps manuelle)
GET    /api/temps/en-cours                   (chrono en cours de l'utilisateur connecté)
POST   /api/temps/{id}/arreter               (arrête un chronomètre)
PUT    /api/temps/{id}
DELETE /api/temps/{id}
POST   /api/dossiers/{id}/factures/generer-depuis-temps   (mémoire d'honoraires auto : forfait ou temps non facturé)

GET    /api/ical/mes-liens                   (URLs d'abonnement iCal : personnel + équipe si admin)
POST   /api/ical/regenerer-personnel
POST   /api/ical/regenerer-equipe            (réservé admin)
GET    /api/ical/perso/{token}.ics           (public — flux iCal individuel, pas de Sanctum)
GET    /api/ical/equipe/{token}.ics          (public — flux iCal collectif, pas de Sanctum)

GET    /api/dossiers/{id}/communications     (historique des échanges avec le client)
POST   /api/dossiers/{id}/communications
PUT    /api/communications/{id}
DELETE /api/communications/{id}

POST   /api/factures/{id}/envoyer            (envoi email + PDF joint, réservé admin/avocat)

GET    /api/public/avocats                   (public — liste des avocats pour le widget de RDV)
GET    /api/public/creneaux                  (public — créneaux disponibles d'un avocat)
POST   /api/public/rendez-vous               (public — réservation, crée le contact, envoie la confirmation)
GET    /api/rendez-vous                      (côté cabinet — demandes reçues)
POST   /api/rendez-vous/{id}/confirmer
POST   /api/rendez-vous/{id}/annuler

POST   /api/portail/connexion                (public — login portail client, guard séparé du cabinet)
GET    /api/portail/mes-dossiers             (portail — réservé aux tokens Client)
GET    /api/portail/dossiers/{id}
GET    /api/portail/mes-factures
GET    /api/portail/documents/{id}/telecharger
POST   /api/portail/documents/{id}/signer    (signature électronique "simple", voir CONFIGURATION.md)
POST   /api/clients/{id}/activer-portail     (côté cabinet — active l'accès portail d'un client)
POST   /api/documents/{id}/demander-signature
```

## Structure frontend Angular

```
src/app/
 ├─ core/
 │   ├─ models/        (interfaces TS : Client, Dossier, Echeance, Facture, DocumentFile)
 │   ├─ services/       (ClientService, DossierService, EcheanceService, FactureService, AuthService)
 │   └─ interceptors/   (AuthInterceptor : ajoute le Bearer token)
 ├─ features/
 │   ├─ clients/
 │   ├─ dossiers/       (dossier-list, dossier-form, dossier-detail)
 │   ├─ echeances/      (vue calendrier/agenda)
 │   └─ factures/
 └─ app.routes.ts
```

Le dossier `features/dossiers` est fourni en exemple complet (liste + formulaire) ; les autres modules (`clients`, `echeances`, `factures`) suivent exactement le même patron : un service HTTP dans `core/services`, un modèle dans `core/models`, et des composants standalone dans `features/<entite>`.

## Ce qui est livré dans ce paquet

- **Backend Laravel** : migrations, modèles Eloquent (avec relations), contrôleurs API (CRUD complet) et fichier de routes pour les 6 entités.
- **Frontend Angular** : modèles TS, services HTTP pour toutes les entités, intercepteur d'authentification, et un module complet « Dossiers » (liste + formulaire) servant de patron à dupliquer.

## Prochaines étapes suggérées

1. `bash install-backend.sh` — crée le projet Laravel, installe Sanctum/dompdf, copie les
   fichiers fournis, prépare `.env` (voir `backend/.env.example` et `backend/CONFIGURATION.md`).
2. Éditer `backend/.env` (DB + `ADMIN_PASSWORD`), puis `php artisan migrate --seed`
   (le seeder `AdminUserSeeder` crée le premier compte admin).
3. `bash install-frontend.sh` — crée le projet Angular (`ng new --standalone --routing`),
   copie les fichiers fournis (`core/`, `features/`, routes, styles), installe les dépendances.
4. Vérifier `frontend/src/environments/environment.ts` (`apiUrl` → backend Laravel), puis
   `ng serve` côté frontend et `php artisan serve` côté backend.
5. (Optionnel) Ajouter Angular Material (ou PrimeNG) pour affiner l'UI au-delà de la
   feuille de style fournie.
6. Dupliquer le patron `dossiers` pour toute nouvelle entité future.

Les deux scripts (`install-backend.sh`, `install-frontend.sh`) sont à la racine de
l'archive et gèrent eux-mêmes la collision de nom avec les dossiers `backend/`/`frontend/`
déjà fournis (ils les renomment en `*-fourni/` avant de créer le vrai projet).
