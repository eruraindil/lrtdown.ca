<?php

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

    $tweet = Tweet::last()->first();

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

    return view('index', compact('contextualClass', 'status', 'lastUpdate'));
});
