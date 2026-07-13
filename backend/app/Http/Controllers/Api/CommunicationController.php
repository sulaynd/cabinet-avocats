<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Communication;
use App\Models\Dossier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommunicationController extends Controller
{
    public function index(Request $request, Dossier $dossier)
    {
        abort_unless($request->user()->can('view', $dossier), 403, "Ce dossier ne vous est pas assigné.");
        abort_if($request->user()->estStagiaire(), 403, "Les communications avec les clients ne sont pas accessibles aux stagiaires, par confidentialité.");

        return response()->json($dossier->communications()->with('user')->get());
    }

    public function store(Request $request, Dossier $dossier)
    {
        abort_unless($request->user()->can('view', $dossier), 403, "Ce dossier ne vous est pas assigné.");
        abort_if($request->user()->estStagiaire(), 403, "Les communications avec les clients ne sont pas accessibles aux stagiaires, par confidentialité.");

        $data = $request->validate([
            'type' => ['required', Rule::in(['appel', 'email', 'courrier', 'reunion', 'note'])],
            'objet' => 'required|string|max:255',
            'contenu' => 'nullable|string',
            'date_communication' => 'nullable|date',
        ]);

        $communication = $dossier->communications()->create([
            ...$data,
            'user_id' => $request->user()->id,
            'date_communication' => $data['date_communication'] ?? now(),
        ]);

        return response()->json($communication->load('user'), 201);
    }

    public function update(Request $request, Communication $communication)
    {
        abort_unless($request->user()->can('view', $communication->dossier), 403, "Ce dossier ne vous est pas assigné.");
        abort_if($request->user()->estStagiaire(), 403, "Les communications avec les clients ne sont pas accessibles aux stagiaires, par confidentialité.");

        $data = $request->validate([
            'type' => [Rule::in(['appel', 'email', 'courrier', 'reunion', 'note'])],
            'objet' => 'string|max:255',
            'contenu' => 'nullable|string',
            'date_communication' => 'date',
        ]);

        $communication->update($data);

        return response()->json($communication->load('user'));
    }

    public function destroy(Request $request, Communication $communication)
    {
        abort_unless($request->user()->can('view', $communication->dossier), 403, "Ce dossier ne vous est pas assigné.");
        abort_if($request->user()->estStagiaire(), 403, "Les communications avec les clients ne sont pas accessibles aux stagiaires, par confidentialité.");

        $communication->delete();

        return response()->json(null, 204);
    }
}
