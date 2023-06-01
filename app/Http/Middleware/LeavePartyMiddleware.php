<?php

namespace App\Http\Middleware;

use App\Http\Controllers\PartyController;
use App\Models\UserParty;
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
            $user = Auth::user();
            $partyId = UserParty::where('user_id', $user->id)->first()->party_id;

            //Delete party (and ququed tracks) when the last participant leaves
            $participants = UserParty::where('party_id', $partyId)->get();
            if (count($participants) == 1) {
                PartyController::delete(); //TODO will not work if the last person is not the creator
            } else {
                UserParty::where('user_id', $user->id)->delete();
            }

            notify()->success('Successfully left the party!');
        }

        return $next($request);
    }
}
