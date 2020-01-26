<?php

use Illuminate\Support\Facades\Cache;
use Abraham\TwitterOAuth\TwitterOAuth;
use App\Tweet;

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
    $tweet = Cache::rememberForever('lastTweet', function () {
        return Tweet::last()->get()[0];
    });

    $mins = $tweet->created->diffInMinutes('now');
    $days = $tweet->created->diffInDays('now');
    $lastUpdate = $tweet->created->diffForHumans();

    $contextualClass = 'success';
    $status = 'No';
    if ($mins < 20) {
        $contextualClass = 'danger';
        $status = 'Yes';
    } elseif ($mins < 60) {
        $contextualClass = 'warning';
        $status = 'Maybe ¯\_(ツ)_/¯';
    }

    list($startDate, $endDate) = Cache::rememberForever('longestStreak', function () {
        return Tweet::streak();
    });

    $days = $startDate->diffInDays($endDate);

    $longestStreak = $days . ' day' . ($days > 1 ? 's' : '') . ' between ' . $startDate->toFormattedDateString() . ' and ' . $endDate->toFormattedDateString();

    return view('index', compact('contextualClass', 'status', 'lastUpdate', 'longestStreak'));
});
