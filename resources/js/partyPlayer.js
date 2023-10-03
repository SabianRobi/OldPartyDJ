// This file only loads when the user is creator!

import { isNull, throttle } from "lodash";
import {
    dataSaver,
    pushFeedback,
    spoitfyToken,
    setSpotifyToken,
    getSpotifyToken,
    refreshToken,
    csrfToken,
} from "./partyCommon.js";
import { isSpotifyEnabled } from "./party.js";

const playerTogglePlayObj = document.querySelector("#player_toggle_play");  // Play/pause button in creator view
const togglePlayIcon = document.querySelector("#player_toggle_play");       // it is the same but with other name - idk why, should be refactored to use one
const playerNextObj = document.querySelector("#player_next");               // next button in creator view
const playerImageObj = document.querySelector("#player_image");             // cover image of the currently playing track in creator view
const playerTitleObj = document.querySelector("#player_title");             // title of the currently playing track in creator view
const playerArtistObj = document.querySelector("#player_artist");           // The list of artists of the currently playing track in creator view

// Connecting the buttons with the functions
playerTogglePlayObj.addEventListener("click", playerTogglePlay);
playerNextObj.addEventListener("click", playerNext);
const playerVolumeBar = document.querySelector("#player_volume");
playerVolumeBar.addEventListener("input", throttle(onVolumeChange, 1000));
document.addEventListener("keypress", keyPressed);

let SPPlayer = null;        // Spotify Player object - we can control the playback through this object, see doc: https://developer.spotify.com/documentation/web-playback-sdk/reference#spotifyplayer
let YTPlayer;               // YouTube Player (IFrame) object - we can control the playback through this object, see doc: https://developers.google.com/youtube/iframe_api_reference
let volume = 0.25;          // Currently set volume level
let firstStart = true;      // Explained at
let currentTrack = {        // Infos about the currently playing track
    isPlaying: false,       //  - does it playing
    platform: "",           //  - on which platform
};
let SpotifyEndTrackCounter = 0; // Explained at

// This funciton is called when the creator presses the space bar -> Pauses/plays the currently playing music
function keyPressed(event) {
    // Space pressed and not pressed while typing a song title to search for
    if (event.code === "Space" && event.srcElement.id !== "query") {
        playerTogglePlayObj.click();
    }
}

// Initalizing YT player (loading the YouTube IFrame API [https://developers.google.com/youtube/iframe_api_reference]):
let tag = document.createElement("script");
tag.src = "https://www.youtube.com/iframe_api";
let firstScriptTag = document.getElementsByTagName("script")[0];
firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

// This function should be called automatically by the previously loaded YouTube IFrame API script, but it did not work for me, that's why the settimeout after this function
// Creates the YouTube player itself with the given data [https://developers.google.com/youtube/iframe_api_reference#Loading_a_Video_Player]
function onYouTubeIframeAPIReady() {
    YTPlayer = new YT.Player("YTPlayerDiv", {
        height: "200",
        width: "200",
        videoId: "dQw4w9WgXcQ",
        origin: "http://localhost:8000", //TODO test this deployed
        playerVars: {
            origin: "http://localhost:8000",
            playsinline: 1,
            controls: 0,
            disablekb: 1,
            enablejsapi: 1,
            fs: 0,
            rel: 0,
        },
        events: {
            onReady: onYTPlayerReady,               // Will be called when the YT player becomes ready
            onStateChange: onYTPlayerStateChange,   // Will fire the the state if the YT player changes (end of track/pause/new track/etc)
        },
    });
}
setTimeout(() => {
    onYouTubeIframeAPIReady();
}, 2000);


// This will automatically will start initailizing the Spotify Web Player when the Spotify Web Playback script is fully loaded (import of this script is decalred at resources/views/party/party.blade.php:17)
window.onSpotifyWebPlaybackSDKReady = async () => {
    setSpotifyToken(await getSpotifyToken());   // Gets the Spotify token from the backend, then sets it to to spoitfyToken variable by the setSpotifyToken function
    activatePlayerInner();                      // Starts the Spotify player initailization
};

// Called when the Spotify playback script is fully loaded (by onSpotifyWebPlaybackSDKReady)
function activatePlayerInner() {
    initSpotifyPlayer();                        // Creates the Spotify player itself
    //TODO: terminate loading if error throws
    addListenersToPlayer();
    connectPlayer();
}

// Creates the Spotify player
function initSpotifyPlayer() {
    SPPlayer = new Spotify.Player({
        name: "PartyDJ Web Player",
        getOAuthToken: async (callback) => {
            // This chunk of code will fire when the Spotify player run into erros, and it is because of an expired token.
            // Refresh token here and call the 'callback' function with the new token.
            if (!firstStart) {
                await refreshToken();
                firstStart = false;
            }
            setSpotifyToken(await getSpotifyToken());
            callback(spoitfyToken);
        },
        volume: volume,
    });
}

// Adds listeners to the Spotify Player for contorlling the GUI and error handling
function addListenersToPlayer() {
    // When the Spotify player will be ready to play tracks (= the player fully loaded)
    SPPlayer.addListener("ready", ({ device_id }) => {
        console.log("Ready with Device ID", device_id);
        setDeviceId(device_id);     // Sends the Spotify playback's ID to the backend
    });

    // Not Ready
    SPPlayer.addListener("not_ready", ({ device_id }) => {
        console.log("Device ID has gone offline", device_id);
    });

    // Player state changed
    // This runs whenever the player changes (on play/pause, end of track, CAN BE FIRED AT RANDOM TIMES also)
    // Will provide us a WebPlaybackState object to work with [https://developer.spotify.com/documentation/web-playback-sdk/reference#webplaybackstate-object]
    // TODO random error: cannot destructure 'position' (when sp token expires?)
    SPPlayer.addListener(
        "player_state_changed",
        async ({ track_window: { current_track, previous_tracks } }) => {
            // 'if' needed because it can fire at random times, make sure to act when the Spotify is playing only.
            if (currentTrack.platform === "Spotify") {
                // Updating the player GUI (this includes the artist/title/cover image/volume) - on the creator view
                let artists = "";
                current_track["artists"].forEach((artist) => {
                    artists += artist["name"] + ", ";
                });
                artists = artists.substring(0, artists.length - 2);

                const GUInfos = {
                    imageSrc: current_track["album"]["images"][0]["url"],
                    title: current_track["name"],
                    artists: artists,
                    volume: await SPPlayer.getVolume(),
                };
                updatePlayerGUI(GUInfos);

                // When a track as ended
                if (
                    previous_tracks.find(
                        (track) => track.id === current_track.id
                    )
                ) {
                    // Counter is neccessary, because the event fires 3 times at once - idk why, but it works like 99.99%
                    SpotifyEndTrackCounter++;
                    if (SpotifyEndTrackCounter % 3 == 0) {
                        console.log("Track ended, playing next one...");
                        currentTrack.isPlaying = false;
                        playNextTrack();    // Plays the next track
                    }
                }
            }
        }
    );

    // In theory they shouldn't fire when everything is okay
    // Error descriptions: https://developer.spotify.com/documentation/web-playback-sdk/reference#errors

    // Autoplay failed
    SPPlayer.addListener("autoplay_failed", () => {
        console.log("Autoplay is not allowed by the browser autoplay rules");
    });

    // Init error
    SPPlayer.addListener("initialization_error", ({ message }) => {
        console.error("initialization_error:", message);
    });

    // Auth error
    // When does occur, we try to refresh the SP token and try again
    SPPlayer.addListener("authentication_error", async ({ message }) => {
        console.error("authentication_error:", message);
        if (message === "Authentication failed") {
            const success = await refreshToken();
            setSpotifyToken(await getSpotifyToken());
            if (!success) {
                console.error("Could not refresh Spotify token!");
                return;
            }
            console.log("Initalizing player again...");
            activatePlayerInner();
        }
    });

    // Account error
    SPPlayer.addListener("account_error", ({ message }) => {
        console.error("account_error:", message);
    });

    // Playback error
    SPPlayer.addListener("playback_error", ({ message }) => {
        console.error("playback_error:", message);
    });
}

// Connects the Spotify player to the Spotify servers, after this you can see this new 'device' in your other Spotify apps.
function connectPlayer() {
    SPPlayer.connect().then((success) => {
        if (success) {
            console.log(
                "The Web Playback SDK successfully connected to Spotify!"
            );
        } else {
            console.error("Could not connect player!");
        }
    });
}

// Plays/pauses the music on the correct platform
function playerTogglePlay(e) {
    pushFeedback(e.target); // fancy user feedback
    if (currentTrack.platform === "Spotify") {
        SPPlayer.togglePlay();
    } else if (currentTrack.platform === "YouTube") {
        if (currentTrack.isPlaying) {
            YTPlayer.pauseVideo();
        } else {
            YTPlayer.playVideo();
        }
    }
    currentTrack.isPlaying = !currentTrack.isPlaying;
}

// Skips the current song and plays the next one, detailed at the other function
function playerNext(e) {
    pushFeedback(e.target); // fancy user feedback
    playNextTrack();
}

// Sets the Spotify and YouTube player's volume level to the volume bar displayed in the creator view
function onVolumeChange(e) {
    YTPlayer.setVolume(e.target.value * 100);
    if (isSpotifyEnabled) {
        SPPlayer.setVolume(e.target.value);
    }
}

// Updates the track player GUI with the given data (only visible in creator view)
async function updatePlayerGUI(infos) {
    togglePlayIcon.src = currentTrack.isPlaying
        ? togglePlayIcon.dataset.pausedSrc
        : togglePlayIcon.dataset.startedSrc;
    playerImageObj.src = dataSaver
        ? "images/party/defaultCover.png"
        : infos.imageSrc;
    playerTitleObj.innerHTML = infos.title;
    playerArtistObj.innerHTML = infos.artists;
    playerVolumeBar.value = infos.volume;
    updateMarquees();   // Update the need of scrolling text if the artist name/title is too long to display correctly.
}

// Sends the Spotify Player's ID to the backend
async function setDeviceId(device_id) {
    console.log("Setting device ID");

    // Creating and sending the form to the backend
    const form = new FormData();
    form.set("deviceId", device_id);

    const response = await fetch("/party/spotify/setDeviceId", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": csrfToken,
        },
        body: form,
    }).then((res) => res.json());

    // Check if it was a success
    if (response["playback_device_id"] == device_id) {
        console.log("Device ID set!");

        // When YT video is the first track in queue, start it
        // May not be required
        if (response["platform"] === "YouTube") {
            if (currentTrack.isPlaying && currentTrack.platform === "Spotify") {
                // Pauses the music on Spotify player
                SPPlayer.togglePlay();
            }
            YTPlayer.setVolume(playerVolumeBar.value * 100);
            YTPlayer.loadVideoById(response["track_uri"]);
            currentTrack.platform = "YouTube";
        } else {
            currentTrack.platform = "Spotify";
        }
        //
        currentTrack.isPlaying = true;
    } else if (response["tokenExpired"]) {
        // Refreshing the token and calling the function again
        console.error(
            "Could not set Device ID due to expired token. Refreshing..."
        );
        refreshToken();
        setSpotifyToken(await getSpotifyToken());
        setDeviceId(device_id);
    } else {
        console.error("Could not set Device ID!", response);
    }
}

// Starts to play the next track form the queue
async function playNextTrack() {
    console.log("Sending request to play next track...");

    // Creating and sending the query to backend
    const responseObj = await fetch("/party/playNextTrack", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": csrfToken,  // Needed to prevent CSRF attacks, just live with it
        },
    });

    // Check for errors
    if (!responseObj.ok) {
        console.error(responseObj.statusText);
        return;
    }

    const response = await responseObj.json();  // Convert the response to JSON

    // More error handling
    if (response["error"]) {
        // Random errors
        console.error("Could not play next track:", response);
        return;
    } else if (response["platform"] === "Spotify") {
        if (response["tokenExpired"]) {
            //SP token expired
            console.error(
                "Could not set Device ID due to expired token. Refreshing..."
            );
            await refreshToken();
            setSpotifyToken(await getSpotifyToken());
            return playNextTrack();
        }
        // Until this backend already started the next song on the Spotify player, only have to pause the video in the YT player
        YTPlayer.pauseVideo();
    } else if (response["platform"] === "YouTube") {
        // Starting the YT video & pausing the SP player
        if (currentTrack.isPlaying && currentTrack.platform === "Spotify") {
            SPPlayer.togglePlay();
        }
        YTPlayer.loadVideoById(response["track_uri"]);
    }

    currentTrack.platform = response["platform"];
    currentTrack.isPlaying = true;
    console.log(
        `Playing track: ${response["track_uri"]} (${
            response["is_recommended"] ? "recommended" : "from queue"
        })`
    );
}

// Makes a text scrolling when it is more than 30 chars long
function updateMarquees() {
    document.querySelectorAll("marquee-text").forEach((obj) => {
        if (obj.innerText.length < 30) {
            obj.setAttribute("duration", "0s");
        } else {
            obj.setAttribute("duration", obj.dataset.duration);
        }
    });
}
updateMarquees();

// Will be called when the YT player becomes ready
// Starts the next track if Spotify is disabled (when SP enabled it starts automatically)
function onYTPlayerReady() {
    console.log("YouTube player is ready to play videos!");
    YTPlayer.setVolume(volume * 100);

    if (isNull(SPPlayer)) {
        console.log(
            "No Spotify connected, sneding request to play next track from YT"
        );
        playNextTrack();
    }
}

// Will be fired when the YT player changes states (toggleplay/end of track/etc)
function onYTPlayerStateChange(event) {
    console.log(event);
    if (event.data === 0) { // 0 means track ended
        console.log("Track ended, playing next one...");
        currentTrack.isPlaying = false;
        playNextTrack();
        return;
    }

    // Updating the creator's plaer GUI with the new infos
    const video = event.target.playerInfo.videoData;
    const GUInfos = {
        imageSrc: "images/party/defaultCover.png",
        title: video.title,
        artists: video.author,
        volume: YTPlayer.getVolume(),
    };
    updatePlayerGUI(GUInfos);
}

console.log("Player JS successfully loaded!");
