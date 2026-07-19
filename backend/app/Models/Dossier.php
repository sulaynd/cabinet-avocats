<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dossier extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference', 'client_id', 'avocat_id', 'assistant_id', 'stagiaire_id', 'titre', 'type_affaire',
        'statut', 'mode_facturation', 'taux_horaire', 'montant_forfait',
        'facturation_periodique', 'frequence_facturation', 'facturer_a_cloture', 'derniere_facturation_auto_le',
        'date_ouverture', 'date_cloture', 'description',
    ];

    protected $casts = [
        'date_ouverture' => 'date:Y-m-d',
        'date_cloture' => 'date:Y-m-d',
        'derniere_facturation_auto_le' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function avocat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'avocat_id');
    }

    /** Assistant(e) traitant(e) du dossier, en plus de l'avocat responsable (peut être null). */
    public function assistant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assistant_id');
    }

    /** Stagiaire assigné(e) au dossier, distinct de l'assistant — les deux
     * peuvent être assignés en même temps sur un même dossier. */
    public function stagiaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'stagiaire_id');
    }

    public function intervenants(): BelongsToMany
    {
        return $this->belongsToMany(Intervenant::class);
    }

    public function echeances(): HasMany
    {
        return $this->hasMany(Echeance::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function factures(): HasMany
    {
        return $this->hasMany(Facture::class);
    }

    public function tempsPasses(): HasMany
    {
        return $this->hasMany(TempsPasse::class);
    }

    public function communications(): HasMany
    {
        return $this->hasMany(Communication::class)->orderByDesc('date_communication');
    }

    public function reponsesQuestionnaires(): HasMany
    {
        return $this->hasMany(ReponseQuestionnaire::class);
    }

    /** Temps passé facturable et pas encore inclus dans une facture. */
    public function tempsNonFactures(): HasMany
    {
        return $this->tempsPasses()->where('facturable', true)->whereNull('facture_id')->whereNotNull('termine_a');
    }

    /**
     * Restreint la requête aux dossiers visibles par cet utilisateur :
     * - un admin voit tout ;
     * - un avocat/assistant ne voit que les dossiers dont il est avocat
     *   responsable OU assistant traitant.
     * Utilisé partout où des dossiers (ou leurs sous-ressources : échéances,
     * factures, communications, temps passé...) sont listés, pour qu'un
     * intervenant n'accède jamais à un dossier qui ne lui est pas assigné.
     */
    public function scopeVisiblePar($query, User $utilisateur)
    {
        if ($utilisateur->role === 'admin') {
            return $query;
        }

        return $query->where(fn ($q) => $q->where('avocat_id', $utilisateur->id)->orWhere('assistant_id', $utilisateur->id)->orWhere('stagiaire_id', $utilisateur->id));
    }
}
