<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Questionnaire;
use Illuminate\Http\Request;

/** Gestion (réservée admin) des modèles de questionnaires d'accueil envoyés automatiquement. */
class QuestionnaireController extends Controller
{
    public function index()
    {
        return response()->json(Questionnaire::orderBy('nom')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type_affaire' => 'nullable|string|max:50',
            'champs' => 'required|array|min:1',
            'champs.*.cle' => 'required|string|max:100',
            'champs.*.label' => 'required|string|max:255',
            'champs.*.type' => ['required', 'in:texte,zone_texte,choix,case'],
            'champs.*.options' => 'nullable|array',
            'champs.*.requis' => 'boolean',
            'actif' => 'boolean',
        ]);

        return response()->json(Questionnaire::create($data), 201);
    }

    public function show(Questionnaire $questionnaire)
    {
        return response()->json($questionnaire);
    }

    public function update(Request $request, Questionnaire $questionnaire)
    {
        $data = $request->validate([
            'nom' => 'string|max:255',
            'description' => 'nullable|string',
            'type_affaire' => 'nullable|string|max:50',
            'champs' => 'array|min:1',
            'actif' => 'boolean',
        ]);

        $questionnaire->update($data);

        return response()->json($questionnaire);
    }

    public function destroy(Questionnaire $questionnaire)
    {
        $questionnaire->delete();

        return response()->json(null, 204);
    }
}
