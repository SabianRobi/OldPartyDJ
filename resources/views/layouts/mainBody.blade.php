<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Font Awesome --}}
    {{-- <script src="https://kit.fontawesome.com/71af6a2087.js" crossorigin="anonymous"></script> --}}

    {{-- Logo --}}
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/logo.png') }}" />

    {{-- Title --}}
    <title>
        {{ config('app.name', 'PartyDJ') }}
        @if (View::hasSection('title'))
            | @yield('title')
        @endif
    </title>

    {{-- Header --}}
    <style>
        .header {
            background-image: url({{ asset('images/pl_1000x500.png') }});
        }
    </style>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/autoRefresh.js'])
    <!-- 'resources/css/flowbite.css', 'resources/js/flowbite.js', 'resources/js/tailwind.js' -->
</head>

<body class="dark">
    @include('layouts.common.navBar')

    @sectionMissing('header')
        @include('layouts.common.header')
    @endif

    <main class="min-h-screen bg-white text-black dark:bg-gray-600 dark:text-white">
        @yield('content')
    </main>

    @include('layouts.common.footer')

    @if (View::hasSection('scripts'))
        @yield('scripts')
    @endif
    <script src="https://unpkg.com/flowbite@1.5.1/dist/flowbite.js"></script>
</body>

</html>
