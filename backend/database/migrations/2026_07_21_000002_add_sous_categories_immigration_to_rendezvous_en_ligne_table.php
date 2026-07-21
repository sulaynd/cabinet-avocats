<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rendezvous_en_ligne', function (Blueprint $table) {
            $table->json('sous_categories_immigration')->nullable()->after('type_affaire');
        });
    }

    public function down(): void
    {
        Schema::table('rendezvous_en_ligne', function (Blueprint $table) {
            $table->dropColumn('sous_categories_immigration');
        });
    }
};
