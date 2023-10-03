@extends('layouts.mainBody')

@section('title', 'Party Time')

@section('header', 'nothing')

@section('viteImports')
    @vite(['resources/js/party.js', 'resources/js/marquee-text-element.js'])
@endsection

@section('content')
    @include('layouts.party.participant')

    @if ($creator)
        @section('scripts')
            @if ($loggedInWithSpotify)
                <script src="https://sdk.scdn.co/spotify-player.js"></script>
                <script>
                    let token = "{{ $spotifyToken }}";
                </script>
            @endif
            @vite(['resources/js/partyPlayer.js'])
        @endsection
        @include('layouts.party.player')
        @include('layouts.party.YTplayer')
    @endif
@endsection
