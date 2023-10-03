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

const queryInp = document.querySelector("#query");          // The search field
const searchBtn = document.querySelector("#searchBtn");     // Search button next to the search field
const resultsUl = document.querySelector("#results");       // Result list
const queueUl = document.querySelector("#queue");           // Queue list

const getSongsBtn = document.querySelector("#getSongs");                    // Watch queue button
const clearResultsBtn = document.querySelector("#clearResults");            // Clear results button
const leaveParty = document.querySelector("#leaveParty");                   // Leave party button
const dataSaverObj = document.querySelector("#dataSaver");                  // Data saver button

searchBtn.addEventListener("click", sendSearchRequest);
export const isSpotifyEnabled = document.querySelector("#searchSpotify")
    ? true
    : false;

// Activate the "select Spotify" to search on button
if (isSpotifyEnabled) {
    document
        .querySelector("#searchSpotify")
        .addEventListener("change", function () {
            platforms.Spotify.enabled = this.checked;
        });
}

// Activate the "select YouTube" to search on button
document
    .querySelector("#searchYouTube")
    .addEventListener("change", function () {
        platforms.YouTube.enabled = this.checked;
    });

// Event listeners, explained later
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
    setDataSaver(!dataSaver);                                       // Toggles data saving
    console.log(`Datasaver turned ${dataSaver ? "on" : "off"}`);
});

let query;
// Music names that show in the search bar when it is empty
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
// Setting platform infos and (offset, limit, reachedEndOfResults) parameters to manage pagination ('search more')
let platforms = {
    Spotify: {
        name: "Spotify", // Name of the platform
        enabled: false, // Search on this platform?
        offset: 0, // Skip the first {offset} tracks when doing search
        limit: 5, // Returned track count
        reachedEndOfResults: false, // When no more tracks found this will be true
    },
    YouTube: {
        name: "YouTube",
        enabled: false,
        offset: 0,
        limit: 5,
        nextPageToken: "",          // YouTube uses a pageToken to get the next x amount of results.
        reachedEndOfResults: false,
    },
    // Returns an array of the currently enabled platform names
    getEnabledNames() {
        const names = [];

        this.getNames().forEach((platform) => {
            platform = platforms[platform];

            if (platform.enabled) {
                names.push(platform.name);
            }
        });
        return names;
    },
    // Returns an array of the currently enabled platform offsets
    getEnabledOffsets() {
        const off = [];

        this.getNames().forEach((platform) => {
            platform = platforms[platform];

            if (platform.enabled) {
                off.push(platform.offset);
            }
        });

        return off;
    },
    // Returns an array of the currently enabled platform limits
    getEnabledLimits() {
        const limits = [];

        this.getNames().forEach((platform) => {
            platform = platforms[platform];

            if (platform.enabled) {
                limits.push(platform.limit);
            }
        });

        return limits;
    },
    // Returns all the platform names
    getNames() {
        const names = [];
        Object.values(platforms).forEach((entry) => {
            if (entry.hasOwnProperty("reachedEndOfResults")) {
                names.push(entry.name);
            }
        });
        return names;
    },
    // Resets te platforms pagination infos
    resetValues() {
        this.getNames().forEach((platform) => {
            platform = platforms[platform];

            platform.offset = 0;
            platform.reachedEndOfResults = false;
        });

        this.YouTube.nextPageToken = "";
    },
};
// Needed to prevent sending a search request multiple times at once
let isInProgress = {
    search: false,
};

// Toggles the searching icon (text - gif)
function toggleSearchAnimation(e) {
    if (e.dataset.inProgress === "false") {
        e.dataset.inProgress = "true";
        e.innerHTML = `<img src="images/loading.gif" alt="Processing..." class="w-5">`;
    } else {
        e.dataset.inProgress = "false";
        e.innerHTML = e.dataset.originalValue;
    }
}

// Sends the search request to backend & displays the results
async function sendSearchRequest(e) {
    e.preventDefault();
    if (isInProgress.search) return;
    isInProgress.search = true;

    toggleSearchAnimation(this);    // Replaces the search text to the loading gif
    pushFeedback(this);             // fancy animation

    query = queryInp.value == "" ? queryInp.placeholder : queryInp.value;

    // Error handling: Empty string
    if (query.trim().length === 0) {
        console.warn("Please be more specific!");

        // Stupid way of giving user feedback, but it works
        // Replaces the searchbar text to the "Please be more specific!" text then hides after a sec
        const text = queryInp.value;
        queryInp.value = "Please be more specific!";
        setTimeout(() => {
            queryInp.value = text;
        }, 1000);

        toggleSearchAnimation(this);    // Stops the loading gif
        isInProgress.search = false;
        return;
    }

    const enabledPlatformNames = platforms.getEnabledNames();

    // Error handling: No platforms selected
    if (enabledPlatformNames.length === 0) {
        console.warn("Set at least one platform to search on!");

        // Here we go again with the stupid way of giving user feedback
        const text = queryInp.value;
        queryInp.value = "Please choose at least one platform!";
        setTimeout(() => {
            queryInp.value = text;
        }, 1000);
        toggleSearchAnimation(this);
        isInProgress.search = false;
        return;
    }

    // Reseting platform pagination values
    platforms.resetValues();

    // Sending query to backend
    query = query.trim();
    queryInp.value = query;

    console.log(
        `Searching '${query}' on ${enabledPlatformNames.join(", ")}...`
    );

    // sendSearchQuery: sends the search query to backend, searches the given platfroms (detailed description at the function)
    const responseObj = await sendSearchQuery(enabledPlatformNames);

    // handleSearchErrorHandling: handles the errors that backend can respond with (detailed description at the function)
    // cleanResponse: an object, the response itself from the backend
    // platformsSuccess: an array of platform names where the search succeeded
    const { cleanResponse, platformsSuccess } = await handleSearchErrorHandling(
        responseObj
    );
    // Displays the results [cleanResponse] to the user (detailed description at the function)
    refreshResultList(true, cleanResponse, platformsSuccess);

    // Resetting the search bar content
    queryInp.value = "";
    refreshListeners();     // Adding event listeners to recently displayed tracks (addToQueue and removeFromQueue events)
    changeHint();
    toggleSearchAnimation(this);    // Stops the loading gif
    isInProgress.search = false;

    console.log("Search complete!");
}

// Adds event listeners (addToQueue and removeFromQueue events) to displayed tracks in resultsUl and queueUl
function refreshListeners() {
    // Selecting the
    let resultCards = resultsUl.querySelectorAll("[data-event-type]");
    let queueCards = queueUl.querySelectorAll("[data-event-type]");
    let cards = [...resultCards, ...queueCards]; // merge the two card arrays

    // Reads the card's dataset and adds the correct event listener to it
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

// Returns the HTML code of a card (at search results or when watching the queue)
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

// Sends a request to backend to add a track to the queue
// card: the clicked result/music at search results
async function addToQueue(card) {
    const uri = card.dataset.uri;
    const platform = card.dataset.platform;
    console.log(`Adding track ${uri} to queue...`);

    // Creating and sending the form then converting the response to JSON - error handling missing? idk
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

    // Animates and logs
    if (response["track_uri"] == uri) {
        addedToQueueFeedback(card, true);
        console.log("Successfully added track to queue!");
    } else {
        addedToQueueFeedback(card, false);
        console.error("Failed to add track to queue:", response);
    }
    return response; // not sure why we need to return anything
}

// Sends a request to backend to remove a track from the queue
// card: the clicked result/music at 'watching queue'
async function removeFromQueue(card) {
    const id = card.dataset.id;

    // Creating and sending the form
    const form = new FormData();
    form.set("id", id);

    const response = await fetch("/party/removeTrack", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": csrfToken,
        },
        body: form,
    }).then((res) => res.json());

    // Animation & error handling (without user feedback - TODO)
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

// Changes the hint in the searchbar
function changeHint() {
    queryInp.placeholder = hints.at(Math.floor(Math.random() * hints.length));
}
changeHint();

// Queries the backend for tracks in the queue
async function getSongsInQueue(e) {
    toggleSearchAnimation(e);
    console.log("Getting songs is queue...");
    // Sending the 'request'
    const response = await fetch(
        `/party/getSongsInQueue?dataSaver=${dataSaver}`
    ).then((res) => res.json());

    // Error handling: happens at backend, when Spotify token expired during getting the track infos from the Spotify API
    if (response["tokenExpired"]) {
        console.error(
            "Could not get tracks in queue due to expired token. Refreshing..."
        );
        // Refreshing and setting a new SP token
        await refreshToken();
        setSpotifyToken(await getSpotifyToken());
        getSongsInQueue(e);
    } else if (response["error"]) {
        // Other error occured
        console.error("Could not get tracks in queue:", response);

        toggleSearchAnimation(e);
        clearResults();
        queueUl.innerHTML += "<p>Error getting tracks in queue!</p>";

        return;
    }
    console.log(`There is ${response.length} track(s) in the queue.`, response);

    // Clearing the result list
    clearResults();

    // Displaying the Now playing song (1st song in the queue)
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

    // Displaying the queue
    let textObj2 = document.createElement("p");
    textObj2.innerHTML = "Queued tracks:";
    queueUl.append(textObj2);

    // Cuts down the first of the queue (which is currently playing)
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
        // Empty queue case
        let textObj3 = document.createElement("p");
        textObj3.classList.add("pl-4");
        textObj3.innerHTML = "No tracks in queue!";
        queueUl.append(textObj3);
    }

    refreshListeners();
    toggleSearchAnimation(e);
}

// Clears the result and queue lists
function clearResults() {
    resultsUl.innerHTML = "";
    queueUl.innerHTML = "";
}

// This was an attempt to give normal user feedback when adding/removing a track to/from the queue
function addedToQueueFeedback(card, success) {
    // TODO implement normal feedback
    // card.classList.remove("dark:border-gray-700");
    // card.classList.remove("dark:bg-gray-800");
    // card.classList.remove("dark:hover:bg-gray-700");
    // card.classList.remove("bg-white");
    // card.classList.remove("hover:bg-gray-100");
    // if (success) {
    //     card.classList.add("dark:border-green-800");
    //     card.classList.add("border-green-400");
    //     card.classList.add("dark:bg-green-700");
    //     card.classList.add("bg-green-400");
    // } else {
    //     card.classList.add("dark:border-red-800");
    //     card.classList.add("border-red-400");
    //     card.classList.add("dark:bg-red-700");
    //     card.classList.add("bg-red-500");
    // }
    // setTimeout(() => {
    //     if (success) {
    //         card.classList.remove("dark:border-green-800");
    //         card.classList.remove("dark:bg-green-700");
    //     } else {
    //         card.classList.remove("dark:border-red-800");
    //         card.classList.remove("dark:bg-red-700");
    //     }
    //     card.classList.add("dark:border-gray-700");
    //     card.classList.add("dark:bg-gray-800");
    //     card.classList.add("dark:hover:bg-gray-700");
    //     card.classList.add("bg-white");
    //     card.classList.add("hover:bg-gray-100");
    // }, 1000);
}

// Sends a search query to the backend with the given platform names
async function sendSearchQuery(platformNames) {
    // Getting the platforms & infos about them
    let searchedNames = [];
    let searchedLimits = [];
    let searchedOffsets = [];

    platformNames.forEach((pname) => {
        searchedNames.push(platforms[pname].name);
        searchedLimits.push(platforms[pname].limit);
        searchedOffsets.push(
            pname === "YouTube"
                ? platforms[pname].nextPageToken === ""
                    ? "noToken"
                    : platforms[pname].nextPageToken
                : platforms[pname].offset
        );
    });

    // Sending the query to backend
    // every nth element of the arrays 'connected' the nth platform name. For example:
    // [Spotify, YouTube], [0,5], [5,10]
    //      Spotify, 0 offset, 5 limit
    //      YouTube, 5 offset, 10 limit
    const reply = await fetch(
        `/party/search?query=${encodeURIComponent(query)}&dataSaver=${
            dataSaver ? 1 : 0
        }&offsets=${searchedOffsets.join(",")}&platforms=${searchedNames.join(
            ","
        )}&limits=${searchedLimits.join(",")}&creator=${isCreator ? 1 : 0}`
    );

    return reply;
}

// Handles the errors that can the backend respond with
async function handleSearchErrorHandling(responseObj) {
    // TODO May return nothing, errors can occur - why can backend give back empty response?
    // Backend response error handling
    if (!responseObj.ok) {
        console.error(responseObj.statusText);
        toggleSearchAnimation(this);
        isInProgress.search = false;
        return;
    }

    const cleanResponse = await responseObj.json();
    const enabledPlatforms = platforms.getEnabledNames();

    let errorCount = 0;
    let platformsSuccess = enabledPlatforms.slice(0, enabledPlatforms.length);  // array containing the platform names which succeeded the search request
    let isSpotifyTokenExpired = false;
    const platformNames = [];

    // Going through the platforms
    cleanResponse.forEach(async (platformResult) => {
        platformNames.push(platformResult["platform"]);

        // If the platform gave error
        if (platformResult["error"]) {
            // Log it to console
            console.error(
                "[" + platformResult["platform"] + "]:",
                platformResult["error"]
            );

            // If the SP token expired refresh it later in this function
            if (
                isCreator &&
                platformResult["platform"] === "Spotify" &&
                platformResult["tokenExpired"]
            ) {
                isSpotifyTokenExpired = true;
            }

            // Remove the platform name from the platformsSuccess array, because the platform had errors
            platformsSuccess = platformsSuccess.filter((p) => {
                p !== platformResult["platform"];
            });
            errorCount++;
        } else {
            // Success, setting the new offsets
            if (platformResult["platform"] === "Spotify") {
                platforms.Spotify.offset += platformResult["tracks"].length;
            }
            if (platformResult["platform"] === "YouTube") {
                platforms.YouTube.offset += platformResult["tracks"].length;
                platforms.YouTube.nextPageToken =
                    platformResult["nextPageToken"];
            }
        }
    });

    // Refreshing the SP token if neccessary
    if (isSpotifyTokenExpired) {
        console.log("Trying to refresh token");
        const success = await refreshToken();
        if (success) {
            setSpotifyToken(await getSpotifyToken());

            const responseObj = await sendSearchQuery(platformNames);
            return await handleSearchErrorHandling(responseObj);
        }
    }

    // If all platform search failed
    if (errorCount === enabledPlatforms.length) {
        toggleSearchAnimation(searchBtn);
        isInProgress.search = false;
        return;
    }

    // Gives back the response and an array of succeeded platform names
    return { cleanResponse, platformsSuccess };
}

// Updates the GUI of the result list
// cleanResponse: contains the card/track infos
// platformsSuccess: platform names where the search is succeeded
function refreshResultList(isFirstSearch, cleanResponse, platformsSuccess) {
    if (isFirstSearch) {
        clearResults();
        resultsUl.innerHTML += "<p>Search results:</p>";
    } else {
        // Remove existing "load more" buttons
        const buttons = Array.from(resultsUl.childNodes).filter((node) => {
            return (
                node.nodeName === "BUTTON" ||
                (node.nodeName === "P" &&
                    Array.from(resultsUl.childNodes)[0] !== node)
            );
        });
        buttons.forEach((btn) => {
            btn.remove();
        });
    }

    // Adding the new cards to the result list
    cleanResponse.forEach((platformResults) => {
        if (
            platformResults.tracks.length <
            platforms[platformResults.platform].limit
        ) {
            platforms[platformResults.platform].reachedEndOfResults = true;
        }

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

    // Show "load more" button(s) when the platform did not reach the end of its results
    let allReached = true;
    platformsSuccess.forEach((platform) => {
        if (!platforms[platform].reachedEndOfResults) {
            allReached = false;
        }
    });

    // If not all platforms reached the end / responded with zero tracks, add the 'load more' button
    if (!allReached) {
        resultsUl.innerHTML += "<p>Load more results from:</p>";
        platformsSuccess.forEach((platform) => {
            if (!platforms[platform].reachedEndOfResults) {
                const btn = getShowMoreButton(platform);
                resultsUl.appendChild(btn);
            }
        });
    } else {
        resultsUl.innerHTML +=
            "<p>You have reached the end of the results!</p>";
    }
}

// Returns a 'load more' button's DOM object
function getShowMoreButton(platformName) {
    const platform = platforms[platformName];

    const newBtn = document.createElement("button");
    newBtn.id = `showMoreBtn${platform.name}`;
    newBtn.name = `showMoreBtn${platform.name}`;
    newBtn.dataset.inProgress = false;
    newBtn.dataset.originalValue = platform.name;
    newBtn.classList.add(
        "bg-blue-500",
        "hover:bg-blue-400",
        "text-white",
        "hover:text-black",
        "hover:bold",
        "p-2",
        "m-2",
        "rounded"
    );
    newBtn.innerText = newBtn.dataset.originalValue;

    newBtn.addEventListener("click", async function () {
        pushFeedback(this);
        toggleSearchAnimation(this);

        const responseObj = await sendSearchQuery([platform.name]);
        const { cleanResponse, platformsSuccess } =
            await handleSearchErrorHandling(responseObj);
        refreshResultList(false, cleanResponse, platformsSuccess);
    });
    return newBtn;
}

console.log("Party JS successfully loaded!");
