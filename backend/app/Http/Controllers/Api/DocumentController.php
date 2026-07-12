<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Dossier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DocumentController extends Controller
{
    public function store(Request $request, Dossier $dossier)
    {
        abort_unless($request->user()->can('view', $dossier), 403, "Ce dossier ne vous est pas assigné.");

        $request->validate([
            'fichier' => 'required|file|max:20480', // 20 Mo
            'type' => [Rule::in(['contrat', 'piece_procedure', 'correspondance', 'autre'])],
            'necessite_signature' => 'boolean',
        ]);

        $fichier = $request->file('fichier');
        $chemin = $fichier->store("dossiers/{$dossier->id}", 'local');

        $document = Document::create([
            'dossier_id' => $dossier->id,
            'nom_original' => $fichier->getClientOriginalName(),
            'chemin' => $chemin,
            'type' => $request->input('type', 'autre'),
            'taille' => $fichier->getSize(),
            'uploaded_by' => $request->user()->id,
            'necessite_signature' => $request->boolean('necessite_signature'),
        ]);

        return response()->json($document, 201);
    }

    public function telecharger(Request $request, Document $document)
    {
        abort_unless($request->user()->can('view', $document->dossier), 403, "Ce dossier ne vous est pas assigné.");

        return Storage::disk('local')->download($document->chemin, $document->nom_original);
    }

    /** Marque (ou démarque) un document comme nécessitant une signature du client via le portail. */
    public function demanderSignature(Request $request, Document $document)
    {
        abort_unless($request->user()->can('view', $document->dossier), 403, "Ce dossier ne vous est pas assigné.");

        $data = $request->validate(['necessite_signature' => 'required|boolean']);
        $document->update($data);

        return response()->json($document);
    }

    public function destroy(Request $request, Document $document)
    {
        abort_unless($request->user()->can('view', $document->dossier), 403, "Ce dossier ne vous est pas assigné.");

        Storage::disk('local')->delete($document->chemin);
        $document->delete();

        return response()->json(null, 204);
    }
}
