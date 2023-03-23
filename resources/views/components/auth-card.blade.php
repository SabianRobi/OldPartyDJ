@props(['title'])

<div class="flex flex-col sm:justify-center py-2">
    {{-- <div>
        {{ $logo }}
    </div> --}}

    <h2 class="text-4xl mb-3">{{ $title }}</h2>

    <div class="w-full sm:max-w-md px-6 py-4 bg-white dark:bg-gray-700 shadow-md overflow-hidden sm:rounded-lg">
        {{ $slot }}
    </div>
    <div class="mt-1 text-left dark:text-gray-300">
        <a href="{{ url()->previous() }}" class="">
                <img src="{{ asset('/images/misc/arrow-left-solid.svg') }}" alt="Go back" class="h-5 inline mr-1">
                Go back
        </a>
    </div>
</div>
