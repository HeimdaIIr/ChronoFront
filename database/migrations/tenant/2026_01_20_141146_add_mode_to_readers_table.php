<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddModeToReadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('readers', function (Blueprint $table) {
            $table->enum('mode', [
                'single_reader_simple',   // Un lecteur départ/arrivée avec plages horaires (pas de vagues)
                'single_reader_waves',    // Un lecteur départ/arrivée avec vagues et TOP départ (fenêtre ±X min)
                'multi_reader',           // Plusieurs lecteurs (chaque lecteur = checkpoint, pas de vagues)
                'multi_reader_waves'      // Plusieurs lecteurs avec vagues (départ groupé, heure exacte)
            ])->default('multi_reader')->after('location')
                ->comment('Mode de fonctionnement du système de chronométrage');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('readers', function (Blueprint $table) {
            $table->dropColumn('mode');
        });
    }
}
