<?php

return [

    'triggers' => '/((delay|close)|(eastbound.*westbound|westbound.*eastbound)|(eastbound|westbound)\s?(platform)?s?\sonly|r1.*(service|between|operat|to|from)|allow extra travel time|experience (slightly\s)?longer travel time|longer waits|longer wait time|longer (wait and travel|travel and wait) time|switch issue|replacement bus|(door|brake) fault|stopped train|(must|should) use ((eastbound|westbound) )?platform|wait times of up to \d\d+ (minutes|mins)|reduced (train )?(service|schedule)|train shortage|shortage of trains|currently \d\d? trains|\d\d? trains (are\s)?in service|trains? (are\s)?(being\s)?held|power issue|transfer between trains|train is immobilized|immobilized train)/miU',
#|(special|s1|supplementary) (bus(es)? )?(service|running)
];
