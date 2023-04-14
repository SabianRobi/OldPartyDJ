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
                let dataSaver = false;

                function pushFeedback(e) {
                    e.animate(animation, timing);
                }

                const animation = [{
                        transform: "scale(1)"
                    },
                    {
                        transform: "scale(0.9)"
                    },
                    {
                        transform: "scale(1)"
                    },
                ];

                const timing = {
                    duration: 150,
                    iterations: 1,
                };
            </script>
            @vite(['resources/js/partyPlayer.js'])
        @endsection
        @include('layouts.party.player')
    @endif
@endsection
