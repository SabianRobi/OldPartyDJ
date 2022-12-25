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

playerTogglePlayObj.addEventListener("click", playerTogglePlay);
playerPrevObj.addEventListener("click", playerPrev);
playerNextObj.addEventListener("click", playerNext);

let player;
let volume = 0.5;

function initPlayer() {
    player = new Spotify.Player({
        name: "PartyDJ Web Player",
        getOAuthToken: (callback) => {
            callback(token);
        },
        volume: volume,
    });
}

window.onSpotifyWebPlaybackSDKReady = () => {
    initPlayer();

    // Ready
    player.addListener("ready", ({ device_id }) => {
        console.log("Ready with Device ID", device_id);
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
        if(message === "Authentication error.") {
            console.log('Refreshing access token...');
            await refreshToken();
            console.log('Refreshed access token, initalizing player again...');
            initPlayer();
            console.log('Success!');
        }
    });

    player.addListener("account_error", ({ message }) => {
        console.error(message);
    });

    player.addListener(
        "player_state_changed",
        ({ paused, track_window: { current_track } }) => {
            // console.log("Currently Playing", current_track["name"]);
            console.log(current_track);
            updatePlayerGUI(paused, current_track);
        }
    );

    player.connect().then((success) => {
        if (success) {
            console.log(
                "The Web Playback SDK successfully connected to Spotify!"
            );
        }
    });
};

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
    player.nextTrack();
}

function updatePlayerGUI(isPaused, track) {
    console.log(pauseIcon);
    if (isPaused) {
        console.log("when stops");
        pauseIcon.hidden = true;
        playIcon.hidden = false;
    } else {
        console.log("when starts");
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
