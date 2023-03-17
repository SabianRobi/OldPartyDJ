@extends('layouts.mainBody')

@section('title', 'Join or create party')

@section('header', 'nothing')

@section('noContentMargin', 'something')

@section('content')
    <div class="flex flex-col md:flex-row justify-center items-center h-full">
        <a href="{{ route('joinParty') }}"
            class="flex-1 text-center self-stretch grid content-center bg-blue-400 bg-no-repeat bg-center bg-cover grayscale hover:grayscale-0"
            id="joinParty" style="background-image: url({{ asset('images/party/joinParty.webp') }})">
            <p class="brightness-200 filter-none text-6xl font-extrabold text-red-700">Join</p>
        </a>
        <a href="{{ route('createParty') }}"
            class="flex-1 text-center self-stretch grid content-center bg-green-500 bg-no-repeat bg-center bg-cover grayscale hover:grayscale-0"
            id="createParty" style="background-image: url({{ asset('images/party/createParty.webp') }})">
            <p class="brightness-200 filter-none text-6xl font-extrabold text-lime-400">Create</p>
        </a>
    </div>

@endsection
