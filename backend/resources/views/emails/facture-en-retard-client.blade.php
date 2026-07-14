<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"></head>
<body style="font-family: Georgia, serif; color:#1B2430; max-width:520px; margin:0 auto; padding:24px;">
    <h2 style="font-weight:normal; border-bottom:2px solid #A8802E; padding-bottom:10px;">{{ $cabinet->nom }}</h2>

    <p>Bonjour {{ $facture->client->nom_complet }},</p>

    <p>
        Nous n'avons pas encore reçu le paiement de la facture <strong>{{ $facture->numero }}</strong>,
        dont la date d'échéance ({{ $facture->date_echeance->translatedFormat('d F Y') }}) est maintenant
        dépassée. Nous vous serions reconnaissants de régulariser cette facture dans les meilleurs délais.
    </p>

    <p style="background:#FBF3DD; color:#8A6D1E; padding:14px 18px; border-radius:4px;">
        <strong>Montant dû :</strong> {{ number_format($facture->montant_ttc, 2) }} $<br>
        <strong>Numéro de facture :</strong> {{ $facture->numero }}
    </p>

    <p>
        Si ce paiement a déjà été effectué entre-temps, veuillez ignorer ce message. Pour toute question,
        n'hésitez pas à communiquer avec nous au {{ $cabinet->telephone }}.
    </p>

    <p style="margin-top:30px; color:#8B93A1; font-size:12px;">
        {{ $cabinet->nom }} — {{ $cabinet->adresse }} — {{ $cabinet->telephone }}
    </p>
</body>
</html>
