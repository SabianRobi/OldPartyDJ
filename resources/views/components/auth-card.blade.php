@props(['title'])

<div class="flex flex-col sm:justify-center items-center py-2">
    {{-- <div>
        {{ $logo }}
    </div> --}}

    <h2 class="text-4xl mb-3">{{ $title }}</h2>

    <div class="w-full sm:max-w-md px-6 py-4 bg-white dark:bg-gray-700 shadow-md overflow-hidden sm:rounded-lg">
        {{ $slot }}
    </div>
</div>
