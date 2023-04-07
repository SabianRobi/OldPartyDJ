<?php

namespace App\Http\Middleware;

use App\Http\Controllers\PartyController;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeavePartyMiddleware
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
        if (PartyController::checkAlredyInParty()) {
            //Leaving party
            $user = User::find(Auth::id());

            //Delete party (and ququed tracks) when the last participant leaves
            $participants = User::where('party_id', $user->party->id)->get();
            if (count($participants) == 1) {
                $user->party->delete();
            } else {
                $user->party_id = null;
            }
            $user->role = null;
            $user->save();
        }

        notify()->success('Successfully left the party!');

        return $next($request);
    }
}
