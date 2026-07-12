<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReponseQuestionnaire;
use Illuminate\Http\Request;

/**
 * Endpoints PUBLICS (pas d'authentification) consommés par la page que le
 * client ouvre depuis le lien reçu par email. Protégés uniquement par le
 * caractère secret du jeton (comme les flux iCal ou le portail).
 */
class QuestionnairePublicController extends Controller
{
    public function afficher(string $token)
    {
        $reponse = ReponseQuestionnaire::where('token', $token)->with('questionnaire', 'dossier')->firstOrFail();

        return response()->json([
            'dossier_titre' => $reponse->dossier->titre,
            'client_nom' => $reponse->dossier->client->nom_complet,
            'questionnaire' => $reponse->questionnaire,
            'deja_rempli' => $reponse->estRempli(),
            'reponses_existantes' => $reponse->reponses,
        ]);
    }

    public function soumettre(Request $request, string $token)
    {
        $reponse = ReponseQuestionnaire::where('token', $token)->firstOrFail();

        if ($reponse->estRempli()) {
            return response()->json(['message' => 'Ce questionnaire a déjà été complété.'], 422);
        }

        $data = $request->validate(['reponses' => 'required|array']);

        $reponse->update(['reponses' => $data['reponses'], 'rempli_le' => now()]);

        return response()->json(['message' => 'Merci, vos réponses ont bien été enregistrées.']);
    }
}
