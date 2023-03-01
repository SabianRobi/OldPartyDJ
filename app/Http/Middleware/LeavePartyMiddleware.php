<?php

namespace App\Http\Middleware;

use App\Http\Controllers\PartyController;
use App\Models\Party;
use App\Models\PartyParticipant;
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
            $participant = PartyParticipant::firstWhere('user_id', Auth::id());
            $party = Party::find($participant->party_id);
            $participant->delete();

            //Delete party when the last participant leaves
            $participants = PartyParticipant::where('party_id', $party->id)->get();
            if (count($participants) == 0) {
                $party->delete();
            }
        }

        notify()->success('Successfully left the party!');

        return $next($request);
    }
}
