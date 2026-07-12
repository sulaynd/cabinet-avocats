<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"></head>
<body style="font-family: Georgia, serif; color:#1B2430; max-width:520px; margin:0 auto; padding:24px;">
    <h2 style="font-weight:normal; border-bottom:2px solid #A8802E; padding-bottom:10px;">Lambert &amp; Associés</h2>

    <p>Bonjour {{ $reponse->dossier->client->nom_complet }},</p>

    <p>Merci d'avoir sollicité notre cabinet pour votre dossier <strong>{{ $reponse->dossier->titre }}</strong>.</p>

    <p>Afin de préparer au mieux votre premier rendez-vous, merci de prendre quelques
        minutes pour compléter ce court questionnaire :</p>

    <p style="text-align:center; margin:28px 0;">
        <a href="{{ $lienPublic }}" style="background:#1B2430; color:#F2EEE3; padding:12px 22px; text-decoration:none; border-radius:2px;">
            Compléter le questionnaire
        </a>
    </p>

    <p style="font-size:12px; color:#8B93A1;">Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :<br>{{ $lienPublic }}</p>

    <p>Ces informations resteront strictement confidentielles et ne seront utilisées
        que pour la préparation de votre dossier.</p>

    <p style="margin-top:30px; color:#8B93A1; font-size:12px;">
        Lambert &amp; Associés — 14 rue des Archives, 75003 Paris — 01 42 00 00 00
    </p>
</body>
</html>
