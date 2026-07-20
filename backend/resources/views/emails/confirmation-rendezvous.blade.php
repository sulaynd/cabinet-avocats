<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"></head>
<body style="font-family: Georgia, serif; color:#1B2430; max-width:520px; margin:0 auto; padding:24px;">
    <?php $cabinet = \App\Models\CabinetSetting::instance(); ?>
    <h2 style="font-weight:normal; border-bottom:2px solid #A8802E; padding-bottom:10px;">{{ $cabinet->nom }}</h2>

    <p>Bonjour {{ $rendezVous->nom }},</p>

    <p>Votre demande de consultation a bien été enregistrée :</p>

    <ul>
        <li><strong>Date :</strong> {{ $rendezVous->date_heure->translatedFormat('l d F Y à H:i') }}</li>
        <li><strong>Motif :</strong> {{ $rendezVous->motif }}</li>
    </ul>

    <p>Un membre du cabinet examinera votre demande et vous assignera l'avocat le plus approprié
        selon votre besoin, puis confirmera ce rendez-vous dans les plus brefs délais. Vous recevrez
        un nouvel email de confirmation définitive, précisant l'avocat qui vous accompagnera.</p>

    <p style="margin-top:30px; color:#8B93A1; font-size:12px;">
        {{ $cabinet->nom }} — {{ $cabinet->adresse }} — {{ $cabinet->telephone }}
    </p>
</body>
</html>
