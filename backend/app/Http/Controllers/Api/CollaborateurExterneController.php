<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\CollaborateurActivationMail;
use App\Models\CollaborateurExterne;
use App\Models\Dossier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CollaborateurExterneController extends Controller
{
    /** Répertoire complet des collaborateurs externes, pour la recherche
     * lors de la liaison à un dossier. */
    public function index(Request $request)
    {
        $collaborateurs = CollaborateurExterne::query()
            ->when($request->recherche, fn ($q, $recherche) => $q->where('nom', 'like', "%{$recherche}%")
                ->orWhere('organisation', 'like', "%{$recherche}%")
                ->orWhere('email', 'like', "%{$recherche}%"))
            ->orderBy('nom')
            ->get();

        return response()->json($collaborateurs);
    }

    public function store(Request $request)
    {
        $data = $this->valider($request);

        return response()->json(CollaborateurExterne::create($data), 201);
    }

    public function update(Request $request, CollaborateurExterne $collaborateurExterne)
    {
        $collaborateurExterne->update($this->valider($request, $collaborateurExterne->id));

        return response()->json($collaborateurExterne);
    }

    public function destroy(CollaborateurExterne $collaborateurExterne)
    {
        $collaborateurExterne->delete();

        return response()->json(null, 204);
    }

    /** Active (ou réactive) l'accès portail : génère un mot de passe
     * temporaire et l'envoie par email — même principe que pour un client. */
    public function activer(Request $request, CollaborateurExterne $collaborateurExterne)
    {
        $motDePasse = Str::password(12, symbols: false);

        $collaborateurExterne->update([
            'password' => Hash::make($motDePasse),
            'portail_active_le' => now(),
            'doit_changer_mot_de_passe' => true,
        ]);

        Mail::to($collaborateurExterne->email)->send(new CollaborateurActivationMail($collaborateurExterne, $motDePasse));

        return response()->json(['message' => 'Accès au portail activé, identifiants envoyés par email.']);
    }

    /** Liste les collaborateurs liés à un dossier précis. */
    public function pourDossier(Request $request, Dossier $dossier)
    {
        abort_unless($request->user()->can('view', $dossier), 403, "Ce dossier ne vous est pas assigné.");

        return response()->json($dossier->collaborateursExternes()->orderBy('nom')->get());
    }

    /** Lie un collaborateur déjà existant à ce dossier. */
    public function lier(Request $request, Dossier $dossier, CollaborateurExterne $collaborateurExterne)
    {
        abort_unless($request->user()->can('view', $dossier), 403, "Ce dossier ne vous est pas assigné.");

        $dossier->collaborateursExternes()->syncWithoutDetaching([$collaborateurExterne->id]);

        return response()->json($dossier->collaborateursExternes()->orderBy('nom')->get(), 201);
    }

    /** Crée un nouveau collaborateur et le lie directement à ce dossier. */
    public function creerEtLier(Request $request, Dossier $dossier)
    {
        abort_unless($request->user()->can('view', $dossier), 403, "Ce dossier ne vous est pas assigné.");

        $collaborateur = CollaborateurExterne::create($this->valider($request));
        $dossier->collaborateursExternes()->attach($collaborateur->id);

        return response()->json($collaborateur, 201);
    }

    public function delier(Request $request, Dossier $dossier, CollaborateurExterne $collaborateurExterne)
    {
        abort_unless($request->user()->can('view', $dossier), 403, "Ce dossier ne vous est pas assigné.");

        $dossier->collaborateursExternes()->detach($collaborateurExterne->id);

        return response()->json(null, 204);
    }

    private function valider(Request $request, ?int $ignorerId = null): array
    {
        return $request->validate([
            'nom' => 'required|string|max:255',
            'email' => 'required|email|unique:collaborateurs_externes,email' . ($ignorerId ? ",{$ignorerId}" : ''),
            'organisation' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:50',
        ]);
    }
}
