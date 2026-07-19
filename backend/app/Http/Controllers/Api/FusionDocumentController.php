<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CabinetSetting;
use App\Models\Document;
use App\Models\Dossier;
use App\Models\ModeleDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;

class FusionDocumentController extends Controller
{
    private const LIBELLES_TYPE_AFFAIRE = [
        'immigration_mobilite' => 'Immigration & mobilité internationale',
        'recrutement_international' => 'Recrutement international',
        'cooperation_internationale' => 'Coopération internationale',
        'developpement_international' => 'Développement international',
        'action_humanitaire' => 'Action humanitaire',
        'conseils_strategiques' => 'Services-conseils stratégiques',
        'autre' => 'Autre',
    ];

    /**
     * Fusionne un modèle Word avec les données du dossier : le document
     * généré est à la fois téléchargé immédiatement ET ajouté aux documents
     * du dossier, pour garder une trace de tout ce qui a été produit.
     */
    public function generer(Request $request, Dossier $dossier)
    {
        abort_unless($request->user()->can('view', $dossier), 403, "Ce dossier ne vous est pas assigné.");
        abort_if($request->user()->estStagiaire(), 403, "En tant que stagiaire, vous ne pouvez pas générer de document.");

        $data = $request->validate(['modele_id' => 'required|exists:modeles_documents,id']);
        $modele = ModeleDocument::findOrFail($data['modele_id']);

        $cheminModele = Storage::disk('local')->path($modele->fichier_chemin);
        $processeur = new TemplateProcessor($cheminModele);

        foreach ($this->valeursDeFusion($dossier) as $variable => $valeur) {
            // setValue lève une exception si la variable n'existe pas dans le
            // modèle — on l'ignore volontairement, un même modèle n'utilise
            // jamais toutes les variables disponibles.
            try {
                $processeur->setValue($variable, htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8'));
            } catch (\Exception $e) {
                // Variable absente du modèle, rien à faire.
            }
        }

        $nomFichier = 'Document généré — ' . $dossier->reference . ' — ' . now()->format('Y-m-d_His') . '.docx';
        $cheminRelatif = "dossiers/{$dossier->id}/{$nomFichier}";
        $cheminComplet = Storage::disk('local')->path($cheminRelatif);

        // S'assure que le dossier de destination existe avant l'écriture.
        Storage::disk('local')->makeDirectory("dossiers/{$dossier->id}");
        $processeur->saveAs($cheminComplet);

        $document = Document::create([
            'dossier_id' => $dossier->id,
            'nom_original' => $nomFichier,
            'chemin' => $cheminRelatif,
            'type' => 'contrat',
            'taille' => filesize($cheminComplet),
            'uploaded_by' => $request->user()->id,
        ]);

        return response()->download($cheminComplet, $nomFichier)->deleteFileAfterSend(false);
    }

    private function valeursDeFusion(Dossier $dossier): array
    {
        $cabinet = CabinetSetting::instance();
        $client = $dossier->client;
        $avocatAdverse = $dossier->intervenants->firstWhere('fonction', 'avocat_adverse');

        return [
            'client_nom' => $client->nom_complet ?? '',
            'client_type' => $client->type === 'entreprise' ? 'Entreprise' : 'Particulier',
            'client_adresse' => $client->adresse ?? '',
            'client_telephone' => $client->telephone ?? '',
            'client_email' => $client->email ?? '',
            'dossier_reference' => $dossier->reference,
            'dossier_titre' => $dossier->titre,
            'dossier_type_affaire' => self::LIBELLES_TYPE_AFFAIRE[$dossier->type_affaire] ?? $dossier->type_affaire,
            'dossier_date_ouverture' => $dossier->date_ouverture?->translatedFormat('d F Y') ?? '',
            'avocat_nom' => $dossier->avocat->name ?? '',
            'avocat_adverse_nom' => $avocatAdverse->nom ?? '',
            'cabinet_nom' => $cabinet->nom,
            'cabinet_adresse' => $cabinet->adresse ?? '',
            'cabinet_telephone' => $cabinet->telephone ?? '',
            'date_jour' => now()->translatedFormat('d F Y'),
        ];
    }
}
