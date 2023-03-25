<?php

namespace App\Http\Middleware;

use App\Http\Controllers\PartyController;
use Closure;
use Illuminate\Http\Request;

class InParty
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
        if(!PartyController::checkAlredyInParty()) {
            notify()->error("Please join a party to use this feature!");
            return response()->redirectTo(url()->previous('/'));
        }
        return $next($request);
    }
}
