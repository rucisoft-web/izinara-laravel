<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function show()
    {
        return auth()->check() ? redirect()->route('dashboard') : view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['login' => 'required|string', 'password' => 'required|string']);
        $field = str_contains($data['login'], '@') ? 'email' : 'username';
        if (Auth::attempt([$field => $data['login'], 'password' => $data['password'], 'active' => true], $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }
        return back()->withInput($request->only('login'))->withErrors(['login' => 'Nama pengguna atau kata sandi salah.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
