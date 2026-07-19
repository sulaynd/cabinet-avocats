<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Debourse extends Model
{
    use HasFactory;

    protected $fillable = ['dossier_id', 'user_id', 'categorie', 'description', 'montant', 'date_debourse', 'facture_id'];

    protected $casts = [
        'date_debourse' => 'date:Y-m-d',
        'montant' => 'decimal:2',
    ];

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(Dossier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function estFacture(): bool
    {
        return $this->facture_id !== null;
    }
}
