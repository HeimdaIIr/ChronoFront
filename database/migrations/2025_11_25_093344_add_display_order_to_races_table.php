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
        Schema->table('races', function (Blueprint $table) {
            $table->integer('display_order')->nullable()->after('event_id');
            $table->index(['event_id', 'display_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema->table('races', function (Blueprint $table) {
            $table->dropIndex(['event_id', 'display_order']);
            $table->dropColumn('display_order');
        });
    }
};
