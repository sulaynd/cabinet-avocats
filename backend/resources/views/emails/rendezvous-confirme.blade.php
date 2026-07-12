<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"></head>
<body style="font-family: Georgia, serif; color:#1B2430; max-width:520px; margin:0 auto; padding:24px;">
    <?php $cabinet = \App\Models\CabinetSetting::instance(); ?>
    <h2 style="font-weight:normal; border-bottom:2px solid #A8802E; padding-bottom:10px;">{{ $cabinet->nom }}</h2>

    <p>Bonjour {{ $rendezVous->nom }},</p>

    <p style="background:#E8F3E8; color:#2E6B2E; padding:10px 16px; border-radius:4px; display:inline-block;">
        ✓ Votre rendez-vous est confirmé
    </p>

    <ul>
        <li><strong>Avec :</strong> {{ $rendezVous->avocat->name }}</li>
        <li><strong>Date :</strong> {{ $rendezVous->date_heure->translatedFormat('l d F Y à H:i') }}</li>
        @if ($rendezVous->motif)
            <li><strong>Motif :</strong> {{ $rendezVous->motif }}</li>
        @endif
    </ul>

    @if ($montantConsultation)
        <p>
            Je vous propose une consultation à distance de 1h au tarif de
            {{ number_format($montantConsultation, 2, ',', ' ') }} $, payable par virement Interac
            au numéro suivant : {{ $cabinet->telephone }}
        </p>
    @endif

    @if ($lienRencontre)
        <p>
            Je vous transmets un lien d'invitation pour la rencontre :
            <a href="{{ $lienRencontre }}">{{ $lienRencontre }}</a>
        </p>
    @endif

    <p>Nous avons hâte de vous rencontrer. Si vous devez annuler ou modifier ce rendez-vous,
        merci de nous contacter directement.</p>

    <p style="margin-top:30px; color:#8B93A1; font-size:12px;">
        {{ $cabinet->nom }} — {{ $cabinet->adresse }} — {{ $cabinet->telephone }}
    </p>
</body>
</html>
