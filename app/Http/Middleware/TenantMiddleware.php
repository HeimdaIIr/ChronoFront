<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

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

        // Get account database file from session
        $accountDb = session('account_db');

        if (!$accountDb) {
            // If session doesn't have account_db, get it from auth user
            $account = Auth::user();
            $accountDb = $account->database_file;
            session(['account_db' => $accountDb]);
        }

        // Configure tenant connection to use this account's database
        $dbPath = database_path($accountDb);

        Config::set('database.connections.tenant.database', $dbPath);

        // Purge existing connection and set tenant as default
        DB::purge('tenant');
        DB::setDefaultConnection('tenant');

        return $next($request);
    }
}
