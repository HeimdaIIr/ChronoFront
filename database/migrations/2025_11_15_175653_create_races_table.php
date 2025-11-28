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
        Schema::create('races', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->string('name', 200);
            $table->string('type', 50)->default('1_passage'); // 1_passage, n_laps, infinite_loop
            $table->decimal('distance', 8, 2)->default(0); // Distance en km
            $table->integer('laps')->default(1); // Nombre de tours
            $table->boolean('best_time')->default(false); // Meilleur temps uniquement
            $table->text('description')->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('races');
    }
};
