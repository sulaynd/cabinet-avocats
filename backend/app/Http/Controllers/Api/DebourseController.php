<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Debourse;
use App\Models\Dossier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DebourseController extends Controller
{
    public function index(Request $request, Dossier $dossier)
    {
        abort_unless($request->user()->can('view', $dossier), 403, "Ce dossier ne vous est pas assigné.");

        return response()->json($dossier->debourses()->with('user')->orderByDesc('date_debourse')->get());
    }

    public function store(Request $request, Dossier $dossier)
    {
        abort_unless($request->user()->can('view', $dossier), 403, "Ce dossier ne vous est pas assigné.");
        abort_if($request->user()->estStagiaire(), 403, "En tant que stagiaire, vous avez un accès en lecture seule aux déboursés.");
        abort_if($dossier->statut === 'clos', 422, "Ce dossier est clos, impossible d'y ajouter un déboursé.");

        $data = $this->valider($request);
        $data['dossier_id'] = $dossier->id;
        $data['user_id'] = $request->user()->id;

        return response()->json(Debourse::create($data)->load('user'), 201);
    }

    public function update(Request $request, Debourse $debourse)
    {
        abort_unless($request->user()->can('view', $debourse->dossier), 403, "Ce dossier ne vous est pas assigné.");
        abort_if($request->user()->estStagiaire(), 403, "En tant que stagiaire, vous avez un accès en lecture seule aux déboursés.");
        abort_if($debourse->estFacture(), 422, "Ce déboursé a déjà été facturé et ne peut plus être modifié.");

        $debourse->update($this->valider($request));

        return response()->json($debourse);
    }

    public function destroy(Request $request, Debourse $debourse)
    {
        abort_unless($request->user()->can('view', $debourse->dossier), 403, "Ce dossier ne vous est pas assigné.");
        abort_if($request->user()->estStagiaire(), 403, "En tant que stagiaire, vous avez un accès en lecture seule aux déboursés.");
        abort_if($debourse->estFacture(), 422, "Ce déboursé a déjà été facturé et ne peut plus être supprimé.");

        $debourse->delete();

        return response()->json(null, 204);
    }

    private function valider(Request $request): array
    {
        return $request->validate([
            'categorie' => ['required', Rule::in(['frais_cour', 'deplacement', 'photocopie', 'autre'])],
            'description' => 'required|string|max:255',
            'montant' => 'required|numeric|min:0.01',
            'date_debourse' => 'required|date',
        ]);
    }
}
