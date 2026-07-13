<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Echeance extends Model
{
    use HasFactory;

    protected $fillable = [
        'dossier_id', 'titre', 'type', 'date_heure', 'lieu', 'statut', 'rappel_avant', 'rappel_envoye',
    ];

    protected $casts = [
        'date_heure' => 'datetime',
        'rappel_envoye' => 'boolean',
    ];

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(Dossier::class);
    }
}
