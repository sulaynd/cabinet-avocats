<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"></head>
<body style="font-family: Georgia, serif; color:#1B2430; max-width:520px; margin:0 auto; padding:24px;">
    <?php $cabinet = \App\Models\CabinetSetting::instance(); ?>
    <h2 style="font-weight:normal; border-bottom:2px solid #A8802E; padding-bottom:10px;">{{ $cabinet->nom }}</h2>

    <p style="background:#FDEAEA; color:#B3261E; padding:10px 16px; border-radius:4px; display:inline-block;">
        ⚠ Facture en retard de paiement
    </p>

    <ul>
        <li><strong>Numéro :</strong> {{ $facture->numero }}</li>
        <li><strong>Dossier :</strong> {{ $facture->dossier->reference }} — {{ $facture->dossier->titre }}</li>
        <li><strong>Client :</strong> {{ $facture->client->nom_complet }}</li>
        <li><strong>Montant :</strong> {{ number_format($facture->montant_ttc, 2) }} $</li>
        <li><strong>Date d'échéance dépassée :</strong> {{ $facture->date_echeance->translatedFormat('d F Y') }}</li>
    </ul>

    <p style="margin-top:30px; color:#8B93A1; font-size:12px;">
        {{ $cabinet->nom }} — {{ $cabinet->adresse }} — {{ $cabinet->telephone }}
    </p>
</body>
</html>
