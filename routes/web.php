<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MusicController;
use App\Http\Controllers\PartyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SpotifyController;
use App\Http\Middleware\LeavePartyMiddleware;
// use App\Models\Party;
// use App\Models\SpotifyThings;
// use App\Models\TrackInQueue;
// use App\Models\User;
// use Illuminate\Support\Facades\Hash;

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
    Route::middleware('hasParty')->post('/party/delete', 'delete')->name('deleteParty');
});

//Spotify
Route::controller(SpotifyController::class)->middleware(['auth'])->group(function () {
    Route::post('/platforms/spotify/login', 'login')                ->name('spotifyLogin');
    Route::get('/platforms/spotify/callback', 'callback');
    Route::delete('/platforms/spotify/disconnect', 'disconnect')    ->name('spotifyDisconnect');
    Route::get('/platforms/spotify/token', 'getToken');
    Route::patch('/platforms/spotify/token', 'refreshToken');
});

//Requires auth and the user to be in a party
Route::controller(MusicController::class)->middleware(['auth', 'inParty'])->group(function () {
    Route::get('/party/search', 'searchTrack');
    Route::post('/party/addTrack', 'addTrackToQueue');
    Route::post('/party/removeTrack', 'removeTrackFromQueue');
    Route::get('/party/getSongsInQueue', 'getSongsInQueue');

    //Requires user to own a party
    Route::middleware('hasParty')->group(function () {
        Route::post('/party/spotify/setDeviceId', 'setDeviceId');
        Route::post('/party/playNextTrack', 'playNextTrack');
    });
});

// Route::get('/testPartyUser', function () {
//     $user = User::where('name', 'New User')->firstOr(function () {
//         $user = new User();
//         $user->name = 'New User';
//         $user->username = 'user1';
//         $user->email = 'user1@partydj.com';
//         $user->password = Hash::make('password');
//         $user->save();
//     });
//     $user = User::where('name', 'New User')->first();

//     $party = Party::where('name', 'New Party')->firstOr(function () use (&$user) {
//         $party = new Party();
//         $party->name = "New Party";
//         $party->creator = $user->id;
//         $party->save();
//     });
//     $party = Party::where('name', 'New Party')->first();
//     $user->party_id = $party->id;
//     $user->role = 'creator';
//     $user->save();
//     $user->update();



//     $party->delete();

//     $data = [
//         'user' => $user,
//         'party' => $party
//     ];

//     return response()->json($data);
//     // return view('test');
// });

// Route::get('/testUserSpotify', function () {
//     $user = User::where('name', 'Test Spotify')->firstOr(function () {
//         $user = new User();
//         $user->name = 'Test Spotify';
//         $user->username = 'testspotify';
//         $user->email = 'testspotify@partydj.com';
//         $user->password = Hash::make('password');
//         $user->save();
//     });
//     $user = User::where('name', 'Test Spotify')->first();

//     $spotify = SpotifyThings::where('owner', $user->id)->firstOr(function () use (&$user) {
//         $spotify = new SpotifyThings();
//         $spotify->token = "krixkrax";
//         $spotify->refresh_token = "krixkraxkrux";
//         $spotify->owner = $user->id;
//         $spotify->save();
//     });
//     $spotify = SpotifyThings::where('owner', $user->id)->first();


//     $user->delete();


//     $data = [
//         'user' => $user,
//         'spotify' => $spotify
//     ];

//     return response()->json($data);
//     // return view('test');
// });

// Route::get('/testUserTrack', function () {
//     $user = User::where('name', 'Test UserTrack')->firstOr(function () {
//         $user = new User();
//         $user->name = 'Test UserTrack';
//         $user->username = 'testUserTrack';
//         $user->email = 'testUserTrack@partydj.com';
//         $user->password = Hash::make('password');
//         $user->save();
//     });
//     $user = User::where('name', 'Test UserTrack')->first();

//     $party = Party::where('name', 'New Party')->firstOr(function () use (&$user) {
//         $party = new Party();
//         $party->name = "New Party";
//         $party->creator = $user->id;
//         $party->save();
//     });
//     $party = Party::where('name', 'New Party')->first();

//     $user->party_id = $party->id;
//     $user->role = 'creator';
//     $user->save();
//     $user->update();

//     $track = TrackInQueue::where('addedBy', $user->id)->firstOr(function () use (&$user, &$party) {
//         $track = new TrackInQueue();
//         $track->addedBy = $user->id;
//         $track->party_id = $party->id;
//         $track->platform = "Spotify";
//         $track->track_uri = "spot:good:stuff";
//         $track->score = 23;
//         $track->save();
//     });
//     $track = TrackInQueue::where('addedBy', $user->id)->first();


//     $user->delete();


//     $data = [
//         'user' => $user,
//         'party' => $party,
//         'track' => $track,
//     ];

//     return response()->json($data);
//     // return view('test');
// });

// Route::get('/testPartyTrack', function () {
//     $user = User::where('name', 'Test PartyTrack')->firstOr(function () {
//         $user = new User();
//         $user->name = 'Test PartyTrack';
//         $user->username = 'testPartyTrack';
//         $user->email = 'testPartyTrack@partydj.com';
//         $user->password = Hash::make('password');
//         $user->save();
//     });
//     $user = User::where('name', 'Test PartyTrack')->first();

//     $party = Party::where('name', 'New Party')->firstOr(function () use (&$user) {
//         $party = new Party();
//         $party->name = "New Party";
//         $party->creator = $user->id;
//         $party->save();
//     });
//     $party = Party::where('name', 'New Party')->first();

//     $user->party_id = $party->id;
//     $user->role = 'creator';
//     $user->save();
//     $user->update();

//     $track = TrackInQueue::where('addedBy', $user->id)->firstOr(function () use (&$user, &$party) {
//         $track = new TrackInQueue();
//         $track->addedBy = $user->id;
//         $track->party_id = $party->id;
//         $track->platform = "Spotify";
//         $track->track_uri = "spot:good:stuff";
//         $track->score = 23;
//         $track->save();
//     });
//     $track = TrackInQueue::where('addedBy', $user->id)->first();


//     $party->delete();


//     $data = [
//         'user' => $user,
//         'party' => $party,
//         'track' => $track,
//     ];

//     return response()->json($data);
//     // return view('test');
// });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
