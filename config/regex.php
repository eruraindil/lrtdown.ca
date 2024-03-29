<?php

return [

    'triggers' => '/((delay|close)|service closure|track repairs|reduce(d)? service|(eastbound.*westbound|westbound.*eastbound)|(eastbound|westbound) ?(platform)?s? only|r1.*(service|between|operat|to|from|available)|allow extra travel time|experience (slightly )?longer travel time|longer waits|longer wait time|longer (wait and travel|travel and wait) time|switch issue|replacement bus|(door|brake) fault|stopped train|(must|should) use ((eastbound|westbound) )?platform|wait times of up to \d\d+ (minutes|mins)|reduced (train )?(service|schedule)|train shortage|shortage of trains|currently \d\d? trains|\d\d? trains (are )?in service|trains? (are )?(being )?held|power issue|transfer between trains|train is immobilized|immobilized train|(special|s1|supplementary) (bus(es)? )?(service|running))/miU',
#|(special|s1|supplementary) (bus(es)? )?(service|running)
];
