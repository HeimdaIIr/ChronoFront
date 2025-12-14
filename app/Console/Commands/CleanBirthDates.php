<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanBirthDates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'entrants:clean-birth-dates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retire la partie heure des dates de naissance (ne garde que la date)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Nettoyage des dates de naissance...');

        // SQLite : UPDATE pour extraire uniquement la date (YYYY-MM-DD)
        $updated = DB::update("
            UPDATE entrants
            SET birth_date = DATE(birth_date)
            WHERE birth_date IS NOT NULL
        ");

        $this->info("✅ {$updated} date(s) de naissance nettoyée(s)");
        $this->info('🎉 Traitement terminé !');

        return 0;
    }
}
