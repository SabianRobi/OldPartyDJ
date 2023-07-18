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

let player;
let volume = 0.25;
let firstStart = true;

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
    player = new Spotify.Player({
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
    player.addListener("ready", ({ device_id }) => {
        console.log("Ready with Device ID", device_id);
        setDeviceId(device_id);
    });

    // Not Ready
    player.addListener("not_ready", ({ device_id }) => {
        console.log("Device ID has gone offline", device_id);
    });

    player.addListener("initialization_error", ({ message }) => {
        console.error("initialization_error:", message);
    });

    player.addListener("authentication_error", async ({ message }) => {
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

    player.addListener("account_error", ({ message }) => {
        console.error("account_error:", message);
    });

    player.addListener(
        "player_state_changed",
        ({ paused, position, duration, track_window: { current_track } }) => {
            updatePlayerGUI(paused, current_track);
            if (position === duration) {
                console.log("Track ended, playing next one...");
                playNextTrack();
            }
        }
    );

    player.addListener("autoplay_failed", () => {
        console.log("Autoplay is not allowed by the browser autoplay rules");
    });
}

function connectPlayer() {
    player.connect().then((success) => {
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
    player.togglePlay();
}

function playerNext(e) {
    pushFeedback(e.target);
    playNextTrack();
    //player.nextTrack();
}

function onVolumeChange(e) {
    player.setVolume(e.target.value);
}

async function updatePlayerGUI(isPaused, track) {
    togglePlayIcon.src = isPaused
        ? togglePlayIcon.dataset.startedSrc
        : togglePlayIcon.dataset.pausedSrc;
    playerImageObj.src = dataSaver
        ? "images/party/defaultCover.png"
        : track["album"]["images"][0]["url"];
    playerTitleObj.innerHTML = track["name"];
    let artists = "";
    track["artists"].forEach((artist) => {
        artists += artist["name"] + ", ";
    });
    artists = artists.substring(0, artists.length - 2);
    playerArtistObj.innerHTML = artists;
    updateMarquees();

    playerVolumeBar.value = await player.getVolume();
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

    if (response["tokenExpired"]) {
        console.error(
            "Could not set Device ID due to expired token. Refreshing..."
        );
        await refreshToken();
        setSpotifyToken(await getSpotifyToken());
        playNextTrack();
    } else if (response["error"]) {
        console.error("Could not play next track:", response);
    } else {
        console.log(
            `Playing track: ${response["track_uri"]} (${
                response["is_recommended"] ? "recommended" : "from queue"
            })`
        );
    }
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

console.log("Player JS successfully loaded!");
