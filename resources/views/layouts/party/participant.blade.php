{{-- Login with Spotify --}}
<img src="images/loading.gif" alt="Pre-load the loading gif" hidden>
<p class="text-center text-xl">{{ $partyName }}</p>
@unless ($loggedInWithSpotify)
    <form action="/platforms/spotify/login" method="post">
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

            {{-- Platform toggle buttons --}}
            <div class="mt-2 flex flex-row-reverse">
                {{-- Spotify --}}
                <label class="relative inline-flex items-center mr-5 cursor-pointer">
                    <input type="checkbox" value="" name="searchSpotify" id="searchSpotify" class="sr-only peer" checked>
                    <div
                        class="w-11 h-6 bg-gray-200 rounded-full peer dark:bg-gray-700 peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-600">
                    </div>
                    <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-300">Spotify</span>
                </label>
                {{-- YouTube --}}
                <label class="relative inline-flex items-center mr-5 cursor-pointer">
                    <input type="checkbox" value="" name="searchYouTube" id="searchYouTube" class="sr-only peer" checked>
                    <div
                        class="w-11 h-6 bg-gray-200 rounded-full peer peer-focus:ring-4 peer-focus:ring-red-300 dark:peer-focus:ring-red-800 dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-red-600">
                    </div>
                    <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-300">YouTube</span>
                </label>
            </div>
        </form>
    </div>
@endunless
{{-- Buttons --}}
<div class="grid grid-cols-5 py-2">
    {{-- Leave party --}}
    <form action="{{ route('leaveParty') }}" method="post" class="">
        @csrf
        <button id="leaveParty" name="leaveParty" data-in-progress="false"
            class="bg-red-800 hover:bg-red-700 text-white font-bold py-2 px-1 w-full rounded">
            Leave party
        </button>
    </form>
    @if ($loggedInWithSpotify)
        {{-- Watch queue --}}
        <button id="getSongs" name="getSongs" data-in-progress="false" data-original-value="Watch queue"
            class="bg-teal-500 hover:bg-teal-400 text-white font-bold py-2 px-1 w-full rounded">
            Watch queue
        </button>

        {{-- Clear results --}}
        <button id="clearResults" name="clearResults"
            class="bg-yellow-500 hover:bg-yellow-400 text-white font-bold py-2 px-1 w-full rounded">
            Clear results
        </button>

        {{-- Data saver --}}
        <div>
            <input type="checkbox" id="dataSaver" value="" class="hidden peer">
            <label for="dataSaver"
                class="inline-flex bg-red-800 hover:bg-red-600 text-white font-bold py-2 px-1 w-full rounded cursor-pointer peer-checked:bg-green-700 peer-checked:hover:bg-green-500">
                <span class="mx-auto">
                    Data saver
                </span>
            </label>
        </div>
    @endif
    {{-- Delete party --}}
    @if ($creator)
        <form action="{{ route('deleteParty') }}" method="post">
            @csrf
            <button id="deleteParty" name="deleteParty" data-in-progress="false"
                class="bg-red-900 hover:bg-red-700 text-white font-bold py-2 px-1 w-full rounded">
                Delete party
            </button>
        </form>
    @endif
</div>

@if ($loggedInWithSpotify)
    {{-- Results --}}
    <ul id="results" class="w-full md:w-1/2 pb-1"></ul>
    <ol id="queue" class="w-full md:w-1/2 pb-1"></ol>
@endif

@if ($creator)
    <span id="isCreator" hidden>true</span>
@else
    <span id="isCreator" hidden>false</span>
@endif
