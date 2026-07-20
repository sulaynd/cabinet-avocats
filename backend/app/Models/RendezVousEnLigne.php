<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RendezVousEnLigne extends Model
{
    use HasFactory;

    protected $table = 'rendezvous_en_ligne';

    protected $fillable = [
        'nom', 'email', 'telephone', 'motif', 'type_affaire', 'avocat_id', 'client_id', 'date_heure', 'statut',
    ];

    protected $casts = [
        'date_heure' => 'datetime',
    ];

    public function avocat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'avocat_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
