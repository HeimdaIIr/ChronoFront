<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRealStartFieldsToWavesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('waves', function (Blueprint $table) {
            $table->dateTime('real_start_time')->nullable()->after('start_time')
                ->comment('Heure réelle du TOP départ (cliqué par le chrono)');
            $table->integer('depart_window_minutes')->default(5)->after('real_start_time')
                ->comment('Fenêtre de détection DEPART en minutes (±X minutes autour du TOP)');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('waves', function (Blueprint $table) {
            $table->dropColumn(['real_start_time', 'depart_window_minutes']);
        });
    }
}

