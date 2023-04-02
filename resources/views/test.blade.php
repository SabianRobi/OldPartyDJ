@extends('layouts.mainBody')

@section('header', 'nothing')

@section('styles')
    @vite(['resources/js/test.js', 'resources/js/marquee-text-element.js'])
    <style>
        .range-bar {
            background-color: #a9acb1;
            border-radius: 15px;
            display: block;
            height: 4px;
            position: relative;
            width: 100%
        }

        .range-quantity {
            background-color: #017afd;
            border-radius: 15px;
            display: block;
            height: 100%;
            width: 0
        }

        .range-handle {
            background-color: #fff;
            border-radius: 100%;
            cursor: move;
            height: 30px;
            left: 0;
            top: -13px;
            position: absolute;
            width: 30px;
            -webkit-box-shadow: 0 1px 3px rgba(0, 0, 0, .4);
            box-shadow: 0 1px 3px rgba(0, 0, 0, .4)
        }

        .range-min,
        .range-max {
            color: #181819;
            font-size: 12px;
            height: 20px;
            padding-top: 4px;
            position: absolute;
            text-align: center;
            top: -9px;
            width: 24px
        }

        .range-min {
            left: -30px
        }

        .range-max {
            right: -30px
        }

        .vertical {
            height: 100%;
            width: 4px
        }

        .vertical .range-quantity {
            bottom: 0;
            height: 0;
            position: absolute;
            width: 100%
        }

        .vertical .range-handle {
            bottom: 0;
            left: -13px;
            top: auto
        }

        .vertical .range-min,
        .vertical .range-max {
            left: -10px;
            right: auto;
            top: auto
        }

        .vertical .range-min {
            bottom: -30px
        }

        .vertical .range-max {
            top: -30px
        }

        .unselectable {
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            -khtml-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none
        }

        .range-disabled {
            cursor: default
        }

        marquee-text {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
@endsection

@section('content')
    <div class="flex flex-row pb-2">
        <div class="flex-none lg:shrink"></div>
        <div class="flex-1 bg-yellow-800 rounded p-2 md:px-4 w-full flex flex-row flex-wrap justify-items-stretch">
            <div class="grow flex flex-row">
                <img src="/images/party/defaultCover.png" alt="Default cover" class="h-12 rounded border border-black">
                <div class="flex flex-col ml-2">
                    <marquee-text duration="14s" class="text-gray-200" id="title" style="max-width: 30ch;">
                        Új szöveg hátha így frissül időben asd asd asdas d
                    </marquee-text>
                    <marquee-text duration="10s" class="text-gray-400" id="artists" style="max-width: 30ch;">
                        123456789111315
                    </marquee-text>
                </div>
            </div>
            <div class="flex flex-row mt-2 justify-items-stretch">
                <div class="self-center grid ml-2 text-center items-center">
                    <label for="volume"class="block mb-2 text-sm">Volume</label>
                    <input id="volume" type="range" min="0" max="1" step="0.01"
                        class="h-2 rounded appearance-none cursor-pointer mb-2 bg-gray-200 dark:bg-yellow-500">
                </div>

                <img src="/images/mediaControls/circle-play-regular.svg" alt="Toggle play" class="h-12 ml-3">
                <img src="/images/mediaControls/forward-step-solid.svg" alt="Skip song" class="h-12 ml-3">
            </div>


        </div>
        <div class="flex-none lg:shrink"></div>
    </div>
@endsection
