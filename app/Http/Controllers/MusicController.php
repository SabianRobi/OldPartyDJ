<?php

namespace App\Http\Controllers;

use App\Models\Party;
use App\Models\TrackInQueue;
use App\Models\TracksPlayedInParty;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MusicController extends Controller
{
    public function searchTrack(Request $request)
    {
        try {
            $request->validate([
                'query' => 'required|string',
                'dataSaver' => 'required|boolean',
                'creator' => 'required|boolean',
                'offset' => 'required|integer|min:0',
                'platforms' => 'required|string'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Required fields did not specified!', 'message' => $e->getMessage()]);
        }

        $platforms = explode(',', $request->string('platforms'));
        $dataSaver = $request->boolean('dataSaver');
        $results = [];

        foreach ($platforms as $platform) {
            $platformResults = [
                'platform' => $platform,
                'tracks' => [],
            ];
            $filteredResult = [];

            if ($platform == "Spotify") {
                $sp = new SpotifyController();
                $result = ($sp->search($request))->original;

                if (isset($result['error'])) {
                    $platformResults['error'] = $result['error'];
                    if (isset($result['tokenExpired'])) {
                        $platformResults['tokenExpired'] = $result['tokenExpired'];
                    }
                } else {
                    $platformResults['tracks'] = $this->filterTracksForClient($result, $dataSaver, true);
                }
            }
            array_push($results, $platformResults);
        }

        return response()->json($results);
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

        $sp = new SpotifyController();
        $sp->activatePlayer($party->playback_device_id);

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
        $trackInQueue->currently_playing = false;
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

        $party = Party::where('creator', $user->id)->first();
        $nextTrack = TrackInQueue::where('party_id', $party->id)->where('currently_playing', false)->orderBy('score', 'DESC')->first();
        $isRecommended = false;

        //Move the played song into TrackPlayed db
        $nowEndedTrack = TrackInQueue::where('party_id', $party->id)->where('currently_playing', true)->first();

        if ($nowEndedTrack) {
            $playedTrack = new TracksPlayedInParty([
                "party_id" => $nowEndedTrack->party_id,
                "added_by" => $nowEndedTrack->addedBy,
                "platform" => $nowEndedTrack->platform,
                "track_uri" => $nowEndedTrack->track_uri,
            ]);
            $playedTrack->save();
            $nowEndedTrack->delete();
        }

        // No next track in queue, set recommended song
        if (!isset($nextTrack)) {
            $playedTracks = TracksPlayedInParty::where('party_id', $party->id)->select('track_uri')->inRandomOrder()->take(5)->get()->toArray();

            if (sizeof($playedTracks) > 0) {
                $sp = new SpotifyController();
                $recTrack = $sp->getRecommended($playedTracks);

                $nextTrack = new TrackInQueue();
                $nextTrack->party_id = $party->id;
                $nextTrack->addedBy = User::where('username', 'Spotify')->first()->id;
                $nextTrack->platform = $recTrack['platform'];
                $nextTrack->track_uri = $recTrack['uri'];
                $nextTrack->score = 0;
                $nextTrack->currently_playing = false;
                $nextTrack->save();

                $isRecommended = true;
            } else {
                return response()->json(['error' => 'There is no track in queue!']);
            }
        }

        //Start song
        $response = "";
        if ($nextTrack->platform === 'Spotify') {
            $sp = new SpotifyController();
            $response = $sp->playTrack($party->playback_device_id, $nextTrack->track_uri);
        }

        //Error playing song
        if (!$response['success']) {
            return response()->json($response);
        }

        //Set the new song currently_playing status to true
        $nextTrack->currently_playing = true;
        $nextTrack->save();

        return response()->json(['track_uri' => $nextTrack->track_uri, 'is_recommended' => $isRecommended]);
    }

    public function getSongsInQueue(Request $request)
    {
        $dataSaver = $request->boolean('dataSaver');

        $user = User::find(Auth::id());
        $songs = TrackInQueue::where('party_id', $user->party_id)->select('addedBy', 'platform', 'track_uri', 'score')->orderBy('score', 'DESC')->get();

        if (count($songs) == 0) {
            return response()->json(['error' => 'There is no track in the queue!']);
        }

        $sp = new SpotifyController();
        $songData = $sp->fetchTrackInfos($songs);

        $filteredTracks = $this->filterTracksForClient($songData, $dataSaver, false);
        for ($i = 0; $i < count($filteredTracks) && !$dataSaver; $i++) {
            $username = User::find($songs[$i]->addedBy)->username;
            $filteredTracks[$i]['addedBy'] = $username;
        }
        return response()->json($filteredTracks);
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
