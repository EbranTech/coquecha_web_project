<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([...$credentials, 'is_active' => true], $request->boolean('remember'))) {
            return back()->withErrors([
                'username' => 'Las credenciales proporcionadas no son válidas.',
            ])->onlyInput('username');
        }

        $request->session()->regenerate();

        if ($request->user()->hasRole('admin') && $request->session()->has('url.intended')) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return $request->user()->hasRole('admin')
            ? redirect()->route('admin.dashboard')
            : redirect()->away((string) config('modules.operations_url'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
