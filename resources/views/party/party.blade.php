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

                function pushFeedback(e) {
                    e.animate(pushFeedbackAnimation, pushFeedbackTiming);
                }

                const pushFeedbackAnimation = [{
                        transform: "scale(1)"
                    },
                    {
                        transform: "scale(0.9)"
                    },
                    {
                        transform: "scale(1)"
                    },
                ];

                const pushFeedbackTiming = {
                    duration: 150,
                    iterations: 1,
                };
            </script>
            @vite(['resources/js/partyPlayer.js'])
        @endsection
        @include('layouts.party.player')
    @endisset
@endsection
