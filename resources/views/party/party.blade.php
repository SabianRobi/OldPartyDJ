@extends('layouts.mainBody')

@section('title', 'Party Time')

@section('header', 'nothing')

@section('viteImports')
    @vite(['resources/js/party.js', 'resources/js/marquee-text-element.js'])
@endsection

@section('content')
    @include('layouts.party.participant')

    @isset($spotifyToken)
        @section('scripts')
            <script src="https://sdk.scdn.co/spotify-player.js"></script>
            <script>
                let token = "{{ $spotifyToken }}";
            </script>
            @vite(['resources/js/partyPlayer.js'])
            <link rel="preload" as="image" href="images/loading.gif">
        @endsection
        @include('layouts.party.player')
    @else
        @section('scripts')
            <link rel="preload" as="image" href="images/loading.gif">
        @endsection
    @endisset
@endsection
