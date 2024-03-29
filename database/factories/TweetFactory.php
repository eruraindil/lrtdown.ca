<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Tweet;
use Faker\Generator as Faker;

$factory->define(Tweet::class, function (Faker $faker) {

    // (delay|close)|service closure|track repairs|reduce(d)? service|(eastbound.*westbound|westbound.*eastbound)|(eastbound|westbound)\s?(platform)?s?\sonly|r1.*(service|between|operat|to|from|available)|allow extra travel time|experience (slightly\s)?longer travel time|longer waits|longer wait time|longer (wait and travel|travel and wait) time|switch issue|replacement bus|(door|brake) fault|stopped train|(must|should) use ((eastbound|westbound) )?platform|wait times of up to \d\d+ (minutes|mins)|reduced (train )?(service|schedule)|train shortage|shortage of trains|currently \d\d? trains|\d\d? trains (are\s)?in service|trains? (are\s)?(being\s)?held|power issue|transfer between trains|train is immobilized|immobilized train|(special|s1|supplementary) (bus(es)? )?(service|running)

    $regex = str_replace(
        [
            '/(',
            ')/miU'
        ],
        '',
        config('regex.triggers')
    );

    // Complex explode. Only the top level of |, retain sub str pipes for regexify later on.
    $regexes = [];
    $i = 0;
    $strLevel = 0;
    foreach (str_split($regex) as $c) {
        switch ($c) {
            case '(':
                $strLevel++;
                break;

            case ')':
                $strLevel--;
                break;

            case '|':
                if ($strLevel === 0) {
                    $i++;
                    continue 2; // break foreach
                }
        }
        if (!isset($regexes[$i])) {
            $regexes[$i] = '';
        }
        $regexes[$i] .= $c;
    }
    $randomRegex = $faker->randomElement($regexes);

    return [
        'uid' => $faker->unique()->randomNumber(5),
        'text' => $randomRegex . ' -> ' . $faker->regexify($randomRegex),
        'created' => $faker->unique()->dateTimeThisYear('-1 day'),
    ];
});
