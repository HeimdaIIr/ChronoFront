<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Entrant;

class AssignCategoriesToEntrants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'entrants:assign-categories {--force : Réassigner même si catégorie déjà définie}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assigne automatiquement les catégories FFA aux participants selon leur âge et sexe';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = $this->option('force');

        $this->info('🔍 Recherche des participants...');

        // Query entrants
        $query = Entrant::whereNotNull('birth_date')
            ->whereNotNull('gender');

        if (!$force) {
            $query->whereNull('category_id');
        }

        $entrants = $query->get();

        if ($entrants->isEmpty()) {
            $this->warn('⚠️  Aucun participant trouvé sans catégorie');
            return 0;
        }

        $this->info("📊 {$entrants->count()} participant(s) trouvé(s)");
        $this->newLine();

        $assigned = 0;
        $failed = 0;

        $progressBar = $this->output->createProgressBar($entrants->count());
        $progressBar->start();

        foreach ($entrants as $entrant) {
            $previousCategory = $entrant->category_id;
            $entrant->assignCategory();

            if ($entrant->category_id !== $previousCategory) {
                $assigned++;
            } else if (!$entrant->category_id) {
                $failed++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info("✅ Catégories assignées: {$assigned}");

        if ($failed > 0) {
            $this->warn("⚠️  Échecs (aucune catégorie trouvée): {$failed}");
        }

        $this->newLine();
        $this->info('🎉 Traitement terminé !');

        return 0;
    }
}
