<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SousCategorieAffaire extends Model
{
    use HasFactory;

    protected $table = 'sous_categories_affaire';

    protected $fillable = ['type_affaire_id', 'slug', 'libelle', 'actif', 'ordre'];

    protected $casts = ['actif' => 'boolean'];

    public function typeAffaire(): BelongsTo
    {
        return $this->belongsTo(TypeAffaire::class);
    }
}
