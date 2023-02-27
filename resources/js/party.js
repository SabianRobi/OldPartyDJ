const searchForm = document.querySelector("#searchForm");
const queryInp = document.querySelector("#query");
const searchBtn = document.querySelector("#searchBtn");
const resultsUl = document.querySelector("#results");
const csrfToken = document.head.querySelector("meta[name=csrf-token]").content;

const searchButtonText = document.querySelector("#searchBtnText");
const searchButtonImage = document.querySelector("#searchBtnImage");
searchBtn.addEventListener("click", sendSearchRequest);
searchForm.addEventListener("submit", sendSearchRequest);

let query;
const hints = [ "Blue", "abcdefu", "Turn it up", "Sweet Dreams", "Glad you came", "Monster", "Mizu", "Csavard fel a szőnyeget", "Everthing Black", "RISE", "Me, Myself & I", "Him & I", "Wellerman", "hot girl boomer", "Save Your Tears", "Summer Waves", "Élvezd", "Low", "Dynamite", "Hangover", "I Gotta Feeling", "Can't hold us", "Shape of You", "Beliver", "Thunder", "Tudod, Hmmmm", "Young, Wild & Free", "Csepereg az eső", "Érik a szőlő"];

//Toggles the searching icon
function toggleSearchAnimation() {
    if (searchButtonText.hidden) {
        searchForm.addEventListener("submit", sendSearchRequest);
        searchBtn.addEventListener("click", sendSearchRequest);
        // setNewSearchHint();
    } else {
        searchForm.removeEventListener("submit", sendSearchRequest);
        searchBtn.removeEventListener("click", sendSearchRequest);
    }
    searchButtonText.toggleAttribute("hidden");
    searchButtonImage.toggleAttribute("hidden");
}

async function sendSearchRequest(e) {
    e.preventDefault();
    toggleSearchAnimation();

    query = queryInp.value == "" ? queryInp.placeholder : queryInp.value;
    const platform = "Spotify";

    if (query.length < 3) {
        console.warn("Please be more specific!"); // TODO alert user
        return;
    }

    console.log(`Searching '${query}' on ${platform}...`);
    let result;
    if (platform == "Spotify") {
        result = await searchSpotify();
    }
    console.log(`Received ${result.length} tracks from ${platform}!`);

    resultsUl.innerHTML = "";
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
    refreshListeners();
    changeHint();
    toggleSearchAnimation();
}

//Sends AJAX request to make a search in the Spotify database
async function searchSpotify() {
    const response = await fetch("/party/spotify/search?query=" + query).then(
        (res) => res.json()
    );
    return response;
}

function refreshListeners() {
    const cards = resultsUl.querySelectorAll("[data-uri]");
    cards.forEach((card) => {
        card.addEventListener("click", () => {
            addToQueue(card.dataset.uri, card.dataset.platform);
        });
    });
}

function getMusicCardHTML(image, title, artists, length, uri, platform) {
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
    lengthP.innerHTML = length;

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
    imgO.src = image;

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
    card.appendChild(imgO);
    card.appendChild(outerDiv);

    return card;
}

async function addToQueue(uri, platform) {
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
        console.log("Successfully added track to queue!");
    } else {
        console.error("Failed to add track to queue:", response);
    }
    return response;
}

function changeHint() {
    queryInp.placeholder = hints.at(Math.floor(Math.random()*hints.length));
}
changeHint();
