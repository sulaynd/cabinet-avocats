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
        'montant_ht', 'taux_tps', 'taux_tvq', 'montant_tps', 'montant_tvq', 'montant_ttc', 'statut',
    ];

    protected $casts = [
        'date_emission' => 'date:Y-m-d',
        'date_echeance' => 'date:Y-m-d',
        'montant_ht' => 'decimal:2',
        'montant_tps' => 'decimal:2',
        'montant_tvq' => 'decimal:2',
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

    /**
     * TPS et TVQ sont calculées indépendamment sur le montant HT (pas de
     * "taxe sur taxe" depuis la réforme québécoise de 2013).
     */
    public function recalculerMontants(): void
    {
        $montantHt = $this->lignes()->sum('montant');
        $montantTps = round($montantHt * ($this->taux_tps / 100), 2);
        $montantTvq = round($montantHt * ($this->taux_tvq / 100), 2);

        $this->montant_ht = $montantHt;
        $this->montant_tps = $montantTps;
        $this->montant_tvq = $montantTvq;
        $this->montant_ttc = round($montantHt + $montantTps + $montantTvq, 2);
        $this->save();
    }
}
