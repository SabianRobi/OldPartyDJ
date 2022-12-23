<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SpotifyWebAPI;
use App\Models\SpotifyState;

class MusicController extends Controller
{
    protected $session;
    protected $api;

    public function __construct()
    {
        $this->session = new SpotifyWebAPI\Session(
            config('spotify.auth.client_id'),
            config('spotify.auth.client_secret'),
            config('spotify.auth.redirect_uri'),
        );
        $this->api = new SpotifyWebAPI\SpotifyWebAPI();
    }


    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return view('party');
    }

    public function doLogin(Request $request)
    {
        // Generate state
        $state = $this->session->generateState();

        // Save state to db
        SpotifyState::factory()->create([
            'state' => $state,
        ]);

        // Set options
        $options = [
            'scope' => [],
            'state' => $state,
        ];

        // Redirect the user
        $url = $this->session->getAuthorizeUrl($options);
        header('Location: ' . $url);
        die();
    }

    public function callback(Request $request)
    {
        $spotState = SpotifyState::find($request->collect()['state']);
        if (!is_null($spotState)) {
            $spotState->delete();

            $this->session->requestAccessToken($request->collect()['code']);

            session(['spotifyToken' => $this->session->getAccessToken()]);
            session(['spotifyRefreshToken' => $this->session->getRefreshToken()]);

            notify()->success('Successfully logged in with Spotify!');
        } else {
            //State mismatch
            notify()->error('Something went wrong, please try again!');;
        }

        return redirect()->route('party');
    }

    public function searchTrack(Request $request) {
        $this->api->setAccessToken($request->session()->get('spotifyToken'));
        $limit = 5;

        $result = $this->api->search($request->collect()['query'], 'track', [
        'limit' => $limit,
        'market' => config('spotify.default_config.market'),
        ]);

        $result = $result->tracks->items;

        $filteredResult = [];

        for ($i=0; $i < $limit; $i++) {
            $artists = [];
            foreach ($result[$i]->artists as $artist) {
                array_push($artists, $artist->name);
            }

            array_push($filteredResult, [
                'image' => $result[$i]->album->images[1]->url, //300x300
                'title' => $result[$i]->name,
                'artists' => $artists,
                'length' => $result[$i]->duration_ms,
            ]);
        }


        return response()->json($filteredResult);
    }
}
