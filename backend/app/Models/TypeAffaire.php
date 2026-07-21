<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeAffaire extends Model
{
    use HasFactory;

    protected $table = 'types_affaire';

    protected $fillable = ['slug', 'libelle', 'actif', 'ordre'];

    protected $casts = ['actif' => 'boolean'];

    public function sousCategories(): HasMany
    {
        return $this->hasMany(SousCategorieAffaire::class);
    }
}
