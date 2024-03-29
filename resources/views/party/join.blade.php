@extends('layouts.mainBody')
@section('header', 'something')
@section('noContentMargin', 'something')
@section('title', 'Join party')
@section('content')
    <div class="flex flex-col justify-center items-center h-full">
        <div class="flex-1"></div>
        <div class="flex-1 flex text-center justify-center">
            <x-auth-card :title="__('Join party')">
                {{-- <x-slot name="logo">
                    <a href="{{ route('home') }}">
                        <x-application-logo class="w-16 h-16 fill-current text-gray-500" />
                    </a>
                </x-slot> --}}

                <form method="POST" action="{{ route('joinParty') }}">
                    @csrf

                    <!-- Party name -->
                    <div>
                        <x-input-label for="party_name" :value="__('Party name')" :required="__('')" />

                        <x-text-input id="party_name" class="block mt-1 w-full" type="text" name="party_name"
                            :value="old('party_name')" required autofocus />

                        <x-input-error :messages="$errors->get('party_name')" class="mt-2" />
                    </div>

                    <!-- Password -->
                    <div class="mt-4">
                        <x-input-label for="party_password" :value="__('Party password')" />

                        <x-text-input id="party_password" class="block mt-1 w-full" type="password" name="party_password" />

                        <x-input-error :messages="$errors->get('party_password')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800"
                            href="{{ route('createParty') }}">
                            {{ __('Create a party instead!') }}
                        </a>

                        <x-primary-button class="ml-4">
                            {{ __('Join Party') }}
                        </x-primary-button>
                    </div>
                </form>
            </x-auth-card>
        </div>
        <div class="flex-1">
        </div>
    </div>

@endsection
