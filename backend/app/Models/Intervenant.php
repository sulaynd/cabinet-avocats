<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Intervenant extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'fonction', 'organisation', 'email', 'telephone', 'notes'];

    /** Un même intervenant (avocat adverse, expert...) peut être lié à
     * plusieurs dossiers — véritable carnet d'adresses partagé du cabinet. */
    public function dossiers(): BelongsToMany
    {
        return $this->belongsToMany(Dossier::class);
    }
}
