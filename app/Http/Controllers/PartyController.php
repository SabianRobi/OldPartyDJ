<?php

namespace App\Http\Controllers;

use App\Models\Party;
use App\Models\PartyParticipant;
use App\Models\SpotifyToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PartyController extends Controller
{
    // Welcome
    public function index()
    {
        if ($this->checkAlredyInParty()) {
            notify()->error("You are already in a party!");
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

        $party = new Party();
        $party->name = $validated['name'];
        if ($validated['password'] !== null) {
            $party->password = Hash::make($validated['password']);
        }
        $party->user_id = Auth::id();
        $party->save();
        $party->update();

        $participant = new PartyParticipant();
        $participant->user_id = Auth::id();
        $participant->party_id = $party->id;
        $participant->role = "creator";
        $participant->save();

        notify()->success("Successfully created party!");

        return redirect()->route('party');
    }

    // Join page
    public function joinPage()
    {
        if ($this->checkAlredyInParty()) {
            notify()->error("You are already in a party!");
            return redirect()->route('party');
        }

        if ($this->checkHasParty()) {
            return view('party.join', [
                'ownParty' => Party::firstWhere('user_id', Auth::id()),
            ]);
        }

        return view('party.join');
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

        $party = Party::firstWhere('name', $validated['name']);

        //Cheking if the party has password and it's correctly given
        if ($party->password && !Hash::check($validated['password'], $party->password)) {
            return redirect()->route('joinPageParty')
                ->withInput($validated)
                ->withErrors(['password' => 'Incorrect password!']);
        }

        //Joining party
        $participant = new PartyParticipant();
        $participant->user_id = Auth::id();
        $participant->party_id = $party->id;
        $participant->role = $party->user_id == Auth::id() ? "creator" : "participant";
        $participant->save();

        notify()->success("Successfully joined the party!");

        return redirect()->route('party');
    }

    // Leave party
    public function leave()
    {
        // LeavePartyMiddleware
        return redirect()->route('landingParty');
    }

    // Show participated parties
    // TODO

    // In party page
    public function inParty()
    {
        if (!$this->checkAlredyInParty()) {
            return redirect()->route('landingParty');
        }

        $participant = PartyParticipant::firstWhere('user_id', Auth::id());
        $party = Party::find($participant->party_id);
        $spotifyToken = SpotifyToken::firstWhere('user_id', Auth::id());

        if ($participant->role == "creator" && $spotifyToken) {
            return view('party.party', [
                'user' => Auth::user(),
                'partyName' => $party->name,
                'spotifyToken' => $spotifyToken->token,
            ]);
        }

        return view('party.party', [
            'user' => Auth::user(),
            'partyName' => $party->name,
        ]);
    }

    public static function checkHasParty()
    {
        return Party::where('user_id', Auth::id())->get()->isNotEmpty();
    }

    public static function checkAlredyInParty()
    {
        return PartyParticipant::where('user_id', Auth::id())->get()->isNotEmpty();
    }
}
