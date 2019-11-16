<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Carbon;
use Abraham\TwitterOAuth\TwitterOAuth;
use App\Tweet;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->describe('Display an inspiring quote');

Artisan::command('getTweets', function () {
    $lastTweet = Tweet::last()->first();

    if (!isset($lastTweet)) {
        $lastTweet = new Tweet();
        $lastTweet->uid = 0;
    }

    $connection = new TwitterOAuth(
        env('TWITTER_CONSUMER_KEY'),
        env('TWITTER_CONSUMER_SECRET'),
        env('TWITTER_ACCESS_TOKEN'),
        env('TWITTER_ACCESS_SECRET')
    );
    $transpoTweets = $connection->get('search/tweets', [
        'q' => 'from:OCTranspoLive "Line 1" OR R1',
        'count' => 1000,
        'tweet_mode' => 'extended',
        'result_type' => 'recent',
        'since_id' => $lastTweet->uid
    ]);
    // if (!App::environment('production')) dump($content->statuses);

    $tweets = array_map(function($t) {
        return $t->full_text;
    }, $transpoTweets->statuses);
    // if (!App::environment('production')) dump($tweets);
    $this->comment('Read ' . count($tweets) . ' tweets');

    // $filtered_tweets = [];

    foreach ($tweets as $tweet) {
        $this->line($tweet);
    }

    $filteredTweets = preg_grep('/((delay|close)|(eastbound.*westbound|westbound.*eastbound)|(eastbound|westbound)\s?(platform)?\sonly|r1.*between)/miU', $tweets);

    // if (!App::environment('production')) dump($filteredTweets);

    $filteredTweets = array_diff(
        $filteredTweets,
        preg_grep('/(minor|slight|small)\s(\w+\s)?(delay|delayed)/miU', $filteredTweets),
        preg_grep('/(restore|complete|resume|open|normal)/miU', $filteredTweets),
        preg_grep('/(without|no)\s(\w+\s)?delay/miU', $filteredTweets)
    );
    // if (!App::environment('production')) dump($filteredTweets);
    $this->comment('Filtered to ' . count($filteredTweets) . ' tweets');


    $outputTweets = [];
    foreach ($filteredTweets as $key => $value) {
        $this->line($value);
        $outputTweets[] = $transpoTweets->statuses[$key];
    }

    usort($outputTweets, function ($a, $b) {
        $aInt = strtotime($a->created_at);
        $bInt = strtotime($b->created_at);

        if ($aInt == $bInt) {
            return 0;
        }
        return $aInt < $bInt ? -1 : 1;
    });

    // if (!App::environment('production')) dump($outputTweets);

    foreach ($outputTweets as $ot) {
        $tweet = Tweet::firstOrCreate(
            ['uid' => $ot->id],
            [
                'text' => $ot->full_text,
                'created' => Carbon::parse($ot->created_at, 'UTC')
                    ->setTimezone(config('app.timezone'))
            ]
        );
    }
    if (count($filteredTweets)) {
        $this->info('Saved ' . count($filteredTweets) . ' tweets');
    } else {
        $this->error('Nothing to save');
    }
})->describe('Get tweets from OCTranspo');

Artisan::command('tweet', function () {
    echo "\u{30}\u{FE0F}\u{20E3}<br>";
    echo "\u{31}\u{FE0F}\u{20E3}<br>";
    echo "\u{32}\u{FE0F}\u{20E3}<br>";
    echo "\u{33}\u{FE0F}\u{20E3}<br>";
    echo "\u{34}\u{FE0F}\u{20E3}<br>";
    echo "\u{35}\u{FE0F}\u{20E3}<br>";
    echo "\u{36}\u{FE0F}\u{20E3}<br>";
    echo "\u{37}\u{FE0F}\u{20E3}<br>";
    echo "\u{38}\u{FE0F}\u{20E3}<br>";
    echo "\u{39}\u{FE0F}\u{20E3}<br>";
})->describe('Send out a tweet to the LRT Down twitter account');
