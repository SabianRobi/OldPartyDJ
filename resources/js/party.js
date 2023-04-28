import { dataSaver, pushFeedback, isCreator, token } from "./partyCommon.js";

const searchForm = document.querySelector("#searchForm");
const queryInp = document.querySelector("#query");
const searchBtn = document.querySelector("#searchBtn");
const resultsUl = document.querySelector("#results");
const queueUl = document.querySelector("#queue");
const csrfToken = document.head.querySelector("meta[name=csrf-token]").content;

const getSongsBtn = document.querySelector("#getSongs");
const clearResultsBtn = document.querySelector("#clearResults");
const leaveParty = document.querySelector("#leaveParty");
const dataSaverObj = document.querySelector("#dataSaver");
searchBtn.addEventListener("click", sendSearchRequest);
// searchForm.addEventListener("submit", sendSearchRequest);
getSongsBtn.addEventListener("click", function () {
    pushFeedback(this);
    getSongsInQueue(this);
});
clearResultsBtn.addEventListener("click", function () {
    pushFeedback(this);
    clearResults();
});
leaveParty.addEventListener("click", function () {
    pushFeedback(this);
});
dataSaverObj.addEventListener("change", function () {
    pushFeedback(this.nextSibling.nextSibling);
    dataSaver = !dataSaver;
    console.log(`Datasaver turned ${dataSaver ? "on" : "off"}`);
});

let query;
let offset = 0;
const hints = [
    "Blue",
    "abcdefu",
    "Turn it up",
    "Sweet Dreams",
    "Glad you came",
    "Monster",
    "Mizu",
    "Csavard fel a szőnyeget",
    "Everthing Black",
    "RISE",
    "Me, Myself & I",
    "Him & I",
    "Wellerman",
    "hot girl boomer",
    "Save Your Tears",
    "Summer Waves",
    "Élvezd",
    "Low",
    "Dynamite",
    "Hangover",
    "I Gotta Feeling",
    "Can't hold us",
    "Shape of You",
    "Believer",
    "Thunder",
    "Tudod, Hmmmm",
    "Young, Wild & Free",
    "Csepereg az eső",
    "Érik a szőlő",
];

//Toggles the searching icon
function toggleSearchAnimation(e) {
    if (e.dataset.inProgress === "false") {
        e.dataset.inProgress = "true";
        e.innerHTML = `<img src="images/loading.gif" alt="Processing..." class="w-5">`;
    } else {
        e.dataset.inProgress = "false";
        e.innerHTML = e.dataset.originalValue;
    }
}

async function sendSearchRequest(e) {
    e.preventDefault();
    toggleSearchAnimation(this);
    pushFeedback(this);
    searchForm.removeEventListener("submit", sendSearchRequest);
    searchBtn.removeEventListener("click", sendSearchRequest);

    query = queryInp.value == "" ? queryInp.placeholder : queryInp.value;
    const platform = "Spotify";

    if (query.length === 0) {
        console.warn("Please be more specific!");
        const text = queryInp.value;
        queryInp.value = "Please be more specific!";
        setTimeout(() => {
            queryInp.value = text;
        }, 1000);
        toggleSearchAnimation(this);
        searchBtn.addEventListener("click", sendSearchRequest);
        searchForm.addEventListener("submit", sendSearchRequest);
        return;
    }

    console.log(`Searching '${query}' on ${platform}...`);
    let result;
    if (platform == "Spotify") {
        result = await searchSpotify(offset);

        if (result["error"]) {
            console.error(result);
            toggleSearchAnimation(this);
            searchBtn.addEventListener("click", sendSearchRequest);
            searchForm.addEventListener("submit", sendSearchRequest);
            return;
        }
    }
    console.log(`Received ${result.length} tracks from ${platform}!`);

    if (result.length === 0) {
        const text = queryInp.value;
        queryInp.value = "There are no results!";
        setTimeout(() => {
            queryInp.value = text;
        }, 1000);
    }

    clearResults();

    const queueText = document.createElement("p");
    queueText.innerText = "Search results:";
    resultsUl.appendChild(queueText);

    result.forEach((track) => {
        let length = new Date(track["length"]);
        const card = getMusicCardHTML(
            track["image"],
            track["title"],
            track["artists"],
            length.getMinutes() + "m" + length.getSeconds() + "s",
            track["uri"],
            platform
        );
        resultsUl.appendChild(card);
    });
    offset = result.length;

    resultsUl.innerHTML += `<button id="showMore" name="showMore" data-in-progress="false" data-original-value="Show more"
    class="bg-blue-500 hover:bg-blue-300 text-white hover:text-black py-2 px-2 my-2 rounded">Show more</button>`;
    const showMoreBtn = resultsUl.querySelector("#showMore");
    showMoreBtn.addEventListener("click", async function () {
        pushFeedback(showMoreBtn);
        toggleSearchAnimation(showMoreBtn);

        result = await searchSpotify(offset);

        resultsUl.removeChild(showMoreBtn);

        result.forEach((track) => {
            let length = new Date(track["length"]);
            const card = getMusicCardHTML(
                track["image"],
                track["title"],
                track["artists"],
                length.getMinutes() + "m" + length.getSeconds() + "s",
                track["uri"],
                platform
            );
            resultsUl.appendChild(card);
        });
        offset += result.length;

        resultsUl.appendChild(showMoreBtn);
        toggleSearchAnimation(showMoreBtn);

        refreshListeners();
    });

    refreshListeners();
    changeHint();
    toggleSearchAnimation(this);
    searchBtn.addEventListener("click", sendSearchRequest);
    searchForm.addEventListener("submit", sendSearchRequest);
}

//Sends AJAX request to make a search in the Spotify database
async function searchSpotify(offset) {
    const response = await fetch(
        `/party/spotify/search?query=${query}&dataSaver=${
            dataSaver ? 1 : 0
        }&offset=${offset}&creator=${isCreator ? 1 : 0}`
    ).then((res) => res.json());

    if (response["tokenExpired"] && isCreator) {
        console.error(
            "Could not get tracks due to expired token. Refreshing..."
        );

        await refreshToken();
        return searchSpotify(offset);
    } else if (response["error"]) {
        console.error(response["error"]);

        const text = queryInp.value;
        queryInp.value = "Unexcepted error, please try again!";
        setTimeout(() => {
            queryInp.value = text;
        }, 1000);
    }
    return response;
}

function refreshListeners() {
    let cards = resultsUl.querySelectorAll("[data-not-listening]");
    cards.forEach((card) => {
        card.addEventListener("click", (e) => {
            pushFeedback(card);
            addToQueue(card);
        });
        delete card.dataset.notListening;
    });
}

function getMusicCardHTML(
    image,
    title,
    artists,
    length,
    uri,
    platform,
    addedBy
) {
    const artistsP = document.createElement("p");
    artistsP.classList.add(
        "mb-3",
        "font-normal",
        "text-gray-700",
        "dark:text-gray-400"
    );
    artists.forEach((artist) => {
        artistsP.innerHTML += artist + ", ";
    });
    artistsP.innerHTML = artistsP.innerHTML.substring(
        artistsP.innerHTML.length - 2,
        0
    );

    const lengthP = document.createElement("p");
    lengthP.classList.add(
        "text-xs",
        "text-gray-500",
        "absolute",
        "bottom-1",
        "right-2"
    );
    lengthP.innerHTML = length === "NaNmNaNs" ? "" : length;

    const innerDiv = document.createElement("div");
    innerDiv.classList.add("m-0", "p-0");
    innerDiv.appendChild(artistsP);
    innerDiv.appendChild(lengthP);

    const titleP = document.createElement("h5");
    titleP.classList.add(
        "mb-2",
        "text-xl",
        "font-bold",
        "tracking-tight",
        "text-gray-900",
        "dark:text-white"
    );
    titleP.innerHTML = title;

    const outerDiv = document.createElement("div");
    outerDiv.classList.add(
        "flex",
        "flex-col",
        "justify-between",
        "pl-2",
        "pr-4",
        "py-1",
        "leading-normal"
    );
    outerDiv.appendChild(titleP);
    outerDiv.appendChild(innerDiv);

    const imgO = document.createElement("img");
    imgO.classList.add("p-2", "object-cover", "h-auto", "w-32");
    imgO.src =
        image === "" || image === undefined
            ? "/images/party/defaultCover.png"
            : image;

    const card = document.createElement("div");
    card.classList.add(
        "relative",
        "flex",
        "flex-row",
        "max-w-xl",
        "items-center",
        "bg-white",
        "border",
        "rounded-lg",
        "shadow-md",
        "hover:bg-gray-100",
        "dark:border-gray-700",
        "dark:bg-gray-800",
        "dark:hover:bg-gray-700",
        "mt-1"
    );
    card.dataset.uri = uri;
    card.dataset.platform = platform;
    card.dataset.notListening = null;
    card.appendChild(imgO);
    card.appendChild(outerDiv);

    if (addedBy !== undefined) {
        const addedByP = document.createElement("p");
        addedByP.classList.add(
            "text-xs",
            "text-gray-500",
            "absolute",
            "top-1",
            "right-2"
        );
        addedByP.innerText = addedBy;
        card.appendChild(addedByP);
    }

    return card;
}

async function addToQueue(card) {
    const uri = card.dataset.uri;
    const platform = card.dataset.platform;
    console.log(`Adding track ${uri} to queue...`);

    const form = new FormData();
    form.set("uri", uri);
    form.set("platform", platform);

    const response = await fetch("/party/spotify/addTrack", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": csrfToken,
        },
        body: form,
    }).then((res) => res.json());

    if (response["track_uri"] == uri) {
        addedToQueueFeedback(card, true);
        console.log("Successfully added track to queue!");
    } else {
        addedToQueueFeedback(card, false);
        console.error("Failed to add track to queue:", response);
    }
    return response;
}

function changeHint() {
    queryInp.placeholder = hints.at(Math.floor(Math.random() * hints.length));
}
changeHint();

async function getSongsInQueue(e) {
    toggleSearchAnimation(e);
    console.log("Getting songs is queue...");
    const response = await fetch(
        `/party/getSongsInQueue?dataSaver=${dataSaver}`
    ).then((res) => res.json());
    if (response["error"]) {
        console.error(response);
        toggleSearchAnimation(e);

        const text = e.innerText;
        e.innerText = response["error"];
        setTimeout(() => {
            e.innerText = text;
        }, 1200);

        return;
    }
    console.log(`There is ${response.length} track(s) in the queue.`, response);

    clearResults();

    const queueText = document.createElement("p");
    queueText.innerText = "Queued tracks:";
    resultsUl.appendChild(queueText);

    response.forEach((track) => {
        let length = new Date(track["length"]);
        const card = getMusicCardHTML(
            track["image"],
            track["title"],
            track["artists"],
            length.getMinutes() + "m" + length.getSeconds() + "s",
            track["uri"],
            track["platform"],
            track["addedBy"]
        );
        queueUl.appendChild(card);
    });
    toggleSearchAnimation(e);
}

function clearResults() {
    resultsUl.innerHTML = "";
    queueUl.innerHTML = "";
}

function addedToQueueFeedback(card, success) {
    card.classList.remove("dark:border-gray-700");
    card.classList.remove("dark:bg-gray-800");
    card.classList.remove("dark:hover:bg-gray-700");

    if (success) {
        card.classList.add("dark:border-green-800");
        card.classList.add("dark:bg-green-700");
    } else {
        card.classList.add("dark:border-red-800");
        card.classList.add("dark:bg-red-700");
    }
    setTimeout(() => {
        if (success) {
            card.classList.remove("dark:border-green-800");
            card.classList.remove("dark:bg-green-700");
        } else {
            card.classList.remove("dark:border-red-800");
            card.classList.remove("dark:bg-red-700");
        }
        card.classList.add("dark:border-gray-700");
        card.classList.add("dark:bg-gray-800");
        card.classList.add("dark:hover:bg-gray-700");
    }, 1000);
}

console.log("Party JS successfully loaded!");
