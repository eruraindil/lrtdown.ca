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

    foreach ($filteredTweets as $ot) {
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
        Log::info('Saved ' . count($filteredTweets) . ' tweets');

        $newTweet = Tweet::last()->get()[0];
        Cache::put('lastTweet', $newTweet);
        Cache::put('longestStreak', Tweet::streak());

        $tweetTime = $lastTweet->created->diffInMinutes('now');
        if ($tweetTime > 30) {
            // Tweet if greater than 30 mins since the last tweet
            $status = 'Mr. Gaeta, restart the clock. ' .
                'Update ' .
                $newTweet->created->toFormattedDateString() . ' ' .
                $newTweet->created->format('g:i A') . ': ' .
                0 . "\u{FE0F}\u{20E3}\u{2060}" .
                0 . "\u{FE0F}\u{20E3}\u{00A0}" .
                'days since last issue. https://www.lrtdown.ca #ottlrt #OttawaLRT';

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

    if (isset($tweet) && ($days = $tweet->created->diffInDays('now')) > 0) {
        list($startDate, $endDate) = Cache::get('longestStreak', [
            Carbon::now(config('app.timezone')),
            Carbon::now(config('app.timezone')),
        ]);

        Log::debug($days);

        $status = 'Update ' .
            Carbon::now(config('app.timezone'))->toFormattedDateString() . ': ' .
            Tweet::formatKeycap($days) .
            'days since last issue. ';

        $prevStreak = $startDate->diffInSeconds($endDate);
        $thisStreak = $tweet->created->diffInSeconds('now');

        Log::debug($prevStreak);
        Log::debug($thisStreak);

        if ($prevStreak > 0 && $thisStreak > $prevStreak) {
            $interval = CarbonInterval::seconds($thisStreak)->subtract(
                CarbonInterval::seconds($prevStreak)
            );
            $status .= 'New uninterupted service record! ' .
                "\u{1F386}" . "\u{1F37E}" . "\u{1F386}" . ' ' .
                '(increased by ' .
                $interval->cascade()->forHumans() . ') ';

            Cache::put('longestStreak', Tweet::streak());
        }

        $status .= 'https://www.lrtdown.ca #ottlrt #OttawaLRT';

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

Artisan::command('twitter:streak', function () {
    $tweet = Cache::rememberForever('lastTweet', function () {
        return Tweet::last()->get()[0];
    });

    list($startDate, $endDate) = Cache::rememberForever('longestStreak', function () {
        return Tweet::streak();
    });

    $days = $startDate->diffInDays($endDate);

    Log::debug($days);

    if ($days > 0) {
        $status = 'The longest streak of uninterupted service has been ' .
            Tweet::formatKeycap($days) .
            'days between ' .
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
})->describe('Send out streak tweet to the LRT Down twitter account');

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
})->describe('Clear app caches');
