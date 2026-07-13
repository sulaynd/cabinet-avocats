<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PortailActivationMail;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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
     * lui-même) : génère un mot de passe temporaire sécurisé et lui envoie ses
     * identifiants par email. Le mot de passe en clair n'existe que le temps de
     * cette requête (jamais stocké ni renvoyé dans la réponse JSON) — seul le
     * client le reçoit, par email.
     */
    public function activerPourClient(Request $request, Client $client)
    {
        abort_unless($client->email, 422, "Ce client n'a pas d'adresse email renseignée — impossible de lui envoyer ses identifiants.");

        $motDePasse = Str::password(12, symbols: false);

        $client->update(['password' => Hash::make($motDePasse), 'portail_active_le' => now(), 'doit_changer_mot_de_passe' => true]);

        Mail::to($client->email)->send(new PortailActivationMail($client, $motDePasse));

        return response()->json(['message' => 'Accès au portail activé, identifiants envoyés par email.']);
    }

    /** Le client change son propre mot de passe (obligatoire après un mot de passe temporaire, ou à sa demande). */
    public function changerMotDePasse(Request $request)
    {
        $data = $request->validate(['password' => ['required', 'min:8']]);

        $client = $request->user();
        $client->update(['password' => Hash::make($data['password']), 'doit_changer_mot_de_passe' => false]);

        return response()->json(['message' => 'Mot de passe mis à jour.']);
    }

    /** Demande d'envoi du lien de réinitialisation côté client. Répond
     * toujours pareil, que l'email existe ou non. */
    public function demanderReinitialisation(Request $request)
    {
        $data = $request->validate(['email' => 'required|email']);

        $client = Client::where('email', $data['email'])->first();

        if ($client) {
            $token = \Illuminate\Support\Str::random(64);

            \Illuminate\Support\Facades\DB::table('client_password_reset_tokens')->updateOrInsert(
                ['email' => $client->email],
                ['token' => Hash::make($token), 'created_at' => now()->toDateTimeString()]
            );

            Mail::to($client->email)->send(new \App\Mail\ReinitialiserMotDePassePortailMail($client->nom_complet, $token, $data['email']));
        }

        return response()->json([
            'message' => "Si un compte existe avec cet email, un lien de réinitialisation vient d'être envoyé.",
        ]);
    }

    /** Confirme le nouveau mot de passe côté client à partir du jeton reçu par email. */
    public function reinitialiser(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => ['required', 'min:8'],
        ]);

        $enregistrement = \Illuminate\Support\Facades\DB::table('client_password_reset_tokens')->where('email', $data['email'])->first();

        if (! $enregistrement || ! Hash::check($data['token'], $enregistrement->token)) {
            return response()->json(['message' => 'Ce lien de réinitialisation est invalide.'], 422);
        }

        if (now()->diffInMinutes(\Carbon\Carbon::parse($enregistrement->created_at), absolute: true) > 60) {
            \Illuminate\Support\Facades\DB::table('client_password_reset_tokens')->where('email', $data['email'])->delete();

            return response()->json(['message' => 'Ce lien de réinitialisation a expiré, merci d\'en redemander un nouveau.'], 422);
        }

        $client = Client::where('email', $data['email'])->firstOrFail();
        $client->update(['password' => Hash::make($data['password']), 'doit_changer_mot_de_passe' => false]);

        \Illuminate\Support\Facades\DB::table('client_password_reset_tokens')->where('email', $data['email'])->delete();

        return response()->json(['message' => 'Mot de passe réinitialisé avec succès.']);
    }
}
