<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Catégories FFA (Fédération Française d'Athlétisme) 2025 - Officielles
     * Source: https://www.athle.fr/contenu/25
     *
     * Total: 36 catégories (18 catégories × 2 sexes)
     */
    public function run(): void
    {
        $categories = [
            // ========================================
            // CATÉGORIES HOMMES (M)
            // ========================================

            // JEUNES
            [
                'name' => 'BB-M',
                'gender' => 'M',
                'age_min' => 0,
                'age_max' => 6,
                'color' => '#A78BFA'  // Purple
            ],
            [
                'name' => 'EA-M',
                'gender' => 'M',
                'age_min' => 7,
                'age_max' => 9,
                'color' => '#60A5FA'  // Blue
            ],
            [
                'name' => 'PO-M',
                'gender' => 'M',
                'age_min' => 10,
                'age_max' => 11,
                'color' => '#34D399'  // Green
            ],
            [
                'name' => 'BE-M',
                'gender' => 'M',
                'age_min' => 12,
                'age_max' => 13,
                'color' => '#FBBF24'  // Yellow
            ],
            [
                'name' => 'MI-M',
                'gender' => 'M',
                'age_min' => 14,
                'age_max' => 15,
                'color' => '#FB923C'  // Orange
            ],
            [
                'name' => 'CA-M',
                'gender' => 'M',
                'age_min' => 16,
                'age_max' => 17,
                'color' => '#14B8A6'  // Teal
            ],
            [
                'name' => 'JU-M',
                'gender' => 'M',
                'age_min' => 18,
                'age_max' => 19,
                'color' => '#06B6D4'  // Cyan
            ],

            // ADULTES
            [
                'name' => 'ES-M',
                'gender' => 'M',
                'age_min' => 20,
                'age_max' => 22,
                'color' => '#3B82F6'  // Blue
            ],
            [
                'name' => 'SE-M',
                'gender' => 'M',
                'age_min' => 23,
                'age_max' => 34,
                'color' => '#2563EB'  // Dark Blue
            ],

            // MASTERS
            [
                'name' => 'M0-M',
                'gender' => 'M',
                'age_min' => 35,
                'age_max' => 39,
                'color' => '#10B981'  // Emerald
            ],
            [
                'name' => 'M1-M',
                'gender' => 'M',
                'age_min' => 40,
                'age_max' => 44,
                'color' => '#059669'  // Dark Emerald
            ],
            [
                'name' => 'M2-M',
                'gender' => 'M',
                'age_min' => 45,
                'age_max' => 49,
                'color' => '#8B5CF6'  // Violet
            ],
            [
                'name' => 'M3-M',
                'gender' => 'M',
                'age_min' => 50,
                'age_max' => 54,
                'color' => '#7C3AED'  // Dark Violet
            ],
            [
                'name' => 'M4-M',
                'gender' => 'M',
                'age_min' => 55,
                'age_max' => 59,
                'color' => '#F59E0B'  // Amber
            ],
            [
                'name' => 'M5-M',
                'gender' => 'M',
                'age_min' => 60,
                'age_max' => 64,
                'color' => '#D97706'  // Dark Amber
            ],
            [
                'name' => 'M6-M',
                'gender' => 'M',
                'age_min' => 65,
                'age_max' => 69,
                'color' => '#EF4444'  // Red
            ],
            [
                'name' => 'M7-M',
                'gender' => 'M',
                'age_min' => 70,
                'age_max' => 74,
                'color' => '#DC2626'  // Dark Red
            ],
            [
                'name' => 'M8-M',
                'gender' => 'M',
                'age_min' => 75,
                'age_max' => 79,
                'color' => '#991B1B'  // Darker Red
            ],
            [
                'name' => 'M9-M',
                'gender' => 'M',
                'age_min' => 80,
                'age_max' => 84,
                'color' => '#7F1D1D'  // Very Dark Red
            ],
            [
                'name' => 'M10-M',
                'gender' => 'M',
                'age_min' => 85,
                'age_max' => 150,
                'color' => '#450A0A'  // Extremely Dark Red
            ],

            // ========================================
            // CATÉGORIES FEMMES (F)
            // ========================================

            // JEUNES
            [
                'name' => 'BB-F',
                'gender' => 'F',
                'age_min' => 0,
                'age_max' => 6,
                'color' => '#DDD6FE'  // Light Purple
            ],
            [
                'name' => 'EA-F',
                'gender' => 'F',
                'age_min' => 7,
                'age_max' => 9,
                'color' => '#BFDBFE'  // Light Blue
            ],
            [
                'name' => 'PO-F',
                'gender' => 'F',
                'age_min' => 10,
                'age_max' => 11,
                'color' => '#A7F3D0'  // Light Green
            ],
            [
                'name' => 'BE-F',
                'gender' => 'F',
                'age_min' => 12,
                'age_max' => 13,
                'color' => '#FDE68A'  // Light Yellow
            ],
            [
                'name' => 'MI-F',
                'gender' => 'F',
                'age_min' => 14,
                'age_max' => 15,
                'color' => '#FED7AA'  // Light Orange
            ],
            [
                'name' => 'CA-F',
                'gender' => 'F',
                'age_min' => 16,
                'age_max' => 17,
                'color' => '#5EEAD4'  // Light Teal
            ],
            [
                'name' => 'JU-F',
                'gender' => 'F',
                'age_min' => 18,
                'age_max' => 19,
                'color' => '#67E8F9'  // Light Cyan
            ],

            // ADULTES
            [
                'name' => 'ES-F',
                'gender' => 'F',
                'age_min' => 20,
                'age_max' => 22,
                'color' => '#EC4899'  // Pink
            ],
            [
                'name' => 'SE-F',
                'gender' => 'F',
                'age_min' => 23,
                'age_max' => 34,
                'color' => '#DB2777'  // Dark Pink
            ],

            // MASTERS
            [
                'name' => 'M0-F',
                'gender' => 'F',
                'age_min' => 35,
                'age_max' => 39,
                'color' => '#F472B6'  // Rose
            ],
            [
                'name' => 'M1-F',
                'gender' => 'F',
                'age_min' => 40,
                'age_max' => 44,
                'color' => '#EC4899'  // Pink
            ],
            [
                'name' => 'M2-F',
                'gender' => 'F',
                'age_min' => 45,
                'age_max' => 49,
                'color' => '#C084FC'  // Light Violet
            ],
            [
                'name' => 'M3-F',
                'gender' => 'F',
                'age_min' => 50,
                'age_max' => 54,
                'color' => '#A855F7'  // Violet
            ],
            [
                'name' => 'M4-F',
                'gender' => 'F',
                'age_min' => 55,
                'age_max' => 59,
                'color' => '#FCD34D'  // Light Amber
            ],
            [
                'name' => 'M5-F',
                'gender' => 'F',
                'age_min' => 60,
                'age_max' => 64,
                'color' => '#FBBF24'  // Amber
            ],
            [
                'name' => 'M6-F',
                'gender' => 'F',
                'age_min' => 65,
                'age_max' => 69,
                'color' => '#FCA5A5'  // Light Red
            ],
            [
                'name' => 'M7-F',
                'gender' => 'F',
                'age_min' => 70,
                'age_max' => 74,
                'color' => '#F87171'  // Red
            ],
            [
                'name' => 'M8-F',
                'gender' => 'F',
                'age_min' => 75,
                'age_max' => 79,
                'color' => '#EF4444'  // Dark Red
            ],
            [
                'name' => 'M9-F',
                'gender' => 'F',
                'age_min' => 80,
                'age_max' => 84,
                'color' => '#DC2626'  // Darker Red
            ],
            [
                'name' => 'M10-F',
                'gender' => 'F',
                'age_min' => 85,
                'age_max' => 150,
                'color' => '#991B1B'  // Very Dark Red
            ],
        ];

        // Désactive les contraintes de clé étrangère
        \DB->statement('SET FOREIGN_KEY_CHECKS=0;');

        // Supprime toutes les catégories existantes
        Category::truncate();

        // Réactive les contraintes de clé étrangère
        \DB->statement('SET FOREIGN_KEY_CHECKS=1;');

        // Crée les nouvelles catégories
        foreach ($categories as $category) {
            Category::create($category);
        }

        $this->command->info('✅ 36 catégories FFA officielles 2025 créées avec succès!');
        $this->command->info('📊 Jeunes: 14 catégories (BB, EA, PO, BE, MI, CA, JU)');
        $this->command->info('🏃 Adultes: 4 catégories (ES, SE)');
        $this->command->info('🎖️  Masters: 18 catégories (M0 à M10)');
    }
}
