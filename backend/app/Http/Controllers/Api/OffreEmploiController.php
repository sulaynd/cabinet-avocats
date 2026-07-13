<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OffreEmploi;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OffreEmploiController extends Controller
{
    /** Liste publique (hors Sanctum) — utilisée sur la page d'accueil. Ne renvoie que les offres publiées, triées par ordre d'affichage, et masque automatiquement celles dont la date limite est dépassée. */
    public function public()
    {
        $offres = OffreEmploi::where('actif', true)
            ->where(function ($q) {
                $q->whereNull('date_limite')->orWhereDate('date_limite', '>=', now());
            })
            ->orderBy('ordre')
            ->get(['id', 'titre', 'description', 'type_contrat', 'lieu', 'date_limite']);

        return response()->json($offres);
    }

    /** Liste complète (admin) — inclut les offres non publiées et celles expirées. */
    public function index(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403, 'Réservé aux administrateurs.');

        return response()->json(OffreEmploi::orderBy('ordre')->get());
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403, 'Réservé aux administrateurs.');

        $data = $this->valider($request);

        return response()->json(OffreEmploi::create($data), 201);
    }

    public function update(Request $request, OffreEmploi $offresEmploi)
    {
        abort_unless($request->user()->role === 'admin', 403, 'Réservé aux administrateurs.');

        $data = $this->valider($request);
        $offresEmploi->update($data);

        return response()->json($offresEmploi);
    }

    public function destroy(Request $request, OffreEmploi $offresEmploi)
    {
        abort_unless($request->user()->role === 'admin', 403, 'Réservé aux administrateurs.');

        $offresEmploi->delete();

        return response()->json(null, 204);
    }

    private function valider(Request $request): array
    {
        return $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'required|string',
            'type_contrat' => ['required', Rule::in(['cdi', 'cdd', 'stage', 'temps_partiel', 'contractuel', 'autre'])],
            'lieu' => 'nullable|string|max:255',
            'date_limite' => 'nullable|date',
            'ordre' => 'nullable|integer|min:0',
            'actif' => 'boolean',
        ]);
    }
}
