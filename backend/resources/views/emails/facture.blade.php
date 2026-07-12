<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"></head>
<body style="font-family: Georgia, serif; color:#1B2430; max-width:520px; margin:0 auto; padding:24px;">
    <?php $cabinet = \App\Models\CabinetSetting::instance(); ?>
    <h2 style="font-weight:normal; border-bottom:2px solid #A8802E; padding-bottom:10px;">{{ $cabinet->nom }}</h2>

    <p>Bonjour {{ $facture->client->type === 'entreprise' ? $facture->client->raison_sociale : $facture->client->prenom . ' ' . $facture->client->nom }},</p>

    <p>Veuillez trouver ci-joint la facture <strong>{{ $facture->numero }}</strong> relative au dossier
        <strong>{{ $facture->dossier->reference }} — {{ $facture->dossier->titre }}</strong>,
        d'un montant de <strong>{{ number_format($facture->montant_ttc, 2, ',', ' ') }} $</strong> TTC.</p>

    @if ($facture->date_echeance)
        <p>Date d'échéance de paiement : {{ $facture->date_echeance->format('d/m/Y') }}.</p>
    @endif

    <p>N'hésitez pas à nous contacter pour toute question relative à cette facture.</p>

    <p style="margin-top:30px; color:#8B93A1; font-size:12px;">
        {{ $cabinet->nom }} — {{ $cabinet->adresse }} — {{ $cabinet->telephone }}
    </p>
</body>
</html>
