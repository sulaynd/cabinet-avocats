<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Actualite extends Model
{
    protected $fillable = ['titre', 'date', 'extrait', 'ordre', 'actif'];

    protected $casts = [
        'actif' => 'boolean',
        'date' => 'date:Y-m-d',
    ];
}
