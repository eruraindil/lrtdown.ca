<?php

use Abraham\TwitterOAuth\TwitterOAuth;

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
    $connection = new TwitterOAuth(
        env('TWITTER_CONSUMER_KEY'),
        env('TWITTER_CONSUMER_SECRET'),
        env('TWITTER_ACCESS_TOKEN'),
        env('TWITTER_ACCESS_SECRET')
    );
    $content = $connection->get('search/tweets', [
        'q' => 'from:OCTranspoLive "Line 1"',
        'count' => 1000,
        'tweet_mode' => 'extended',
        'result_type' => 'recent'
    ]);
    if (!App::environment('production')) dump($content->statuses);

    $tweets = array_map(function($t) {
        return $t->full_text;
    }, $content->statuses);
    if (!App::environment('production')) dump($tweets);

    // $filtered_tweets = [];

    // foreach ($content->statuses as $tweet) {
    // }

    $filteredTweets = preg_grep('/(delay|close)/miU', $tweets);

    $filteredTweets = array_diff(
        $filteredTweets,
        preg_grep('/(minor|slight|small)\s\w?\s?(delay|delayed)/miU', $filteredTweets),
        preg_grep('/(restore|complete|resume|open)/miU', $filteredTweets),
        preg_grep('/(without|no)\s\w?\s?delay/miU', $filteredTweets)
    );

    if (!App::environment('production')) dump($filteredTweets);

    reset($filteredTweets);
    $key = key($filteredTweets);
    $lastTweet = $content->statuses[$key];

    if (strtotime($lastTweet->created_at) > strtotime('-24 hours')) {
        $lrtDown = true;
    } else {
        $lrtDown = false;
    }

    if (!App::environment('production')) dump($lrtDown);

    return view('index', compact('lrtDown'));
});
Route::get('/yes', function () {
    return view('yes');
});
