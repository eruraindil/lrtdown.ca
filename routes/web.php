<?php

use Illuminate\Support\Carbon;
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

    $maintenance = false;
    $now = Carbon::now(config('app.timezone'));
    foreach (config('app.maintenance_days') as $day) {
        if ($now->isSameDay($day)) {
            $maintenance = true;
            break;
        }
    }

    if ($maintenance === false) {
        $contextualClass = 'success';
        $status = 'No';
        if ($mins < 20) {
            $contextualClass = 'danger';
            $status = 'Yes';
        } elseif ($mins < 60) {
            $contextualClass = 'warning';
            $status = 'Maybe ¯\_(ツ)_/¯';
        }
    } else {
        $contextualClass = 'warning';
        $status = 'Closed for maintenance';
    }

    list($startDate, $endDate, $counter) = Cache::get('longestStreak');
    if (isset($startDate) && isset($endDate) && isset($counter)) {
        $days = $startDate->diffInDays($counter);

        $longestStreak = $days . ' day' . ($days > 1 ? 's' : '') . ' between ' . $startDate->toFormattedDateString() . ' and ' . $endDate->toFormattedDateString();
    } else {
        $longestStreak = null;
    }

    return view('index', compact('contextualClass', 'status', 'trigger', 'lastUpdate', 'longestStreak'));
});
