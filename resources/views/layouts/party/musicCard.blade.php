@section('content')
    <a href="@yield('link')"
        class="relative flex flex-row max-w-xl items-center bg-white border rounded-lg shadow-md hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700">
        <img class="p-2 object-cover h-auto w-32" src="@yield('image')" alt="">
        <div class="flex flex-col justify-between pl-2 pr-4 py-1 leading-normal">
            <h5 class="mb-2 text-xl font-bold tracking-tight text-gray-900 dark:text-white">@yield('title')</h5>
            <div class="m-0 p-0">
                <p class="mb-3 font-normal text-gray-700 dark:text-gray-400">@yield('artist')</p>
                <p class="text-xs text-gray-500 absolute bottom-1 right-2">@yield('length')</p>
            </div>
        </div>
    </a>
@endsection
