@extends('layouts.mainBody')

@section('title', 'Join or create party')

@section('header', 'nothing')

@section('noContentMargin', 'something')

@section('content')
    <div class="flex flex-col md:flex-row justify-center items-center h-full">
        <a href="{{ route('joinParty') }}"
            class="flex-1 text-center self-stretch grid content-center bg-blue-400 bg-no-repeat bg-center bg-cover grayscale hover:grayscale-0 duration-300"
            id="joinParty" style="background-image: url({{ asset('images/party/joinParty.webp') }})">
            <p class="brightness-200 filter-none text-6xl font-extrabold text-red-700">Join</p>
        </a>
        <a href="{{ route('createParty') }}"
            class="flex-1 text-center self-stretch grid content-center bg-green-500 bg-no-repeat bg-center bg-cover grayscale hover:grayscale-0 duration-300"
            id="createParty" style="background-image: url({{ asset('images/party/createParty.webp') }})">
            <p class="brightness-200 filter-none text-6xl font-extrabold text-lime-400">Create</p>
        </a>
    </div>

@endsection

@section('scripts')
    <script>
        const joinDiv = document.querySelector("#joinParty");
        const createDiv = document.querySelector("#createParty");

        joinDiv.addEventListener('mouseenter', hover);
        joinDiv.addEventListener('mouseleave', hover);
        createDiv.addEventListener('mouseenter', hover);
        createDiv.addEventListener('mouseleave', hover);

        let hovering = false;
        function hover() {
            hovering = !hovering;
        }

        function highlight() {
            if (!hovering) {
                focusJoin();
                setTimeout(() => {
                    focusCreate();
                }, 150);
            }
        }

        setTimeout(() => {
            highlight();
        }, 500);
        setInterval(() => {
            highlight();
        }, 5000);

        function focusJoin() {
            joinDiv.classList.toggle("grayscale");
            setTimeout(() => {
                joinDiv.classList.toggle("grayscale");
            }, 350);
        }

        function focusCreate() {
            createDiv.classList.toggle("grayscale");
            setTimeout(() => {
                createDiv.classList.toggle("grayscale");
            }, 350);
        }
    </script>
@endsection
