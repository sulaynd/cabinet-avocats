<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OffreEmploi extends Model
{
    use HasFactory;

    protected $table = 'offres_emploi';

    protected $fillable = ['titre', 'description', 'type_contrat', 'lieu', 'date_limite', 'ordre', 'actif'];

    protected $casts = [
        'actif' => 'boolean',
        'date_limite' => 'date:Y-m-d',
    ];
}
