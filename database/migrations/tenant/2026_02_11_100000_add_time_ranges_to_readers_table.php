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
        Schema::table('readers', function (Blueprint $table) {
            $table->time('depart_time_start')->nullable()->after('is_active')->comment('Start time for DEPART detection');
            $table->time('depart_time_end')->nullable()->after('depart_time_start')->comment('End time for DEPART detection');
            $table->time('arrival_time_start')->nullable()->after('depart_time_end')->comment('Start time for ARRIVEE detection');
            $table->time('arrival_time_end')->nullable()->after('arrival_time_start')->comment('End time for ARRIVEE detection');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('readers', function (Blueprint $table) {
            $table->dropColumn([
                'depart_time_start',
                'depart_time_end',
                'arrival_time_start',
                'arrival_time_end'
            ]);
        });
    }
};
