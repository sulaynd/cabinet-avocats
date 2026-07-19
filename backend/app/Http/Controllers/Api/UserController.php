<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->search, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })
            ->when($request->role, fn ($q, $role) => str_contains($role, ',')
                ? $q->whereIn('role', explode(',', $role))
                : $q->where('role', $role))
            ->orderBy('name')
            ->paginate($request->per_page ?? 20);

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', Password::min(8)],
            'role' => ['required', Rule::in(['admin', 'avocat', 'assistant', 'stagiaire'])],
            'phone' => 'nullable|string|max:30',
            'taux_horaire_defaut' => 'nullable|numeric|min:0',
            'specialites' => 'nullable|array',
            'specialites.*' => 'string',
            'afficher_equipe_publique' => 'boolean',
            'titre_public' => 'nullable|string|max:255',
            'bio_publique' => 'nullable|string',
            'ordre_equipe' => 'nullable|integer|min:0',
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['doit_changer_mot_de_passe'] = true;

        $user = User::create($data);

        return response()->json($user, 201);
    }

    public function show(User $user)
    {
        return response()->json($user->loadCount('dossiers'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'string|max:255',
            'email' => ['email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', Password::min(8)],
            'role' => [Rule::in(['admin', 'avocat', 'assistant', 'stagiaire'])],
            'phone' => 'nullable|string|max:30',
            'taux_horaire_defaut' => 'nullable|numeric|min:0',
            'specialites' => 'nullable|array',
            'specialites.*' => 'string',
            'afficher_equipe_publique' => 'boolean',
            'titre_public' => 'nullable|string|max:255',
            'bio_publique' => 'nullable|string',
            'ordre_equipe' => 'nullable|integer|min:0',
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
            $data['doit_changer_mot_de_passe'] = true;
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json($user);
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer votre propre compte.'], 422);
        }

        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return response()->json(['message' => 'Impossible de supprimer le dernier administrateur.'], 422);
        }

        if ($user->dossiers()->exists()) {
            return response()->json(['message' => 'Cet utilisateur est avocat responsable de dossiers actifs ; réaffectez-les avant suppression.'], 422);
        }

        $user->delete();

        return response()->json(null, 204);
    }

    /** Téléverse (ou remplace) la photo de présentation publique d'un utilisateur — disque PUBLIC (photo affichée sans authentification sur la page d'accueil). */
    public function televerserPhoto(Request $request, User $user)
    {
        $request->validate(['photo' => 'required|image|max:5120']);

        if ($user->photo_chemin) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->photo_chemin);
        }

        $chemin = $request->file('photo')->store('equipe', 'public');
        $user->update(['photo_chemin' => $chemin]);

        return response()->json($user);
    }
}
