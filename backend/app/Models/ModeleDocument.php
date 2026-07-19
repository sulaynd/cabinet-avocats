<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModeleDocument extends Model
{
    use HasFactory;

    protected $table = 'modeles_documents';

    protected $fillable = ['nom', 'description', 'type_affaire', 'fichier_chemin', 'nom_original'];

    /** Un modèle sans type_affaire (null) est générique — proposé pour tous
     * les dossiers, en plus de ceux correspondant à son type précis. */
    public static function pourTypeAffaire(?string $typeAffaire)
    {
        return static::where('type_affaire', $typeAffaire)
            ->orWhereNull('type_affaire')
            ->orderBy('nom')
            ->get();
    }
}
