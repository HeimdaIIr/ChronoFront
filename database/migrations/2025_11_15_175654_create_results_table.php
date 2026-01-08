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
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('race_id')->constrained()->onDelete('cascade');
            $table->foreignId('entrant_id')->constrained()->onDelete('cascade');
            $table->foreignId('wave_id')->nullable()->constrained()->onDelete('set null');
            $table->string('rfid_tag', 50);
            $table->timestamp('raw_time'); // Heure de passage brute
            $table->integer('calculated_time')->nullable(); // Temps calculé en secondes
            $table->integer('lap_number')->default(1); // Numéro de tour
            $table->integer('lap_time')->nullable(); // Temps du tour en secondes
            $table->decimal('speed', 8, 2)->nullable(); // Vitesse moyenne km/h
            $table->integer('position')->nullable(); // Position scratch
            $table->integer('category_position')->nullable(); // Position catégorie
            $table->string('status', 10)->default('V'); // V, DNS, DNF, DSQ, NS
            $table->boolean('is_manual')->default(false); // Ajout manuel ou RFID
            $table->timestamps();

            // Index unique pour éviter les doublons (race + entrant + tour)
            $table->unique(['race_id', 'entrant_id', 'lap_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
