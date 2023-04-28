export let token = "";
export let dataSaver = false;
export const isCreator = Boolean(document.querySelector("#isCreator").innerHTML);

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
    token = await fetch("/party/spotify/refreshToken").then((res) =>
        res.json()
    );
    console.log("Refreshed token successfully!");
}

console.log("Party Common JS succesfully loaded!");
