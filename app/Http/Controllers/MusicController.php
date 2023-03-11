<?php

namespace App\Http\Controllers;

use App\Models\Party;
use App\Models\PartyParticipant;
use App\Models\MusicQueue;
use Illuminate\Http\Request;
use SpotifyWebAPI;
use App\Models\SpotifyState;
use App\Models\SpotifyToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

use function PHPUnit\Framework\isEmpty;

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

                $this->refreshToken();
                notify()->success("Successfully updated Spotify access token!");
                $this->searchTrack($request);
                die();

                //return redirect([MusicController::class, 'searchTrack']);



                // $this->session->refreshAccessToken($token->refresh_token);

                // $options = [
                //     'auto_refresh' => true,
                // ];
                // $this->api->setSession($this->session);

                // $token->token = $this->session->getAccessToken();
                // $token->refresh_token = $this->session->getRefreshToken();
                // $token->save();


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
                'uri' => $result[$i]->uri,
            ]);
        }

        return response()->json($filteredResult);
    }

    public function refreshToken()
    {
        $token = SpotifyToken::firstWhere('user_id', auth()->user()->id);
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
                'deviceId' => 'required',
            ],
        );

        $party = Party::firstWhere('user_id', auth()->user()->id);
        $party->playback_device_id = $validated['deviceId'];
        $party->save();

        $data = [
            'playback_device_id' => $party->playback_device_id,
        ];

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

        $participant = PartyParticipant::where([
            ['user_id', '=', auth()->user()->id],
        ])->get()->first();
        $party = Party::find($participant->party_id)->first();

        $trackInQueue = new MusicQueue();
        $trackInQueue->party_id = $party->id;
        $trackInQueue->user_id = auth()->user()->id;
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
        $participant = PartyParticipant::firstWhere('user_id', auth()->user()->id);
        if (strcmp($participant->role, "creator")) {
            return response()->json(['message' => 'You do not have permission to do this action!'], 403);
        }

        $token = SpotifyToken::firstWhere('user_id', auth()->user()->id);
        $this->api->setAccessToken($token->token);
        $this->session->setAccessToken($token->token);

        $party = Party::firstWhere('user_id', auth()->user()->id);
        $nextTrack = MusicQueue::firstWhere('party_id', $party->id);

        if (!isset($nextTrack)) {
            $party->waiting_for_track = true;
            $party->save();

            return response()->json(['error' => 'There is no next track in queue!']);
        }

        $options = [
            'uris' => [$nextTrack->track_uri],
        ];
        $this->api->play($party->playback_device_id, $options);
        $nextTrack->delete();

        return response()->json(['track_uri' => $nextTrack->track_uri]);

        // $this->session->refreshAccessToken($token->refresh_token);

        // $partyId = DB::table('party_participants')->where([
        //     ['user_id', '=', auth()->user()->id],
        // ])->get('party_id');

        // if ($partyId->isEmpty()) {
        //     return response()->json(['error' => 'Not in a party!']);
        // }
        // $partyId = $partyId[0]->party_id;

        // //TODO: Verify if the user is creator

        // $track = MusicQueue::where('party_id', $partyId)
        //     ->orderByDesc('score')
        //     ->get()
        //     ->first();

        // $track->delete();

        // $data = [
        //     'uri' => $track->track_uri,
        //     'platform' => $track->platform,
        // ];
        //return response()->json($data);
    }
}
