<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dossiers', function (Blueprint $table) {
            // Assistant(e) traitant(e) du dossier, en complément de l'avocat responsable.
            // Nullable : un dossier peut n'avoir qu'un avocat responsable, sans assistant dédié.
            $table->foreignId('assistant_id')->nullable()->after('avocat_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dossiers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assistant_id');
        });
    }
};
