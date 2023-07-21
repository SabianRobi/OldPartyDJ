<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use function PHPUnit\Framework\isNull;

class YouTubeController extends Controller
{
    private $link = 'https://www.googleapis.com/youtube/v3/search?part=snippet&kind=video&videoCaption=any';

    // Get search results
    public function searchVideos(Request $request)
    {
        $query = $request->input('query');
        // $offset = $request->input('offset'); // TODO
        $limit = 5;
        $result = [];

        // Send the query to YouTube
        $finalLink = $this->link . '&key=' . env('YOUTUBE_API_KEY') . '&maxResults=' . $limit . '&q=' . str_replace(' ', '%20', $query);
        $result = json_decode(@file_get_contents($finalLink));

        return response()->json($result);
    }

    public function filterVideos($videos, $dataSaver, $includeURI)
    {
        $filteredVideos = [];

        for ($i = 0; $i < count($videos); $i++) {
            if ($dataSaver) {
                if ($includeURI) {
                    array_push($filteredVideos, [
                        'title' => $videos[$i]->snippet->title,
                        'artists' => array($videos[$i]->snippet->channelTitle),
                        'uri' => $videos[$i]->id->videoId,
                    ]);
                } else {
                    array_push($filteredVideos, [
                        'title' => $videos[$i]->snippet->title,
                        'artists' => array($videos[$i]->snippet->channelTitle),
                    ]);
                }
            } else {
                array_push($filteredVideos, [
                    'image' => $videos[$i]->snippet->thumbnails->medium->url, // 320x180
                    'title' => $videos[$i]->snippet->title,
                    'artists' => array($videos[$i]->snippet->channelTitle),
                    'length' => 0,
                    'uri' => $videos[$i]->id->videoId,
                ]);
            }
        }
        return $filteredVideos;
    }

    public function test()
    {
        return view('test.yt');
    }
}
