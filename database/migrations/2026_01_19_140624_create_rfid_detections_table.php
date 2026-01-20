<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRfidDetectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rfid_detections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reader_id');
            $table->string('serial', 50); // Tag RFID (ex: 20001686)
            $table->dateTime('raw_time'); // Heure exacte de détection
            $table->unsignedBigInteger('entrant_id')->nullable(); // Si coureur trouvé
            $table->unsignedBigInteger('wave_id')->nullable(); // Si vague trouvée
            $table->boolean('processed')->default(false); // Détection traitée ?
            $table->unsignedBigInteger('created_result_id')->nullable(); // Result créé (si ARRIVEE)
            $table->enum('action_taken', [
                'start_time_updated',
                'result_created', 
                'ignored',
                'skipped',
                'error'
            ])->nullable();
            $table->text('error_message')->nullable(); // Message d'erreur si action = error
            $table->timestamps();

            // Index pour performances
            $table->index('reader_id');
            $table->index('entrant_id');
            $table->index('wave_id');
            $table->index('raw_time');
            $table->index('processed');
            $table->index(['wave_id', 'processed']); // Pour retraitement par vague
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rfid_detections');
    }
}

