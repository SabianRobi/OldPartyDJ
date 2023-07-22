import {
    dataSaver,
    setDataSaver,
    pushFeedback,
    isCreator,
    csrfToken,
    getSpotifyToken,
    setSpotifyToken,
    refreshToken,
} from "./partyCommon.js";

const searchForm = document.querySelector("#searchForm");
const queryInp = document.querySelector("#query");
const searchBtn = document.querySelector("#searchBtn");
const resultsUl = document.querySelector("#results");
const queueUl = document.querySelector("#queue");

const getSongsBtn = document.querySelector("#getSongs");
const clearResultsBtn = document.querySelector("#clearResults");
const leaveParty = document.querySelector("#leaveParty");
const dataSaverObj = document.querySelector("#dataSaver");
searchBtn.addEventListener("click", sendSearchRequest);
// searchForm.addEventListener("submit", sendSearchRequest);

const doSearchSpotify = document.querySelector("#searchSpotify");
const doSearchYouTube = document.querySelector("#searchYouTube");

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
    setDataSaver(!dataSaver);
    console.log(`Datasaver turned ${dataSaver ? "on" : "off"}`);
});

const SpotifySearchLimit = 5;
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
let platforms = [];

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

    platforms = [];
    if (doSearchSpotify.checked) {
        platforms.push("Spotify");
    }
    if (doSearchYouTube.checked) {
        platforms.push("YouTube");
    }

    query = queryInp.value == "" ? queryInp.placeholder : queryInp.value;
    offset = 0;

    // Error handling: Empty query
    if (query.trim().length === 0) {
        console.warn("Please be more specific!");
        const text = queryInp.value;
        queryInp.value = "Please be more specific!";
        setTimeout(() => {
            queryInp.value = text;
            searchBtn.addEventListener("click", sendSearchRequest);
            searchForm.addEventListener("submit", sendSearchRequest);
        }, 1000);
        toggleSearchAnimation(this);
        return;
    }

    // Error handling: No platforms selected
    if (platforms.length === 0) {
        console.warn("Set at least one platform to search on!");
        const text = queryInp.value;
        queryInp.value = "Please choose at least one platform!";
        setTimeout(() => {
            queryInp.value = text;
            searchBtn.addEventListener("click", sendSearchRequest);
            searchForm.addEventListener("submit", sendSearchRequest);
        }, 1000);
        toggleSearchAnimation(this);
        return;
    }

    query = query.trim();
    queryInp.value = query;

    console.log(`Searching '${query}' on ${platforms.join(", ")}...`);

    const response = await fetch(
        `/party/search?query=${encodeURIComponent(query)}&dataSaver=${
            dataSaver ? 1 : 0
        }&offset=${offset}&creator=${
            isCreator ? 1 : 0
        }&platforms=${platforms.join(",")}`
    ).then((res) => res.json());

    let errorCount = 0;
    let platformsSuccess = platforms.slice(0, platforms.length);
    response.forEach((platformResult) => {
        if (platformResult["error"]) {
            console.error(platformResult["platform"], platformResult["error"]);
            platformsSuccess = platformsSuccess.filter((p) => {
                p !== platformResult["platform"];
            });
            errorCount++;
        }
    });

    if (errorCount === platforms.length) {
        // All platform search failed
        toggleSearchAnimation(this);
        searchBtn.addEventListener("click", sendSearchRequest);
        searchForm.addEventListener("submit", sendSearchRequest);
        return;
    }

    let trackCount = 0;
    response.map((platform) => {
        trackCount += platform.tracks.length;
    });
    console.log(
        `Received ${trackCount} tracks from ${platformsSuccess.join(", ")}!`
    );

    clearResults();

    const queueText = document.createElement("p");
    queueText.innerText = "Search results:";
    resultsUl.appendChild(queueText);

    response.forEach((platformResults) => {
        platformResults.tracks.forEach((track) => {
            let length = new Date(track["length"]);
            const card = getMusicCardHTML(
                track["image"],
                track["title"],
                track["artists"],
                length.getMinutes() + "m" + length.getSeconds() + "s",
                track["uri"],
                platformResults.platform,
                undefined,
                track["id"],
                "addToQueue"
            );
            resultsUl.appendChild(card);
        });
    });
    offset = trackCount; //TODO manage offset per platform

    if (trackCount === SpotifySearchLimit) {
        resultsUl.innerHTML += `<button id="showMore" name="showMore" data-in-progress="false" data-original-value="Show more"
        class="bg-blue-500 hover:bg-blue-300 text-white hover:text-black py-2 px-2 my-2 rounded">Show more</button>`;

        const showMoreBtn = resultsUl.querySelector("#showMore");
        showMoreBtn.addEventListener("click", async function () {
            pushFeedback(showMoreBtn);
            toggleSearchAnimation(showMoreBtn);

            const result = await sendOnlySearchRequest(offset);

            resultsUl.removeChild(showMoreBtn);

            result.forEach((platformResults) => {
                platformResults.tracks.forEach((track) => {
                    let length = new Date(track["length"]);
                    const card = getMusicCardHTML(
                        track["image"],
                        track["title"],
                        track["artists"],
                        length.getMinutes() + "m" + length.getSeconds() + "s",
                        track["uri"],
                        platformResults.platform,
                        track["id"],
                        undefined,
                        "addToQueue"
                    );
                    resultsUl.appendChild(card);
                });
            });

            let trackCount = 0;
            result.map((platform) => {
                trackCount += platform.tracks.length;
            });

            offset += trackCount; //TODO manage offsets per platform

            resultsUl.appendChild(showMoreBtn);
            toggleSearchAnimation(showMoreBtn);

            refreshListeners();

            if (trackCount < SpotifySearchLimit) {
                showMoreBtn.remove();
                resultsUl.innerHTML += "<p>No more results!</p>";
            }
        });
    } else {
        resultsUl.innerHTML += "<p>No more results!</p>";
    }

    queryInp.value = "";
    refreshListeners();
    changeHint();
    toggleSearchAnimation(this);
    searchBtn.addEventListener("click", sendSearchRequest);
    searchForm.addEventListener("submit", sendSearchRequest);
}

//Sends AJAX request to make a search in the Spotify database
async function sendOnlySearchRequest(offset) {
    const response = await fetch(
        `/party/search?query=${query}&dataSaver=${
            dataSaver ? 1 : 0
        }&offset=${offset}&creator=${
            isCreator ? 1 : 0
        }&platforms=${platforms.join(",")}`
    ).then((res) => res.json());

    response.forEach(
        (platformResult) =>
            async function () {
                if (platformResult["error"]) {
                    //TODO check isset
                    if (
                        isCreator &&
                        platformResult["token_expired"] &&
                        platformResult["platform"] === "Spotify"
                    ) {
                        console.error(
                            "Could not get tracks due to expired Spotify token. Refreshing..."
                        );

                        let success = await refreshToken();
                        if (success) {
                            setSpotifyToken(await getSpotifyToken());
                        }
                        return sendOnlySearchRequest(offset);
                    } else {
                        console.error(response["error"]);

                        const text = queryInp.value;
                        queryInp.value = "Unexcepted error, please try again!";
                        setTimeout(() => {
                            queryInp.value = text;
                        }, 1000);
                        return;
                    }
                }
            }
    );
    return response;
}

function refreshListeners() {
    let resultCards = resultsUl.querySelectorAll("[data-event-type]");
    let queueCards = queueUl.querySelectorAll("[data-event-type]");
    let cards = [...resultCards, ...queueCards];

    cards.forEach((card) => {
        if (card.dataset.eventType === "addToQueue") {
            card.addEventListener("click", (e) => {
                pushFeedback(card);
                addToQueue(card);
            });
        } else if (card.dataset.eventType === "removeFromQueue") {
            card.addEventListener("click", (e) => {
                pushFeedback(card);
                removeFromQueue(card);
            });
        }
        delete card.dataset.eventType;
    });
}

function getMusicCardHTML(
    image,
    title,
    artists,
    length,
    uri,
    platform,
    addedBy,
    id,
    eventType
) {
    const div = document.createElement("div");
    div.innerHTML = `<div
        class="relative flex flex-row max-w-xl items-center border rounded-lg shadow-md mt-1 dark:border-gray-700 ${
            platform === "Spotify"
                ? "bg-green-100 hover:bg-green-200 dark:bg-green-950 dark:hover:bg-green-900"
                : "bg-red-100 hover:bg-red-200 dark:bg-red-950 dark:hover:bg-red-900"
        }"
        data-uri="${uri}"
        data-platform="${platform}"
        data-id="${id === undefined ? "" : id}"
        data-event-type="${eventType}"
    >
        <img
            class="p-2 object-cover h-auto w-32"
            src="${
                image === "" || image === undefined
                    ? "/images/party/defaultCover.png"
                    : image
            }"
        />
        <div class="flex flex-col justify-between pl-2 pr-4 py-1 leading-normal">
            <h5 class="mb-2 text-xl font-bold tracking-tight text-gray-900 dark:text-white">
                ${title}
            </h5>
            <div class="m-0 p-0">
                <p class="mb-3 font-normal text-gray-700 dark:text-gray-400">
                    ${artists.join(", ")}
                </p>
                <p class="text-xs text-gray-500 absolute bottom-1 right-2">
                    ${length === "NaNmNaNs" ? "" : length}
                </p>
            </div>
        </div>
        <p class="text-xs text-gray-500 absolute top-1 right-2">${
            addedBy === undefined ? "" : addedBy
        }</p>
    </div>`;
    return div;
}

async function addToQueue(card) {
    const uri = card.dataset.uri;
    const platform = card.dataset.platform;
    console.log(`Adding track ${uri} to queue...`);

    const form = new FormData();
    form.set("uri", uri);
    form.set("platform", platform);

    const response = await fetch("/party/addTrack", {
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

async function removeFromQueue(card) {
    const id = card.dataset.id;

    const form = new FormData();
    form.set("id", id);

    const response = await fetch("/party/removeTrack", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": csrfToken,
        },
        body: form,
    }).then((res) => res.json());

    addedToQueueFeedback(card, false);
    if (response["success"]) {
        console.log("Successfully removed track from queue!");

        setTimeout(() => {
            card.remove();
        }, 1050);
    } else {
        console.error("Failed to remove track from queue:", response);
    }
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

    if (response["tokenExpired"]) {
        console.error(
            "Could not get tracks in queue due to expired token. Refreshing..."
        );
        await refreshToken();
        setSpotifyToken(await getSpotifyToken());
        getSongsInQueue(e);
    } else if (response["error"]) {
        console.error("Could not get tracks in queue:", response);

        toggleSearchAnimation(e);
        clearResults();
        queueUl.innerHTML += "<p>Error getting tracks in queue!</p>";

        return;
    }
    console.log(`There is ${response.length} track(s) in the queue.`, response);

    clearResults();

    //Now playing
    let textObj = document.createElement("p");
    textObj.innerHTML = "Now playing:";
    queueUl.append(textObj);

    let length = new Date(response[0]["length"]);
    const card = getMusicCardHTML(
        response[0]["image"],
        response[0]["title"],
        response[0]["artists"],
        length.getMinutes() + "m" + length.getSeconds() + "s",
        response[0]["uri"],
        response[0]["platform"],
        response[0]["addedBy"],
        undefined,
        undefined
    );
    queueUl.appendChild(card);

    //Queue
    let textObj2 = document.createElement("p");
    textObj2.innerHTML = "Queued tracks:";
    queueUl.append(textObj2);

    if (response.slice(1).length > 0) {
        response.slice(1).forEach((track) => {
            let length = new Date(track["length"]);
            const card = getMusicCardHTML(
                track["image"],
                track["title"],
                track["artists"],
                length.getMinutes() + "m" + length.getSeconds() + "s",
                track["uri"],
                track["platform"],
                track["addedBy"],
                track["id"],
                "removeFromQueue"
            );
            queueUl.appendChild(card);
        });
    } else {
        let textObj3 = document.createElement("p");
        textObj3.classList.add("pl-4");
        textObj3.innerHTML = "No tracks in queue!";
        queueUl.append(textObj3);
    }

    refreshListeners();
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
    card.classList.remove("bg-white");
    card.classList.remove("hover:bg-gray-100");

    if (success) {
        card.classList.add("dark:border-green-800");
        card.classList.add("border-green-400");
        card.classList.add("dark:bg-green-700");
        card.classList.add("bg-green-400");
    } else {
        card.classList.add("dark:border-red-800");
        card.classList.add("border-red-400");
        card.classList.add("dark:bg-red-700");
        card.classList.add("bg-red-500");
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
        card.classList.add("bg-white");
        card.classList.add("hover:bg-gray-100");
    }, 1000);
}

console.log("Party JS successfully loaded!");
