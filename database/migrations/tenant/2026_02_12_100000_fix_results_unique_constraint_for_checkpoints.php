<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix unique constraint on results table to allow multiple checkpoints per runner.
     *
     * Old constraint: UNIQUE(race_id, entrant_id, lap_number)
     * This blocked ARRIVEE detections when Inter1 already existed (same lap_number=1).
     *
     * New constraint: UNIQUE(race_id, entrant_id, lap_number, reader_location)
     * This allows one result per checkpoint per lap per runner.
     */
    public function up(): void
    {
        // SQLite doesn't support dropping indexes directly with ALTER TABLE,
        // so we need to recreate the table. Laravel's Schema builder handles this.
        Schema::table('results', function (Blueprint $table) {
            $table->dropUnique(['race_id', 'entrant_id', 'lap_number']);
        });

        Schema::table('results', function (Blueprint $table) {
            $table->unique(['race_id', 'entrant_id', 'lap_number', 'reader_location']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->dropUnique(['race_id', 'entrant_id', 'lap_number', 'reader_location']);
        });

        Schema::table('results', function (Blueprint $table) {
            $table->unique(['race_id', 'entrant_id', 'lap_number']);
        });
    }
};
