<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dossier;
use App\Services\EnvoiQuestionnaireAccueil;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DossierController extends Controller
{
    public function index(Request $request)
    {
        $dossiers = Dossier::query()
            ->with(['client', 'avocat', 'assistant'])
            // Un avocat/assistant ne voit que les dossiers qui lui sont assignés ;
            // un admin voit tout. Voir Dossier::scopeVisiblePar().
            ->visiblePar($request->user())
            ->when($request->statut, fn ($q, $statut) => $q->where('statut', $statut))
            ->when($request->avocat_id, fn ($q, $id) => $q->where('avocat_id', $id))
            ->when($request->client_id, fn ($q, $id) => $q->where('client_id', $id))
            // Filtre "traitant" (utilisé par la vue agenda) : seul un admin peut
            // l'utiliser pour regarder l'agenda d'un tiers ; pour un avocat/assistant,
            // le scope ci-dessus le restreint de toute façon à lui-même.
            ->when($request->traitant_id && $request->user()->role === 'admin', function ($q) use ($request) {
                $id = $request->traitant_id;
                $q->where(fn ($sous) => $sous->where('avocat_id', $id)->orWhere('assistant_id', $id));
            })
            ->when($request->search, function ($q, $search) {
                $q->where('titre', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%");
            })
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 20);

        return response()->json($dossiers);
    }

    public function store(Request $request)
    {
        // Un avocat s'auto-assigne automatiquement (voir plus bas) : avocat_id n'est
        // donc obligatoire à la saisie que pour un admin ou un assistant (qui doit
        // choisir sous quel avocat le dossier est ouvert).
        $avocatIdRequis = $request->user()->role !== 'avocat';

        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'avocat_id' => ($avocatIdRequis ? 'required' : 'nullable') . '|exists:users,id',
            'assistant_id' => 'nullable|exists:users,id',
            'titre' => 'required|string|max:255',
            'type_affaire' => [Rule::in(['immigration_mobilite', 'recrutement_international', 'cooperation_internationale', 'developpement_international', 'action_humanitaire', 'conseils_strategiques', 'autre'])],
            'statut' => [Rule::in(['ouvert', 'en_cours', 'en_attente', 'clos', 'archive'])],
            'mode_facturation' => [Rule::in(['horaire', 'forfait'])],
            'taux_horaire' => 'nullable|numeric|min:0',
            'montant_forfait' => 'nullable|numeric|min:0',
            'facturation_periodique' => 'boolean',
            'frequence_facturation' => ['nullable', Rule::in(['hebdomadaire', 'mensuelle'])],
            'facturer_a_cloture' => 'boolean',
            'date_ouverture' => 'nullable|date',
            'date_cloture' => 'nullable|date',
            'description' => 'nullable|string',
            'envoyer_questionnaire_accueil' => 'boolean',
        ]);

        $envoyerQuestionnaire = $data['envoyer_questionnaire_accueil'] ?? true;
        unset($data['envoyer_questionnaire_accueil']);

        // Un non-admin ne peut pas ouvrir un dossier sans y apparaître lui-même :
        // l'assignation "libre" à un tiers reste réservée à l'admin (cf. assigner()).
        if ($request->user()->role === 'avocat') {
            $data['avocat_id'] = $request->user()->id;
        } elseif ($request->user()->role === 'assistant') {
            $data['assistant_id'] = $request->user()->id;
        }

        if (empty($data['avocat_id'])) {
            return response()->json(['message' => "Un avocat responsable est requis pour ouvrir un dossier."], 422);
        }

        $data['reference'] = $this->genererReference();

        // Cas rare (création directe avec statut "clos") : mêmes règles qu'en
        // modification, voir update() ci-dessous pour l'explication complète.
        if (($data['statut'] ?? 'ouvert') === 'clos' && empty($data['date_cloture'])) {
            $data['date_cloture'] = now()->toDateString();
        }

        $dossier = Dossier::create($data);

        // Onboarding client : envoie automatiquement le questionnaire de
        // pré-consultation actif (le cas échéant) dès l'ouverture du dossier.
        if ($envoyerQuestionnaire) {
            EnvoiQuestionnaireAccueil::pourDossier($dossier);
        }

        return response()->json($dossier->load(['client', 'avocat', 'assistant']), 201);
    }

    public function show(Request $request, Dossier $dossier)
    {
        abort_unless($request->user()->can('view', $dossier), 403, "Ce dossier ne vous est pas assigné.");

        return response()->json($dossier->load(['client', 'avocat', 'assistant', 'echeances', 'documents', 'factures']));
    }

    public function update(Request $request, Dossier $dossier)
    {
        abort_unless($request->user()->can('update', $dossier), 403, "Ce dossier ne vous est pas assigné.");

        $data = $request->validate([
            'client_id' => 'exists:clients,id',
            'avocat_id' => 'exists:users,id',
            'assistant_id' => 'nullable|exists:users,id',
            'titre' => 'string|max:255',
            'type_affaire' => [Rule::in(['immigration_mobilite', 'recrutement_international', 'cooperation_internationale', 'developpement_international', 'action_humanitaire', 'conseils_strategiques', 'autre'])],
            'statut' => [Rule::in(['ouvert', 'en_cours', 'en_attente', 'clos', 'archive'])],
            'mode_facturation' => [Rule::in(['horaire', 'forfait'])],
            'taux_horaire' => 'nullable|numeric|min:0',
            'montant_forfait' => 'nullable|numeric|min:0',
            'facturation_periodique' => 'boolean',
            'frequence_facturation' => ['nullable', Rule::in(['hebdomadaire', 'mensuelle'])],
            'facturer_a_cloture' => 'boolean',
            'date_ouverture' => 'nullable|date',
            'date_cloture' => 'nullable|date',
            'description' => 'nullable|string',
        ]);

        // Un stagiaire peut travailler sur un dossier au quotidien, mais la
        // décision de le clôturer ou de l'archiver reste réservée à l'avocat
        // responsable ou à l'admin.
        if ($request->user()->estStagiaire() && isset($data['statut']) && in_array($data['statut'], ['clos', 'archive'])) {
            abort(403, "En tant que stagiaire, vous ne pouvez pas clôturer ou archiver un dossier — demandez à l'avocat responsable.");
        }

        // Changer l'avocat responsable ou l'assistant traitant d'un dossier est une
        // action d'assignation réservée à l'admin (voir aussi assigner() ci-dessous) ;
        // un avocat/assistant qui modifie "son" dossier ne peut pas se le retirer
        // ni se l'attribuer à lui-même via ce endpoint générique.
        if ($request->user()->role !== 'admin') {
            unset($data['avocat_id'], $data['assistant_id']);
        }

        $statutAvant = $dossier->statut;

        // Renseigne automatiquement la date de clôture quand le dossier passe à
        // "clos" (l'utilisateur ne la saisit jamais manuellement — aucun champ
        // dédié dans le formulaire) ; et la vide si le dossier est rouvert par
        // la suite, pour ne pas garder une date de clôture obsolète affichée.
        if (($data['statut'] ?? $dossier->statut) === 'clos' && $statutAvant !== 'clos') {
            $data['date_cloture'] = now()->toDateString();
        } elseif (($data['statut'] ?? $dossier->statut) !== 'clos' && $statutAvant === 'clos') {
            $data['date_cloture'] = null;
        }

        $dossier->update($data);

        // Déclenche automatiquement un mémoire d'honoraires (généré + envoyé par email)
        // quand le dossier vient de passer au statut "clos" et que l'option est activée.
        // Compatible avec facturation_periodique : genererPourDossier() ne prend que le
        // temps NON encore facturé (facture_id null), donc aucun double comptage même si
        // les deux options sont actives simultanément sur le même dossier.
        if ($statutAvant !== 'clos' && $dossier->statut === 'clos' && $dossier->facturer_a_cloture) {
            \App\Services\FacturationAutomatique::genererPourDossier($dossier, envoyerParEmail: true);
        }

        return response()->json($dossier->load(['client', 'avocat', 'assistant']));
    }

    /**
     * Assigne (ou réassigne) l'avocat responsable et/ou l'assistant traitant
     * d'un dossier. Action volontairement séparée de update() et réservée à
     * l'admin, pour que l'assignation des dossiers reste une décision explicite
     * du cabinet plutôt qu'un simple champ parmi d'autres.
     */
    public function assigner(Request $request, Dossier $dossier)
    {
        abort_unless($request->user()->can('assigner', $dossier), 403, "Seul un administrateur peut assigner un dossier.");
        abort_if($dossier->statut === 'clos', 422, 'Ce dossier est clos, impossible de le réassigner. Rouvrez-le d\'abord si besoin.');

        $data = $request->validate([
            'avocat_id' => 'required|exists:users,id',
            'assistant_id' => 'nullable|exists:users,id',
        ]);

        $dossier->update($data);

        return response()->json($dossier->load(['client', 'avocat', 'assistant']));
    }

    public function destroy(Request $request, Dossier $dossier)
    {
        abort_unless($request->user()->can('delete', $dossier), 403);
        abort_if(
            in_array($dossier->statut, ['clos', 'archive']),
            422,
            "Un dossier clos ou archivé ne peut pas être supprimé (obligation de conservation professionnelle). Rouvrez-le d'abord si la suppression est réellement nécessaire."
        );

        $dossier->delete();

        return response()->json(null, 204);
    }

    public function echeances(Request $request, Dossier $dossier)
    {
        abort_unless($request->user()->can('view', $dossier), 403, "Ce dossier ne vous est pas assigné.");

        return response()->json($dossier->echeances()->orderBy('date_heure')->get());
    }

    public function documents(Request $request, Dossier $dossier)
    {
        abort_unless($request->user()->can('view', $dossier), 403, "Ce dossier ne vous est pas assigné.");

        return response()->json($dossier->documents()->orderByDesc('created_at')->get());
    }

    public function factures(Request $request, Dossier $dossier)
    {
        abort_unless($request->user()->can('view', $dossier), 403, "Ce dossier ne vous est pas assigné.");

        return response()->json($dossier->factures()->orderByDesc('date_emission')->get());
    }

    private function genererReference(): string
    {
        $annee = now()->year;
        $dernier = Dossier::where('reference', 'like', "DOS-{$annee}-%")->count() + 1;

        return sprintf('DOS-%d-%04d', $annee, $dernier);
    }
}
