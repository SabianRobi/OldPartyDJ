<?php

namespace App\Http\Controllers;

use App\Models\Party;
use App\Models\SpotifyThings;
use App\Models\TrackInQueue;
use Illuminate\Http\Request;
use SpotifyWebAPI;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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
            // 'auto_refresh' => true,
        ];
        $this->api = new SpotifyWebAPI\SpotifyWebAPI($options, $this->session);
    }

    public function spotifyLogin()
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
        header('Location: ' . $url);
        die();
    }

    public function spotifyCallback(Request $request)
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
        return redirect()->route('party');
    }

    public function searchTrack(Request $request)
    {
        //TODO validate?
        // $request->validate([
        //         'query' => 'required|string',
        //         'dataSaver' => 'required|bool',
        //         'creator' => 'nullable|bool',
        //         'offset' => 'required|number|min:0',
        // ]);
        $query = $request->input('query');
        $dataSaver = $request->boolean('dataSaver');
        $offset = $request->input('offset');

        $token = SpotifyThings::where('owner', Auth::id())->first();
        $this->api->setAccessToken($token->token);
        $this->session->setAccessToken($token->token);
        $this->session->setRefreshToken($token->refresh_token);
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
                if ($request->boolean('creator')) {
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

        $filteredResult = $this->filterTracksForClient($result, $dataSaver, true);

        return response()->json($filteredResult);
    }

    public function refreshToken()
    {
        $token = SpotifyThings::where('owner', Auth::id())->first();
        $this->session->refreshAccessToken($token->refresh_token);
        $this->api->setSession($this->session);

        $token->token = $this->session->getAccessToken();
        $token->refresh_token = $this->session->getRefreshToken();
        $token->save();

        return response()->json($token->token);
    }

    public function setDeviceId(Request $request)
    {
        $validated = $request->validate(
            [
                'deviceId' => ['required', 'string'],
            ],
        );

        $party = Party::where('creator', Auth::id())->first();
        $party->playback_device_id = $validated['deviceId'];
        $party->save();

        $data = [
            'playback_device_id' => $party->playback_device_id,
        ];

        // Activate the player by playing the currnetly playing song (in other player)
        $token = SpotifyThings::where('owner', Auth::id())->first();
        $this->api->setAccessToken($token->token);
        $this->session->setAccessToken($token->token);

        //Get the current playing song and continue it in the web player.
        try {
            $currentTrack = $this->api->getMyCurrentTrack();
            $options = [
                'uris' => [$currentTrack && $currentTrack->item ? $currentTrack->item->uri : 'spotify:track:4mPAxO918YuLgviTMMqw8P'],
                'position_ms' => $currentTrack && $currentTrack->item ? $currentTrack->progress_ms : 0,
            ];
            // $this->api->play($party->playback_device_id, $options);
        } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
            if ($e->hasExpiredToken()) {
                return response()->json(['error' => 'Spotify token expired, please refresh it!', 'tokenExpired' => true]);
            } else {
                return response()->json(['error' => $e->getMessage()]);
            }
        }
        return response()->json($data);
    }

    public function addTrackToQueue(Request $request)
    {
        $validated = $request->validate(
            [
                'uri' => 'required',
                'platform' => Rule::in(['Spotify']),
            ],
        );

        $user = User::find(Auth::id());
        $party = Party::find($user->party_id);

        $trackInQueue = new TrackInQueue();
        $trackInQueue->party_id = $party->id;
        $trackInQueue->addedBy = $user->id;
        $trackInQueue->platform = $validated['platform'];
        $trackInQueue->track_uri = $validated['uri'];
        $trackInQueue->score = 0;
        $trackInQueue->save();

        //Check if player stopped because of no more tracks were in the queue
        if ($party->waiting_for_track) {
            $party->waiting_for_track = false;
            $party->save();

            $this->playNextTrack();
        }

        $data = [
            'track_uri' => $trackInQueue->track_uri,
        ];
        return response()->json($data);
    }

    public function playNextTrack()
    {
        $user = User::find(Auth::id());
        $party = Party::find($user->party_id);
        if (strcmp($party->creator, $user->id)) {
            return response()->json(['error' => 'You do not have permission to do this action!'], 403);
        }

        $token = SpotifyThings::where('owner', $user->id)->first();
        $this->api->setAccessToken($token->token);
        $this->session->setAccessToken($token->token);

        $party = Party::where('creator', $user->id)->first();
        $nextTrack = TrackInQueue::where('party_id', $party->id)->orderBy('score', 'DESC')->first();

        if (!isset($nextTrack)) {
            $party->waiting_for_track = true;
            $party->save();

            return response()->json(['error' => 'There is no track in queue!']);
        }

        $options = [
            'uris' => [$nextTrack->track_uri],
        ];

        try {
            $this->api->play($party->playback_device_id, $options);
        } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
            if ($e->hasExpiredToken()) {
                return response()->json(['error' => 'Spotify token expired, please refresh it!', 'tokenExpired' => true]);
            } else {
                return response()->json(['error' => $e->getMessage()]);
            }
        }
        $nextTrack->delete();

        return response()->json(['track_uri' => $nextTrack->track_uri]);
    }

    public function getSongsInQueue(Request $request)
    {
        $dataSaver = $request->boolean('dataSaver');

        $user = User::find(Auth::id());
        $songs = TrackInQueue::where('party_id', $user->party_id)->select('addedBy', 'platform', 'track_uri', 'score')->orderBy('score', 'DESC')->get();
        if (count($songs) == 0) {
            return response()->json(['error' => 'There is no track in the queue!']);
        }
        $songData = $this->fetchTrackInfos($songs);

        $filteredTracks = $this->filterTracksForClient($songData, $dataSaver, false);
        for ($i = 0; $i < count($filteredTracks) && !$dataSaver; $i++) {
            $userId = $songs[$i]->user_id;
            $username = User::find($songs[$i]->addedBy)->username;
            $filteredTracks[$i]['addedBy'] = $username;
        }
        return response()->json($filteredTracks);
    }

    private function fetchTrackInfos($dbTrack)
    {
        $token = SpotifyThings::where('owner', Auth::id())->first();
        $this->api->setAccessToken($token->token);
        $this->session->setAccessToken($token->token);
        $this->session->setRefreshToken($token->refresh_token);

        $uris = [];
        foreach ($dbTrack as $track) {
            array_push($uris, $track['track_uri']);
        }

        $tracks = $this->api->getTracks($uris);

        return $tracks->tracks;
    }

    private function filterTracksForClient($tracks, $dataSaver, $includeURI)
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
