<?php

use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\CabinetSettingController;
use App\Http\Controllers\Api\MotDePasseOublieController;
use App\Http\Controllers\Api\ActualiteController;
use App\Http\Controllers\Api\OffreEmploiController;
use App\Http\Controllers\Api\TemoignageController;
use App\Http\Controllers\Api\TableauDeBordController;
use App\Http\Controllers\Api\MembreEquipeController;
use App\Http\Controllers\Api\CommunicationController;
use App\Http\Controllers\Api\DebourseController;
use App\Http\Controllers\Api\IntervenantController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\DossierController;
use App\Http\Controllers\Api\EcheanceController;
use App\Http\Controllers\Api\FactureController;
use App\Http\Controllers\Api\IcalController;
use App\Http\Controllers\Api\PortailAuthController;
use App\Http\Controllers\Api\PortailController;
use App\Http\Controllers\Api\QuestionnaireController;
use App\Http\Controllers\Api\QuestionnairePublicController;
use App\Http\Controllers\Api\RendezVousController;
use App\Http\Controllers\Api\RendezVousPublicController;
use App\Http\Controllers\Api\ReponseQuestionnaireController;
use App\Http\Controllers\Api\TempsPasseController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Limité à 5 tentatives par minute par IP — protection contre la force brute
// sur les mots de passe, sans gêner un utilisateur légitime qui se trompe une
// ou deux fois.
Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (! Auth::attempt($credentials)) {
        return response()->json(['message' => 'Identifiants invalides'], 401);
    }

    $user = Auth::user();
    $token = $user->createToken('spa')->plainTextToken;

    return response()->json(['user' => $user, 'token' => $token]);
})->middleware(\App\Http\Middleware\ThrottlePublicRequests::class . ':5,1');

// Récupération de mot de passe côté cabinet — limité contre les abus (spam
// d'emails ou tentatives de deviner des adresses existantes).
Route::post('/mot-de-passe-oublie', [MotDePasseOublieController::class, 'demander'])->middleware(\App\Http\Middleware\ThrottlePublicRequests::class . ':5,1');
Route::post('/reinitialiser-mot-de-passe', [MotDePasseOublieController::class, 'reinitialiser'])->middleware(\App\Http\Middleware\ThrottlePublicRequests::class . ':5,1');

// Flux iCal publics : consommés par des logiciels d'agenda externes (Google Calendar,
// Outlook, Apple Calendar...) qui ne savent pas envoyer de token Bearer. La sécurité
// repose sur le caractère secret et régénérable du jeton présent dans l'URL elle-même.
Route::get('/ical/perso/{token}.ics', [IcalController::class, 'personnel']);
Route::get('/ical/equipe/{token}.ics', [IcalController::class, 'equipe']);

// Prise de rendez-vous en ligne : routes publiques consommées par le site vitrine.
// Lecture limitée large (le calendrier peut être rafraîchi souvent en navigant),
// la soumission elle-même est plus stricte pour éviter le spam de demandes.
Route::middleware(\App\Http\Middleware\ThrottlePublicRequests::class . ':30,1')->group(function () {
    Route::get('/public/avocats', [RendezVousPublicController::class, 'avocats']);
    Route::get('/public/creneaux', [RendezVousPublicController::class, 'creneauxDisponibles']);
});
Route::post('/public/rendez-vous', [RendezVousPublicController::class, 'reserver'])->middleware(\App\Http\Middleware\ThrottlePublicRequests::class . ':5,60');

// Connexion au portail client (guard séparé des comptes internes du cabinet).
Route::post('/portail/connexion', [PortailAuthController::class, 'login'])->middleware(\App\Http\Middleware\ThrottlePublicRequests::class . ':5,1');
Route::post('/portail/mot-de-passe-oublie', [PortailAuthController::class, 'demanderReinitialisation'])->middleware(\App\Http\Middleware\ThrottlePublicRequests::class . ':5,1');
Route::post('/portail/reinitialiser-mot-de-passe', [PortailAuthController::class, 'reinitialiser'])->middleware(\App\Http\Middleware\ThrottlePublicRequests::class . ':5,1');

// Questionnaire de pré-consultation : page publique ouverte depuis le lien reçu par email.
Route::middleware(\App\Http\Middleware\ThrottlePublicRequests::class . ':20,1')->group(function () {
    Route::get('/questionnaire/{token}', [QuestionnairePublicController::class, 'afficher']);
    Route::post('/questionnaire/{token}', [QuestionnairePublicController::class, 'soumettre']);
});

// Coordonnées du cabinet — public, utilisé par les pages de connexion (cabinet
// et portail) et le questionnaire public pour afficher le nom avant tout login.
Route::middleware(\App\Http\Middleware\ThrottlePublicRequests::class . ':60,1')->group(function () {
    Route::get('/parametres-cabinet/public', [CabinetSettingController::class, 'public']);
    Route::get('/membres-equipe/public', [MembreEquipeController::class, 'public']);
    Route::get('/temoignages/public', [TemoignageController::class, 'public']);
    Route::get('/offres-emploi/public', [OffreEmploiController::class, 'public']);
    Route::get('/actualites/public', [ActualiteController::class, 'public']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();

        return response()->json(null, 204);
    });

    Route::post('/changer-mot-de-passe', function (Request $request) {
        $data = $request->validate(['password' => ['required', 'min:8']]);

        $user = $request->user();
        $user->update(['password' => \Illuminate\Support\Facades\Hash::make($data['password']), 'doit_changer_mot_de_passe' => false]);

        return response()->json(['message' => 'Mot de passe mis à jour.']);
    });

    Route::get('/me', fn (Request $request) => response()->json($request->user()));

    Route::apiResource('clients', ClientController::class);
    Route::get('clients/{client}/dossiers', [ClientController::class, 'dossiers']);

    Route::apiResource('dossiers', DossierController::class);
    Route::get('dossiers/{dossier}/echeances', [DossierController::class, 'echeances']);
    Route::get('dossiers/{dossier}/documents', [DossierController::class, 'documents']);
    Route::get('dossiers/{dossier}/factures', [DossierController::class, 'factures']);
    Route::post('dossiers/{dossier}/documents', [DocumentController::class, 'store']);
    // Assignation/réassignation de l'avocat responsable et de l'assistant traitant — réservée admin.
    Route::post('dossiers/{dossier}/assigner', [DossierController::class, 'assigner'])->middleware('role:admin');

    // Onboarding client : questionnaires de pré-consultation.
    Route::apiResource('questionnaires', QuestionnaireController::class)->middleware('role:admin');
    Route::get('dossiers/{dossier}/reponses-questionnaires', [ReponseQuestionnaireController::class, 'index']);
    Route::post('dossiers/{dossier}/renvoyer-questionnaire', [ReponseQuestionnaireController::class, 'renvoyer']);

    Route::apiResource('echeances', EcheanceController::class);

    // Historique des communications (appels, emails, courriers, réunions, notes) par dossier.
    Route::get('dossiers/{dossier}/communications', [CommunicationController::class, 'index']);
    Route::post('dossiers/{dossier}/communications', [CommunicationController::class, 'store']);
    Route::put('communications/{communication}', [CommunicationController::class, 'update']);
    Route::delete('communications/{communication}', [CommunicationController::class, 'destroy']);

    // Carnet d'adresses partagé du cabinet (avocat adverse, expert...), réutilisable entre dossiers.
    Route::get('intervenants', [IntervenantController::class, 'index']);
    Route::post('intervenants', [IntervenantController::class, 'store']);
    Route::put('intervenants/{intervenant}', [IntervenantController::class, 'update']);
    Route::delete('intervenants/{intervenant}', [IntervenantController::class, 'destroy']);

    Route::get('dossiers/{dossier}/intervenants', [IntervenantController::class, 'pourDossier']);
    Route::post('dossiers/{dossier}/intervenants', [IntervenantController::class, 'creerEtLier']);
    Route::post('dossiers/{dossier}/intervenants/{intervenant}/lier', [IntervenantController::class, 'lier']);
    Route::delete('dossiers/{dossier}/intervenants/{intervenant}', [IntervenantController::class, 'delier']);

    // Chronométrage et temps passé sur un dossier.
    Route::get('dossiers/{dossier}/temps', [TempsPasseController::class, 'index']);
    Route::post('dossiers/{dossier}/temps/demarrer', [TempsPasseController::class, 'demarrer']);
    Route::post('dossiers/{dossier}/temps', [TempsPasseController::class, 'store']);
    Route::get('temps/en-cours', [TempsPasseController::class, 'enCours']);
    Route::post('temps/{temps}/arreter', [TempsPasseController::class, 'arreter']);
    Route::put('temps/{temps}', [TempsPasseController::class, 'update']);
    Route::delete('temps/{temps}', [TempsPasseController::class, 'destroy']);

    Route::get('dossiers/{dossier}/debourses', [DebourseController::class, 'index']);
    Route::post('dossiers/{dossier}/debourses', [DebourseController::class, 'store']);
    Route::put('debourses/{debourse}', [DebourseController::class, 'update']);
    Route::delete('debourses/{debourse}', [DebourseController::class, 'destroy']);

    // Liens d'abonnement iCal (agenda personnel + agenda collectif du cabinet).
    Route::get('ical/mes-liens', [IcalController::class, 'mesLiens']);
    Route::get('parametres-cabinet', [CabinetSettingController::class, 'show']);
    Route::get('tableau-de-bord', [TableauDeBordController::class, 'index']);
    Route::put('parametres-cabinet', [CabinetSettingController::class, 'update']);
    Route::post('parametres-cabinet/photo', [CabinetSettingController::class, 'televerserPhoto']);
    Route::post('ical/regenerer-personnel', [IcalController::class, 'regenererPersonnel']);
    Route::post('ical/regenerer-equipe', [IcalController::class, 'regenererEquipe'])->middleware('role:admin');

    Route::apiResource('factures', FactureController::class)->except(['destroy', 'store', 'update']);
    Route::middleware('role:admin,avocat')->group(function () {
        Route::post('factures', [FactureController::class, 'store']);
        Route::put('factures/{facture}', [FactureController::class, 'update']);
        Route::delete('factures/{facture}', [FactureController::class, 'destroy']);
    });
    Route::post('factures/{facture}/marquer-payee', [FactureController::class, 'marquerPayee']);
    Route::post('factures/{facture}/envoyer', [FactureController::class, 'envoyer'])->middleware('role:admin,avocat');
    Route::get('factures/{facture}/pdf', [FactureController::class, 'genererPdf']);
    Route::post('dossiers/{dossier}/factures/generer-depuis-temps', [FactureController::class, 'genererDepuisTemps'])
        ->middleware('role:admin,avocat');

    Route::get('documents/{document}/telecharger', [DocumentController::class, 'telecharger']);
    Route::delete('documents/{document}', [DocumentController::class, 'destroy']);
    Route::post('documents/{document}/demander-signature', [DocumentController::class, 'demanderSignature']);

    // Gestion des utilisateurs du cabinet (avocats, assistants, admins) — réservée aux admins.
    Route::apiResource('users', UserController::class)->middleware('role:admin');
    Route::post('users/{user}/photo', [UserController::class, 'televerserPhoto'])->middleware('role:admin');
    Route::apiResource('temoignages', TemoignageController::class)->only(['index', 'update', 'destroy']);
    Route::apiResource('offres-emploi', OffreEmploiController::class)->except(['show']);
    Route::apiResource('actualites', ActualiteController::class)->except(['show']);

    // Activation du portail client (déclenchée par le cabinet, pas par le client).
    Route::post('clients/{client}/activer-portail', [PortailAuthController::class, 'activerPourClient']);

    // Demandes de rendez-vous en ligne : gestion côté cabinet.
    Route::get('rendez-vous', [RendezVousController::class, 'index']);
    Route::post('rendez-vous/{rendezVous}/confirmer', [RendezVousController::class, 'confirmer']);
    Route::post('rendez-vous/{rendezVous}/annuler', [RendezVousController::class, 'annuler']);
});

// Espace portail client : mêmes tokens Sanctum, mais middleware "portail" dédié qui
// vérifie que le token appartient bien à un Client (jamais à un User du cabinet).
Route::middleware(['auth:sanctum', 'portail'])->prefix('portail')->group(function () {
    Route::post('/deconnexion', [PortailAuthController::class, 'logout']);
    Route::get('/moi', [PortailAuthController::class, 'moi']);
    Route::post('/changer-mot-de-passe', [PortailAuthController::class, 'changerMotDePasse']);
    Route::get('/mes-dossiers', [PortailController::class, 'mesDossiers']);
    Route::get('/dossiers/{dossierId}', [PortailController::class, 'monDossier']);
    Route::get('/mes-factures', [PortailController::class, 'mesFactures']);
    Route::get('/documents/{document}/telecharger', [PortailController::class, 'telechargerDocument']);
    Route::post('/documents/{document}/signer', [PortailController::class, 'signerDocument']);
    Route::post('/temoignage', [TemoignageController::class, 'soumettreDepuisPortail']);
    Route::get('/mon-temoignage', [TemoignageController::class, 'monTemoignage']);
});
