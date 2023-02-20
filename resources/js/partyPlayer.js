const playerPrevObj = document.querySelector("#spotify_player_previous");
const playerTogglePlayObj = document.querySelector(
    "#spotify_player_toggle_play"
);
let playIcon = document.querySelector("#play-icon");
let pauseIcon = document.querySelector("#pause-icon");
const playerNextObj = document.querySelector("#spotify_player_next");
const playerImageObj = document.querySelector("#spotify_player_image");
const playerTitleObj = document.querySelector("#spotify_player_title");
const playerArtistObj = document.querySelector("#spotify_player_artist");

const csrfToken = document.head.querySelector("meta[name=csrf-token]").content;

playerTogglePlayObj.addEventListener("click", playerTogglePlay);
playerPrevObj.addEventListener("click", playerPrev);
playerNextObj.addEventListener("click", playerNext);

let player;
let volume = 0.5;

function activatePlayer() {
    window.onSpotifyWebPlaybackSDKReady = () => {
        initPlayer();
        //TODO: terminate loading if error throws
        addListenersToPlayer();
        connectPlayer();
    };
}
activatePlayer();

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
            activatePlayer();
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
            if(position === duration) {
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

function playerPrev() {
    player.previousTrack();
}

function playerTogglePlay() {
    player.togglePlay();
}

function playerNext() {
    playNextTrack();
    //player.nextTrack();
}

function updatePlayerGUI(isPaused, track) {
    if (isPaused) {
        pauseIcon.hidden = true;
        playIcon.hidden = false;
    } else {
        pauseIcon.hidden = false;
        playIcon.hidden = true;
    }

    playerImageObj.src = track["album"]["images"][0]["url"];
    playerTitleObj.innerHTML = track["name"];
    let artists = "";
    track["artists"].forEach((artist) => {
        artists += artist["name"] + ", ";
    });
    artists = artists.substring(0, artists.length - 2);
    playerArtistObj.innerHTML = artists;
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
