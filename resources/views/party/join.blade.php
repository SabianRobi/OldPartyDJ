@extends('layouts.mainBody')
@section('header', 'something')
@section('title', 'Join party')
@section('content')
    <x-auth-card>
        <x-slot name="logo">
            <a href="{{ route('home') }}">
                <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
            </a>
        </x-slot>

        <form method="POST" action="{{ route('joinParty') }}">
            @csrf

            <!-- Party name -->
            <div>
                <x-input-label for="name" :value="__('Name')" :required="__('')"/>

                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required
                    autofocus />

                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <!-- Password -->
            <div class="mt-4">
                <x-input-label for="password" :value="__('Password')" />

                <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" />

                <x-input-error :messages="$errors->get('password')" class="mt-2" />
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
@endsection
