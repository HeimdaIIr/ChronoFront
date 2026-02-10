<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite ne supporte pas ALTER COLUMN facilement
        // Comme la table readers est vide, on la recrée

        // 1. Drop la table existante
        Schema::dropIfExists('readers');

        // 2. Recrée la table avec les bonnes contraintes
        Schema::create('readers', function (Blueprint $table) {
            $table->id();
            $table->string('serial', 50)->comment('Reader serial number');
            $table->string('name', 200)->nullable()->comment('Reader friendly name');
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('race_id')->nullable()->constrained()->onDelete('set null');
            $table->string('location', 100)->comment('Reader physical location');
            $table->decimal('distance_from_start', 8, 2)->default(0)->comment('Distance en km depuis le départ');
            $table->integer('checkpoint_order')->nullable()->comment('Ordre du checkpoint calculé automatiquement');
            $table->integer('anti_rebounce_seconds')->default(5)->comment('Anti-rebounce time in seconds');
            $table->datetime('date_min')->nullable()->comment('Start datetime for reader activation');
            $table->datetime('date_max')->nullable()->comment('End datetime for reader activation');
            $table->boolean('is_active')->default(true);
            $table->integer('clone_reader_id')->nullable();
            $table->boolean('test_terrain')->default(false);
            $table->datetime('date_test')->nullable()->comment('Last communication with reader');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ne rien faire, on ne peut pas revenir en arrière facilement avec SQLite
    }
};
