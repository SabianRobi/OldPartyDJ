{{-- Login with Spotify --}}
<img src="images/loading.gif" alt="Pre-load the loading gif" hidden>
<p class="text-center text-xl">{{ $partyName }}</p>
@unless (session('spotifyToken'))
    <form action="/party/spotify/login" method="post">
        @csrf
        <button id="spotifyLogin" name="spotifyLogin"
            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Login with Spotify!</button>
    </form>
@else
    {{-- Search form --}}
    <div class="w-full mt-2">
        <form action="/party/spotify/search" method="get" id="searchForm">
            @csrf
            <label for="query" class="mb-2 text-sm font-medium text-gray-900 sr-only dark:text-white">Search
                tracks</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <svg aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="search" id="query" name="query" value=""
                    class="block w-full p-4 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                    placeholder="" required>
                <button type="submit" id="searchBtn" name="searchBtn" data-in-progress="false" data-original-value="Search"
                    class="text-white absolute right-2.5 bottom-2.5 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                    <span id="searchBtnText">Search</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Buttons --}}
    <div class="flex flex-row">
        {{-- Leave party --}}
        <form action="{{ route('leaveParty') }}" method="post" class="m-2">
            @csrf
            <button id="leaveParty" name="leaveParty" data-in-progress="false"
                class="bg-red-800 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Leave party</button>
        </form>

        {{-- Watch queue --}}
        <button id="getSongs" name="getSongs" data-in-progress="false" data-original-value="Get queued songs"
            class="bg-teal-500 hover:bg-teal-400 text-white font-bold py-2 px-4 m-2 rounded">Watch queue</button>

        {{-- Clear results --}}
        <button id="clearResults" name="clearResults"
            class="bg-yellow-500 hover:bg-yellow-400 text-white font-bold py-2 px-4 m-2 rounded">Clear results</button>
        <input type="checkbox" id="dataSaver" value="" class="hidden peer">

        {{-- Data saver --}}
        <label for="dataSaver"
            class="inline-flex bg-red-800 hover:bg-red-600 text-white font-bold py-2 px-4 m-2 rounded cursor-pointer peer-checked:bg-green-700 peer-checked:hover:bg-green-500">
            <span class="self-center">
                Data saver
            </span>
        </label>
    </div>

    {{-- Results --}}
    <ul id="results" class="w-full md:w-1/2 pb-1"></ul>
    <ol id="queue" class="w-full md:w-1/2 pb-1"></ol>
@endunless
