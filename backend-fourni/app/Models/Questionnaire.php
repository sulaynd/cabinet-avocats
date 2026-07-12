<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Questionnaire extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'description', 'type_affaire', 'champs', 'actif'];

    protected $casts = [
        'champs' => 'array',
        'actif' => 'boolean',
    ];

    public function reponses(): HasMany
    {
        return $this->hasMany(ReponseQuestionnaire::class);
    }

    /**
     * Sélectionne le questionnaire à envoyer pour un type d'affaire donné :
     * priorité au questionnaire actif ciblant spécifiquement ce type, sinon
     * repli sur le questionnaire actif "par défaut" (type_affaire = null).
     */
    public static function pourTypeAffaire(?string $typeAffaire): ?self
    {
        return static::where('actif', true)->where('type_affaire', $typeAffaire)->first()
            ?? static::where('actif', true)->whereNull('type_affaire')->first();
    }
}
