# PartyDJ Used APIs

## Spotify

#### Web Playback SDK _(frontend)_

Link: https://developer.spotify.com/documentation/web-playback-sdk

This API is used to play tracks & control playback on Spotify at frontend.

#### Web API _(backend)_

Link: https://developer.spotify.com/documentation/web-api

This API is used to search in the Spotify catalog and get detailed infos about specific tracks (like artists, title, duration, cover image)

I dunno which endpoints we use from this API because I used a wrapper module that handles that for us.

## YouTube

#### IFrame Player API _(frontend)_

Link: https://developers.google.com/youtube/iframe_api_reference

This API is used to play & control videos on YouTube at frontend.

#### YouTube Data _(backend)_

Link: https://developers.google.com/youtube/v3/docs

This API is used to search videos in the Youtube catalog.

Used endpoints:

-   Searching tracks:
    https://developers.google.com/youtube/v3/docs/search/list
-   Getting detailed infos about a video:
    https://developers.google.com/youtube/v3/docs/videos
