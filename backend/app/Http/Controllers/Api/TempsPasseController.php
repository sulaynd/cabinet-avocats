<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dossier;
use App\Models\TempsPasse;
use Illuminate\Http\Request;

class TempsPasseController extends Controller
{
    /** Liste des entrées de temps d'un dossier, les plus récentes en premier. */
    public function index(Request $request, Dossier $dossier)
    {
        abort_unless($request->user()->can('view', $dossier), 403, "Ce dossier ne vous est pas assigné.");

        return response()->json(
            $dossier->tempsPasses()->with('user')->orderByDesc('demarre_a')->get()
        );
    }

    /**
     * Démarre un chronomètre sur ce dossier pour l'utilisateur connecté.
     * Refuse s'il a déjà un chronomètre en cours ailleurs, pour éviter de
     * cumuler du temps sur deux dossiers en même temps.
     */
    public function demarrer(Request $request, Dossier $dossier)
    {
        abort_unless($request->user()->can('view', $dossier), 403, "Ce dossier ne vous est pas assigné.");
        abort_if($dossier->statut === 'clos', 422, 'Ce dossier est clos, impossible d\'y démarrer un chronomètre.');

        $data = $request->validate(['description' => 'nullable|string|max:255']);

        $dejaEnCours = TempsPasse::where('user_id', $request->user()->id)
            ->whereNull('termine_a')
            ->whereNotNull('demarre_a')
            ->first();

        if ($dejaEnCours) {
            return response()->json([
                'message' => 'Un chronomètre est déjà en cours sur un autre dossier. Arrêtez-le avant d\'en démarrer un nouveau.',
                'temps_en_cours' => $dejaEnCours->load('dossier'),
            ], 422);
        }

        $temps = TempsPasse::create([
            'dossier_id' => $dossier->id,
            'user_id' => $request->user()->id,
            'description' => $data['description'] ?? null,
            'demarre_a' => now(),
            'facturable' => true,
        ]);

        return response()->json($temps, 201);
    }

    /** Arrête un chronomètre en cours et calcule sa durée. */
    public function arreter(Request $request, TempsPasse $temps)
    {
        abort_unless($request->user()->can('view', $temps->dossier), 403, "Ce dossier ne vous est pas assigné.");

        if (! $temps->estEnCours()) {
            return response()->json(['message' => 'Ce chronomètre est déjà arrêté.'], 422);
        }

        $temps->arreter();

        return response()->json($temps);
    }

    /** Renvoie le chronomètre actuellement en cours pour l'utilisateur connecté (s'il y en a un). */
    public function enCours(Request $request)
    {
        $temps = TempsPasse::where('user_id', $request->user()->id)
            ->whereNull('termine_a')
            ->whereNotNull('demarre_a')
            ->with('dossier')
            ->first();

        // response()->json($temps) se comporte mal quand $temps est null (renvoie
        // "{}" au lieu de "null" avec cette version de Laravel/Symfony) — on
        // contourne explicitement ce cas plutôt que de laisser le frontend
        // interpréter à tort un objet vide comme "un chronomètre est en cours".
        if (!$temps) {
            return response('null', 200)->header('Content-Type', 'application/json');
        }

        return response()->json($temps);
    }

    /** Ajoute une entrée de temps manuelle (sans passer par le chronomètre). */
    public function store(Request $request, Dossier $dossier)
    {
        abort_unless($request->user()->can('view', $dossier), 403, "Ce dossier ne vous est pas assigné.");
        abort_if($dossier->statut === 'clos', 422, "Ce dossier est clos, impossible d'y ajouter du temps.");

        $data = $request->validate([
            'description' => 'nullable|string|max:255',
            'duree_minutes' => 'required|integer|min:1',
            'facturable' => 'boolean',
        ]);

        $temps = TempsPasse::create([
            'dossier_id' => $dossier->id,
            'user_id' => $request->user()->id,
            'description' => $data['description'] ?? null,
            'demarre_a' => now()->subMinutes($data['duree_minutes']),
            'termine_a' => now(),
            'duree_secondes' => $data['duree_minutes'] * 60,
            'facturable' => $data['facturable'] ?? true,
        ]);

        return response()->json($temps, 201);
    }

    public function update(Request $request, TempsPasse $temps)
    {
        abort_unless($request->user()->can('view', $temps->dossier), 403, "Ce dossier ne vous est pas assigné.");

        if ($temps->estFacture()) {
            return response()->json(['message' => 'Ce temps a déjà été facturé et ne peut plus être modifié.'], 422);
        }

        $data = $request->validate([
            'description' => 'nullable|string|max:255',
            'duree_minutes' => 'integer|min:1',
            'facturable' => 'boolean',
        ]);

        if (isset($data['duree_minutes'])) {
            $temps->duree_secondes = $data['duree_minutes'] * 60;
        }
        if (array_key_exists('description', $data)) {
            $temps->description = $data['description'];
        }
        if (array_key_exists('facturable', $data)) {
            $temps->facturable = $data['facturable'];
        }
        $temps->save();

        return response()->json($temps);
    }

    public function destroy(Request $request, TempsPasse $temps)
    {
        abort_unless($request->user()->can('view', $temps->dossier), 403, "Ce dossier ne vous est pas assigné.");

        if ($temps->estFacture()) {
            return response()->json(['message' => 'Ce temps a déjà été facturé et ne peut plus être supprimé.'], 422);
        }

        $temps->delete();

        return response()->json(null, 204);
    }
}
