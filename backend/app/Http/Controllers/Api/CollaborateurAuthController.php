<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\CollaborateurActivationMail;
use App\Mail\ReinitialiserMotDePasseCollaborateurMail;
use App\Models\CollaborateurExterne;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Authentification du portail collaborateur externe : totalement
 * indépendante des comptes du cabinet et du portail client (voir
 * PortailAuthController pour l'explication complète du mécanisme Sanctum
 * polymorphe utilisé).
 */
class CollaborateurAuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $collaborateur = CollaborateurExterne::where('email', $data['email'])->first();

        if (! $collaborateur || ! $collaborateur->possedeAccesPortail() || ! Hash::check($data['password'], $collaborateur->password)) {
            return response()->json(['message' => 'Identifiants invalides.'], 401);
        }

        $token = $collaborateur->createToken('portail-collaborateur')->plainTextToken;

        return response()->json(['collaborateur' => $collaborateur->makeHidden('password'), 'token' => $token]);
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

    public function changerMotDePasse(Request $request)
    {
        $data = $request->validate(['password' => ['required', 'min:8']]);

        $collaborateur = $request->user();
        $collaborateur->update(['password' => Hash::make($data['password']), 'doit_changer_mot_de_passe' => false]);

        return response()->json(['message' => 'Mot de passe mis à jour.']);
    }

    public function demanderReinitialisation(Request $request)
    {
        $data = $request->validate(['email' => 'required|email']);

        $collaborateur = CollaborateurExterne::where('email', $data['email'])->first();

        if ($collaborateur) {
            $token = Str::random(64);

            DB::table('collaborateur_password_reset_tokens')->updateOrInsert(
                ['email' => $collaborateur->email],
                ['token' => Hash::make($token), 'created_at' => now()->toDateTimeString()]
            );

            Mail::to($collaborateur->email)->send(new ReinitialiserMotDePasseCollaborateurMail($collaborateur->nom, $token, $data['email']));
        }

        return response()->json([
            'message' => "Si un compte existe avec cet email, un lien de réinitialisation vient d'être envoyé.",
        ]);
    }

    public function reinitialiser(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => ['required', 'min:8'],
        ]);

        $enregistrement = DB::table('collaborateur_password_reset_tokens')->where('email', $data['email'])->first();

        if (! $enregistrement || ! Hash::check($data['token'], $enregistrement->token)) {
            return response()->json(['message' => 'Ce lien de réinitialisation est invalide.'], 422);
        }

        if (now()->diffInMinutes(\Carbon\Carbon::parse($enregistrement->created_at), absolute: true) > 60) {
            DB::table('collaborateur_password_reset_tokens')->where('email', $data['email'])->delete();

            return response()->json(['message' => 'Ce lien de réinitialisation a expiré, merci d\'en redemander un nouveau.'], 422);
        }

        $collaborateur = CollaborateurExterne::where('email', $data['email'])->firstOrFail();
        $collaborateur->update(['password' => Hash::make($data['password']), 'doit_changer_mot_de_passe' => false]);

        DB::table('collaborateur_password_reset_tokens')->where('email', $data['email'])->delete();

        return response()->json(['message' => 'Mot de passe réinitialisé avec succès.']);
    }
}
