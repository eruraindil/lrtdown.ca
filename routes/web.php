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

    $trigger = Tweet::filterTrigger($tweet->text);

    $contextualClass = 'success';
    $status = 'No';
    if ($mins < 20) {
        $contextualClass = 'danger';
        $status = 'Yes';
    } elseif ($mins < 60) {
        $contextualClass = 'warning';
        $status = 'Maybe ¯\_(ツ)_/¯';
    }

    $streak = Cache::get('longestStreak');
    if (isset($streak)) {
        $startDate = $streak[0];
        $endDate = $streak[1];
        $counter = $streak[2];

        $days = $startDate->diffInDays($counter);

        $longestStreak = $days . ' day' . ($days > 1 ? 's' : '') . ' between ' . $startDate->toFormattedDateString() . ' and ' . $endDate->toFormattedDateString();
    } else {
        $longestStreak = null;
    }

    return view('index', compact('contextualClass', 'status', 'trigger', 'lastUpdate', 'longestStreak'));
});
