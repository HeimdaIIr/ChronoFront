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
            $table->decimal('distance_from_start', 8, 2)->default(0)->after('location')->comment('Distance en km depuis le départ');
            $table->integer('checkpoint_order')->nullable()->after('distance_from_start')->comment('Ordre du checkpoint calculé automatiquement depuis la distance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('readers', function (Blueprint $table) {
            $table->dropColumn(['distance_from_start', 'checkpoint_order']);
        });
    }
};
