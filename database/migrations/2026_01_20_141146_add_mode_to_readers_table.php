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
                'single_reader_waves',    // Un lecteur départ/arrivée avec vagues et TOP départ
                'single_reader_simple',   // Un lecteur départ/arrivée avec plages horaires
                'multi_reader'            // Plusieurs lecteurs (chaque lecteur = checkpoint)
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
