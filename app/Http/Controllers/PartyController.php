<?php

namespace App\Http\Controllers;

use App\Models\Party;
use App\Models\SpotifyThings;
use App\Models\UserParty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PartyController extends Controller
{
    // Welcome
    public function index()
    {
        if ($this->checkAlredyInParty()) {
            notify()->info("You are already in a party!");
            return redirect()->route('party');
        }

        return view('party.index', [
            // 'user' => auth()->user()
        ]);
    }

    //Create party
    public function create()
    {
        if ($this->checkAlredyInParty()) {
            notify()->error("You are already in a party!");
            return redirect()->route('party');
        }
        if ($this->checkHasParty()) {
            notify()->error("You already have a party!");
            return redirect()->route('landingParty');
        }

        return view('party.create');
    }

    // Store party
    public function store(Request $request)
    {
        if ($this->checkAlredyInParty()) {
            notify()->error("You are already in a party!");
            return redirect()->route('party');
        }
        if ($this->checkHasParty()) {
            notify()->error("You already have a party!");
            return redirect()->route('party');
        }

        $validated = $request->validate([
            'name' => 'required|string|min:3|max:255|unique:parties,name',
            'password' => 'nullable|string|min:3|max:255',
        ]);

        $user = Auth::user();

        $party = new Party();
        $party->name = $validated['name'];
        if ($validated['password'] !== null) {
            $party->password = Hash::make($validated['password']);
        }
        $party->creator = $user->id;
        $party->save();
        $party->update();

        $userParty = new UserParty();
        $userParty->user_id = $user->id;
        $userParty->party_id = $party->id;
        $userParty->role = 'creator';
        $userParty->save();

        notify()->success("Successfully created the party!");

        return redirect()->route('party');
    }

    // Join page
    public function joinPage()
    {
        if ($this->checkAlredyInParty()) {
            notify()->error("You are already in a party!");
            return redirect()->route('party');
        } else if ($this->checkHasParty()) {
            //Join back to they party
            $user = Auth::user();
            $party = Party::where('creator', $user->id)->first();

            $userParty = new UserParty();
            $userParty->user_id = $user->id;
            $userParty->party_id = $party->id;
            $userParty->role = 'creator';
            $userParty->save();

            notify()->success("Successfully joined back to your party!");
            return redirect()->route('party');
        } else {
            return view('party.join');
        }
    }

    // Join party
    public function join(Request $request)
    {
        if ($this->checkAlredyInParty()) {
            notify()->error("You are already in a party!");
            return redirect()->route('party');
        }

        $validated = $request->validate([
            'name' => 'required|string|min:3|max:255|exists:parties,name',
            'password' => 'nullable|string|min:3|max:255',
        ], [
            'name.exists' => 'There is no party with this name!',
        ]);

        $party = Party::where('name', $validated['name'])->first();

        //Cheking if the party has password and it's correctly given
        if ($party->password && !Hash::check($validated['password'], $party->password)) {
            return redirect()->route('joinPageParty')
                ->withInput($validated)
                ->withErrors(['password' => 'Incorrect password!']);
        }

        //Joining party
        $user = Auth::user();

        $userParty = new UserParty();
        $userParty->user_id = $user->id;
        $userParty->party_id = $party->id;
        $userParty->role = $party->creator == $user->id ? "creator" : "participant";
        $userParty->save();

        notify()->success("Successfully joined the party!");

        return redirect()->route('party');
    }

    // Leave party
    public function leave()
    {
        // LeavePartyMiddleware
        return redirect()->route('landingParty');
    }

    // Delete party
    public static function delete()
    {
        $user = Auth::user();
        $party = Party::where('creator', $user->id)->first(); //TODO can fail if not the creator wants to delete?

        if ($party->creator != $user->id) {
            notify()->error('You can not delete other\'s party!');
            return redirect()->redirectTo(url()->previous('/'));
        }

        UserParty::where('party_id', $party->id)->delete();
        $party->delete();

        notify()->success('Successfully deleted your party!');

        return redirect()->route('landingParty');
    }

    // In party page
    public function inParty()
    {
        $user = Auth::user();
        $userParty = UserParty::where('user_id', $user->id)->first();
        $party = Party::find($userParty->party_id);
        $spotify = SpotifyThings::where('owner', $user->id)->first();

        return view('party.party', [
            'partyName' => $party->name,
            'loggedInWithSpotify' => isset($spotify->token) && $spotify->token ? true : false,
            'creator' => $userParty->role == "creator",
            'spotifyToken' => $userParty->role == "creator" && isset($spotify->token) ? $spotify->token : '', //TODO still needed?
        ]);
    }

    public static function checkHasParty()
    {
        return Party::where('creator', Auth::id())->get()->isNotEmpty();
    }

    public static function checkAlredyInParty()
    {
        return UserParty::where('user_id', Auth::id())->get()->isNotEmpty();
    }
}
