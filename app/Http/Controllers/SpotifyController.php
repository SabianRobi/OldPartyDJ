<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SpotifyThings;
use App\Models\TrackInQueue;
use App\Models\User;
use App\Models\UserParty;
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

    public function searchTracks(Request $request)
    {
        $this->setCredentials();
        $query = $request->input('query');
        $offset = $request->input('offset');
        $isCreator = $request->boolean('creator');
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
                    return $this->searchTracks($request);
                }
            } else {
                return response()->json(['error' => $e->getMessage()]);
            }
        }

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

    public function activatePlayer()
    {
        $this->setCredentials();

        $user = Auth::user();
        $partyId = UserParty::where('user_id', $user->id)->first()->party_id;

        $nextTrack = TrackInQueue::where('party_id', $partyId)->where('currently_playing', false)->orderBy('score', 'DESC')->first();
        if (!$nextTrack) {
            $track = new TrackInQueue();
            $track->party_id = $partyId;
            $track->addedBy = User::where('username', 'Spotify')->first()->id;
            $track->platform = "Spotify";
            $track->track_uri = $this->getRandomStarterTrack();
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

        try {
            $tracks = $this->api->getTracks($uris);
        } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
            if ($e->hasExpiredToken()) {
                return ['success' => false, 'error' => 'Spotify token expired, please refresh it!', 'tokenExpired' => true];
            } else {
                return ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return ['success' => true, 'tracks' => $tracks->tracks];
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

    private function getRandomStarterTrack()
    {
        $tracks = [
            "spotify:track:3UEnF6y5tyHVtMzldS3svp",
            "spotify:track:0yrlRdgnfEFvk5zlZ9yCKy",
            "spotify:track:5a3rLTbh7L7lBj5cflW3sf",
            "spotify:track:1TfqLAPs4K3s2rJMoCokcS",
            "spotify:track:0nrRP2bk19rLc0orkWPQk2",
            "spotify:track:50DMJJpAeQv4fIpxZvQz2e",
            "spotify:track:0YU17F0BlVXvmx5ytsR43w",
            "spotify:track:3H7ihDc1dqLriiWXwsc2po",
            "spotify:track:0AOmbw8AwDnwXhHC3OhdVB",
            "spotify:track:5pmL3RzOy3IvGFaSDi4hZL",
            "spotify:track:1Mu0qs9DQ8OfhiPvHxZMMM",
            "spotify:track:5ohL55vbPhN999ETafibnk",
            "spotify:track:4fouWK6XVHhzl78KzQ1UjL",
            "spotify:track:2woZDcgHTSK51f3UKuTGFj",
            "spotify:track:0UXm4C89srJgv7nCE4aXA3",
        ];
        return $tracks[array_rand($tracks)];
    }

    public function filterTracks($tracks, $dataSaver, $includeURI)
    {
        $filteredTracks = [];

        for ($i = 0; $i < count($tracks); $i++) {
            $artists = [];
            foreach ($tracks[$i]->artists as $artist) {
                array_push($artists, $artist->name);
            }

            if ($dataSaver) {
                if ($includeURI) {
                    array_push($filteredTracks, [
                        'title' => $tracks[$i]->name,
                        'artists' => $artists,
                        'uri' => $tracks[$i]->uri,
                    ]);
                } else {
                    array_push($filteredTracks, [
                        'title' => $tracks[$i]->name,
                        'artists' => $artists,
                    ]);
                }
            } else {
                array_push($filteredTracks, [
                    'image' => $tracks[$i]->album->images[1]->url, //300x300
                    'title' => $tracks[$i]->name,
                    'artists' => $artists,
                    'length' => $tracks[$i]->duration_ms,
                    'uri' => $tracks[$i]->uri,
                ]);
            }
        }
        return $filteredTracks;
    }
}
