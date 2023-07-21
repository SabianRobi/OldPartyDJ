<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use function PHPUnit\Framework\isNull;

class YouTubeController extends Controller
{
    private $api = 'https://www.googleapis.com/youtube/v3/';
    private $searchApi = 'search?part=snippet&kind=video&videoCaption=any';
    private $fetchVideosApi = 'videos?part=snippet';


    // Get search results
    public function searchVideos(Request $request)
    {
        $query = $request->input('query');
        // $offset = $request->input('offset'); // TODO
        $limit = 5;
        $result = [];

        // Send the query to YouTube
        $finalLink = $this->api . $this->searchApi . '&maxResults=' . $limit . '&q=' . str_replace(' ', '%20', $query) . '&key=' . env('YOUTUBE_API_KEY');
        // TODO: use a normal converter instead of str replace (for example & causes bad search results)
        $result = json_decode(@file_get_contents($finalLink));

        return response()->json($result);
    }

    public function filterVideos($videos, $dataSaver, $includeURI)
    {
        $filteredVideos = [];

        for ($i = 0; $i < count($videos); $i++) {
            $platform = 'YouTube';
            $title = $videos[$i]->snippet->title;
            $artists = array($videos[$i]->snippet->channelTitle);
            $uri = is_string($videos[$i]->id) ? $videos[$i]->id : $videos[$i]->id->videoId;
            $image = $videos[$i]->snippet->thumbnails->medium->url; // 320x180

            if ($dataSaver) {
                if ($includeURI) {
                    array_push($filteredVideos, [
                        'platform' => $platform,
                        'title' => $title,
                        'artists' => $artists,
                        'uri' => $uri,
                    ]);
                } else {
                    array_push($filteredVideos, [
                        'platform' => $platform,
                        'title' => $title,
                        'artists' => $artists,
                    ]);
                }
            } else {
                array_push($filteredVideos, [
                    'platform' => $platform,
                    'title' => $title,
                    'artists' => $artists,
                    'uri' => $uri,
                    'image' => $image,
                    'length' => 0, // TODO get the duration of the video also and include it here
                ]);
            }
        }
        return $filteredVideos;
    }

    public function fetchVideoInfos($dbVideos)
    {
        $uris = [];
        foreach ($dbVideos as $video) {
            array_push($uris, $video['track_uri']);
        }

        $finalLink = $this->api . $this->fetchVideosApi . '&key=' . env('YOUTUBE_API_KEY') . '&id=' . implode('&id=', $uris);


        $videos = json_decode(@file_get_contents($finalLink));

        return ['success' => true, 'tracks' => $videos->items];
    }

    public function test()
    {
        return view('test.yt');
    }
}
