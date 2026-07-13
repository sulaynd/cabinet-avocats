<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;

class MembreEquipeController extends Controller
{
    /**
     * Équipe affichée sur la page d'accueil publique — lit directement les
     * utilisateurs ayant coché "Afficher sur la page d'accueil" sur leur
     * fiche (menu Utilisateurs), plutôt qu'une liste séparée à ressaisir :
     * une seule fiche par personne, pas de double saisie ni de désynchronisation.
     */
    public function public()
    {
        $membres = User::where('afficher_equipe_publique', true)
            ->orderBy('ordre_equipe')
            ->get(['id', 'name', 'role', 'titre_public', 'bio_publique', 'photo_chemin'])
            ->map(fn ($u) => [
                'nom' => $u->name,
                'role' => $u->role,
                'titre' => $u->titre_public,
                'bio' => $u->bio_publique,
                'photo_url' => $u->photo_url,
            ]);

        return response()->json($membres);
    }
}
