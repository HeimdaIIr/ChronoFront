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
        // Chemin de la DB actuelle (database.sqlite directement)
        $dbPath = database_path('database.sqlite');

        // Vérifier que le fichier existe
        if (!file_exists($dbPath)) {
            abort(404, 'Base de données introuvable');
        }

        // Nom du fichier téléchargé
        $filename = "chronofront_" . now()->format('Ymd_His') . ".sqlite";

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

        // Validation - Accepter n'importe quel fichier .sqlite peu importe le MIME type
        $request->validate([
            'database_file' => 'required|file|max:204800', // Max 200MB, pas de restriction MIME
        ]);

        \Log::info("Validation passée");

        // Vérifier que le fichier a bien l'extension .sqlite
        $uploadedFile = $request->file('database_file');
        if (!in_array(strtolower($uploadedFile->getClientOriginalExtension()), ['sqlite', 'db'])) {
            \Log::error("Extension invalide : " . $uploadedFile->getClientOriginalExtension());
            return redirect()->route('dashboard')
                ->with('error', 'Le fichier doit avoir l\'extension .sqlite ou .db');
        }

        // Chemin de la DB actuelle (database.sqlite)
        $currentDbPath = database_path('database.sqlite');

        // Créer le dossier archives s'il n'existe pas
        $archiveDir = database_path('archives');
        if (!is_dir($archiveDir)) {
            mkdir($archiveDir, 0755, true);
        }

        // Backup de l'ancienne DB avant de remplacer
        if (file_exists($currentDbPath)) {
            $backupFilename = "chronofront_backup_" . now()->format('Ymd_His') . ".sqlite";
            $backupPath = "{$archiveDir}/{$backupFilename}";
            copy($currentDbPath, $backupPath);
        }

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
        DB::disconnect('sqlite');

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

        // Purger uniquement le cache de configuration (plus rapide)
        \Artisan::call('config:clear');

        // Purger les connexions pour forcer le rechargement de la nouvelle DB
        DB::reconnect('sqlite');

        return redirect()->route('dashboard')
            ->with('success', "Base de données importée avec succès ! L'ancienne DB a été sauvegardée dans database/archives/")
            ->with('timestamp', time()); // Force le reload
    }
}
