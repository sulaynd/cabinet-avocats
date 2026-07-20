<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CabinetSetting;
use Illuminate\Http\Request;

class CabinetSettingController extends Controller
{
    /**
     * Coordonnées du cabinet — endpoint PUBLIC (hors Sanctum), utilisé par les
     * pages accessibles sans authentification (connexion, portail client,
     * questionnaire public) pour afficher le nom du cabinet avant tout login.
     * Ne renvoie volontairement que nom/adresse/téléphone/email, rien de sensible.
     */
    public function public()
    {
        return response()->json(CabinetSetting::coordonneesPubliques());
    }

    /** Coordonnées complètes — tout le personnel authentifié peut consulter
     * (ex: le taux horaire par défaut du cabinet, nécessaire pour savoir si
     * le taux horaire d'un dossier est réellement obligatoire) ; seule la
     * modification reste réservée à l'admin (voir update() ci-dessous). */
    public function show(Request $request)
    {
        return response()->json(CabinetSetting::instance());
    }

    /** Met à jour les coordonnées du cabinet — réservé à l'admin. */
    public function update(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403, 'Réservé aux administrateurs.');

        $data = $request->validate([
            'nom' => 'required|string|max:255',
            'adresse' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'taux_horaire_defaut' => 'nullable|numeric|min:0',
        ]);

        $parametres = CabinetSetting::instance();
        $parametres->update($data);

        return response()->json($parametres);
    }

    /** Téléverse (ou remplace) la photo du fondateur — réservé à l'admin, disque PUBLIC (photo affichée sans authentification sur la page d'accueil). */
    public function televerserPhoto(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403, 'Réservé aux administrateurs.');

        $request->validate([
            'photo' => 'required|image|max:5120',
        ]);

        $parametres = CabinetSetting::instance();

        // Supprime l'ancienne photo si elle existe, pour ne pas accumuler des
        // fichiers orphelins sur le disque à chaque remplacement.
        if ($parametres->photo_fondateur_chemin) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($parametres->photo_fondateur_chemin);
        }

        $chemin = $request->file('photo')->store('cabinet', 'public');
        $parametres->update(['photo_fondateur_chemin' => $chemin]);

        return response()->json($parametres);
    }
}
