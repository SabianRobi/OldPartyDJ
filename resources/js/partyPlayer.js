import { throttle } from "lodash";
import {
    dataSaver,
    pushFeedback,
    spoitfyToken,
    setSpotifyToken,
    getSpotifyToken,
    refreshToken,
    csrfToken,
} from "./partyCommon.js";

// const playerPrevObj = document.querySelector("#spotify_player_previous");
const playerTogglePlayObj = document.querySelector("#player_toggle_play");
const togglePlayIcon = document.querySelector("#player_toggle_play");
const playerNextObj = document.querySelector("#player_next");
const playerImageObj = document.querySelector("#player_image");
const playerTitleObj = document.querySelector("#player_title");
const playerArtistObj = document.querySelector("#player_artist");

playerTogglePlayObj.addEventListener("click", playerTogglePlay);
// playerPrevObj.addEventListener("click", playerPrev);
playerNextObj.addEventListener("click", playerNext);
const playerVolumeBar = document.querySelector("#player_volume");
playerVolumeBar.addEventListener("input", throttle(onVolumeChange, 1000));

let SPPlayer;
let YTPlayer;
let volume = 0.25;
let firstStart = true;
let currentTrack = {
    isPlaying: false,
    platform: "",
};

// Initalizing YT player:
let tag = document.createElement("script");
tag.src = "https://www.youtube.com/iframe_api";
let firstScriptTag = document.getElementsByTagName("script")[0];
firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

function onYouTubeIframeAPIReady() {
    YTPlayer = new YT.Player("YTPlayerDiv", {
        height: "200",
        width: "200",
        videoId: "dQw4w9WgXcQ",
        playerVars: {
            playsinline: 1,
            controls: 0,
            disablekb: 1,
            enablejsapi: 1,
            fs: 0,
            rel: 0,
        },
        events: {
            onReady: onYTPlayerReady,
            onStateChange: onYTPlayerStateChange,
        },
    });
}
setTimeout(() => {
    onYouTubeIframeAPIReady();
}, 500);

window.onSpotifyWebPlaybackSDKReady = async () => {
    setSpotifyToken(await getSpotifyToken());
    activatePlayerInner();
};

function activatePlayerInner() {
    initPlayer();
    //TODO: terminate loading if error throws
    addListenersToPlayer();
    connectPlayer();
}

function initPlayer() {
    SPPlayer = new Spotify.Player({
        name: "PartyDJ Web Player",
        getOAuthToken: async (callback) => {
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

function addListenersToPlayer() {
    // Ready
    SPPlayer.addListener("ready", ({ device_id }) => {
        console.log("Ready with Device ID", device_id);
        setDeviceId(device_id);
    });

    // Not Ready
    SPPlayer.addListener("not_ready", ({ device_id }) => {
        console.log("Device ID has gone offline", device_id);
    });

    SPPlayer.addListener("initialization_error", ({ message }) => {
        console.error("initialization_error:", message);
    });

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

    SPPlayer.addListener("account_error", ({ message }) => {
        console.error("account_error:", message);
    });

    // TODO random error: cannot destructure 'position' (when sp token expires?)
    SPPlayer.addListener(
        "player_state_changed",
        async ({ position, duration, track_window: { current_track } }) => {
            if (currentTrack.platform === "Spotify") {
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
                if (position === duration) {
                    console.log("Track ended, playing next one...");
                    playNextTrack();
                }
            }
        }
    );

    SPPlayer.addListener("autoplay_failed", () => {
        console.log("Autoplay is not allowed by the browser autoplay rules");
    });
}

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

// function playerPrev() {
//     player.previousTrack();
// }

function playerTogglePlay(e) {
    pushFeedback(e.target);
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

function playerNext(e) {
    pushFeedback(e.target);
    playNextTrack();
    //player.nextTrack();
}

function onVolumeChange(e) {
    YTPlayer.setVolume(e.target.value * 100);
    SPPlayer.setVolume(e.target.value);
}

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
    updateMarquees();
}

async function setDeviceId(device_id) {
    console.log("Setting device ID");

    const form = new FormData();
    form.set("deviceId", device_id);

    const response = await fetch("/party/spotify/setDeviceId", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": csrfToken,
        },
        body: form,
    }).then((res) => res.json());

    if (response["playback_device_id"] == device_id) {
        console.log("Device ID set!");

        // When YT video is the first track in queue, start it
        if(response['platform'] === "YouTube") {
            if (currentTrack.isPlaying && currentTrack.platform === "Spotify") {
                SPPlayer.togglePlay();
            }
            YTPlayer.setVolume(playerVolumeBar.value*100);
            YTPlayer.loadVideoById(response["track_uri"]);
            currentTrack.platform = "YouTube";
        } else {
            currentTrack.platform = "Spotify";
        }
        currentTrack.isPlaying = true;
    } else if (response["tokenExpired"]) {
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

async function playNextTrack() {
    console.log("Sending request to play next track...");

    const response = await fetch("/party/playNextTrack", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": csrfToken,
        },
    }).then((res) => res.json());

    if (response["platform"] === "Spotify") {
        if (response["tokenExpired"]) {
            console.error(
                "Could not set Device ID due to expired token. Refreshing..."
            );
            await refreshToken();
            setSpotifyToken(await getSpotifyToken());
            playNextTrack();
        } else if (response["error"]) {
            console.error("Could not play next track:", response);
            return;
        }
        YTPlayer.pauseVideo();
    } else if (response["platform"] === "YouTube") {
        if (currentTrack.isPlaying && currentTrack.platform === "Spotify") {
            SPPlayer.togglePlay();
        }
        YTPlayer.setVolume(playerVolumeBar.value*100);
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

function onYTPlayerReady() {
    console.log("YouTube player is ready to play videos!");
    YTPlayer.setVolume(volume * 100);
}

function onYTPlayerStateChange(event) {
    console.log("YT player state changed to:", event);

    if(event.data === 0) {
        console.log("Track ended, playing next one...");
        playNextTrack();
        return;
    }

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

// TODO list:
// YT image
// if the first track is from yt, it wont start

// Remove required login to Spotify | PREMIUM NOT REQUIRED !!
// Next page search on YT
// addedToQueueFeedback
