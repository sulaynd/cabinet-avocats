<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"></head>
<body style="font-family: Georgia, serif; color:#1B2430; max-width:520px; margin:0 auto; padding:24px;">
    <?php
        $cabinet = \App\Models\CabinetSetting::instance();
        $libellesType = [
            'audience' => 'Audience',
            'delai_procedural' => 'Délai procédural',
            'rdv_client' => 'RDV client',
            'autre' => 'Autre',
        ];
    ?>
    <h2 style="font-weight:normal; border-bottom:2px solid #A8802E; padding-bottom:10px;">{{ $cabinet->nom }}</h2>

    <p style="background:#FBF3DD; color:#8A6D1E; padding:10px 16px; border-radius:4px; display:inline-block;">
        ⏰ Rappel — échéance à venir
    </p>

    <ul>
        <li><strong>Type :</strong> {{ $libellesType[$echeance->type] ?? $echeance->type }}</li>
        <li><strong>Titre :</strong> {{ $echeance->titre }}</li>
        <li><strong>Dossier :</strong> {{ $echeance->dossier->reference }} — {{ $echeance->dossier->titre }}</li>
        <li><strong>Date :</strong> {{ $echeance->date_heure->translatedFormat('l d F Y à H:i') }}</li>
        @if ($echeance->lieu)
            <li><strong>Lieu :</strong> {{ $echeance->lieu }}</li>
        @endif
    </ul>

    <p style="margin-top:30px; color:#8B93A1; font-size:12px;">
        {{ $cabinet->nom }} — {{ $cabinet->adresse }} — {{ $cabinet->telephone }}
    </p>
</body>
</html>
