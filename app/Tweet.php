<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Stringy\Stringy as S;

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

	$filteredTweets = preg_grep(config('regex.triggers'), $tweets);

	// (special|s1) bus(es| service) // removing S1 service from trigger phrases. https://twitter.com/LRTdown/status/1224699103646572544
	// 2020-03-10 re-add s1 because less than 13 standard trains in service, OC running S1 parallel. https://twitter.com/LRTdown/status/1237492441109987330

        $filteredTweets = array_diff(
            $filteredTweets,
            preg_grep('/(restore|complete|resum|open|normal|resolv|ended)/miU', $filteredTweets),
            preg_grep('/(without)\s(\w+\s)*delay/miU', $filteredTweets),
            #preg_grep('/(without|no)\s(\w+\s)*delay/miU', $filteredTweets),
            preg_grep('/(in anticipation|tomorrow)/miU', $filteredTweets),
            preg_grep('/(currently 13 trains| 13 trains (are\s)?in service)/miU', $filteredTweets),
            preg_grep('/s1.*reduced service/miU', $filteredTweets),
            preg_grep('/will be on detour/miU', $filteredTweets)
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

    public static function filterTrigger($tweetText)
    {
        $hasTrigger = preg_match(config('regex.triggers'), $tweetText, $matches);

        $trigger = '';
        if ($hasTrigger && count($matches)) {
            $ignore = ['a', 'an', 'and', 'at', 'but', 'by', 'for', 'in', 'nor', 'of', 'on', 'or', 'out', 'so', 'the', 'to', 'yet'];
            $trigger = S::create($matches[0])->titleize($ignore)->upperCaseFirst();
        }

        return $trigger;
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

        $maintenanceDays = config('app.maintenance_days');
        $startDate = null; //$tweets[0]->created->copy();
        $endDate = null; //$tweets[1]->created->copy();
        $counter = null; //$endDate->copy();
        $longestDiff = 0;

        for ($i = 0; $i < count($tweets) - 1; $i++) {
            $newStartDate = $tweets[$i]->created->copy();
            $newEndDate = $tweets[$i+1]->created->copy();
            $newCounter = $newEndDate->copy();

            // Handle maintenance days
            foreach ($maintenanceDays as $day) {
                // Subtract a day for each maintenance day from the end of the streak, if the maintenace
                // day occurred during a streak, reducing the difference.
                // Maintenance days don't reset the clock but don't count as a day of service either.
                if ($newStartDate->lessThanOrEqualTo($day) && $newEndDate->greaterThanOrEqualTo($day)) {
                    $newCounter->subDay();
                }
            }

            $diff = $newStartDate->diffInSeconds($newCounter);
            if ($diff > $longestDiff) {
                $longestDiff = $diff;
                $startDate = $newStartDate;
                $endDate = $newEndDate;
                $counter = $newCounter;
            }
        }

        // Handle current time. Are we currently in the longest streak?
        $lastTweet = Cache::rememberForever('lastTweet', function () {
            return Tweet::last()->get()[0];
        });

        $lastDate = $lastTweet->created->copy();
        $now = Carbon::now(config('app.timezone'));
        $counterNow = $now->copy();

        // Handle maintenance days
        foreach ($maintenanceDays as $day) {
            // Subtract a day for each maintenance day from the end of the streak, if the maintenace
            // day occurred during a streak, reducing the difference.
            // Maintenance days don't reset the clock but don't count as a day of service either.
            if ($lastDate->lessThanOrEqualTo($day) && $now->greaterThanOrEqualTo($day)) {
                $counterNow->subDay();
            }
        }

        $currDiff = $lastDate->diffInSeconds($counterNow);
        if ($currDiff > $longestDiff) {
            $startDate = $lastDate;
            $endDate = $now;
            $counter = $counterNow;
        }

        return [
            $startDate,
            $endDate,
            $counter,
        ];
    }
}
