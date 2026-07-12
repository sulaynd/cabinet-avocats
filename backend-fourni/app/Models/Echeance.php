<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Echeance extends Model
{
    use HasFactory;

    protected $fillable = [
        'dossier_id', 'titre', 'type', 'date_heure', 'lieu', 'statut', 'rappel_avant',
    ];

    protected $casts = [
        'date_heure' => 'datetime',
    ];

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(Dossier::class);
    }
}
