<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CabinetSetting extends Model
{
    protected $table = 'cabinet_settings';

    protected $fillable = ['ical_token_equipe', 'nom', 'adresse', 'telephone', 'email', 'photo_fondateur_chemin', 'taux_horaire_defaut'];
    protected $appends = ['photo_fondateur_url'];

    /** URL publique de la photo du fondateur (null si aucune photo téléversée). */
    public function getPhotoFondateurUrlAttribute(): ?string
    {
        return $this->photo_fondateur_chemin ? asset('storage/' . $this->photo_fondateur_chemin) : null;
    }

    /** Récupère l'unique ligne de paramètres (la crée si elle n'existe pas encore). */
    public static function instance(): self
    {
        return static::firstOrCreate([], [
            'ical_token_equipe' => Str::random(40),
            'nom' => 'JCA — Juristyle Conseil & Accompagnement',
            'adresse' => '1-616 rue des Mélèzes Nord, Québec G1X 3C5',
            'telephone' => '418 262-9610',
            'email' => 'sulaynd@gmail.com',
        ]);
    }

    /**
     * Sous-ensemble public (nom/adresse/téléphone/email) — utilisé sur les pages
     * accessibles sans authentification (connexion, portail, questionnaire public)
     * qui ont besoin d'afficher le nom du cabinet avant que l'utilisateur ne soit connecté.
     */
    public static function coordonneesPubliques(): array
    {
        $parametres = static::instance();

        return [
            'nom' => $parametres->nom,
            'adresse' => $parametres->adresse,
            'telephone' => $parametres->telephone,
            'email' => $parametres->email,
            'photo_fondateur_url' => $parametres->photo_fondateur_url,
        ];
    }

    public static function regenererTokenEquipe(): string
    {
        $parametres = static::instance();
        $parametres->ical_token_equipe = Str::random(40);
        $parametres->save();

        return $parametres->ical_token_equipe;
    }
}
