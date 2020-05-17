<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Carbon\CarbonInterval as CarbonInterval;
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
    $lastTweet = Cache::rememberForever('lastTweet', function () {
        return Tweet::last()->firstOr(function () {
            $lastTweet = new Tweet();
            $lastTweet->uid = 0;
            $lastTweet->created = Carbon::now();

            return $lastTweet;
        });
    });

    $connection = resolve('Abraham\TwitterOAuth\TwitterOAuth');
    $transpoTweets = $connection->get('search/tweets', [
        'q' => 'from:OCTranspoLive "Line 1" OR R1',
        'count' => 100,
        'tweet_mode' => 'extended',
        'result_type' => 'recent',
        'since_id' => $lastTweet->uid
    ]);

    if (!isset($transpoTweets) || !isset($transpoTweets->statuses)) {
        $this->error('Error getting tweets. ' . dump($transpoTweets));
        Log::error('Error getting tweets. ' . dump($transpoTweets));
        return;
    }

    $filteredTweets = Tweet::filterOC($transpoTweets);

    foreach ($filteredTweets as $key => &$ot) {
        $tweetDate = Carbon::parse($ot->created_at, 'UTC')->setTimeZone(config('app.timezone'));

        $maintenance = false;
        foreach (config('app.maintenance_days') as $day) {
            if ($tweetDate->isSameDay($day)) {
                $maintenance = true;
            }
        }

        if ($maintenance === false) {
            $tweet = Tweet::firstOrCreate(
                ['uid' => $ot->id],
                [
                    'text' => $ot->full_text,
                    'created' => $tweetDate,
                ]
            );
        } else {
            // unset an array element inside a foreach from: https://stackoverflow.com/a/1949275
            unset($filteredTweets[$key]);
        }
    }

    unset($ot);

    if (count($filteredTweets)) {
        $this->info('Saved ' . count($filteredTweets) . ' tweets');
        Log::info('Saved ' . count($filteredTweets) . ' tweets');

        $newTweet = Tweet::last()->get()[0];
        Cache::put('lastTweet', $newTweet);
        Cache::put('longestStreak', Tweet::streak());

        $tweetTime = $lastTweet->created->diffInMinutes('now');
        if ($tweetTime > 30) {
            $trigger = Tweet::filterTrigger($newTweet->text);

            // Tweet if greater than 30 mins since the last tweet
            $status = 'Mr. Gaeta, restart the clock. ' .
                'Update ' .
                $newTweet->created->toFormattedDateString() . ' ' .
                $newTweet->created->format('g:i A') . ': ' .
                0 . "\u{FE0F}\u{20E3}\u{2060}" .
                0 . "\u{FE0F}\u{20E3}\u{00A0}" .
                'days since last issue. ' .
                (strlen($trigger) > 0 ? '<' . $trigger . '> ' : '') .
                'https://www.lrtdown.ca #ottlrt #OttawaLRT';

            if (!App::environment('production')) {
                $this->info($status);
            } else {
                $update = $connection->post('statuses/update', ['status' => $status]);

                if (isset($update) && isset($update->id)) {
                    $this->info('Tweet sent. ' . $update->id);
                    Log::info('Tweet sent. ' . $update->id);
                } else {
                    $this->error('Could not send tweet. ' . dump($update));
                    Log::error('Could not send tweet. ' . dump($update));
                }
            }
        }
    } else {
        $this->info('Nothing to save');
        Log::info('Nothing to save');
    }
})->describe('Get tweets from OCTranspoLive');

Artisan::command('twitter:update', function () {
    $tweet = Cache::rememberForever('lastTweet', function () {
        return Tweet::last()->get()[0];
    });

    if (!isset($tweet)) {
        return;
    }

    $tweetDate = $tweet->created->copy();
    $now = Carbon::now(config('app.timezone'));

    // Handle maintenance days
    $maintenance = false;
    foreach (config('app.maintenance_days') as $day) {
        // Add a day for each maintenance day, to offset the counter (reducing the difference)
        // Maintenance days don't reset the clock but don't count as a day of service either.
        // Only consider if the last issue was before the maintenance day, otherwise it doesn't
        // affect the counter.
        if ($tweetDate->lessThanOrEqualTo($day) && $now->greaterThanOrEqualTo($day)) {
            $tweetDate->addDay();
        }

        // If we happen to be on a maintenance day, flag it.
        if ($now->isSameDay($day)) {
            $maintenance = true;
        }
    }

    // Find the number of days since the last issue tweet.
    // (This may have been offset by maintenance days above)
    $days = $tweetDate->diffInDays('now');

    // Tweet an update once a day, only if we're not on maintenance today.
    if ($days > 0 && $maintenance === false) {
        list($startDate, $endDate, $counter) = Cache::get('longestStreak', [
            Carbon::now(config('app.timezone')),
            Carbon::now(config('app.timezone')),
            Carbon::now(config('app.timezone')),
        ]);

        Log::debug($days);

        $status = 'Update ' .
            Carbon::now(config('app.timezone'))->toFormattedDateString() . ': ' .
            Tweet::formatKeycap($days) .
            'days since last issue.* ';

        // Add streak info if new service record
        $prevStreak = $startDate->diffInSeconds($counter);
        $thisStreak = $tweetDate->diffInSeconds('now');

        Log::debug($prevStreak);
        Log::debug($thisStreak);

        if ($prevStreak > 0 && $thisStreak > $prevStreak) {
            $interval = CarbonInterval::seconds($thisStreak)->subtract(
                CarbonInterval::seconds($prevStreak)
            );
            $status .= 'New uninterrupted service record! ' .
                "\u{1F386}" . "\u{1F37E}" . "\u{1F386}" . ' ' .
                '(increased by ' .
                $interval->cascade()->forHumans() . ') ';

            Cache::put('longestStreak', Tweet::streak());
        }

        // End of tweet boilerplate
        $status .= '*LRT CURRENTLY ON REDUCED SCHEDULE* https://www.lrtdown.ca #ottlrt #OttawaLRT';

        if (!App::environment('production')) {
            $this->info($status);
        } else {
            $connection = resolve('Abraham\TwitterOAuth\TwitterOAuth');
            $update = $connection->post('statuses/update', ['status' => $status]);

            if (isset($update) && isset($update->id)) {
                $this->info('Tweet sent. ' . $update->id);
                Log::info('Tweet sent. ' . $update->id);
            } else {
                $this->error('Could not send tweet. ' . dump($update));
                Log::error('Could not send tweet. ' . dump($update));
            }
        }

    } else {
        Log::debug('Less than 1 day since last tweet.' . dump($tweet));
    }
})->describe('Send out an update tweet to the LRT Down twitter account');

Artisan::command('twitter:streak {dow}', function ($dow) {
    if (date('w') != $dow) {
        $this->info('Not day of week ' . $dow . ', not scheduled for tweet');
        Log::info('Not day of week ' . $dow . ', not scheduled for tweet');
        return;
    }

    $tweet = Cache::rememberForever('lastTweet', function () {
        return Tweet::last()->get()[0];
    });

    list($startDate, $endDate, $counter) = Cache::rememberForever('longestStreak', function () {
        return Tweet::streak();
    });

    $days = $startDate->diffInDays($counter);

    Log::debug($days);

    if ($days > 0) {
        $status = 'The longest streak of uninterupted service has been ' .
            Tweet::formatKeycap($days) .
            'day' . ($days > 1 ? 's' : '') . ' between ' .
            $startDate->toFormattedDateString() . ' and ' .
            $endDate->toFormattedDateString() .
            '. https://www.lrtdown.ca #ottlrt #OttawaLRT';

        if (!App::environment('production')) {
            $this->info($status);
        } else {
            $connection = resolve('Abraham\TwitterOAuth\TwitterOAuth');
            $update = $connection->post('statuses/update', ['status' => $status]);

            if (isset($update) && isset($update->id)) {
                $this->info('Tweet sent. ' . $update->id);
                Log::info('Tweet sent. ' . $update->id);
            } else {
                $this->error('Could not send tweet. ' . dump($update));
                Log::error('Could not send tweet. ' . dump($update));
            }
        }
    }
})->describe('Send out streak tweet to the LRT Down twitter account, only on day of week specified (php dow, 0 = Sun..6 = Sat)');

Artisan::command('debug:read', function () {
    $headers = ['id', 'text', 'created'];
    $tweets = Tweet::all(['id', 'text', 'created'])->toArray();
    $this->table($headers, $tweets);
})->describe('See db rows for debugging purposes');

Artisan::command('debug:delete {id}', function ($id) {
    try {
        $tweet = Tweet::findOrFail($id);
        $tweet->delete();
        Cache::forget('lastTweet');
        Cache::forget('longestStreak');
        $this->info('Tweet #' . $id . ' deleted.');
        Log::info('Tweet #' . $id . ' deleted.');
    } catch (\Exception $e) {
        $this->error('Unable to delete tweet #' . $id . '.');
        $this->error($e->getMessage());
        Log::error('Unable to delete tweet #' . $id . '. ' . PHP_EOL . $e->getMessage());
    }
})->describe('Delete a db row');

Artisan::command('debug:tweet', function () {
    $status = '@eruraindil ' .
        'Update ' .
        Carbon::now(config('app.timezone'))->toFormattedDateString() . ' ' .
        Carbon::now(config('app.timezone'))->format('g:i A') . ': ' .
        0 . "\u{FE0F}\u{20E3}\u{2060}" .
        0 . "\u{FE0F}\u{20E3}\u{00A0}" .
        'testing encoding.';

    $connection = resolve('Abraham\TwitterOAuth\TwitterOAuth');
    $update = $connection->post('statuses/update', ['status' => $status]);
    if (isset($update) && isset($update->id)) {
        $this->info('Tweet sent. ' . $update->id);
        Log::info('Tweet sent. ' . $update->id);
    } else {
        $this->error('Could not send tweet. ' . dump($update));
        Log::error('Could not send tweet. ' . dump($update));
    }
})->describe('Send a debug tweet out to the LRT Down twitter account');

Artisan::command('debug:clear', function () {
    Cache::forget('lastTweet');
    Cache::forget('longestStreak');
    Cache::put('lastTweet', Tweet::last()->get()[0]);
    Cache::put('longestStreak', Tweet::streak());
})->describe('Clear app caches');
