<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Authentification du portail client : totalement indépendante des comptes du
 * cabinet (table users). Un client Sanctum-token est reconnu par son modèle
 * (Client) et jamais confondu avec un token d'utilisateur interne, car
 * PortailAuthMiddleware vérifie explicitement le type du modèle authentifié.
 */
class PortailAuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $client = Client::where('email', $data['email'])->first();

        if (! $client || ! $client->possedeAccesPortail() || ! Hash::check($data['password'], $client->password)) {
            return response()->json(['message' => 'Identifiants invalides.'], 401);
        }

        $token = $client->createToken('portail-client')->plainTextToken;

        return response()->json(['client' => $client->makeHidden('password'), 'token' => $token]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(null, 204);
    }

    public function moi(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Active le portail pour un client (appelé par le cabinet, pas par le client
     * lui-même) : définit un mot de passe et lui envoie ses identifiants.
     * Volontairement simple ici ; en production, envoyer un email avec un lien
     * d'activation à durée de vie limitée plutôt qu'un mot de passe en clair.
     */
    public function activerPourClient(Request $request, Client $client)
    {
        $data = $request->validate(['password' => 'required|min:8']);

        $client->update(['password' => Hash::make($data['password']), 'portail_active_le' => now()]);

        return response()->json(['message' => 'Accès au portail activé.']);
    }
}
