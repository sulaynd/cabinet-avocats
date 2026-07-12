<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PortailController extends Controller
{
    /** Dossiers du client connecté (jamais ceux d'un autre client). */
    public function mesDossiers(Request $request)
    {
        return response()->json(
            $request->user()->dossiers()->with('avocat')->orderByDesc('created_at')->get()
        );
    }

    public function monDossier(Request $request, int $dossierId)
    {
        $dossier = $request->user()->dossiers()
            ->with(['avocat', 'echeances', 'documents', 'factures', 'communications'])
            ->findOrFail($dossierId);

        return response()->json($dossier);
    }

    public function mesFactures(Request $request)
    {
        return response()->json(
            $request->user()->factures()->with('dossier')->orderByDesc('date_emission')->get()
        );
    }

    /** Téléchargement d'un document, uniquement s'il appartient à un dossier du client connecté. */
    public function telechargerDocument(Request $request, Document $document)
    {
        abort_unless($document->dossier->client_id === $request->user()->id, 403);

        return Storage::disk('local')->download($document->chemin, $document->nom_original);
    }

    /**
     * Signature électronique "simple" par le client : capture le nom saisi,
     * l'horodatage et l'adresse IP comme piste d'audit de consentement.
     * Ceci NE constitue PAS une signature électronique qualifiée au sens eIDAS
     * (pas de certificat, pas d'horodatage tiers de confiance) — suffisant pour
     * un accusé de réception ou un document à faible enjeu, mais pour des actes
     * nécessitant une valeur probante forte, intégrer un prestataire certifié
     * (Yousign, DocuSign, Universign...). Voir CONFIGURATION.md.
     */
    public function signerDocument(Request $request, Document $document)
    {
        abort_unless($document->dossier->client_id === $request->user()->id, 403);

        if (! $document->necessite_signature) {
            return response()->json(['message' => "Ce document ne nécessite pas de signature."], 422);
        }
        if ($document->estSigne()) {
            return response()->json(['message' => 'Ce document a déjà été signé.'], 422);
        }

        $data = $request->validate(['nom_signataire' => 'required|string|max:255']);

        $document->update([
            'signe_le' => now(),
            'signature_nom' => $data['nom_signataire'],
            'signature_ip' => $request->ip(),
        ]);

        return response()->json($document);
    }
}
