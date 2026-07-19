<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Dossier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Espace du portail collaborateur : accès strictement limité aux dossiers
 * auxquels le collaborateur est explicitement lié, et aux seuls documents
 * marqués "partage_externe" (jamais l'ensemble du dossier) — voir
 * Document::partage_externe pour le principe d'opt-in explicite.
 */
class CollaborateurPortailController extends Controller
{
    public function dossiers(Request $request)
    {
        $dossiers = $request->user()->dossiers()->with('client')->orderByDesc('id')->get();

        return response()->json($dossiers);
    }

    public function documents(Request $request, Dossier $dossier)
    {
        $this->verifierAcces($request, $dossier);

        return response()->json(
            $dossier->documents()->where('partage_externe', true)->orderByDesc('created_at')->get()
        );
    }

    public function televerser(Request $request, Dossier $dossier)
    {
        $this->verifierAcces($request, $dossier);

        $request->validate([
            'fichier' => 'required|file|max:20480',
            'type' => [Rule::in(['contrat', 'piece_procedure', 'correspondance', 'autre'])],
        ]);

        $fichier = $request->file('fichier');
        $chemin = $fichier->store("dossiers/{$dossier->id}", 'local');

        $document = Document::create([
            'dossier_id' => $dossier->id,
            'nom_original' => $fichier->getClientOriginalName(),
            'chemin' => $chemin,
            'type' => $request->input('type', 'autre'),
            'taille' => $fichier->getSize(),
            'collaborateur_externe_id' => $request->user()->id,
            'partage_externe' => true,
        ]);

        return response()->json($document, 201);
    }

    public function telecharger(Request $request, Document $document)
    {
        $this->verifierAcces($request, $document->dossier);
        abort_unless($document->partage_externe, 403, "Ce document n'est pas partagé avec vous.");

        return Storage::disk('local')->download($document->chemin, $document->nom_original);
    }

    private function verifierAcces(Request $request, Dossier $dossier): void
    {
        $lie = $request->user()->dossiers()->where('dossiers.id', $dossier->id)->exists();
        abort_unless($lie, 403, "Ce dossier ne vous est pas accessible.");
    }
}
