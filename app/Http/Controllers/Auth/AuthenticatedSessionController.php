<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\Connexion;


class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
    $request->authenticate();
    $request->session()->regenerate();

    Connexion::ouvrirSession($request->user(), $request);

    return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
    Connexion::fermerSession($request);

    Connexion::where('user_id', Auth::id())
    ->where('session_id', $request->session()->getId())
    ->whereNull('date_deconnexion')
    ->update(['date_deconnexion' => now()]);

    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();



    return redirect('/');
    }
}
