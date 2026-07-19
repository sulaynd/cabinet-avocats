<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dossier;
use App\Models\Echeance;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EcheanceController extends Controller
{
    /** Vérifie si un créneau (audience/RDV client) entre en conflit avec une
     * autre échéance déjà prévue pour les mêmes intervenants du dossier —
     * appelé par le frontend avant l'enregistrement, à titre d'avertissement
     * (n'empêche pas l'enregistrement si l'utilisateur choisit de continuer). */
    public function verifierConflits(Request $request)
    {
        $data = $request->validate([
            'dossier_id' => 'required|exists:dossiers,id',
            'date_heure' => 'required|date',
            'exclure_id' => 'nullable|exists:echeances,id',
        ]);

        $dossier = Dossier::findOrFail($data['dossier_id']);
        $userIds = [$dossier->avocat_id, $dossier->assistant_id, $dossier->stagiaire_id];

        $conflits = Echeance::conflitsPour($userIds, \Carbon\Carbon::parse($data['date_heure']), $data['exclure_id'] ?? null);

        return response()->json($conflits->map(fn ($e) => [
            'id' => $e->id,
            'titre' => $e->titre,
            'date_heure' => $e->date_heure,
            'dossier_reference' => $e->dossier->reference,
            'dossier_titre' => $e->dossier->titre,
        ]));
    }

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
        abort_if($dossier->statut === 'clos', 422, "Ce dossier est clos, impossible d'y ajouter une nouvelle échéance.");

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
        abort_if($request->user()->estStagiaire(), 403, "En tant que stagiaire, vous ne pouvez pas modifier une échéance.");

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
        abort_if($request->user()->estStagiaire(), 403, "En tant que stagiaire, vous ne pouvez pas supprimer une échéance.");

        $echeance->delete();

        return response()->json(null, 204);
    }
}
