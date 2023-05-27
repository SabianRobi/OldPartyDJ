<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SpotifyThings;
use App\Models\TrackInQueue;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use SpotifyWebAPI;

class SpotifyController extends Controller
{
    private $session;
    private $api;

    public function __construct()
    {
        $this->session = new SpotifyWebAPI\Session(
            config('spotify.auth.client_id'),
            config('spotify.auth.client_secret'),
            config('spotify.auth.redirect_uri'),
        );
        $this->api = new SpotifyWebAPI\SpotifyWebAPI(null, $this->session);
    }

    private function setCredentials()
    {
        $token = SpotifyThings::where('owner', Auth::id())->first();
        $this->api->setAccessToken($token->token);
        $this->session->setAccessToken($token->token);
        $this->session->setRefreshToken($token->refresh_token);
    }

    //Login
    public function login()
    {
        // Generate state
        $state = $this->session->generateState();

        // Save state to db
        $spotify = SpotifyThings::where('owner', Auth::id())->first();
        if (!$spotify) {
            $spotify = new SpotifyThings();
            $spotify->owner = Auth::id();
        }
        $spotify->state = $state;
        $spotify->save();

        // Set options
        $options = [
            'scope' => [
                // 'user-read-email',
                'streaming',
                'user-modify-playback-state',
                'user-read-currently-playing',
                'user-read-playback-state'
            ],
            'state' => $state,
        ];

        // Redirect the user
        $url = $this->session->getAuthorizeUrl($options);
        return redirect($url);
    }

    //Callback
    public function callback(Request $request)
    {
        $spotify = SpotifyThings::where([
            ['state', $request->input('state')],
            ['owner', Auth::id()]
        ])->first();


        if ($spotify) {
            //State matches, store the token
            $this->session->requestAccessToken($request->input('code'));
            $spotify->token = $this->session->getAccessToken();
            $spotify->refresh_token = $this->session->getRefreshToken();
            $spotify->state = null;
            $spotify->save();

            notify()->success('Successfully logged in with Spotify!');
        } else {
            //State mismatch
            notify()->error('Something went wrong, please try again!');
        }
        return back();
    }

    //Disconnect
    public function disconnect()
    {
        $spotify = SpotifyThings::where('owner', Auth::id());
        if ($spotify->get()->isEmpty()) {
            notify()->error('Spotify account is not associated!');
            return back();
        }

        $spotify->delete();
        //TODO if still in a party, reload the page? Errors will occur!

        notify()->success('Successfully disconnected Spotify!');
        return back();
    }

    public function search(Request $request)
    {
        $this->setCredentials();
        $query = $request->input('query');
        $offset = $request->input('offset');
        $isCreator = $request->boolean('isCreator');

        $limit = 5;
        $result = [];

        try {
            $result = $this->api->search($query, 'track', [
                'limit' => $limit,
                'offset' => $offset,
                'market' => config('spotify.default_config.market'),
            ]);
        } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
            if ($e->hasExpiredToken()) {
                if ($isCreator) {
                    return response()->json(['error' => 'Spotify token expired, please refresh it!', 'tokenExpired' => true]);
                } else {
                    $this->refreshToken();
                    return $this->searchTrack($request);
                }
            } else {
                return response()->json(['error' => $e->getMessage()]);
            }
        }

        $result = $result->tracks->items;

        return response()->json($result);
    }

    public function refreshToken()
    {
        $this->setCredentials();
        $token = $this->getTokens();

        $success = $this->session->refreshAccessToken($token->refresh_token);

        if ($success) {
            $token->token = $this->session->getAccessToken();
            $token->refresh_token = $this->session->getRefreshToken();

            $this->api->setAccessToken($token->token);

            $token->save();
        }
        return response()->json(['success' => $success]);
    }

    private function getTokens()
    {
        return SpotifyThings::where('owner', Auth::id())->first();
    }

    public function getToken()
    {
        $token = $this->getTokens();
        return response()->json(['success' => true, 'token' => $token->token]);
    }

    public function activatePlayer($playbackDeviceId)
    {
        $this->setCredentials();
        $token = $this->getTokens();
        $this->api->setAccessToken($token->token);
        $this->session->setAccessToken($token->token);

        $user = Auth::user();

        $nextTrack = TrackInQueue::where('party_id', $user->party_id)->where('currently_playing', false)->orderBy('score', 'DESC')->first();
        if(!$nextTrack) {
            $track = new TrackInQueue();
            $track->party_id = $user->party_id;
            $track->addedBy = User::where('username', 'Spotify')->first()->id;
            $track->platform = "Spotify";
            $track->track_uri = "spotify:track:5ygDXis42ncn6kYG14lEVG"; //Baby Shark
            $track->score = 0;
            $track->currently_playing = false;
            $track->save();
        }

        $mc = new MusicController();
        $mc->playNextTrack();
    }

    public function playTrack($playbackDeviceId, $trackUri)
    {
        $this->setCredentials();
        $options = [
            'uris' => [$trackUri],
        ];

        try {
            $this->api->play($playbackDeviceId, $options);
        } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
            if ($e->hasExpiredToken()) {
                return ['success' => false, 'error' => 'Spotify token expired, please refresh it!', 'tokenExpired' => true];
            } else {
                return ['success' => false, 'error' => $e->getMessage()];
            }
        }
        return ['success' => true];
    }

    public function fetchTrackInfos($dbTracks)
    {
        $this->setCredentials();

        $uris = [];
        foreach ($dbTracks as $track) {
            array_push($uris, $track['track_uri']);
        }

        $tracks = $this->api->getTracks($uris);

        return $tracks->tracks;
    }

    public function getRecommended($queueTrackUris)
    {
        $this->setCredentials();

        $ids = [];
        $something = array_map(function ($uri) {
            return str_replace('spotify:track:', '', $uri);
        }, $queueTrackUris);

        foreach ($something as $pair => $id) {
            array_push($ids, $id['track_uri']);
        }

        $options = [
            'limit' => 1,
            'seed_tracks' => $ids,
        ];
        $recommendations = $this->api->getRecommendations($options);

        $data = [
            'platform' => "Spotify",
            'uri' => $recommendations->tracks[0]->uri,
        ];

        return $data;
    }
}
