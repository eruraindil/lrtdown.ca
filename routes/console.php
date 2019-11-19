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

Artisan::command('twitter:get', function () {
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

    $tweets = array_map(function($t) {
        return $t->full_text;
    }, $transpoTweets->statuses);

    $this->comment('Read ' . count($tweets) . ' tweets');

    foreach ($tweets as $tweet) {
        $this->line($tweet);
    }

    $filteredTweets = preg_grep('/((delay|close)|(eastbound.*westbound|westbound.*eastbound)|(eastbound|westbound)\s?(platform)?\sonly|r1.*between)/miU', $tweets);

    $filteredTweets = array_diff(
        $filteredTweets,
        preg_grep('/(restore|complete|resum|open|normal|resolv)/miU', $filteredTweets),
        preg_grep('/(without|no)\s(\w+\s)*delay/miU', $filteredTweets)
    );

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

        if ($lastTweet->created->diffInDays('now') > 0) {
            // Tweet if greater than 1 day.
            $status = 'Mr. Gaeta, restart the clock. ' .
                'Update ' .
                Carbon::now(config('app.timezone'))->toFormattedDateString() . ': ' .
                0 . "\u{FE0F}\u{20E3}\u{2060}" .
                0 . "\u{FE0F}\u{20E3}\u{00A0}" .
                'days since last issue. https://www.lrtdown.ca #ottlrt #OttawaLRT';
            $update = $connection->post('statuses/update', ['status' => $status]);

            if (isset($update) && isset($update->id)) {
                $this->info('Tweet sent. ' . $update->id);
            } else {
                $this->error('Could not send tweet. ' . dump($update));
            }
        }
    } else {
        $this->error('Nothing to save');
    }
})->describe('Get tweets from OCTranspo');

Artisan::command('twitter:tweet', function () {
    $tweet = Tweet::last()->get()[0];

    if (isset($tweet) && ($days = $tweet->created->diffInDays('now')) > 0) {
        $status = 'Update ' . Carbon::now(config('app.timezone'))->toFormattedDateString() . ': ';
        if (strlen($days) == 1) { // prepend a 0 on to numbers less than 10
            $status .= 0 . "\u{FE0F}\u{20E3}";
        }
        foreach (str_split($days) as $i) {
            $status .= "\u{2060}" . $i . "\u{FE0F}\u{20E3}";
        }

        $status .= "\u{00A0}" . 'days since last issue. https://www.lrtdown.ca #ottlrt #OttawaLRT';

        $connection = resolve('Abraham\TwitterOAuth\TwitterOAuth');
        $update = $connection->post('statuses/update', ['status' => $status]);
        if (isset($update) && isset($update->id)) {
            $this->info('Tweet sent. ' . $update->id);
        } else {
            $this->error('Could not send tweet. ' . dump($update));
        }
    }
})->describe('Send out a tweet to the LRT Down twitter account');

Artisan::command('debug:read', function () {
    $headers = ['id', 'text', 'created'];
    $tweets = Tweet::all(['id', 'text', 'created'])->toArray();
    $this->table($headers, $tweets);
})->describe('See db rows for debugging purposes');

Artisan::command('debug:delete {id}', function ($id) {
    try {
        $tweet = Tweet::findOrFail($id);
        $tweet->delete();
        $this->info('Tweet #' . $id . ' deleted.');
    } catch (\Exception $e) {
        $this->error('Unable to delete tweet #' . $id . '.');
        $this->error($e->getMessage());
    }
})->describe('Delete a db row');

Artisan::command('debug:tweet', function () {
    $status = '@eruraindil ' .
        'Update ' .
        Carbon::now(config('app.timezone'))->toFormattedDateString() . ': ' .
        0 . "\u{FE0F}\u{20E3}\u{2060}" .
        0 . "\u{FE0F}\u{20E3}\u{00A0}" .
        'testing encoding.';

    $connection = resolve('Abraham\TwitterOAuth\TwitterOAuth');
    $update = $connection->post('statuses/update', ['status' => $status]);
    if (isset($update) && isset($update->id)) {
        $this->info('Tweet sent. ' . $update->id);
    } else {
        $this->error('Could not send tweet. ' . dump($update));
    }
})->describe('Send a debug tweet out to the LRT Down twitter account');
