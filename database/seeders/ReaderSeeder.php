<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Reader;
use Illuminate\Support\Facades\DB;

class ReaderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Crée tous les lecteurs RFID de 101 à 120
     * Structure IP : 192.168.10.{50 + numéro}
     *
     * Exemple :
     * - Lecteur 101 → IP 192.168.10.151
     * - Lecteur 104 → IP 192.168.10.154
     * - Lecteur 120 → IP 192.168.10.170
     */
    public function run(): void
    {
        // Désactive les contraintes de clé étrangère
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Supprime tous les lecteurs existants
        Reader::truncate();

        // Réactive les contraintes de clé étrangère
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Crée les 20 lecteurs (101 à 120)
        for ($i = 101; $i <= 120; $i++) {
            Reader::create([
                'serial' => (string) $i,
                'name' => "Lecteur $i",
                'location' => null, // À configurer par épreuve
                'event_id' => null, // À configurer par épreuve
                'race_id' => null, // À configurer par épreuve
                'anti_rebounce_seconds' => 5, // Valeur par défaut
                'date_min' => null,
                'date_max' => null,
                'is_active' => false, // Inactif par défaut
                'test_terrain' => false,
            ]);
        }

        $this->command->info('✅ 20 lecteurs RFID créés avec succès (101 à 120)');
        $this->command->info('📡 Structure IP : 192.168.10.{50 + numéro}');
        $this->command->info('💡 Configurer les lecteurs par épreuve via l\'interface');
    }
}
