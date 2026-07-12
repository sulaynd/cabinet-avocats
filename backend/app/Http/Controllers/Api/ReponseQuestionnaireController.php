<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dossier;
use App\Services\EnvoiQuestionnaireAccueil;
use Illuminate\Http\Request;

class ReponseQuestionnaireController extends Controller
{
    /** Réponses de questionnaire d'un dossier — mêmes règles d'accès qu'au dossier lui-même. */
    public function index(Request $request, Dossier $dossier)
    {
        abort_unless($request->user()->can('view', $dossier), 403, "Ce dossier ne vous est pas assigné.");

        return response()->json($dossier->reponsesQuestionnaires()->with('questionnaire')->get());
    }

    /** Renvoie manuellement le questionnaire (ex. si le client a perdu l'email). */
    public function renvoyer(Request $request, Dossier $dossier)
    {
        abort_unless($request->user()->can('view', $dossier), 403, "Ce dossier ne vous est pas assigné.");

        $reponse = EnvoiQuestionnaireAccueil::pourDossier($dossier);

        if (! $reponse) {
            return response()->json(['message' => "Aucun questionnaire actif pour ce type d'affaire, ou client sans email."], 422);
        }

        return response()->json($reponse);
    }
}
