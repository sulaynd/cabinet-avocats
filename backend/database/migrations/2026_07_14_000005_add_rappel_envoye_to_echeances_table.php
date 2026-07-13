<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('echeances', function (Blueprint $table) {
            $table->boolean('rappel_envoye')->default(false)->after('statut');
        });
    }

    public function down(): void
    {
        Schema::table('echeances', function (Blueprint $table) {
            $table->dropColumn('rappel_envoye');
        });
    }
};
