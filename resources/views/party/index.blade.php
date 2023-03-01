@extends('layouts.mainBody')

@section('title', 'Join or create party')

@section('header', 'nothing')

@section('viteImports')
    @vite(['resources/js/party.js'])
@endsection

@section('content')
<div class="container mx-auto flex flex-col justify-between items-center md:flex-row">
    <div id="joinParty" class="flex-auto justify-center text-center align-middle">
        <a href="{{ route('joinParty') }}">
            <p>Join a Party</p>
        </a>
    </div>
    <div id="createParty" class="flex-auto justify-center text-center align-middle">
        <a href="{{ route('createParty') }}">
            <p>Create a Party</p>
        </a>
    </div>
</div>

@endsection
