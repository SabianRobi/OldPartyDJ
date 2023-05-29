<div class="flex flex-row pb-2">
    <div class="flex-none lg:shrink"></div>
    <div class="flex-1 bg-yellow-800 rounded p-2 md:px-4 w-full flex flex-row flex-wrap justify-items-stretch">
        <div class="grow flex flex-row">
            <img src="/images/party/defaultCover.png" alt="Default cover" id="player_image"
                class="h-12 border border-black">
            <div class="flex flex-col ml-2">
                <marquee-text data-duration="14s" class="text-gray-200" id="player_title" style="max-width: 30ch;">
                    Track title comes here
                </marquee-text>
                <marquee-text data-duration="10s" class="text-gray-400" id="player_artist" style="max-width: 30ch;">
                    Track artist will be here
                </marquee-text>
            </div>
        </div>
        <div class="flex flex-row mt-2 justify-items-stretch">
            <div class="self-center grid ml-2 text-center items-center">
                <label for="player_volume"class="block mb-2 text-sm text-white">Volume</label>
                <input id="player_volume" type="range" min="0" max="1" step="0.01" value="0.25"
                    class="h-2 rounded appearance-none cursor-pointer mb-2 bg-gray-200 dark:bg-yellow-500">
            </div>

            <img src="/images/mediaControls/circle-play-regular.svg" alt="Toggle play" id="player_toggle_play"
                class="h-12 ml-3" data-started-src='/images/mediaControls/circle-play-regular.svg' data-paused-src='/images/mediaControls/circle-pause-regular.svg'>
            <img src="/images/mediaControls/forward-step-solid.svg" alt="Skip song" id="player_next" class="h-12 ml-3">
        </div>
    </div>
    <div class="flex-none lg:shrink"></div>
</div>
