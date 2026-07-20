<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Echeance extends Model
{
    use HasFactory;

    protected $fillable = [
        'dossier_id', 'titre', 'type', 'date_heure', 'lieu', 'statut', 'rappel_avant', 'rappel_envoye',
    ];

    protected $casts = [
        'date_heure' => 'datetime',
        'rappel_envoye' => 'boolean',
    ];

    /**
     * Par défaut, Carbon convertit systématiquement en UTC lors de la
     * sérialisation JSON (suffixe "Z"), même quand l'app utilise un fuseau
     * local (America/Toronto). Ce champ représente une heure murale locale
     * saisie telle quelle par l'utilisateur — on la restitue exactement sans
     * conversion, pour que le frontend affiche toujours l'heure réellement
     * saisie, peu importe l'écran.
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d\TH:i:s');
    }

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(Dossier::class);
    }

    /**
     * Échéances en conflit d'horaire pour les mêmes intervenants (avocat,
     * assistant ou stagiaire d'un dossier), à ±1 heure du créneau donné.
     * Ne s'applique qu'aux types nécessitant une présence physique/virtuelle
     * réelle à un moment précis (audience, RDV client) — un délai procédural
     * n'occupe personne à une heure fixe, donc ne peut pas être "en conflit".
     */
    public static function conflitsPour(array $userIds, \Carbon\Carbon $dateHeure, ?int $exclureId = null)
    {
        $userIds = array_filter($userIds);
        if (empty($userIds)) {
            return collect();
        }

        return static::whereIn('type', ['audience', 'rdv_client'])
            ->where('statut', 'a_venir')
            ->whereBetween('date_heure', [$dateHeure->copy()->subHour(), $dateHeure->copy()->addHour()])
            ->when($exclureId, fn ($q) => $q->where('id', '!=', $exclureId))
            ->whereHas('dossier', function ($q) use ($userIds) {
                $q->whereIn('avocat_id', $userIds)->orWhereIn('assistant_id', $userIds)->orWhereIn('stagiaire_id', $userIds);
            })
            ->with('dossier.avocat', 'dossier.assistant', 'dossier.stagiaire')
            ->get();
    }
}
