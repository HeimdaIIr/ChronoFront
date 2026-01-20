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
            // Type de réseau : local, vpn, custom
            $table->enum('network_type', ['local', 'vpn', 'custom'])->default('local')->after('serial')
                ->comment('Network type: local (192.168.10.X), vpn (10.8.0.X), custom (manual IP)');

            // IP personnalisée (si network_type = custom)
            $table->string('custom_ip')->nullable()->after('network_type')
                ->comment('Custom IP address if network_type is custom');

            // Distance depuis le départ (pour calcul checkpoint_order) - si pas déjà présent
            if (!Schema::hasColumn('readers', 'distance_from_start')) {
                $table->decimal('distance_from_start', 8, 2)->default(0)->after('location')
                    ->comment('Distance from start in kilometers (used for checkpoint ordering)');
            }

            // Ordre de checkpoint (calculé automatiquement) - si pas déjà présent
            if (!Schema::hasColumn('readers', 'checkpoint_order')) {
                $table->integer('checkpoint_order')->nullable()->after('distance_from_start')
                    ->comment('Checkpoint order based on distance (1=DEPART, 2=KM5, etc.)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('readers', function (Blueprint $table) {
            $table->dropColumn(['network_type', 'custom_ip']);

            // On ne supprime distance_from_start et checkpoint_order que s'ils ont été ajoutés par cette migration
            // Ils peuvent exister via une autre migration
        });
    }
};
