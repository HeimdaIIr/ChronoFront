<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTimeRangesToReadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('readers', function (Blueprint $table) {
            $table->time('depart_time_start')->nullable()->after('location')
                ->comment('Start time for DEPART mode (e.g., 15:00:00)');
            $table->time('depart_time_end')->nullable()->after('depart_time_start')
                ->comment('End time for DEPART mode (e.g., 15:30:00)');
            $table->time('arrival_time_start')->nullable()->after('depart_time_end')
                ->comment('Start time for ARRIVEE mode (e.g., 15:30:00)');
            $table->time('arrival_time_end')->nullable()->after('arrival_time_start')
                ->comment('End time for ARRIVEE mode (e.g., 19:00:00 or NULL for no limit)');
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
            $table->dropColumn(['depart_time_start', 'depart_time_end', 'arrival_time_start', 'arrival_time_end']);
        });
    }
}
