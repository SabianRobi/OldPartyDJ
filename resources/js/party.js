const searchForm = document.querySelector('#searchForm');
const queryInp = document.querySelector('#query');
const searchBtn = document.querySelector('#searchBtn');

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

	console.log(result['tracks']['items']);

	console.log(query);
}

//Sends AJAX request to make a search in the Spotify database
async function searchSpotify() {
	const response = await fetch('/party/spotify/search?query=' + query)
			.then(res => res.json());
	return response;
}