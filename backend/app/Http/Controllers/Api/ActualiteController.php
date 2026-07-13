<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Actualite;
use Illuminate\Http\Request;

class ActualiteController extends Controller
{
    /** Liste publique (hors Sanctum) — utilisée sur la page d'accueil. Ne renvoie que les actualités publiées, les plus récentes en premier. */
    public function public()
    {
        $actualites = Actualite::where('actif', true)
            ->orderByDesc('date')
            ->orderBy('ordre')
            ->get(['id', 'titre', 'date', 'extrait']);

        return response()->json($actualites);
    }

    /** Liste complète (admin) — inclut les actualités non publiées. */
    public function index(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403, 'Réservé aux administrateurs.');

        return response()->json(Actualite::orderByDesc('date')->get());
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403, 'Réservé aux administrateurs.');

        $data = $this->valider($request);

        return response()->json(Actualite::create($data), 201);
    }

    public function update(Request $request, Actualite $actualite)
    {
        abort_unless($request->user()->role === 'admin', 403, 'Réservé aux administrateurs.');

        $data = $this->valider($request);
        $actualite->update($data);

        return response()->json($actualite);
    }

    public function destroy(Request $request, Actualite $actualite)
    {
        abort_unless($request->user()->role === 'admin', 403, 'Réservé aux administrateurs.');

        $actualite->delete();

        return response()->json(null, 204);
    }

    private function valider(Request $request): array
    {
        return $request->validate([
            'titre' => 'required|string|max:255',
            'date' => 'required|date',
            'extrait' => 'required|string',
            'ordre' => 'nullable|integer|min:0',
            'actif' => 'boolean',
        ]);
    }
}
