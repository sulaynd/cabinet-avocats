<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dossier;
use App\Models\Intervenant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IntervenantController extends Controller
{
    /** Répertoire complet du cabinet (tous les intervenants existants), pour
     * la recherche lors de la liaison à un dossier. */
    public function index(Request $request)
    {
        $intervenants = Intervenant::query()
            ->when($request->recherche, fn ($q, $recherche) => $q->where('nom', 'like', "%{$recherche}%")
                ->orWhere('organisation', 'like', "%{$recherche}%"))
            ->orderBy('nom')
            ->get();

        return response()->json($intervenants);
    }

    /** Crée un nouvel intervenant dans le répertoire du cabinet (sans le lier
     * à un dossier — utiliser lier() ensuite, ou creerEtLier() pour les deux
     * en une seule action depuis un dossier). */
    public function store(Request $request)
    {
        return response()->json(Intervenant::create($this->valider($request)), 201);
    }

    /** Modifie un intervenant du répertoire — le changement s'applique à tous
     * les dossiers où il est lié, puisqu'il s'agit d'une fiche partagée. */
    public function update(Request $request, Intervenant $intervenant)
    {
        $intervenant->update($this->valider($request));

        return response()->json($intervenant);
    }

    /** Supprime définitivement un intervenant du répertoire (détaché de tous
     * les dossiers où il apparaissait). */
    public function destroy(Intervenant $intervenant)
    {
        $intervenant->delete();

        return response()->json(null, 204);
    }

    /** Liste les intervenants liés à un dossier précis. */
    public function pourDossier(Request $request, Dossier $dossier)
    {
        abort_unless($request->user()->can('view', $dossier), 403, "Ce dossier ne vous est pas assigné.");

        return response()->json($dossier->intervenants()->orderBy('nom')->get());
    }

    /** Lie un intervenant déjà existant du répertoire à ce dossier. */
    public function lier(Request $request, Dossier $dossier, Intervenant $intervenant)
    {
        abort_unless($request->user()->can('view', $dossier), 403, "Ce dossier ne vous est pas assigné.");

        $dossier->intervenants()->syncWithoutDetaching([$intervenant->id]);

        return response()->json($dossier->intervenants()->orderBy('nom')->get(), 201);
    }

    /** Crée un nouvel intervenant et le lie directement à ce dossier, en une
     * seule action (cas le plus courant : on découvre un nouvel intervenant
     * en travaillant sur un dossier précis). */
    public function creerEtLier(Request $request, Dossier $dossier)
    {
        abort_unless($request->user()->can('view', $dossier), 403, "Ce dossier ne vous est pas assigné.");

        $intervenant = Intervenant::create($this->valider($request));
        $dossier->intervenants()->attach($intervenant->id);

        return response()->json($intervenant, 201);
    }

    /** Détache un intervenant de ce dossier uniquement — ne le supprime pas
     * du répertoire, puisqu'il peut être lié à d'autres dossiers. */
    public function delier(Request $request, Dossier $dossier, Intervenant $intervenant)
    {
        abort_unless($request->user()->can('view', $dossier), 403, "Ce dossier ne vous est pas assigné.");

        $dossier->intervenants()->detach($intervenant->id);

        return response()->json(null, 204);
    }

    private function valider(Request $request): array
    {
        return $request->validate([
            'nom' => 'required|string|max:255',
            'fonction' => ['required', Rule::in(['avocat_adverse', 'expert', 'greffier', 'huissier', 'mediateur_arbitre', 'notaire', 'autre'])],
            'organisation' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'telephone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);
    }
}
