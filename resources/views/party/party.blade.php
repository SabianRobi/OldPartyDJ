@extends('layouts.mainBody')

@section('title', 'Party Time')

@section('header', 'nothing')

@if ($loggedInWithSpotify)
    @section('viteImports')
        @vite(['resources/js/party.js', 'resources/js/marquee-text-element.js'])
    @endsection
@endif

@section('content')
    @include('layouts.party.participant')

    @if ($loggedInWithSpotify && $creator)
        @section('scripts')
            <script src="https://sdk.scdn.co/spotify-player.js"></script>
            <script>
                let token = "{{ $spotifyToken }}";
            </script>
            @vite(['resources/js/partyPlayer.js'])
        @endsection
        @include('layouts.party.player')
    @endif
@endsection
