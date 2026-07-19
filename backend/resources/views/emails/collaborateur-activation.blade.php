<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"></head>
<body style="font-family: Georgia, serif; color:#1B2430; max-width:520px; margin:0 auto; padding:24px;">
    <?php $cabinet = \App\Models\CabinetSetting::instance(); ?>
    <h2 style="font-weight:normal; border-bottom:2px solid #A8802E; padding-bottom:10px;">{{ $cabinet->nom }}</h2>

    <p>Bonjour {{ $collaborateur->nom }},</p>

    <p>Un espace collaborateur vous a été ouvert pour accéder aux documents partagés avec vous
        et en déposer vos propres documents, sur les dossiers où votre collaboration est requise.</p>

    <table style="background:#F7F5EF; border-radius:4px; padding:16px 20px; margin:20px 0; width:100%; border-collapse:collapse;">
        <tr>
            <td style="padding:4px 0; color:#8B93A1; font-size:12px; text-transform:uppercase;">Email</td>
        </tr>
        <tr>
            <td style="padding:0 0 12px; font-weight:bold;">{{ $collaborateur->email }}</td>
        </tr>
        <tr>
            <td style="padding:4px 0; color:#8B93A1; font-size:12px; text-transform:uppercase;">Mot de passe temporaire</td>
        </tr>
        <tr>
            <td style="padding:0; font-weight:bold; font-family: monospace; font-size:16px;">{{ $motDePasse }}</td>
        </tr>
    </table>

    <p>
        <a href="{{ $urlPortail }}" style="background:#1B2430; color:#F2EEE3; padding:10px 18px; text-decoration:none; border-radius:4px; display:inline-block;">
            Accéder à mon espace collaborateur
        </a>
    </p>

    <p style="font-size:13px; color:#555;">
        Seuls les documents explicitement partagés avec vous sur chaque dossier vous seront visibles.
        Par mesure de sécurité, nous vous recommandons de conserver ce mot de passe en lieu sûr.
    </p>

    <p style="margin-top:30px; color:#8B93A1; font-size:12px;">
        {{ $cabinet->nom }} — {{ $cabinet->adresse }} — {{ $cabinet->telephone }}
    </p>
</body>
</html>
