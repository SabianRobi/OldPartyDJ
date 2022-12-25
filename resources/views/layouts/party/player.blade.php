<div class="fixed bottom-1 right-1 h-fit flex flex-col items-center justify-center">
    <div class="relative flex flex-col rounded-xl shadow border dark:backdrop-blur-xl bg-lime-300 dark:bg-lime-900">
        <div class="p-3 flex items-center z-50">
            <img class="w-24 h-24 mr-6 border" id="spotify_player_image" src="https://i.scdn.co/image/ab67616d00001e02d5568dedd90ea5dcc0fd063a"/>
            <div class="flex flex-col">
                <span class="font-sans text-lg font-medium leading-7 text-black dark:text-white" id="spotify_player_title">Song title</span>
                <span class="font-sans text-base font-medium leading-6 text-gray-500 dark:text-gray-400" id="spotify_player_artist">Drake, Báró dikkha, Hogyava</span>
            </div>
        </div>
        <div class="px-10 rounded-b-xl flex items-center justify-between z-50">

            {{-- Heart icon --}}
            {{-- <div class="cursor-pointer" id="song-saved">
                <svg width="26" height="24" viewBox="0 0 26 24" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M25 7C25 3.68629 22.2018 1 18.75 1C16.1692 1 13.9537 2.5017 13 4.64456C12.0463 2.5017 9.83082 1 7.25 1C3.79822 1 1 3.68629 1 7C1 14.6072 8.49219 20.1822 11.6365 22.187C12.4766 22.7226 13.5234 22.7226 14.3635 22.187C17.5078 20.1822 25 14.6072 25 7Z"
                        stroke="#94A3B8" stroke-width="2" stroke-linejoin="round" />
                </svg>
            </div> --}}

            {{-- Previous --}}
            <div class="cursor-pointer" id="spotify_player_previous">
                <img src="{{ asset('images/mediaControls/backward-step-solid.svg') }}" alt="Previous" class="h-12">
                {{-- <svg width="32" height="32" viewBox="0 0 32 32" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M26 7C26 5.76393 24.5889 5.05836 23.6 5.8L11.6 14.8C10.8 15.4 10.8 16.6 11.6 17.2L23.6 26.2C24.5889 26.9416 26 26.2361 26 25V7Z"
                        fill="#94A3B8" stroke="#94A3B8" stroke-width="2" stroke-linejoin="round" />
                    <path d="M6 5L6 27" stroke="#94A3B8" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg> --}}
            </div>

            {{-- Toggle play --}}
            <div class="cursor-pointer rounded-full p-2 mb-2 bg-black dark:bg-white shadow-xl flex items-center justify-center" id="spotify_player_toggle_play">
                {{-- Play --}}
                <div id="play-icon">
                    <img src="{{ asset('images/mediaControls/circle-play-regular.svg') }}" alt="Play" class="h-12">
                    {{-- <svg class="ml-[10px]" width="31" height="37" viewBox="0 0 31 37" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M29.6901 16.6608L4.00209 0.747111C2.12875 -0.476923 0.599998 0.421814 0.599998 2.75545V33.643C0.599998 35.9728 2.12747 36.8805 4.00209 35.6514L29.6901 19.7402C29.6901 19.7402 30.6043 19.0973 30.6043 18.2012C30.6043 17.3024 29.6901 16.6608 29.6901 16.6608Z"
                            class="fill-slate-500" />
                    </svg> --}}
                </div>

                {{-- Pause --}}
                <div id="pause-icon" hidden>
                    <img src="{{ asset('images/mediaControls/circle-pause-regular.svg') }}" alt="Pause" class="h-12">
                    {{-- <svg width="24" height="36" viewBox="0 0 24 36" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <rect width="6" height="36" rx="3" class="fill-slate-500 dark:fill-slate-400" />
                        <rect x="18" width="6" height="36" rx="3"
                            class="fill-slate-500 dark:fill-slate-400" />
                    </svg> --}}
                </div>
            </div>

            {{-- Next --}}
            <div class="cursor-pointer" id="spotify_player_next">
                <img src="{{ asset('images/mediaControls/forward-step-solid.svg') }}" alt="Next" class="h-12">
                {{-- <svg width="32" height="32" viewBox="0 0 32 32" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M6 7C6 5.76393 7.41115 5.05836 8.4 5.8L20.4 14.8C21.2 15.4 21.2 16.6 20.4 17.2L8.4 26.2C7.41115 26.9416 6 26.2361 6 25V7Z"
                        fill="#94A3B8" stroke="#94A3B8" stroke-width="2" stroke-linejoin="round" />
                    <path d="M26 5L26 27" stroke="#94A3B8" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg> --}}
            </div>
        </div>
    </div>
</div>
