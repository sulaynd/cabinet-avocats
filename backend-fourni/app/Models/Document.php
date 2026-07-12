<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use HasFactory;

    protected $table = 'documents';

    protected $fillable = [
        'dossier_id', 'nom_original', 'chemin', 'type', 'taille', 'uploaded_by',
        'necessite_signature', 'signe_le', 'signature_nom', 'signature_ip',
    ];

    protected $casts = [
        'necessite_signature' => 'boolean',
        'signe_le' => 'datetime',
    ];

    public function estSigne(): bool
    {
        return $this->signe_le !== null;
    }

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(Dossier::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
