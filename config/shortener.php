<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Shortening Rate Limit
    |--------------------------------------------------------------------------
    |
    | Maximum number of URLs a single client IP may shorten per minute.
    |
    */

    'rate_limit_per_minute' => (int) env('SHORTENER_RATE_LIMIT_PER_MINUTE', 20),

];
