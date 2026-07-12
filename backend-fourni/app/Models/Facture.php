<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Facture extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero', 'dossier_id', 'client_id', 'date_emission', 'date_echeance',
        'montant_ht', 'taux_tva', 'montant_ttc', 'statut',
    ];

    protected $casts = [
        'date_emission' => 'date',
        'date_echeance' => 'date',
        'montant_ht' => 'decimal:2',
        'montant_ttc' => 'decimal:2',
    ];

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(Dossier::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(FactureLigne::class);
    }

    /** Entrées de temps passé incluses dans cette facture (mémoire d'honoraires horaire). */
    public function tempsPasses(): HasMany
    {
        return $this->hasMany(TempsPasse::class);
    }

    public function recalculerMontants(): void
    {
        $montantHt = $this->lignes()->sum('montant');
        $this->montant_ht = $montantHt;
        $this->montant_ttc = round($montantHt * (1 + $this->taux_tva / 100), 2);
        $this->save();
    }
}
