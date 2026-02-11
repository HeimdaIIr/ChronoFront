<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response;

class TenantBySubdomain
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Récupère l'host complet
        $host = $request->getHost();

        // Extrait le sous-domaine (chrono1, chrono2, etc.)
        // Pattern: chrono1.recursing-nash.87-106-13-88.plesk.page
        // Ou en production: chrono1.ats-chronos.fr
        preg_match('/^(chrono\d+)[\.-]/', $host, $matches);

        // Déterminer le tenant : chrono1-9 ou "main" pour le domaine principal
        $tenant = isset($matches[1]) ? $matches[1] : 'main';

        // Définir le chemin de la DB du tenant
        $dbPath = storage_path("databases/{$tenant}.sqlite");

        // Créer le dossier databases s'il n'existe pas
        $dbDir = storage_path('databases');
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        // Si la DB n'existe pas, la créer et lancer les migrations
        $isNewDatabase = false;
        if (!file_exists($dbPath)) {
            touch($dbPath);
            chmod($dbPath, 0644);
            $isNewDatabase = true;
        }

        // Configurer la connexion tenant
        Config::set('database.connections.tenant', [
            'driver' => 'sqlite',
            'database' => $dbPath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        // Purger les connexions existantes et définir tenant comme connexion par défaut
        DB::purge('tenant');
        DB::setDefaultConnection('tenant');

        // Run migrations for new databases, and check for pending migrations on existing ones
        if ($isNewDatabase) {
            try {
                // Run main migrations first
                Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--force' => true,
                ]);

                // Then run tenant-specific migrations
                Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/tenant',
                    '--force' => true,
                ]);
            } catch (\Exception $e) {
                // Log l'erreur mais continue
                \Log::error("Migration failed for tenant {$tenant}: " . $e->getMessage());
            }
        } else {
            // For existing databases, check and run any pending tenant-specific migrations
            try {
                Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/tenant',
                    '--force' => true,
                ]);
            } catch (\Exception $e) {
                // Log l'erreur mais continue (ne pas bloquer l'application)
                \Log::error("Tenant migration check failed for {$tenant}: " . $e->getMessage());
            }
        }

        // Stocker le tenant actuel dans la requête pour usage ultérieur si nécessaire
        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }
}
