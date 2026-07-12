<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'phone', 'taux_horaire_defaut',
    ];

    // ical_token n'est jamais mass-assignable ni renvoyé par défaut dans les réponses
    // JSON (ex. GET /api/users) : il n'est exposé qu'explicitement par IcalController,
    // pour éviter qu'un jeton secret ne fuite dans un listing d'utilisateurs.
    protected $hidden = [
        'password', 'remember_token', 'ical_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function dossiers(): HasMany
    {
        return $this->hasMany(Dossier::class, 'avocat_id');
    }

    /** Dossiers où cet utilisateur est assistant traitant (et non avocat responsable). */
    public function dossiersEnTantQuAssistant(): HasMany
    {
        return $this->hasMany(Dossier::class, 'assistant_id');
    }

    /** Vrai si l'utilisateur est avocat responsable OU assistant traitant du dossier donné. */
    public function estTraitantDe(Dossier $dossier): bool
    {
        return $dossier->avocat_id === $this->id || $dossier->assistant_id === $this->id;
    }

    public function tempsPasses(): HasMany
    {
        return $this->hasMany(TempsPasse::class);
    }

    /** Génère (ou régénère) le jeton secret de l'abonnement iCal personnel. */
    public function regenererTokenIcal(): string
    {
        $this->ical_token = \Illuminate\Support\Str::random(40);
        $this->save();

        return $this->ical_token;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
