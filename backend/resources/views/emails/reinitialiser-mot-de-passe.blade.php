<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"></head>
<body style="font-family: Georgia, serif; color:#1B2430; max-width:520px; margin:0 auto; padding:24px;">
    <?php $cabinet = \App\Models\CabinetSetting::instance(); ?>
    <h2 style="font-weight:normal; border-bottom:2px solid #A8802E; padding-bottom:10px;">{{ $cabinet->nom }}</h2>

    <p>Bonjour {{ $nom }},</p>

    <p>
        Vous avez demandé la réinitialisation de votre mot de passe. Cliquez sur le bouton
        ci-dessous pour en choisir un nouveau — ce lien n'est valide qu'une heure.
    </p>

    <p style="text-align:center; margin:30px 0;">
        <a href="{{ $lien }}" style="background:#1B2430; color:#F2EEE3; padding:12px 24px; border-radius:2px; text-decoration:none; font-size:14px;">
            Réinitialiser mon mot de passe
        </a>
    </p>

    <p style="font-size:12px; color:#8B93A1;">
        Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet email sans
        risque — votre mot de passe restera inchangé.
    </p>

    <p style="margin-top:30px; color:#8B93A1; font-size:12px;">
        {{ $cabinet->nom }} — {{ $cabinet->adresse }} — {{ $cabinet->telephone }}
    </p>
</body>
</html>
