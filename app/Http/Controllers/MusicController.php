<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SpotifyWebAPI;
use App\Models\SpotifyState;
use App\Models\SpotifyToken;

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
        $options = [
            'auto_refresh' => true,
        ];
        $this->api = new SpotifyWebAPI\SpotifyWebAPI($options, $this->session);
    }


    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if(auth()->check()) {
            if(auth()->user()->party_role == "creator") {
                return view('party', [
                    'user' => auth()->user(),
                    'spotifyToken' => (SpotifyToken::where('user_id', auth()->user()->id)->first())->token,
                ]);
            }

        }
        return view('party', [
            'user' => auth()->user()
        ]);
    }

    public function doLogin(Request $request)
    {
        // Generate state
        $state = $this->session->generateState();

        // Save state to db
        $spotState = new SpotifyState();
        $spotState->state = $state;
        $spotState->save();

        // Set options
        $options = [
            'scope' => [
                // 'user-read-email',
                'streaming',
                'user-modify-playback-state'
            ],
            'state' => $state,
        ];

        // Redirect the user
        $url = $this->session->getAuthorizeUrl($options);
        header('Location: ' . $url);
        die();
    }

    public function callback(Request $request)
    {
        //Check if the state received from Spotify is matches the state we stored before redirecting to Spotify
        $spotState = SpotifyState::find($request->collect()['state']);
        if (!is_null($spotState)) {
            $spotState->delete();

            $this->session->requestAccessToken($request->collect()['code']);

            //If its his first login with Spotify, store the Spoitfy tokens in the db
            $token = (SpotifyToken::where('user_id', auth()->user()->id)->firstOr(function () {
                $spotToken = new SpotifyToken();
                $spotToken->user_id = auth()->user()->id;
                $spotToken->token = $this->session->getAccessToken();
                $spotToken->refresh_token = $this->session->getRefreshToken();
                $spotToken->save();
            }));

            // if (!empty($token->token)) {

            //     $token->save();
            // } else {
            //     SpotifyToken::create([
            //         'user_id' => auth()->user()->id,
            //         'token' => $this->session->getAccessToken(),
            //         'refresh_token' => $this->session->getRefreshToken(),
            //     ]);
            // }
            session(['spotifyToken' => true]);

            notify()->success('Successfully logged in with Spotify!');
        } else {
            //State mismatch
            notify()->error('Something went wrong, please try again!');;
        }

        return redirect()->route('party');
    }

    public function searchTrack(Request $request)
    {
        $token = (SpotifyToken::where('user_id', auth()->user()->id)->first());
        $this->api->setAccessToken($token->token);
        $this->session->setAccessToken($token->token);
        $this->session->setRefreshToken($token->refresh_token);
        $limit = 5;
        $result = [];

        try {
            $result = $this->api->search($request->collect()['query'], 'track', [
                'limit' => $limit,
                'market' => config('spotify.default_config.market'),
            ]);
        } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
            if ($e->hasExpiredToken()) {
                //TODO it doesn't come here anytime, WHY? (cause of autorefresh turned on?)
                //$refreshToken = $token->refresh_token;
                $this->session->refreshAccessToken();

                // $options = [
                //     'auto_refresh' => true,
                // ];
                $this->api->setSession($this->session);

                $token->token = $this->session->getAccessToken();
                $token->refresh_token = $this->session->getRefreshToken();
                $token->save();

                return 'Successfully updated Spotify access token!';

                // notify()->success("Successfully updated Spotify access token!");
            } else {
                throw new SpotifyWebAPI\SpotifyWebAPIException;
            }
        }

        $result = $result->tracks->items;

        $filteredResult = [];

        for ($i = 0; $i < $limit; $i++) {
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

    function refreshToken(Request $request) {
        $token = SpotifyToken::firstWhere('user_id', auth()->user()->id);
        $this->session->refreshAccessToken($token->refresh_token);
        $this->api->setSession($this->session);

        $token->token = $this->session->getAccessToken();
        $token->refresh_token = $this->session->getRefreshToken();
        $token->save();

        return response()->json($token->token);
    }
}
