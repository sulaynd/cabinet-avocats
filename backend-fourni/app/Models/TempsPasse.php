<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TempsPasse extends Model
{
    use HasFactory;

    protected $table = 'temps_passes';

    protected $fillable = [
        'dossier_id', 'user_id', 'description', 'demarre_a', 'termine_a',
        'duree_secondes', 'facturable', 'taux_horaire_applique', 'facture_id',
    ];

    protected $casts = [
        'demarre_a' => 'datetime',
        'termine_a' => 'datetime',
        'facturable' => 'boolean',
    ];

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(Dossier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function facture(): BelongsTo
    {
        return $this->belongsTo(Facture::class);
    }

    public function estEnCours(): bool
    {
        return $this->demarre_a !== null && $this->termine_a === null;
    }

    public function estFacture(): bool
    {
        return $this->facture_id !== null;
    }

    /** Arrête le chronomètre et calcule la durée écoulée depuis demarre_a. */
    public function arreter(): void
    {
        $this->termine_a = now();
        $this->duree_secondes = max(0, $this->termine_a->diffInSeconds($this->demarre_a));
        $this->save();
    }

    public function getHeuresAttribute(): float
    {
        return round($this->duree_secondes / 3600, 2);
    }
}
