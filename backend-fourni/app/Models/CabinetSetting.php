<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CabinetSetting extends Model
{
    protected $table = 'cabinet_settings';

    protected $fillable = ['ical_token_equipe'];

    /** Récupère l'unique ligne de paramètres (la crée si elle n'existe pas encore). */
    public static function instance(): self
    {
        return static::firstOrCreate([], ['ical_token_equipe' => Str::random(40)]);
    }

    public static function regenererTokenEquipe(): string
    {
        $parametres = static::instance();
        $parametres->ical_token_equipe = Str::random(40);
        $parametres->save();

        return $parametres->ical_token_equipe;
    }
}
