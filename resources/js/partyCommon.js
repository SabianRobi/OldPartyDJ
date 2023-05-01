export let spoitfyToken = "";
export function setSpotifyToken(newToken) {
    spoitfyToken = newToken;
}
export async function getSpotifyToken() {
    const result = await fetch("/platforms/spotify/token").then((res) =>
        res.json()
    );
    return result.token;
}
export let dataSaver = false;
export function setDataSaver(newValue) {
    dataSaver = newValue;
}
export const csrfToken = document.head.querySelector(
    "meta[name=csrf-token]"
).content;
export const isCreator = Boolean(
    document.querySelector("#isCreator").innerHTML
);

export function pushFeedback(e) {
    e.animate(animation, timing);
}
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

    if (!response["success"]) {
        console.error("Could not refresh token!", response);
        return;
    } else {
        console.log("Refreshed token successfully!");
    }
    return response["success"];
}

console.log("Party Common JS succesfully loaded!");
