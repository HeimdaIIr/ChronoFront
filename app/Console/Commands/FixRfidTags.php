<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Entrant;

class FixRfidTags extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'entrants:fix-rfid-tags';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corrige les tags RFID mal formatés (2000 + 4 chiffres)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Recherche des participants avec numéro de dossard...');

        $entrants = Entrant::whereNotNull('bib_number')->get();

        if ($entrants->isEmpty()) {
            $this->warn('⚠️  Aucun participant trouvé avec numéro de dossard');
            return 0;
        }

        $this->info("📊 {$entrants->count()} participant(s) trouvé(s)");
        $this->newLine();

        $fixed = 0;

        $progressBar = $this->output->createProgressBar($entrants->count());
        $progressBar->start();

        foreach ($entrants as $entrant) {
            $correctRfid = '2000' . str_pad($entrant->bib_number, 4, '0', STR_PAD_LEFT);

            if ($entrant->rfid_tag !== $correctRfid) {
                $entrant->rfid_tag = $correctRfid;
                $entrant->save();
                $fixed++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info("✅ Tags RFID corrigés: {$fixed}");
        $this->info("✓  Tags déjà corrects: " . ($entrants->count() - $fixed));

        $this->newLine();
        $this->info('🎉 Traitement terminé !');

        return 0;
    }
}
