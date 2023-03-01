@extends('layouts.mainBody')

@section('title', 'Party Time')

@section('header', 'nothing')

@section('viteImports')
    @vite(['resources/js/party.js'])
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
        @endsection
        @include('layouts.party.player')
    @endif
@endsection
