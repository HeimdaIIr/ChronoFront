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
            // Identifiants HTTP pour accéder au lecteur via VPN
            $table->string('http_username')->nullable()->after('custom_ip')
                ->comment('HTTP Basic Auth username for VPN access');

            $table->string('http_password')->nullable()->after('http_username')
                ->comment('HTTP Basic Auth password for VPN access (encrypted)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('readers', function (Blueprint $table) {
            $table->dropColumn(['http_username', 'http_password']);
        });
    }
};
