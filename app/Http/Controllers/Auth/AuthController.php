<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Show the login form
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect('/dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $account = Account::where('username', $request->username)
                          ->where('is_active', true)
                          ->first();

        if ($account && Hash::check($request->password, $account->password)) {
            // Login successful
            Auth::login($account, $request->filled('remember'));

            // Store account info in session
            session([
                'account_id' => $account->id,
                'account_name' => $account->username,
                'account_db' => $account->database_file,
                'role' => $account->role,
            ]);

            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'username' => 'Identifiants incorrects.',
        ])->onlyInput('username');
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
