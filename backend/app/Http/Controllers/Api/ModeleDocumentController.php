<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ModeleDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ModeleDocumentController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403, 'Réservé aux administrateurs.');

        return response()->json(ModeleDocument::orderBy('nom')->get());
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403, 'Réservé aux administrateurs.');

        $data = $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type_affaire' => ['nullable', Rule::in([
                'immigration_mobilite', 'recrutement_international', 'cooperation_internationale',
                'developpement_international', 'action_humanitaire', 'conseils_strategiques', 'autre',
            ])],
            'fichier' => 'required|file|mimes:docx|max:5120', // 5 Mo
        ]);

        $fichier = $request->file('fichier');
        $chemin = $fichier->store('modeles-documents', 'local');

        $modele = ModeleDocument::create([
            'nom' => $data['nom'],
            'description' => $data['description'] ?? null,
            'type_affaire' => $data['type_affaire'] ?? null,
            'fichier_chemin' => $chemin,
            'nom_original' => $fichier->getClientOriginalName(),
        ]);

        return response()->json($modele, 201);
    }

    public function destroy(Request $request, ModeleDocument $modeleDocument)
    {
        abort_unless($request->user()->role === 'admin', 403, 'Réservé aux administrateurs.');

        Storage::disk('local')->delete($modeleDocument->fichier_chemin);
        $modeleDocument->delete();

        return response()->json(null, 204);
    }

    /** Modèles proposés pour un dossier précis (les siens + les génériques),
     * accessible à quiconque a accès au dossier — pas seulement l'admin. */
    public function pourDossier(Request $request, \App\Models\Dossier $dossier)
    {
        abort_unless($request->user()->can('view', $dossier), 403, "Ce dossier ne vous est pas assigné.");

        return response()->json(ModeleDocument::pourTypeAffaire($dossier->type_affaire));
    }
}
