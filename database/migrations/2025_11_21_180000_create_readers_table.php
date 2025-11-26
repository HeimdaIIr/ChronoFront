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
        Schema::create('readers', function (Blueprint $table) {
            $table->id();
            $table->string('serial')->unique()->comment('Serial number of the RFID reader (e.g., 107)');
            $table->string('name')->nullable()->comment('Reader friendly name');
            $table->foreignId('event_id')->nullable()->constrained('events')->onDelete('cascade');
            $table->foreignId('race_id')->nullable()->constrained('races')->onDelete('cascade');
            $table->string('location')->nullable()->comment('Location/point name (e.g., ARRIVEE, DEPART, KM5)');
            $table->integer('anti_rebounce_seconds')->default(5)->comment('Minimum seconds between two reads for same bib');
            $table->datetime('date_min')->nullable()->comment('Start datetime for reader activation');
            $table->datetime('date_max')->nullable()->comment('End datetime for reader activation');
            $table->boolean('is_active')->default(false);
            $table->integer('clone_reader_id')->nullable()->comment('If set, use this reader ID for logging');
            $table->boolean('test_terrain')->default(false);
            $table->datetime('date_test')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema->dropIfExists('readers');
    }
};
