<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dossier;
use App\Models\Echeance;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EcheanceController extends Controller
{
    public function index(Request $request)
    {
        $echeances = Echeance::query()
            ->with('dossier.avocat', 'dossier.assistant')
            // Un avocat/assistant ne voit que les échéances des dossiers qui lui
            // sont assignés ; un admin voit tout. Le filtre "traitant_id" (vue
            // agenda) ne permet de regarder l'agenda d'un tiers qu'à un admin.
            ->whereHas('dossier', fn ($q) => $q->visiblePar($request->user()))
            ->when($request->dossier_id, fn ($q, $id) => $q->where('dossier_id', $id))
            ->when($request->traitant_id && $request->user()->role === 'admin', function ($q) use ($request) {
                $id = $request->traitant_id;
                $q->whereHas('dossier', function ($sous) use ($id) {
                    $sous->where('avocat_id', $id)->orWhere('assistant_id', $id);
                });
            })
            ->when($request->from, fn ($q, $from) => $q->where('date_heure', '>=', $from))
            ->when($request->to, fn ($q, $to) => $q->where('date_heure', '<=', $to))
            ->orderBy('date_heure')
            ->get();

        return response()->json($echeances);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'dossier_id' => 'required|exists:dossiers,id',
            'titre' => 'required|string|max:255',
            'type' => [Rule::in(['audience', 'delai_procedural', 'rdv_client', 'autre'])],
            'date_heure' => 'required|date',
            'lieu' => 'nullable|string|max:255',
            'statut' => [Rule::in(['a_venir', 'realisee', 'annulee'])],
            'rappel_avant' => 'nullable|integer|min:0',
        ]);

        $dossier = Dossier::findOrFail($data['dossier_id']);
        abort_unless($request->user()->can('view', $dossier), 403, "Ce dossier ne vous est pas assigné.");

        $echeance = Echeance::create($data);

        return response()->json($echeance, 201);
    }

    public function show(Request $request, Echeance $echeance)
    {
        abort_unless($request->user()->can('view', $echeance->dossier), 403, "Ce dossier ne vous est pas assigné.");

        return response()->json($echeance->load('dossier'));
    }

    public function update(Request $request, Echeance $echeance)
    {
        abort_unless($request->user()->can('view', $echeance->dossier), 403, "Ce dossier ne vous est pas assigné.");

        $data = $request->validate([
            'titre' => 'string|max:255',
            'type' => [Rule::in(['audience', 'delai_procedural', 'rdv_client', 'autre'])],
            'date_heure' => 'date',
            'lieu' => 'nullable|string|max:255',
            'statut' => [Rule::in(['a_venir', 'realisee', 'annulee'])],
            'rappel_avant' => 'nullable|integer|min:0',
        ]);

        $echeance->update($data);

        return response()->json($echeance);
    }

    public function destroy(Request $request, Echeance $echeance)
    {
        abort_unless($request->user()->can('view', $echeance->dossier), 403, "Ce dossier ne vous est pas assigné.");

        $echeance->delete();

        return response()->json(null, 204);
    }
}
