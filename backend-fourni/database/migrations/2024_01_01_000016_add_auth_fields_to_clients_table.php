<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Accès au portail client (facultatif : un client n'a pas forcément de compte).
            $table->string('password')->nullable()->after('notes');
            $table->timestamp('portail_active_le')->nullable()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['password', 'portail_active_le']);
        });
    }
};
