<?php

namespace App\Http\Controllers;

use App\Models\Party;
use App\Models\SpotifyThings;
use App\Models\User;
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
            notify()->error("You are already have a party!");
            return redirect()->route('party');
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
            notify()->error("You are already have a party!");
            return redirect()->route('party');
        }

        $validated = $request->validate([
            'name' => 'required|string|min:3|max:255|unique:parties,name',
            'password' => 'nullable|string|min:3|max:255',
        ]);

        $user = User::find(Auth::id());

        $party = new Party();
        $party->name = $validated['name'];
        if ($validated['password'] !== null) {
            $party->password = Hash::make($validated['password']);
        }
        $party->creator = $user->id;
        $party->save();
        $party->update();

        $user->party_id = $party->id;
        $user->role = 'creator';
        $user->save();

        notify()->success("Successfully created party!");

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
            $user = User::find(Auth::id());
            $user->party_id = $user->party->id;
            $user->role = 'creator';
            $user->save();

            notify()->error("Successfully joined back to your party!");
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
        $user = User::find(Auth::id());
        $user->party_id = $party->id;
        $user->role = $party->creator == $user->id ? "creator" : "participant";
        $user->save();

        notify()->success("Successfully joined the party!");

        return redirect()->route('party');
    }

    // Leave party
    public function leave()
    {
        // LeavePartyMiddleware
        return redirect()->route('landingParty');
    }

    // In party page
    public function inParty()
    {
        $user = User::find(Auth::id());
        $spotify = SpotifyThings::where('owner', $user->id)->first();

        if ($user->role == "creator" && isset($spotify->token)) {
            return view('party.party', [
                // 'user' => $user,
                'partyName' => $user->party->name,
                'spotifyToken' => $spotify->token,
            ]);
        }

        return view('party.party', [
            // 'user' => $user,
            'partyName' => $user->party->name,
        ]);
    }

    public static function checkHasParty()
    {
        $user = User::find(Auth::id());
        return Party::where('creator', $user->id)->get()->isNotEmpty();
    }

    public static function checkAlredyInParty()
    {
        $user = User::find(Auth::id());
        return isset($user->party_id);
    }
}
