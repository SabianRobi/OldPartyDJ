import { throttle } from "lodash";

// const playerPrevObj = document.querySelector("#spotify_player_previous");
const playerTogglePlayObj = document.querySelector("#player_toggle_play");
const togglePlayIcon = document.querySelector("#player_toggle_play");
const playerNextObj = document.querySelector("#player_next");
const playerImageObj = document.querySelector("#player_image");
const playerTitleObj = document.querySelector("#player_title");
const playerArtistObj = document.querySelector("#player_artist");

const csrfToken = document.head.querySelector("meta[name=csrf-token]").content;

playerTogglePlayObj.addEventListener("click", playerTogglePlay);
// playerPrevObj.addEventListener("click", playerPrev);
playerNextObj.addEventListener("click", playerNext);
const playerVolumeBar = document.querySelector("#player_volume");
playerVolumeBar.addEventListener("input", throttle(onVolumeChange, 1000));

let player;
let volume = 0.25;

function activatePlayerOuter() {
    window.onSpotifyWebPlaybackSDKReady = () => {
        activatePlayerInner();
    };
}
activatePlayerOuter();

function activatePlayerInner() {
    initPlayer();
    //TODO: terminate loading if error throws
    addListenersToPlayer();
    connectPlayer();
}

function initPlayer() {
    player = new Spotify.Player({
        name: "PartyDJ Web Player",
        getOAuthToken: (callback) => {
            callback(token);
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
        console.error(message);
    });

    player.addListener("authentication_error", async ({ message }) => {
        console.error(message);
        if (message === "Authentication failed") {
            console.log("Refreshing access token...");
            await refreshToken();
            console.log("Refreshed access token, initalizing player again...");
            activatePlayerInner();
            console.log("Success!");
        }
    });

    player.addListener("account_error", ({ message }) => {
        console.error(message);
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
}

function connectPlayer() {
    player.connect().then((success) => {
        if (success) {
            console.log(
                "The Web Playback SDK successfully connected to Spotify!"
            );
        }
    });
}

async function refreshToken() {
    token = await fetch("/party/spotify/refreshToken").then((res) =>
        res.json()
    );
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
    if (isPaused) {
        togglePlayIcon.src = togglePlayIcon.dataset.startedSrc;
    } else {
        togglePlayIcon.src = togglePlayIcon.dataset.pausedSrc;
    }

    playerImageObj.src = track["album"]["images"][0]["url"];
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
    } else {
        console.error("Could not set Device ID!");
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

    console.log(response);
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
