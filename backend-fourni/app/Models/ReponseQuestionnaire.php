<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReponseQuestionnaire extends Model
{
    use HasFactory;

    protected $table = 'reponses_questionnaires';

    protected $fillable = ['dossier_id', 'questionnaire_id', 'token', 'reponses', 'envoye_le', 'rempli_le'];

    protected $casts = [
        'reponses' => 'array',
        'envoye_le' => 'datetime',
        'rempli_le' => 'datetime',
    ];

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(Dossier::class);
    }

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(Questionnaire::class);
    }

    public function estRempli(): bool
    {
        return $this->rempli_le !== null;
    }
}
