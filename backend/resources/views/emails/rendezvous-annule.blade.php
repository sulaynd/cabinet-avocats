<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"></head>
<body style="font-family: Georgia, serif; color:#1B2430; max-width:520px; margin:0 auto; padding:24px;">
    <?php $cabinet = \App\Models\CabinetSetting::instance(); ?>
    <h2 style="font-weight:normal; border-bottom:2px solid #A8802E; padding-bottom:10px;">{{ $cabinet->nom }}</h2>

    <p>Bonjour {{ $rendezVous->nom }},</p>

    <p style="background:#FBEAE8; color:#A3402C; padding:10px 16px; border-radius:4px; display:inline-block;">
        Votre rendez-vous a été annulé
    </p>

    <p>Le rendez-vous suivant, que nous avions confirmé, a dû être annulé :</p>

    <ul>
        <li><strong>Avec :</strong> {{ $rendezVous->avocat->name }}</li>
        <li><strong>Date :</strong> {{ $rendezVous->date_heure->translatedFormat('l d F Y à H:i') }}</li>
    </ul>

    <p>Nous vous invitons à reprendre rendez-vous à un moment qui vous convient, ou à
        communiquer directement avec nous pour en discuter.</p>

    <p style="margin-top:30px; color:#8B93A1; font-size:12px;">
        {{ $cabinet->nom }} — {{ $cabinet->adresse }} — {{ $cabinet->telephone }}
    </p>
</body>
</html>
