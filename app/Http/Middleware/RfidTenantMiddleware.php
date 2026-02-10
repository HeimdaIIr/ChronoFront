<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RfidTenantMiddleware
{
    /**
     * Handle an incoming RFID request and route to correct tenant database
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Get reader serial from header
        $readerSerial = $request->header('Serial');

        if (!$readerSerial) {
            // No serial header - cannot determine tenant
            // Let controller handle this error
            return $next($request);
        }

        // Search for reader in all tenant databases to find which account it belongs to
        $accountDb = $this->findReaderTenantDatabase($readerSerial);

        if (!$accountDb) {
            // Reader not found in any tenant database
            // Let controller handle this error
            Log::warning('RFID Tenant Middleware: Reader not found in any tenant database', [
                'serial' => $readerSerial
            ]);
            return $next($request);
        }

        // Configure tenant connection to use this account's database
        $dbPath = database_path($accountDb);

        Config::set('database.connections.tenant.database', $dbPath);

        // Purge existing connection to force reconnection
        DB::purge('tenant');

        // Reconnect with new config
        DB::reconnect('tenant');

        // Set tenant as default connection
        DB::setDefaultConnection('tenant');

        Log::debug('RFID Tenant Middleware: Database switched', [
            'serial' => $readerSerial,
            'database' => $accountDb
        ]);

        return $next($request);
    }

    /**
     * Find which tenant database contains this reader
     *
     * @param string $readerSerial
     * @return string|null Database filename or null if not found
     */
    private function findReaderTenantDatabase(string $readerSerial): ?string
    {
        // Get all accounts from system database
        $accounts = DB::connection('system')
            ->table('accounts')
            ->where('is_active', true)
            ->get();

        foreach ($accounts as $account) {
            try {
                // Test connection to this tenant database
                $dbPath = database_path($account->database_file);

                if (!file_exists($dbPath)) {
                    continue;
                }

                Config::set('database.connections.tenant_search.database', $dbPath);
                DB::purge('tenant_search');

                // Search for reader in this database
                $reader = DB::connection('tenant_search')
                    ->table('readers')
                    ->where('serial', $readerSerial)
                    ->first();

                if ($reader) {
                    // Found it!
                    return $account->database_file;
                }
            } catch (\Exception $e) {
                // Database error, skip this tenant
                Log::warning('RFID Tenant Middleware: Error searching tenant database', [
                    'database' => $account->database_file,
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        return null;
    }
}
