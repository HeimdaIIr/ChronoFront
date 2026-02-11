<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

trait SyncToMainDatabase
{
    /**
     * Sync data to main database
     */
    public function syncToMain(string $action = 'created')
    {
        try {
            // Get current account ID
            $accountId = Auth::check() ? Auth::id() : session('account_id');

            if (!$accountId) {
                \Log::warning("Cannot sync to main DB: no account ID found");
                return;
            }

            $table = $this->getMainTableName();
            $data = $this->getMainSyncData($accountId);

            if ($action === 'deleted') {
                // Delete from main database
                DB::connection('main')->table($table)
                    ->where('account_id', $accountId)
                    ->where($this->getMainSyncIdField(), $this->id)
                    ->delete();
            } else {
                // Insert or update
                DB::connection('main')->table($table)->updateOrInsert(
                    [
                        'account_id' => $accountId,
                        $this->getMainSyncIdField() => $this->id,
                    ],
                    $data
                );
            }
        } catch (\Exception $e) {
            // Log error but don't fail the main operation
            \Log::error("Failed to sync {$this->getTable()} to main DB: " . $e->getMessage());
        }
    }

    /**
     * Get the main database table name
     */
    abstract protected function getMainTableName(): string;

    /**
     * Get the main database sync ID field name
     */
    abstract protected function getMainSyncIdField(): string;

    /**
     * Get the data to sync to main database
     */
    abstract protected function getMainSyncData(int $accountId): array;
}
