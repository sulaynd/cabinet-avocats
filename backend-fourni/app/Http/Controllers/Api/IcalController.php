<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CabinetSetting;
use App\Models\Echeance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class IcalController extends Controller
{
    /**
     * Flux iCal PERSONNEL d'un utilisateur (audiences, délais, RDV des dossiers
     * dont il est avocat responsable OU assistant traitant). Route publique
     * (pas de Sanctum) car les applications d'agenda (Google Calendar, Outlook...)
     * ne savent pas envoyer de header Authorization — la sécurité repose sur le
     * caractère secret et régénérable du jeton contenu dans l'URL, comme le fait
     * tout logiciel d'agenda partagé (Google, Outlook, etc.).
     */
    public function personnel(string $token)
    {
        $utilisateur = User::where('ical_token', $token)->first();

        if (! $utilisateur) {
            abort(404, 'Lien de calendrier invalide ou expiré.');
        }

        $echeances = Echeance::query()
            ->whereHas('dossier', function ($q) use ($utilisateur) {
                $q->where('avocat_id', $utilisateur->id)->orWhere('assistant_id', $utilisateur->id);
            })
            ->where('statut', '!=', 'annulee')
            ->with('dossier')
            ->get();

        $ics = $this->construireIcs("Agenda de {$utilisateur->name} — Lambert & Associés", $echeances);

        return $this->reponseIcs($ics, 'agenda-' . str($utilisateur->name)->slug() . '.ics');
    }

    /**
     * Flux iCal COLLECTIF de tout le cabinet (toutes les échéances, tous
     * intervenants confondus) — destiné à l'agenda partagé de l'équipe.
     * Protégé par le jeton unique stocké dans cabinet_settings.
     */
    public function equipe(string $token)
    {
        $parametres = CabinetSetting::instance();

        if (! hash_equals($parametres->ical_token_equipe, $token)) {
            abort(404, 'Lien de calendrier invalide ou expiré.');
        }

        $echeances = Echeance::query()
            ->where('statut', '!=', 'annulee')
            ->with('dossier.avocat', 'dossier.assistant')
            ->get();

        $ics = $this->construireIcs('Agenda collectif — Lambert & Associés', $echeances);

        return $this->reponseIcs($ics, 'agenda-cabinet.ics');
    }

    /**
     * Renvoie au frontend les URLs d'abonnement à afficher/copier dans les
     * paramètres du compte : son lien personnel, et (si admin) le lien d'équipe.
     */
    public function mesLiens(Request $request)
    {
        $utilisateur = $request->user();

        if (! $utilisateur->ical_token) {
            $utilisateur->regenererTokenIcal();
        }

        $liens = [
            'personnel' => url("/api/ical/perso/{$utilisateur->ical_token}.ics"),
        ];

        if ($utilisateur->isAdmin()) {
            $liens['equipe'] = url('/api/ical/equipe/' . CabinetSetting::instance()->ical_token_equipe . '.ics');
        }

        return response()->json($liens);
    }

    public function regenererPersonnel(Request $request)
    {
        $token = $request->user()->regenererTokenIcal();

        return response()->json(['personnel' => url("/api/ical/perso/{$token}.ics")]);
    }

    public function regenererEquipe(Request $request)
    {
        $token = CabinetSetting::regenererTokenEquipe();

        return response()->json(['equipe' => url("/api/ical/equipe/{$token}.ics")]);
    }

    /** Construit le texte iCalendar (RFC 5545) à partir d'une collection d'échéances. */
    private function construireIcs(string $nomCalendrier, Collection $echeances): string
    {
        $lignes = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Lambert & Associes//Cabinet Avocats//FR',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:' . $this->echapper($nomCalendrier),
            'X-WR-TIMEZONE:Europe/Paris',
            // Les logiciels d'agenda relisent le flux périodiquement : cet intervalle
            // suggéré (1h) permet de refléter assez vite les ajouts/modifications/
            // annulations d'échéances faits dans le cabinet.
            'REFRESH-INTERVAL;VALUE=DURATION:PT1H',
            'X-PUBLISHED-TTL:PT1H',
        ];

        foreach ($echeances as $echeance) {
            $debut = $echeance->date_heure->clone();
            $dureeMinutes = $echeance->type === 'delai_procedural' ? 30 : 60;
            $fin = $debut->clone()->addMinutes($dureeMinutes);

            $dossier = $echeance->dossier;
            $intervenants = collect([$dossier?->avocat?->name, $dossier?->assistant?->name])
                ->filter()
                ->implode(' / ');

            $resume = ($echeance->type === 'delai_procedural' ? '[Délai] ' : '')
                . $echeance->titre
                . ($dossier ? " — {$dossier->reference}" : '');

            $descriptionParts = array_filter([
                $dossier ? "Dossier : {$dossier->reference} — {$dossier->titre}" : null,
                $intervenants ? "Intervenant(s) : {$intervenants}" : null,
                "Type : {$echeance->type}",
            ]);

            $lignes[] = 'BEGIN:VEVENT';
            $lignes[] = 'UID:echeance-' . $echeance->id . '@lambert-associes.fr';
            $lignes[] = 'DTSTAMP:' . now()->utc()->format('Ymd\THis\Z');
            $lignes[] = 'DTSTART:' . $debut->utc()->format('Ymd\THis\Z');
            $lignes[] = 'DTEND:' . $fin->utc()->format('Ymd\THis\Z');
            $lignes[] = 'SUMMARY:' . $this->echapper($resume);
            if ($echeance->lieu) {
                $lignes[] = 'LOCATION:' . $this->echapper($echeance->lieu);
            }
            $lignes[] = 'DESCRIPTION:' . $this->echapper(implode('\n', $descriptionParts));
            $lignes[] = 'STATUS:' . ($echeance->statut === 'realisee' ? 'CONFIRMED' : 'TENTATIVE');
            // Rappel intégré au fichier iCal lui-même, en plus des rappels internes
            // à l'application, pour que l'alerte suive l'utilisateur dans son
            // client de messagerie/agenda habituel.
            if ($echeance->rappel_avant) {
                $lignes[] = 'BEGIN:VALARM';
                $lignes[] = 'ACTION:DISPLAY';
                $lignes[] = 'DESCRIPTION:' . $this->echapper($resume);
                $lignes[] = 'TRIGGER:-PT' . $echeance->rappel_avant . 'M';
                $lignes[] = 'END:VALARM';
            }
            $lignes[] = 'END:VEVENT';
        }

        $lignes[] = 'END:VCALENDAR';

        // RFC 5545 impose des fins de ligne CRLF.
        return implode("\r\n", $lignes) . "\r\n";
    }

    private function echapper(string $texte): string
    {
        return str_replace(["\\", ',', ';'], ['\\\\', '\\,', '\\;'], $texte);
    }

    private function reponseIcs(string $ics, string $nomFichier): Response
    {
        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => "inline; filename=\"{$nomFichier}\"",
        ]);
    }
}
