<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Temoignage;
use Illuminate\Http\Request;

class TemoignageController extends Controller
{
    /** Liste publique (hors Sanctum) — utilisée sur la page d'accueil. Ne renvoie que les témoignages approuvés par l'admin, triés par ordre d'affichage. */
    public function public()
    {
        $temoignages = Temoignage::where('actif', true)
            ->with('client')
            ->orderBy('ordre')
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'nom' => $t->client->nom_complet ?? 'Client de JCA',
                'texte' => $t->texte,
            ]);

        return response()->json($temoignages);
    }

    /** Liste complète (admin) — inclut les témoignages en attente d'approbation, avec le client associé. */
    public function index(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403, 'Réservé aux administrateurs.');

        return response()->json(Temoignage::with('client')->orderByDesc('created_at')->get());
    }

    /** Le client (portail) soumet son propre témoignage — jamais publié automatiquement, en attente d'approbation par l'admin. */
    public function soumettreDepuisPortail(Request $request)
    {
        $data = $request->validate(['texte' => 'required|string|max:2000']);
        $client = $request->user();

        $temoignage = Temoignage::updateOrCreate(
            ['client_id' => $client->id],
            ['texte' => $data['texte'], 'actif' => false]
        );

        return response()->json($temoignage, 201);
    }

    /** Le client (portail) consulte son propre témoignage et son statut de publication. */
    public function monTemoignage(Request $request)
    {
        $temoignage = Temoignage::where('client_id', $request->user()->id)->first();

        return response()->json($temoignage);
    }

    /** L'admin approuve/masque un témoignage (active/désactive son affichage public) — ne modifie jamais le texte, ce sont les mots du client. */
    public function update(Request $request, Temoignage $temoignage)
    {
        abort_unless($request->user()->role === 'admin', 403, 'Réservé aux administrateurs.');

        $data = $request->validate([
            'actif' => 'required|boolean',
            'ordre' => 'nullable|integer|min:0',
        ]);

        $temoignage->update($data);

        return response()->json($temoignage);
    }

    public function destroy(Request $request, Temoignage $temoignage)
    {
        abort_unless($request->user()->role === 'admin', 403, 'Réservé aux administrateurs.');

        $temoignage->delete();

        return response()->json(null, 204);
    }
}
