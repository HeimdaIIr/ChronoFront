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
            // Indique si ce lecteur est le lecteur principal (ARRIVÉE)
            $table->boolean('is_primary')->default(false)->after('is_active')
                ->comment('True if this is the primary reader (ARRIVÉE) with ChronoFront interface');

            // Référence vers le lecteur principal (pour les lecteurs secondaires)
            $table->foreignId('primary_reader_id')->nullable()->after('is_primary')
                ->constrained('readers')->onDelete('set null')
                ->comment('ID of the primary reader this secondary reader sends data to');

            // Index pour optimiser les requêtes
            $table->index(['event_id', 'is_primary']);
            $table->index(['primary_reader_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('readers', function (Blueprint $table) {
            $table->dropForeign(['primary_reader_id']);
            $table->dropIndex(['event_id', 'is_primary']);
            $table->dropIndex(['primary_reader_id']);
            $table->dropColumn(['is_primary', 'primary_reader_id']);
        });
    }
};
