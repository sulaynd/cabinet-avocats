<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dossier;
use App\Services\EnvoiQuestionnaireAccueil;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DossierController extends Controller
{
    /**
     * Suggère l'avocat à assigner à un nouveau dossier : priorité aux
     * avocats dont les spécialités déclarées incluent ce type d'affaire
     * (sinon tous les avocats sont éligibles), puis départage par la charge
     * de travail la plus faible (nombre de dossiers actuellement ouverts,
     * indicateur plus stable que le temps non facturé). Reste une simple
     * suggestion, modifiable librement par l'admin avant l'enregistrement.
     */
    public function suggererAvocat(Request $request)
    {
        $typeAffaire = $request->query('type_affaire');

        $avocats = \App\Models\User::where('role', 'avocat')->get();

        $specialises = $typeAffaire
            ? $avocats->filter(fn ($a) => in_array($typeAffaire, $a->specialites ?? []))
            : collect();

        $candidats = $specialises->isNotEmpty() ? $specialises : $avocats;

        if ($candidats->isEmpty()) {
            return response()->json(['avocat_id' => null, 'raison' => "Aucun avocat disponible."]);
        }

        $statutsActifs = ['ouvert', 'en_cours', 'en_attente'];

        $meilleur = $candidats
            ->map(fn ($a) => [
                'id' => $a->id,
                'nom' => $a->name,
                'nb_dossiers_ouverts' => Dossier::where('avocat_id', $a->id)->whereIn('statut', $statutsActifs)->count(),
                'specialise' => $specialises->contains('id', $a->id),
            ])
            ->sortBy('nb_dossiers_ouverts')
            ->first();

        return response()->json([
            'avocat_id' => $meilleur['id'],
            'nom' => $meilleur['nom'],
            'raison' => $meilleur['specialise']
                ? "Spécialisé dans ce type d'affaire, {$meilleur['nb_dossiers_ouverts']} dossier(s) ouvert(s) actuellement."
                : "Aucun avocat spécialisé trouvé — suggestion basée sur la charge de travail ({$meilleur['nb_dossiers_ouverts']} dossier(s) ouvert(s)).",
        ]);
    }

    public function index(Request $request)
    {
        $dossiers = Dossier::query()
            ->with(['client', 'avocat', 'assistant', 'stagiaire'])
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
                $q->where(fn ($sous) => $sous->where('avocat_id', $id)->orWhere('assistant_id', $id)->orWhere('stagiaire_id', $id));
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
            'stagiaire_id' => 'nullable|exists:users,id',
            'titre' => 'required|string|max:255',
            'type_affaire' => [Rule::in(['immigration_mobilite', 'recrutement_international', 'cooperation_internationale', 'developpement_international', 'action_humanitaire', 'conseils_strategiques', 'autre'])],
            'statut' => [Rule::in(['ouvert', 'en_cours', 'en_attente', 'clos', 'archive'])],
            'mode_facturation' => [Rule::in(['horaire', 'forfait'])],
            'taux_horaire' => 'nullable|numeric|min:0',
            'montant_forfait' => 'required_if:mode_facturation,forfait|nullable|numeric|min:0.01',
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
        } elseif ($request->user()->role === 'stagiaire') {
            $data['stagiaire_id'] = $request->user()->id;
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

        return response()->json($dossier->load(['client', 'avocat', 'assistant', 'stagiaire']), 201);
    }

    public function show(Request $request, Dossier $dossier)
    {
        abort_unless($request->user()->can('view', $dossier), 403, "Ce dossier ne vous est pas assigné.");

        return response()->json($dossier->load(['client', 'avocat', 'assistant', 'stagiaire', 'echeances', 'documents', 'factures', 'intervenants', 'debourses.user']));
    }

    public function update(Request $request, Dossier $dossier)
    {
        abort_unless($request->user()->can('update', $dossier), 403, "Ce dossier ne vous est pas assigné.");

        $data = $request->validate([
            'client_id' => 'exists:clients,id',
            'avocat_id' => 'exists:users,id',
            'assistant_id' => 'nullable|exists:users,id',
            'stagiaire_id' => 'nullable|exists:users,id',
            'titre' => 'string|max:255',
            'type_affaire' => [Rule::in(['immigration_mobilite', 'recrutement_international', 'cooperation_internationale', 'developpement_international', 'action_humanitaire', 'conseils_strategiques', 'autre'])],
            'statut' => [Rule::in(['ouvert', 'en_cours', 'en_attente', 'clos', 'archive'])],
            'mode_facturation' => [Rule::in(['horaire', 'forfait'])],
            'taux_horaire' => 'nullable|numeric|min:0',
            'montant_forfait' => 'required_if:mode_facturation,forfait|nullable|numeric|min:0.01',
            'facturation_periodique' => 'boolean',
            'frequence_facturation' => ['nullable', Rule::in(['hebdomadaire', 'mensuelle'])],
            'facturer_a_cloture' => 'boolean',
            'date_cloture' => 'nullable|date',
            'description' => 'nullable|string',
        ]);

        // La date d'ouverture est figée dès la création du dossier, pour tout
        // le monde y compris l'admin — elle sert de repère fiable (délais,
        // rapports) qui ne doit jamais pouvoir être corrigée après coup.

        // Le mode "forfait" exige un montant — on vérifie contre l'état final
        // (nouvelle valeur envoyée, sinon celle déjà en base), pour couvrir le
        // cas d'une modification partielle qui ne renvoie pas mode_facturation.
        $modeEffectif = $data['mode_facturation'] ?? $dossier->mode_facturation;
        $montantEffectif = array_key_exists('montant_forfait', $data) ? $data['montant_forfait'] : $dossier->montant_forfait;
        abort_if(
            $modeEffectif === 'forfait' && empty($montantEffectif),
            422,
            "Le montant du forfait est obligatoire lorsque le mode de facturation est 'Forfait'."
        );

        // Un stagiaire peut travailler sur un dossier au quotidien, mais la
        // décision de le clôturer ou de l'archiver reste réservée à l'avocat
        // responsable ou à l'admin.
        if ($request->user()->estStagiaire() && isset($data['statut']) && in_array($data['statut'], ['clos', 'archive'])) {
            abort(403, "En tant que stagiaire, vous ne pouvez pas clôturer ou archiver un dossier — demandez à l'avocat responsable.");
        }

        // Un stagiaire a un accès en lecture seule aux factures ; le laisser
        // modifier les réglages de facturation automatique (mode, taux,
        // périodicité, facturation à la clôture) reviendrait à contourner
        // cette restriction par la bande.
        if ($request->user()->estStagiaire()) {
            unset(
                $data['mode_facturation'], $data['taux_horaire'], $data['montant_forfait'],
                $data['facturation_periodique'], $data['frequence_facturation'], $data['facturer_a_cloture']
            );
        }

        // Changer l'avocat responsable ou l'assistant traitant d'un dossier est une
        // action d'assignation réservée à l'admin (voir aussi assigner() ci-dessous) ;
        // un avocat/assistant qui modifie "son" dossier ne peut pas se le retirer
        // ni se l'attribuer à lui-même via ce endpoint générique.
        if ($request->user()->role !== 'admin') {
            unset($data['avocat_id'], $data['assistant_id'], $data['stagiaire_id']);
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

        return response()->json($dossier->load(['client', 'avocat', 'assistant', 'stagiaire']));
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
            'stagiaire_id' => 'nullable|exists:users,id',
        ]);

        $dossier->update($data);

        return response()->json($dossier->load(['client', 'avocat', 'assistant', 'stagiaire']));
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

        // Basé sur le MAXIMUM du suffixe existant, pas un simple compte — un
        // dossier supprimé ferait autrement retomber le compte en arrière et
        // regénérer une référence déjà utilisée (contrainte d'unicité en
        // base). Extraction faite en PHP plutôt qu'en SQL brut, pour rester
        // portable entre MySQL (production) et SQLite (tests).
        $dernierSuffixe = Dossier::where('reference', 'like', "DOS-{$annee}-%")
            ->get()
            ->map(fn ($d) => (int) substr($d->reference, -4))
            ->max() ?? 0;

        return sprintf('DOS-%d-%04d', $annee, $dernierSuffixe + 1);
    }
}
