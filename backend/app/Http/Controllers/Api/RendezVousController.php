<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\RendezVousAnnuleMail;
use App\Mail\RendezVousConfirmeMail;
use App\Models\RendezVousEnLigne;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class RendezVousController extends Controller
{
    public function index(Request $request)
    {
        $rendezVous = RendezVousEnLigne::query()
            ->with(['avocat', 'client'])
            ->when($request->statut, fn ($q, $statut) => $q->where('statut', $statut))
            // Les demandes non encore confirmées remontent toujours en tête de
            // liste (peu importe leur date), pour qu'elles ne se perdent jamais
            // au milieu des rendez-vous déjà traités.
            ->orderByRaw("CASE WHEN statut = 'demande' THEN 0 ELSE 1 END")
            ->orderBy('date_heure')
            ->paginate($request->per_page ?? 20);

        return response()->json($rendezVous);
    }

    public function confirmer(Request $request, RendezVousEnLigne $rendezVous)
    {
        abort_if($request->user()->estStagiaire(), 403, "En tant que stagiaire, vous ne pouvez pas confirmer un rendez-vous.");

        $data = $request->validate([
            'montant_consultation' => 'required|numeric|min:0',
            'lien_rencontre' => 'nullable|string|max:500',
        ]);

        $rendezVous->update(['statut' => 'confirme']);

        Mail::to($rendezVous->email)->send(
            new RendezVousConfirmeMail($rendezVous->load('avocat'), $data['montant_consultation'] ?? null, $data['lien_rencontre'] ?? null)
        );

        return response()->json($rendezVous);
    }

    public function annuler(Request $request, RendezVousEnLigne $rendezVous)
    {
        abort_if($request->user()->estStagiaire(), 403, "En tant que stagiaire, vous ne pouvez pas annuler un rendez-vous.");

        $etaitConfirme = $rendezVous->statut === 'confirme';

        $rendezVous->update(['statut' => 'annule']);

        if ($etaitConfirme) {
            Mail::to($rendezVous->email)->send(new RendezVousAnnuleMail($rendezVous->load('avocat')));
        }

        return response()->json($rendezVous);
    }
}
