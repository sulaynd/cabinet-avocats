<?php $cabinet = \App\Models\CabinetSetting::instance(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Facture {{ $facture->numero }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #1a1a1a; }
        .en-tete { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .cabinet h1 { font-size: 18px; margin: 0 0 4px; }
        .cabinet p { margin: 0; color: #555; }
        .facture-titre { text-align: right; }
        .facture-titre h2 { margin: 0; font-size: 22px; }
        .facture-titre p { margin: 2px 0; color: #555; }
        .adresses { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .bloc { width: 48%; }
        .bloc h3 { font-size: 11px; text-transform: uppercase; color: #888; margin-bottom: 6px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 8px 10px; border-bottom: 1px solid #e0e0e0; text-align: left; }
        th { background: #f5f5f5; font-size: 11px; text-transform: uppercase; color: #666; }
        td.montant, th.montant { text-align: right; }
        .totaux { width: 300px; margin-left: auto; }
        .totaux td { border: none; padding: 4px 10px; }
        .totaux .total-ttc td { font-weight: bold; font-size: 14px; border-top: 2px solid #1a1a1a; }
        .statut { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 11px; text-transform: uppercase; }
        .statut-payee { background: #d1f7d6; color: #1a7a2a; }
        .statut-en_retard { background: #fbdada; color: #a11d1d; }
        .statut-envoyee, .statut-brouillon { background: #eee; color: #555; }
        .pied { margin-top: 50px; font-size: 10px; color: #888; text-align: center; }
    </style>
</head>
<body>
    <div class="en-tete">
        <div class="cabinet">
            <h1>{{ $cabinet->nom }}</h1>
            <p>{{ $cabinet->adresse }}</p>
            <p>{{ $cabinet->telephone }} · {{ $cabinet->email }}</p>
        </div>
        <div class="facture-titre">
            <h2>FACTURE</h2>
            <p>N° {{ $facture->numero }}</p>
            <p><span class="statut statut-{{ $facture->statut }}">{{ $facture->statut }}</span></p>
        </div>
    </div>

    <div class="adresses">
        <div class="bloc">
            <h3>Facturé à</h3>
            <p>
                @if ($facture->client->type === 'entreprise')
                    {{ $facture->client->raison_sociale }}
                @else
                    {{ $facture->client->prenom }} {{ $facture->client->nom }}
                @endif
            </p>
            <p>{{ $facture->client->adresse }}</p>
            <p>{{ $facture->client->code_postal }} {{ $facture->client->ville }}</p>
        </div>
        <div class="bloc">
            <h3>Dossier concerné</h3>
            <p>{{ $facture->dossier->reference }} — {{ $facture->dossier->titre }}</p>
            <h3 style="margin-top: 14px;">Dates</h3>
            <p>Émission : {{ $facture->date_emission->format('d/m/Y') }}</p>
            @if ($facture->date_echeance)
                <p>Échéance : {{ $facture->date_echeance->format('d/m/Y') }}</p>
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="montant">Quantité</th>
                <th class="montant">Prix unitaire</th>
                <th class="montant">Montant</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($facture->lignes as $ligne)
                <tr>
                    <td>{{ $ligne->description }}</td>
                    <td class="montant">{{ number_format($ligne->quantite, 2, ',', ' ') }}</td>
                    <td class="montant">{{ number_format($ligne->prix_unitaire, 2, ',', ' ') }} $</td>
                    <td class="montant">{{ number_format($ligne->montant, 2, ',', ' ') }} $</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totaux">
        <tr>
            <td>Total HT</td>
            <td class="montant">{{ number_format($facture->montant_ht, 2, ',', ' ') }} $</td>
        </tr>
        <tr>
            <td>TPS ({{ number_format($facture->taux_tps, 3) }} %)</td>
            <td class="montant">{{ number_format($facture->montant_tps, 2, ',', ' ') }} $</td>
        </tr>
        <tr>
            <td>TVQ ({{ number_format($facture->taux_tvq, 3) }} %)</td>
            <td class="montant">{{ number_format($facture->montant_tvq, 2, ',', ' ') }} $</td>
        </tr>
        <tr class="total-ttc">
            <td>Total TTC</td>
            <td class="montant">{{ number_format($facture->montant_ttc, 2, ',', ' ') }} $</td>
        </tr>
    </table>

    <div class="pied">
        Cabinet d'Avocats — Document généré automatiquement le {{ now()->format('d/m/Y à H:i') }}
    </div>
</body>
</html>
