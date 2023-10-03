// Contains the Spotify token
export let spoitfyToken = "";
// Sets the Spotify token to a new value (the new value is must be queried from the backend, use getSpotifyToken())
export function setSpotifyToken(newToken) { Sets
    spoitfyToken = newToken;
}
// Gets the user's Spotify token from the backend
export async function getSpotifyToken() {
    const result = await fetch("/platforms/spotify/token").then((res) =>
        res.json()
    );
    return result.token;
}
// Datasaver & it's toggler function
export let dataSaver = false;
export function setDataSaver(newValue) {
    dataSaver = newValue;
}
// CSRF token to prevent CSRF attacks - just live with it
export const csrfToken = document.head.querySelector(
    "meta[name=csrf-token]"
).content;
// is the user creator?
export const isCreator = Boolean(
    document.querySelector("#isCreator").innerHTML
);

// Animates the given object
export function pushFeedback(e) {
    e.animate(animation, timing);
}
// Things for the animation
const animation = [
    {
        transform: "scale(1)",
    },
    {
        transform: "scale(0.9)",
    },
    {
        transform: "scale(1)",
    },
];
const timing = {
    duration: 150,
    iterations: 1,
};

// Sends a request to backend to refresh the spotify token, this function only refreshes the token at backend, we have to get and set at frontend also (getSpotifyToken, setSpotifyToken)
export async function refreshToken() {
    console.log("Refreshing Spotify token...");

    //Setting up the form
    const form = new FormData();

    const response = await fetch("/platforms/spotify/token", {
        method: "PATCH",
        headers: {
            "X-CSRF-TOKEN": csrfToken,
        },
        body: form,
    }).then((res) => res.json());

    // Error handling
    if (!response["success"]) {
        console.error("Could not refresh token!", response);
        return;
    } else {
        console.log("Refreshed token successfully!");
    }
    return response["success"];
}

console.log("Party Common JS succesfully loaded!");
