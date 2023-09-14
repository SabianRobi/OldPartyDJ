<?php

namespace App\Http\Controllers;

use App\Models\Party;
use App\Models\TrackInQueue;
use App\Models\TracksPlayedInParty;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserParty;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MusicController extends Controller
{
    public function searchTrack(Request $request)
    {
        try {
            $request->validate([
                'query' => 'required|string|min:1',
                'dataSaver' => 'required|boolean',
                'creator' => 'required|boolean',
                'platforms' => 'required|string',
                'offsets' => 'required|string',
                'limits' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Required fields did not specified!', 'message' => $e->getMessage()]);
        }

        $platforms = explode(',', $request->string('platforms'));
        $offsets = explode(',', $request->string('offsets'));
        $limits = explode(',', $request->string('limits'));

        // TODO: test this
        if (count($platforms) != count($offsets) || count($platforms) != count($limits)) {
            return response()->json(['error' => 'Platform count, offset count and limit count does not matches.']);
        }

        $query = $request->string('query');
        $dataSaver = $request->boolean('dataSaver');
        $isCreator = $request->boolean('creator');


        $results = [];

        for ($i = 0; $i < count($platforms); $i++) {
            $platform = $platforms[$i];
            $offset = $offsets[$i];
            $limit = $limits[$i];

            $platformResult = [
                'platform' => $platform,
                // 'tracks' => [],
                // 'error' => 'message'
            ];

            if ($platform == "Spotify") {
                $sp = new SpotifyController();
                $resp = $sp->searchTracks($query, $offset, $limit, $isCreator)->getData();

                // Check whether the search was successful
                if (in_array('error', get_object_vars($resp))) {
                    // On error
                    $platformResult['error'] = $resp->error;

                    // Check whether the search failed due to expired token
                    if (in_array('tokenExpired', get_object_vars($resp))) {
                        $platformResult['tokenExpired'] = $resp->tokenExpired;
                    }
                } else {
                    // On successful search
                    $tracks = $resp->tracks->items;
                    $filteredTracks = $sp->filterTracks($tracks, $dataSaver, true);
                    $platformResult['tracks'] = $filteredTracks;
                }
            }

            if ($platform == "YouTube") {
                $yt = new YouTubeController();

                $videoResponse = ($yt->searchVideos($query, $offset, $limit, $isCreator))->original;

                // Check whether the search was successful (empty object)
                if (count((array)$videoResponse) == 0) {
                    // On error
                    $platformResult['error'] = "YouTube API did not respond.";
                } else {
                    // On success
                    // return response()->json($videoResponse);

                    $videos = $videoResponse->items;
                    $filteredVideos = $yt->filterVideos($videos, $dataSaver, true);
                    $platformResult['tracks'] = $filteredVideos;
                    $platformResult['nextPageToken'] = $videoResponse->nextPageToken;
                }
            }
            array_push($results, $platformResult);
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
        $isFirstTrackYT = null;
        $isFirstTrackYT = $sp->activatePlayer();

        // TODO Sends bacb every time
        if (isset($isFirstTrackYT)) {
            return response()->json(array_merge($isFirstTrackYT->original, ['playback_device_id' => $party->playback_device_id]));
        } else {
            return response()->json($data);
        }
    }

    public function addTrackToQueue(Request $request)
    {
        $validated = $request->validate(
            [
                'uri' => 'required',
                'platform' => Rule::in(['Spotify', 'YouTube']),
            ],
        );

        $user = Auth::user();
        $partyId = UserParty::where('user_id', $user->id)->first()->party_id;
        $party = Party::find($partyId);

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

    public function removeTrackFromQueue(Request $request)
    {
        $validated = $request->validate(
            [
                'id' => 'required|integer|exists:track_in_queues,id',
            ],
        );

        $track = TrackInQueue::find($validated['id']);

        if (Auth::id() !== $track->addedBy) {
            return response()->json(['success' => false, 'error' => 'You can only remove tracks that you added!'], 403);
        }

        $track->delete();
        return response()->json(['success' => true]);
    }

    public function playNextTrack()
    {
        $user = Auth::user();
        $partyId = UserParty::where('user_id', $user->id)->first()->party_id;
        $party = Party::find($partyId);
        if (strcmp($party->creator, $user->id)) {
            return response()->json(['error' => 'You do not have permission to do this action!'], 403);
        }

        $nextTrack = TrackInQueue::where('party_id', $party->id)->where('currently_playing', false)->orderBy('score', 'DESC')->first();
        $isRecommended = false;

        // Move the played song into TrackPlayed db & delete from the queue
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

        // No next track in queue, get recommended song from spotify
        if (!isset($nextTrack)) {
            $playedTracks = TracksPlayedInParty::where('party_id', $party->id)->where('platform', "Spotify")->select('track_uri')->inRandomOrder()->take(5)->get()->toArray();

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

        // Start song on Spotify
        if ($nextTrack->platform === 'Spotify') {
            $sp = new SpotifyController();
            $response = $sp->playTrack($party->playback_device_id, $nextTrack->track_uri);

            // Error playing song
            if (!$response['success']) {
                return response()->json($response);
            }
        }

        // Set the new song currently_playing status to true
        $nextTrack->currently_playing = true;
        $nextTrack->save();

        // When next track is from YouTube, just send the uri, frontend handles the rest
        return response()->json(['platform' => $nextTrack->platform, 'track_uri' => $nextTrack->track_uri, 'is_recommended' => $isRecommended]);
    }

    public function getSongsInQueue(Request $request)
    {
        $dataSaver = $request->boolean('dataSaver');

        $user = Auth::user();
        $partyId = UserParty::where('user_id', $user->id)->first()->party_id;
        $songs = TrackInQueue::where('party_id', $partyId)->select('id', 'addedBy', 'platform', 'track_uri', 'score')->orderBy('score', 'DESC')->get()->toArray();

        if (count($songs) == 0) {
            return response()->json(['error' => 'There is no track in the queue!']);
        }

        $filteredSpTracks = [];
        $filteredYtTracks = [];

        // Spotify tracks
        $spTracks = array_values(array_filter($songs, function ($song) {
            return !strcmp($song['platform'], "Spotify");
        }));

        if (count($spTracks) > 0) {
            $sp = new SpotifyController();
            $spSongData = $sp->fetchTrackInfos($spTracks);

            if (!$spSongData['success']) {
                return response()->json(['success' => false, 'error' => 'Spotify token expired, please refresh it!', 'tokenExpired' => true]);
            }

            $filteredSpTracks = $sp->filterTracks($spSongData['tracks'], $dataSaver, false);
            for ($i = 0; $i < count($filteredSpTracks); $i++) {
                $filteredSpTracks[$i]['id'] = $spTracks[$i]['id'];
                if (!$dataSaver) {
                    $username = User::find($spTracks[$i]['addedBy'])->username;
                    $filteredSpTracks[$i]['addedBy'] = $username;
                }
            }
        }

        // YouTube videos
        $ytTracks = array_values(array_filter($songs, function ($song) {
            return !strcmp($song['platform'], "YouTube");
        }));

        if (count($ytTracks) > 0) {
            $yt = new YouTubeController();
            $ytVideoData = $yt->fetchVideoInfos($ytTracks);

            $filteredYtTracks = $yt->filterVideos($ytVideoData['tracks'], $dataSaver, false);

            for ($i = 0; $i < count($filteredYtTracks); $i++) {
                $filteredYtTracks[$i]['id'] = $ytTracks[$i]['id'];
                if (!$dataSaver) {
                    $username = User::find($ytTracks[$i]['addedBy'])->username;
                    $filteredYtTracks[$i]['addedBy'] = $username;
                }
            }
        }

        // TODO priority (DESC order in score) losed, temporarily using the id
        // Merge results and apply the sort (id)
        $allTracks = array_merge($filteredSpTracks, $filteredYtTracks);
        $sortedAllTrack = usort($allTracks, function ($t1, $t2) {
            return ($t1['id'] < $t2['id']) ? -1 : 1;
        });

        return response()->json($allTracks);
    }
}
