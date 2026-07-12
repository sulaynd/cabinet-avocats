<?php

namespace App\Policies;

use App\Models\Dossier;
use App\Models\User;

/**
 * Un admin a un accès total. Un avocat/assistant n'a accès (lecture ET écriture)
 * qu'aux dossiers dont il est avocat responsable OU assistant traitant.
 * Réutilisée pour les sous-ressources d'un dossier (échéances, documents,
 * communications, temps passé, factures) : on autorise toujours contre le
 * dossier PARENT, jamais contre la sous-ressource elle-même.
 */
class DossierPolicy
{
    public function view(User $user, Dossier $dossier): bool
    {
        return $user->role === 'admin' || $user->estTraitantDe($dossier);
    }

    public function update(User $user, Dossier $dossier): bool
    {
        return $this->view($user, $dossier);
    }

    /**
     * La suppression d'un dossier reste volontairement réservée à l'admin :
     * une action destructive et rare, qui ne doit pas dépendre de l'assignation
     * du moment (un dossier réassigné ne devient pas "supprimable" par son
     * ancien titulaire, ni par le nouveau sans validation du cabinet).
     */
    public function delete(User $user, Dossier $dossier): bool
    {
        return $user->role === 'admin';
    }

    /** Seul un admin peut modifier l'avocat responsable / l'assistant traitant d'un dossier. */
    public function assigner(User $user, Dossier $dossier): bool
    {
        return $user->role === 'admin';
    }
}
