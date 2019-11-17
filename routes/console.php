<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Carbon;
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
    $lastTweet = Tweet::last()->get()[0];

    if (!isset($lastTweet)) {
        $lastTweet = new Tweet();
        $lastTweet->uid = 0;
    }

    $connection = resolve('Abraham\TwitterOAuth\TwitterOAuth');
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
        preg_grep('/(without|no)\s(\w+\s)*delay/miU', $filteredTweets)
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
    // echo "\u{30}\u{FE0F}\u{20E3}<br>"; // 0
    // echo "\u{31}\u{FE0F}\u{20E3}<br>"; // 1
    // echo "\u{32}\u{FE0F}\u{20E3}<br>"; // 2
    // echo "\u{33}\u{FE0F}\u{20E3}<br>"; // 3
    // echo "\u{34}\u{FE0F}\u{20E3}<br>"; // 4
    // echo "\u{35}\u{FE0F}\u{20E3}<br>"; // 5
    // echo "\u{36}\u{FE0F}\u{20E3}<br>"; // 6
    // echo "\u{37}\u{FE0F}\u{20E3}<br>"; // 7
    // echo "\u{38}\u{FE0F}\u{20E3}<br>"; // 8
    // echo "\u{39}\u{FE0F}\u{20E3}<br>"; // 9

    $tweet = Tweet::last()->get()[0];

    if (isset($tweet)) {
        $days = $tweet->created->diffInDays('now');

        $status = '';
        if (strlen($days) == 1) { // prepend a 0 on to numbers less than 10
            $status .= 0 . "\u{FE0F}\u{20E3}";
        }
        foreach (str_split($days) as $i) {
            $status .= $i . "\u{FE0F}\u{20E3}";
        }

        $status .= ' days since last issue. https://www.lrtdown.ca #ottlrt';

        $connection = resolve('Abraham\TwitterOAuth\TwitterOAuth');
        $status = $connection->post('statuses/update', ['status' => $status]);
        if (isset($status)) {
            $this->info('Tweet sent. ' . $status->id);
        } else {
            $this->error('Could not send tweet. ' . dump($status));
        }
    }
})->describe('Send out a tweet to the LRT Down twitter account');

Artisan::command('debug:read', function () {
    $headers = ['id', 'text', 'created'];
    $tweets = Tweet::all(['id', 'text', 'created'])->toArray();
    $this->table($headers, $tweets);
})->describe('See db rows for debugging purposes.');

Artisan::command('debug:delete {id}', function ($id) {
    try {
        $tweet = Tweet::findOrFail($id);
        $tweet->delete();
        $this->info('Tweet #' . $id . ' deleted.');
    } catch (\Exception $e) {
        $this->error('Unable to delete tweet #' . $id . '.');
        $this->error($e->getMessage());
    }
})->describe('Delete a db row.');
