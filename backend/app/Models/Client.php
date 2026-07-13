<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

/**
 * Le client est mass-assignable comme fiche contact classique, mais peut AUSSI
 * s'authentifier lui-même sur le portail client (email + password + Sanctum),
 * indépendamment des comptes internes du cabinet (table users). Un client sans
 * mot de passe défini n'a simplement pas accès au portail.
 */
class Client extends Model implements Authenticatable
{
    use HasFactory, HasApiTokens, AuthenticatableTrait;

    protected $fillable = [
        'type', 'nom', 'prenom', 'raison_sociale', 'siret',
        'email', 'telephone', 'adresse', 'code_postal', 'ville', 'notes',
        'password', 'portail_active_le', 'doit_changer_mot_de_passe',
    ];

    protected $hidden = ['password'];
    protected $appends = ['nom_complet'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'doit_changer_mot_de_passe' => 'boolean'];
    }

    public function dossiers(): HasMany
    {
        return $this->hasMany(Dossier::class);
    }

    public function factures(): HasMany
    {
        return $this->hasMany(Facture::class);
    }

    public function rendezVous(): HasMany
    {
        return $this->hasMany(RendezVousEnLigne::class);
    }

    public function getNomCompletAttribute(): string
    {
        return $this->type === 'entreprise'
            ? (string) $this->raison_sociale
            : trim("{$this->prenom} {$this->nom}");
    }

    public function possedeAccesPortail(): bool
    {
        return ! empty($this->password) && $this->portail_active_le !== null;
    }
}
