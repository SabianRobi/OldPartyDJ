<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\SpotifyToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        session(['url.intended' => url()->previous(route('home'))]);
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        $request->session()->regenerate();

        //Update the last login field
        $user = User::find(Auth::id());
        $user->last_login = now();
        $user->save();

        //Check if user has Spotify token
        $isOldToken = true;
        SpotifyToken::where('user_id', auth()->user()->id)->firstOr(function () use (&$isOldToken) {
            $isOldToken = false;
        });
        session(['spotifyToken' => $isOldToken]);

        notify()->success('Successfully logged in!');

        $redirectTo = session()->get('url.intended');
        return redirect($redirectTo);
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        notify()->success('Successfully logged out!');

        return redirect(url()->previous(route('home')));
    }
}
