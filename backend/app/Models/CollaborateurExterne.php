<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

/**
 * Collaborateur externe (co-conseil, expert...) — distinct du carnet
 * d'intervenants (qui inclut aussi l'avocat adverse, jamais destinataire
 * d'un accès). Peut s'authentifier lui-même sur un portail dédié (email +
 * password + Sanctum), indépendamment des comptes internes et du portail
 * client. Un collaborateur sans mot de passe défini n'a simplement pas
 * encore d'accès activé.
 */
class CollaborateurExterne extends Model implements Authenticatable
{
    use HasFactory, HasApiTokens, AuthenticatableTrait;

    protected $table = 'collaborateurs_externes';

    protected $fillable = [
        'nom', 'email', 'organisation', 'telephone',
        'password', 'portail_active_le', 'doit_changer_mot_de_passe',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'portail_active_le' => 'datetime',
            'doit_changer_mot_de_passe' => 'boolean',
        ];
    }

    public function dossiers(): BelongsToMany
    {
        return $this->belongsToMany(Dossier::class, 'dossier_collaborateur_externe');
    }

    public function documentsTeleverses(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function possedeAccesPortail(): bool
    {
        return ! empty($this->password) && $this->portail_active_le !== null;
    }
}
