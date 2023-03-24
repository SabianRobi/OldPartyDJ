<?php

use App\Http\Controllers\ProfileController;
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

Route::get('/party/landing', [PartyController::class, 'index'])
    ->name('landingParty');
Route::get('/party/create', [PartyController::class, 'create'])
    ->middleware('auth')
    ->name('createParty');
Route::post('/party/create', [PartyController::class, 'store'])
    ->middleware('auth')
    ->name('storeParty');
Route::get('/party/join', [PartyController::class, 'joinPage'])
    ->middleware('auth')
    ->name('joinPageParty');
Route::post('/party/join', [PartyController::class, 'join'])
    ->middleware('auth')
    ->name('joinParty');
Route::post('/party/leave', [PartyController::class, 'leave'])
    ->middleware(LeavePartyMiddleware::class)
    ->middleware('auth')
    ->name('leaveParty');
Route::get('/party', [PartyController::class, 'inParty'])
    ->middleware('auth')
    ->name('party');

Route::post('/party/spotify/login', [MusicController::class, 'doLogin']);
Route::get('/party/spotify/callback', [MusicController::class, 'callback']);
Route::get('/party/spotify/search', [MusicController::class, 'searchTrack']);
Route::get('/party/spotify/refreshToken', [MusicController::class, 'refreshToken']);
Route::post('/party/spotify/setDeviceId', [MusicController::class, 'setDeviceId']);
Route::post('/party/spotify/addTrack', [MusicController::class, 'addTrackToQueue']);
Route::post('/party/playNextTrack', [MusicController::class, 'playNextTrack']);
Route::get('/party/getSongsInQueue', [MusicController::class, 'getSongsInQueue'])->middleware('auth');

//Temp
Route::get('/test', function() {
    return view('test');
});

// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
