<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('entrants', function (Blueprint $table) {
            $table->time('start_time')->nullable()->after('wave_id')
                ->comment('Heure de départ individuelle pour contre-la-montre (colonne TOP du CSV)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entrants', function (Blueprint $table) {
            $table->dropColumn('start_time');
        });
    }
};
