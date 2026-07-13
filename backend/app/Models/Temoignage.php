<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Temoignage extends Model
{
    protected $fillable = ['client_id', 'texte', 'ordre', 'actif'];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public function client(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
