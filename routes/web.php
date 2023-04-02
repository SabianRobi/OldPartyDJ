<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MusicController;
use App\Http\Controllers\PartyController;
use App\Http\Middleware\LeavePartyMiddleware;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('home');
})->name('home');

//Requires auth
Route::controller(PartyController::class)->middleware('auth')->group(function () {
    Route::get('/party/landing', 'index')->name('landingParty')
        ->withoutMiddleware('auth');
    Route::get('/party/create', 'create')->name('createParty');
    Route::post('/party/create', 'store')->name('storeParty');
    Route::get('/party/join', 'joinPage')->name('joinPageParty');
    Route::post('/party/join', 'join')->name('joinParty');

    //Requires user to be in a party
    Route::middleware('inParty')->group(function () {
        Route::get('/party', 'inParty')->name('party');
        Route::post('/party/leave', 'leave')->name('leaveParty')
            ->middleware(LeavePartyMiddleware::class);
    });
});

//Requires auth and the user to be in a party
Route::controller(MusicController::class)->middleware(['auth', 'inParty'])->group(function () {
    Route::post('/party/spotify/login', 'doLogin');
    Route::get('/party/spotify/callback', 'callback');
    Route::get('/party/spotify/search', 'searchTrack');
    Route::get('/party/spotify/refreshToken', 'refreshToken');
    Route::post('/party/spotify/addTrack', 'addTrackToQueue');
    Route::get('/party/getSongsInQueue', 'getSongsInQueue');

    //Requires user to own a party
    Route::middleware('hasParty')->group(function () {
        Route::post('/party/spotify/setDeviceId', 'setDeviceId');
        Route::post('/party/playNextTrack', 'playNextTrack');
    });
});

Route::get('/test', function () {
    return view('test');
});

// Route::middleware('auth')->group(function () {
//     Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
//     Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
//     Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
// });

require __DIR__ . '/auth.php';
