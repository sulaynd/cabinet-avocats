<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ReinitialiserMotDePasseMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MotDePasseOublieController extends Controller
{
    /** Demande d'envoi du lien de réinitialisation. Répond toujours pareil,
     * que l'email existe ou non, pour ne jamais révéler quels emails sont
     * enregistrés dans le système. */
    public function demander(Request $request)
    {
        $data = $request->validate(['email' => 'required|email']);

        $user = User::where('email', $data['email'])->first();

        if ($user) {
            $token = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                ['token' => Hash::make($token), 'created_at' => now()->toDateTimeString()]
            );

            Mail::to($user->email)->send(new ReinitialiserMotDePasseMail($user->name, $token, $data['email']));
        }

        return response()->json([
            'message' => "Si un compte existe avec cet email, un lien de réinitialisation vient d'être envoyé.",
        ]);
    }

    /** Confirme le nouveau mot de passe à partir du jeton reçu par email. */
    public function reinitialiser(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => ['required', 'min:8'],
        ]);

        $enregistrement = DB::table('password_reset_tokens')->where('email', $data['email'])->first();

        if (! $enregistrement || ! Hash::check($data['token'], $enregistrement->token)) {
            return response()->json(['message' => 'Ce lien de réinitialisation est invalide.'], 422);
        }

        // Un lien de réinitialisation n'est valide qu'une heure — au-delà, on
        // exige d'en redemander un nouveau plutôt que de risquer d'accepter un
        // jeton ancien potentiellement compromis.
        if (now()->diffInMinutes(\Carbon\Carbon::parse($enregistrement->created_at), absolute: true) > 60) {
            DB::table('password_reset_tokens')->where('email', $data['email'])->delete();

            return response()->json(['message' => 'Ce lien de réinitialisation a expiré, merci d\'en redemander un nouveau.'], 422);
        }

        $user = User::where('email', $data['email'])->firstOrFail();
        $user->update(['password' => Hash::make($data['password']), 'doit_changer_mot_de_passe' => false]);

        DB::table('password_reset_tokens')->where('email', $data['email'])->delete();

        return response()->json(['message' => 'Mot de passe réinitialisé avec succès.']);
    }
}
