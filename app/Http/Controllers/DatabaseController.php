<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatabaseController extends Controller
{
    /**
     * Exporte la base de données actuelle
     */
    public function export(Request $request): BinaryFileResponse
    {
        // Récupérer le tenant actuel depuis la requête (défini par le middleware)
        $tenant = $request->attributes->get('tenant', 'main');

        // Chemin de la DB actuelle
        $dbPath = storage_path("databases/{$tenant}.sqlite");

        // Vérifier que le fichier existe
        if (!file_exists($dbPath)) {
            abort(404, 'Base de données introuvable');
        }

        // Nom du fichier téléchargé
        $filename = "{$tenant}_" . now()->format('Ymd_His') . ".sqlite";

        // Télécharger le fichier
        return response()->download($dbPath, $filename, [
            'Content-Type' => 'application/x-sqlite3',
        ]);
    }

    /**
     * Importe une base de données pour remplacer l'actuelle
     */
    public function import(Request $request)
    {
        // LOG AU TOUT DÉBUT pour voir si la méthode s'exécute
        \Log::info("=== DÉBUT IMPORT DB ===");
        \Log::info("Tenant: " . $request->attributes->get('tenant', 'inconnu'));

        // Validation
        $request->validate([
            'database_file' => 'required|file|mimes:sqlite,db|max:102400', // Max 100MB
        ]);

        \Log::info("Validation passée");

        // Récupérer le tenant actuel
        $tenant = $request->attributes->get('tenant', 'main');

        // Chemin de la DB actuelle
        $currentDbPath = storage_path("databases/{$tenant}.sqlite");

        // Créer le dossier archives s'il n'existe pas
        $archiveDir = storage_path('databases/archives');
        if (!is_dir($archiveDir)) {
            mkdir($archiveDir, 0755, true);
        }

        // Backup de l'ancienne DB avant de remplacer
        if (file_exists($currentDbPath)) {
            $backupFilename = "{$tenant}_backup_" . now()->format('Ymd_His') . ".sqlite";
            $backupPath = "{$archiveDir}/{$backupFilename}";
            copy($currentDbPath, $backupPath);
        }

        // Récupérer le fichier uploadé
        $uploadedFile = $request->file('database_file');

        // Log de debug
        \Log::info("Import DB - Fichier uploadé : " . $uploadedFile->getClientOriginalName());
        \Log::info("Import DB - Taille fichier : " . $uploadedFile->getSize() . " octets");

        // Vérifier que c'est un vrai fichier SQLite
        try {
            // Tenter d'ouvrir le fichier avec SQLite pour validation
            $tempPath = $uploadedFile->getPathname();
            $testDb = new \PDO("sqlite:{$tempPath}");

            // Compter le nombre d'events dans le fichier uploadé
            $stmt = $testDb->query("SELECT COUNT(*) as count FROM events");
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            \Log::info("Import DB - Nombre d'events dans le fichier : " . $result['count']);

            $testDb = null; // Fermer la connexion
        } catch (\Exception $e) {
            \Log::error("Import DB - Erreur validation : " . $e->getMessage());
            return redirect()->route('dashboard')
                ->with('error', 'Le fichier n\'est pas une base de données SQLite valide.');
        }

        // IMPORTANT : Fermer toutes les connexions à la DB actuelle avant de la remplacer
        DB::purge('tenant');
        DB::disconnect('tenant');

        // Attendre un peu pour que les connexions se ferment complètement
        usleep(500000); // 0.5 secondes

        // Supprimer l'ancien fichier DB ET ses fichiers de journalisation SQLite
        if (file_exists($currentDbPath)) {
            if (!@unlink($currentDbPath)) {
                return redirect()->route('dashboard')
                    ->with('error', "Impossible de supprimer l'ancienne base de données. Vérifiez les permissions.");
            }
        }

        // Supprimer les fichiers de journalisation SQLite (WAL, SHM)
        @unlink("{$currentDbPath}-wal");
        @unlink("{$currentDbPath}-shm");
        @unlink("{$currentDbPath}-journal");

        // Copier le fichier uploadé vers la destination finale
        $tempPath = $uploadedFile->getPathname();
        \Log::info("Import DB - Copie de {$tempPath} vers {$currentDbPath}");

        if (!@copy($tempPath, $currentDbPath)) {
            \Log::error("Import DB - Échec de la copie");
            return redirect()->route('dashboard')
                ->with('error', "Impossible de copier la nouvelle base de données. Vérifiez les permissions.");
        }

        \Log::info("Import DB - Copie réussie, taille finale : " . filesize($currentDbPath) . " octets");

        // Vérifier le contenu après copie
        try {
            $checkDb = new \PDO("sqlite:{$currentDbPath}");
            $stmt = $checkDb->query("SELECT COUNT(*) as count FROM events");
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            \Log::info("Import DB - Nombre d'events après copie : " . $result['count']);
            $checkDb = null;
        } catch (\Exception $e) {
            \Log::error("Import DB - Erreur vérification après copie : " . $e->getMessage());
        }

        // S'assurer que les permissions sont correctes
        @chmod($currentDbPath, 0664);
        @chown($currentDbPath, fileowner(storage_path('databases')));
        @chgrp($currentDbPath, filegroup(storage_path('databases')));

        // Purger tous les caches Laravel
        \Artisan::call('cache:clear');
        \Artisan::call('config:clear');
        \Artisan::call('view:clear');

        // Purger à nouveau les connexions pour forcer le rechargement
        DB::purge('tenant');
        DB::reconnect('tenant');

        return redirect()->route('dashboard')
            ->with('success', "Base de données importée avec succès ! L'ancienne DB a été sauvegardée dans archives/")
            ->with('timestamp', time()); // Force le reload
    }
}
