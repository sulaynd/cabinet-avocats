<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cabinet_settings', function (Blueprint $table) {
            $table->string('nom')->nullable()->after('id');
            $table->string('adresse')->nullable()->after('nom');
            $table->string('telephone')->nullable()->after('adresse');
            $table->string('email')->nullable()->after('telephone');
        });
    }

    public function down(): void
    {
        Schema::table('cabinet_settings', function (Blueprint $table) {
            $table->dropColumn(['nom', 'adresse', 'telephone', 'email']);
        });
    }
};
