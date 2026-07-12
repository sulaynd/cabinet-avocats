<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RendezVousEnLigne;
use Illuminate\Http\Request;

class RendezVousController extends Controller
{
    public function index(Request $request)
    {
        $rendezVous = RendezVousEnLigne::query()
            ->with(['avocat', 'client'])
            ->when($request->statut, fn ($q, $statut) => $q->where('statut', $statut))
            ->orderBy('date_heure')
            ->get();

        return response()->json($rendezVous);
    }

    public function confirmer(RendezVousEnLigne $rendezVous)
    {
        $rendezVous->update(['statut' => 'confirme']);

        return response()->json($rendezVous);
    }

    public function annuler(RendezVousEnLigne $rendezVous)
    {
        $rendezVous->update(['statut' => 'annule']);

        return response()->json($rendezVous);
    }
}
