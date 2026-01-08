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
        Schema::table('results', function (Blueprint $table) {
            $table->foreignId('reader_id')->nullable()->after('wave_id')->constrained('readers')->onDelete('set null');
            $table->string('serial')->nullable()->after('rfid_tag')->comment('Full serial from RFID reader (e.g., [2000003])');
            $table->string('reader_location')->nullable()->after('serial')->comment('Location where read occurred');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->dropForeign(['reader_id']);
            $table->dropColumn(['reader_id', 'serial', 'reader_location']);
        });
    }
};
