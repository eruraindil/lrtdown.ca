<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class Tweet extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created',
    ];

    /**
     * Scope a query to only include popular users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLast($query)
    {
        return $query->latest('created')->limit(1);
    }

    /**
     * Convert a series of digits into ascii emojis to stand out
     *
     * @param int $num
     * @return string
     */
    public static function formatKeycap($num)
    {
        $str = '';
        if (strlen($num) == 1) { // prepend a 0 on to numbers less than 10
            $str .= 0 . "\u{FE0F}\u{20E3}";
        }

        foreach (str_split($num) as $i) {
            $str .= "\u{2060}" . $i . "\u{FE0F}\u{20E3}";
        }

        $str .= "\u{00A0}";

        return $str;
    }

    /**
     * Filter OCTranspoLive tweets by criteria.
     *
     * @param array $transpoTweets from \Abraham\TwitterOAuth\TwitterOAuth::get
     * @return array filtered $transpoTweets
     */
    public static function filterOC($transpoTweets)
    {
        $tweets = array_map(function($t) {
            return $t->full_text;
        }, $transpoTweets->statuses);

        Log::info('Read ' . count($tweets) . ' tweets');

        foreach ($tweets as $tweet) {
            Log::debug($tweet);
        }

        $filteredTweets = preg_grep('/((delay|close)|(eastbound.*westbound|westbound.*eastbound)|(eastbound|westbound)\s?(platform)?\sonly|r1.*(between|operat)|allow extra travel time|experience (slightly\s)?longer travel time|longer (wait and travel|travel and wait) time|switch issue|replacement bus|door fault|stopped train|(must|should) use ((eastbound|westbound) )?platform|wait times of up to \d\d+ (minutes|mins)|reduced (train )?service|train shortage|shortage of trains|currently \d\d? trains|\d\d? trains (are\s)?in service|trains (are\s)?(being\s)?held|power issue)/miU', $tweets);

        // (special|s1) bus(es| service) // removing S1 service from trigger phrases. https://twitter.com/LRTdown/status/1224699103646572544

        $filteredTweets = array_diff(
            $filteredTweets,
            preg_grep('/(restore|complete|resum|open|normal|resolv|ended)/miU', $filteredTweets),
            preg_grep('/(without|no)\s(\w+\s)*delay/miU', $filteredTweets),
            preg_grep('/(in anticipation|tomorrow)/miU', $filteredTweets),
            preg_grep('/(currently 13 trains| 13 trains (are\s)?in service)/miU', $filteredTweets)
        );

        Log::info('Filtered to ' . count($filteredTweets) . ' tweets');

        $outputTweets = [];
        foreach ($filteredTweets as $key => $value) {
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

        return $outputTweets;
    }

    /**
     * Find the longest streak between two tweets.
     *
     * @return Carbon[]
     */
    public static function streak()
    {
        $tweets = Tweet::orderBy('created', 'asc')->get();

        if (count($tweets) < 2) {
            return [
                Carbon::now(config('app.timezone')),
                Carbon::now(config('app.timezone')),
            ];
        }

        $longestDiff = $tweets[0]->created->diffInSeconds($tweets[1]->created);
        $startDate = $tweets[0]->created;
        $endDate = $tweets[1]->created;

        for ($i = 0; $i < count($tweets) - 1; $i++) {
            $diff = $tweets[$i]->created->diffInSeconds($tweets[$i+1]->created);
            if ($diff > $longestDiff) {
                $longestDiff = $diff;
                $startDate = $tweets[$i]->created;
                $endDate = $tweets[$i+1]->created;
            }
        }

        // Handle current time. Are we currently in the longest streak?
        $lastTweet = Cache::rememberForever('lastTweet', function () {
            return Tweet::last()->get()[0];
        });

        $currDiff = $lastTweet->created->diffInSeconds('now');
        if ($currDiff > $longestDiff) {
            $startDate = $lastTweet->created;
            $endDate = Carbon::now();
        }

        return [
            $startDate,
            $endDate
        ];
    }
}
