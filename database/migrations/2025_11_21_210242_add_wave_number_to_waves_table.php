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
        Schema->table('waves', function (Blueprint $table) {
            $table->integer('wave_number')->after('race_id')->nullable();

            // Index unique : un même numéro de vague ne peut exister qu'une seule fois par épreuve
            $table->unique(['race_id', 'wave_number'], 'waves_race_number_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema->table('waves', function (Blueprint $table) {
            $table->dropUnique('waves_race_number_unique');
            $table->dropColumn('wave_number');
        });
    }
};
