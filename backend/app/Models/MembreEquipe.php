<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MembreEquipe extends Model
{
    protected $table = 'membres_equipe';

    protected $fillable = ['nom', 'titre', 'bio', 'photo_chemin', 'ordre', 'actif'];

    protected $casts = [
        'actif' => 'boolean',
    ];

    protected $appends = ['photo_url'];

    /** URL publique de la photo (null si aucune photo téléversée). */
    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_chemin ? asset('storage/' . $this->photo_chemin) : null;
    }
}
