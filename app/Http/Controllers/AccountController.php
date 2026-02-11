<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class AccountController extends Controller
{
    /**
     * Display a listing of accounts (admin only)
     */
    public function index()
    {
        $accounts = Account::orderBy('created_at', 'desc')->get();
        return view('accounts.index', compact('accounts'));
    }

    /**
     * Show the form for creating a new account
     */
    public function create()
    {
        return view('accounts.create');
    }

    /**
     * Store a newly created account in storage
     */
    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:255|unique:accounts,username',
            'email' => 'nullable|email|max:255|unique:accounts,email',
            'password' => 'required|string|min:8|confirmed',
            'telephone' => 'nullable|string|max:20',
            'role' => 'required|in:admin,orga,viewer',
            'account_name' => 'required|string|max:255',
        ]);

        // Generate database file name from account_name
        // Clean the account name: remove special chars, replace spaces with underscores
        $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '_', $request->account_name));
        $dbFileName = $cleanName . '.sqlite';

        // Ensure the database file name is unique
        $counter = 1;
        $originalDbFileName = $dbFileName;
        while (file_exists(database_path($dbFileName))) {
            $dbFileName = pathinfo($originalDbFileName, PATHINFO_FILENAME) . '_' . $counter . '.sqlite';
            $counter++;
        }

        // Create the account
        $account = Account::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'telephone' => $request->telephone,
            'role' => $request->role,
            'database_file' => $dbFileName,
            'is_active' => true,
        ]);

        // Create the tenant database file
        $this->createTenantDatabase($dbFileName);

        return redirect()->route('accounts.index')
            ->with('success', "Compte '{$request->username}' créé avec succès. Base de données: {$dbFileName}");
    }

    /**
     * Show the form for editing the specified account
     */
    public function edit(Account $account)
    {
        return view('accounts.edit', compact('account'));
    }

    /**
     * Update the specified account in storage
     */
    public function update(Request $request, Account $account)
    {
        $request->validate([
            'username' => 'required|string|max:255|unique:accounts,username,' . $account->id,
            'email' => 'nullable|email|max:255|unique:accounts,email,' . $account->id,
            'telephone' => 'nullable|string|max:20',
            'role' => 'required|in:admin,orga,viewer',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $data = [
            'username' => $request->username,
            'email' => $request->email,
            'telephone' => $request->telephone,
            'role' => $request->role,
        ];

        // Only update password if provided
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $account->update($data);

        return redirect()->route('accounts.index')
            ->with('success', "Compte '{$account->username}' mis à jour avec succès.");
    }

    /**
     * Toggle account active status
     */
    public function toggleStatus(Account $account)
    {
        $account->update([
            'is_active' => !$account->is_active
        ]);

        $status = $account->is_active ? 'activé' : 'désactivé';
        return redirect()->route('accounts.index')
            ->with('success', "Compte '{$account->username}' {$status} avec succès.");
    }

    /**
     * Remove the specified account from storage
     */
    public function destroy(Account $account)
    {
        $username = $account->username;
        $dbFile = database_path($account->database_file);

        // Delete the account
        $account->delete();

        // Optionally delete the database file (commented out for safety)
        // if (file_exists($dbFile)) {
        //     unlink($dbFile);
        // }

        return redirect()->route('accounts.index')
            ->with('success', "Compte '{$username}' supprimé avec succès.");
    }

    /**
     * Create a new tenant database and run migrations
     */
    private function createTenantDatabase(string $dbFileName)
    {
        $dbPath = database_path($dbFileName);

        // Create empty SQLite database file
        touch($dbPath);
        chmod($dbPath, 0666);

        // Configure temporary tenant connection
        config(['database.connections.temp_tenant' => [
            'driver' => 'sqlite',
            'database' => $dbPath,
            'foreign_key_constraints' => true,
        ]]);

        // Run tenant migrations on the new database
        try {
            Artisan::call('migrate', [
                '--database' => 'temp_tenant',
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);

            // Purge the temporary connection
            DB::purge('temp_tenant');
        } catch (\Exception $e) {
            // If migration fails, log error but don't fail account creation
            \Log::error("Failed to run migrations for {$dbFileName}: " . $e->getMessage());
        }
    }
}
