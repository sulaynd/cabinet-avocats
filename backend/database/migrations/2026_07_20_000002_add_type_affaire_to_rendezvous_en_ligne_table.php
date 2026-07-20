<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rendezvous_en_ligne', function (Blueprint $table) {
            // Choisi par le client à la prise de rendez-vous — aide le cabinet
            // à assigner l'avocat le plus approprié à la confirmation, et
            // pré-remplit le type d'affaire si un dossier est créé ensuite.
            $table->enum('type_affaire', [
                'immigration_mobilite', 'recrutement_international', 'cooperation_internationale',
                'developpement_international', 'action_humanitaire', 'conseils_strategiques', 'autre',
            ])->nullable()->after('motif');
        });
    }

    public function down(): void
    {
        Schema::table('rendezvous_en_ligne', function (Blueprint $table) {
            $table->dropColumn('type_affaire');
        });
    }
};
