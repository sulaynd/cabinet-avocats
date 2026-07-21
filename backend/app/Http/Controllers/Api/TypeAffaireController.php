<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SousCategorieAffaire;
use App\Models\TypeAffaire;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TypeAffaireController extends Controller
{
    /** Liste complète (admin) ou seulement les actifs (?actif=1, pour les
     * menus déroulants des formulaires dossier/rendez-vous), avec leurs
     * sous-catégories actives. */
    public function index(Request $request)
    {
        $types = TypeAffaire::query()
            ->when($request->boolean('actif'), fn ($q) => $q->where('actif', true))
            ->with(['sousCategories' => fn ($q) => $request->boolean('actif') ? $q->where('actif', true)->orderBy('ordre') : $q->orderBy('ordre')])
            ->orderBy('ordre')
            ->get();

        return response()->json($types);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403, 'Réservé aux administrateurs.');

        $data = $request->validate([
            'libelle' => 'required|string|max:255',
        ]);

        $type = TypeAffaire::create([
            'slug' => $this->genererSlugUnique($data['libelle']),
            'libelle' => $data['libelle'],
            'actif' => true,
            'ordre' => (TypeAffaire::max('ordre') ?? 0) + 1,
        ]);

        return response()->json($type, 201);
    }

    public function update(Request $request, TypeAffaire $typeAffaire)
    {
        abort_unless($request->user()->role === 'admin', 403, 'Réservé aux administrateurs.');

        $data = $request->validate([
            'libelle' => 'sometimes|required|string|max:255',
            'actif' => 'sometimes|boolean',
            'ordre' => 'sometimes|integer',
        ]);

        $typeAffaire->update($data);

        return response()->json($typeAffaire);
    }

    public function destroy(Request $request, TypeAffaire $typeAffaire)
    {
        abort_unless($request->user()->role === 'admin', 403, 'Réservé aux administrateurs.');

        // Suppression douce recommandée (désactiver) pour les types déjà
        // utilisés par des dossiers existants — mais on autorise aussi la
        // suppression définitive si l'admin le souhaite explicitement.
        $typeAffaire->delete();

        return response()->json(null, 204);
    }

    // --- Sous-catégories, imbriquées sous un type d'affaire ---

    public function storeSousCategorie(Request $request, TypeAffaire $typeAffaire)
    {
        abort_unless($request->user()->role === 'admin', 403, 'Réservé aux administrateurs.');

        $data = $request->validate([
            'libelle' => 'required|string|max:255',
        ]);

        $sousCategorie = $typeAffaire->sousCategories()->create([
            'slug' => $this->genererSlugUnique($data['libelle']),
            'libelle' => $data['libelle'],
            'actif' => true,
            'ordre' => ($typeAffaire->sousCategories()->max('ordre') ?? 0) + 1,
        ]);

        return response()->json($sousCategorie, 201);
    }

    public function updateSousCategorie(Request $request, SousCategorieAffaire $sousCategorieAffaire)
    {
        abort_unless($request->user()->role === 'admin', 403, 'Réservé aux administrateurs.');

        $data = $request->validate([
            'libelle' => 'sometimes|required|string|max:255',
            'actif' => 'sometimes|boolean',
            'ordre' => 'sometimes|integer',
        ]);

        $sousCategorieAffaire->update($data);

        return response()->json($sousCategorieAffaire);
    }

    public function destroySousCategorie(Request $request, SousCategorieAffaire $sousCategorieAffaire)
    {
        abort_unless($request->user()->role === 'admin', 403, 'Réservé aux administrateurs.');

        $sousCategorieAffaire->delete();

        return response()->json(null, 204);
    }

    /** Génère un slug technique unique (ex: "Nouveau type" -> "nouveau_type",
     * "nouveau_type_2" si déjà pris) — jamais affiché au client, seulement
     * utilisé en interne pour identifier la valeur de façon stable. */
    private function genererSlugUnique(string $libelle): string
    {
        $base = Str::slug($libelle, '_');
        $slug = $base;
        $compteur = 2;

        while (TypeAffaire::where('slug', $slug)->exists() || SousCategorieAffaire::where('slug', $slug)->exists()) {
            $slug = "{$base}_{$compteur}";
            $compteur++;
        }

        return $slug;
    }
}
