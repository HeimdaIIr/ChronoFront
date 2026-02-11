<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class TenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Ensure user is authenticated
        if (!Auth::check()) {
            return redirect('/login');
        }

        // ALWAYS get account from currently authenticated user
        // This ensures we use the correct DB even after logout/login
        $account = Auth::user();
        $accountDb = $account->database_file;

        // Update session to match current user (in case of account switch)
        session([
            'account_id' => $account->id,
            'account_name' => $account->username,
            'account_db' => $accountDb,
            'role' => $account->role,
        ]);

        // Configure tenant connection to use this account's database
        $dbPath = database_path($accountDb);

        Config::set('database.connections.tenant.database', $dbPath);

        // Purge existing connection to force reconnection
        DB::purge('tenant');

        // Reconnect with new config
        DB::reconnect('tenant');

        // Set tenant as default connection
        DB::setDefaultConnection('tenant');

        // Check and run any pending tenant-specific migrations ONCE per session
        // This ensures new migrations are automatically applied without running on every request
        $migrationKey = "tenant_migrations_checked_{$account->id}";
        if (!session()->has($migrationKey)) {
            try {
                Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/tenant',
                    '--force' => true,
                ]);
                // Mark migrations as checked for this session
                session()->put($migrationKey, true);
            } catch (\Exception $e) {
                // Log l'erreur mais continue (ne pas bloquer l'application)
                \Log::error("Tenant migration check failed for account {$account->username}: " . $e->getMessage());
            }
        }

        return $next($request);
    }
}
