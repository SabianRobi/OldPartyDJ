const searchForm = document.querySelector('#searchForm');
const queryInp = document.querySelector('#query');
const searchBtn = document.querySelector('#searchBtn');
const resultsUl = document.querySelector('#results');

searchBtn.addEventListener('click', sendSearchRequest);
searchForm.addEventListener('submit', sendSearchRequest);

let query;

async function sendSearchRequest(e) {
	e.preventDefault();
	query = queryInp.value;

	if(query.length < 3) {
		console.log('Please be more specific!'); // TODO alert user
		return;
	}
	console.log('Searching \'' + query + '\'');
	let result = await searchSpotify();

    resultsUl.innerHTML = "";
    result.forEach(track => {
        let length = new Date(track['length']);
        resultsUl.appendChild(getMusicCardHTML(track['image'], track['title'], track['artists'], length.getMinutes()+"m"+length.getSeconds()+"s"));
    });
}

//Sends AJAX request to make a search in the Spotify database
async function searchSpotify() {
	const response = await fetch('/party/spotify/search?query=' + query)
			.then(res => res.json());
	return response;
}

function getMusicCardHTML(image, title, artists, length) {

    const artistsP = document.createElement('p');
    artistsP.classList.add("mb-3", "font-normal", "text-gray-700", "dark:text-gray-400");
    artists.forEach(artist => {
        artistsP.innerHTML += artist+", ";
    });
    artistsP.innerHTML = artistsP.innerHTML.substring((artistsP.innerHTML.length)-2, 0);

    const lengthP = document.createElement('p');
    lengthP.classList.add("text-xs", "text-gray-500", "absolute", "bottom-1", "right-2");
    lengthP.innerHTML = length;

    const innerDiv = document.createElement('div');
    innerDiv.classList.add("m-0", "p-0");
    innerDiv.appendChild(artistsP);
    innerDiv.appendChild(lengthP);

    const titleP = document.createElement('h5');
    titleP.classList.add("mb-2", "text-xl", "font-bold", "tracking-tight", "text-gray-900", "dark:text-white");
    titleP.innerHTML = title;

    const outerDiv = document.createElement('div');
    outerDiv.classList.add("flex", "flex-col", "justify-between", "pl-2", "pr-4", "py-1", "leading-normal");
    outerDiv.appendChild(titleP);
    outerDiv.appendChild(innerDiv);

    const imgO = document.createElement('img');
    imgO.classList.add("p-2", "object-cover", "h-auto", "w-32");
    imgO.src = image;

    const card = document.createElement('a');
    card.classList.add("relative", "flex", "flex-row", "max-w-xl", "items-center", "bg-white", "border", "rounded-lg", "shadow-md", "hover:bg-gray-100", "dark:border-gray-700", "dark:bg-gray-800", "dark:hover:bg-gray-700", "mt-1");
    card.appendChild(imgO);
    card.appendChild(outerDiv);

    return card;
}
