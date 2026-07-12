<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $clients = Client::query()
            ->when($request->search, function ($q, $search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('raison_sociale', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 20);

        return response()->json($clients);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['particulier', 'entreprise'])],
            'nom' => 'nullable|string|max:255',
            'prenom' => 'nullable|string|max:255',
            'raison_sociale' => 'nullable|string|max:255',
            'siret' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'telephone' => 'nullable|string|max:30',
            'adresse' => 'nullable|string|max:255',
            'code_postal' => 'nullable|string|max:20',
            'ville' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $client = Client::create($data);

        return response()->json($client, 201);
    }

    public function show(Client $client)
    {
        return response()->json($client->load('dossiers'));
    }

    public function update(Request $request, Client $client)
    {
        $data = $request->validate([
            'type' => [Rule::in(['particulier', 'entreprise'])],
            'nom' => 'nullable|string|max:255',
            'prenom' => 'nullable|string|max:255',
            'raison_sociale' => 'nullable|string|max:255',
            'siret' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'telephone' => 'nullable|string|max:30',
            'adresse' => 'nullable|string|max:255',
            'code_postal' => 'nullable|string|max:20',
            'ville' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $client->update($data);

        return response()->json($client);
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return response()->json(null, 204);
    }

    public function dossiers(Client $client)
    {
        return response()->json($client->dossiers()->with('avocat')->paginate(20));
    }
}
