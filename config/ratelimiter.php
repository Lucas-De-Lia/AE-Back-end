<?php

return [
    'cache' => env('RATE_LIMITER_CACHE_DRIVER', 'file'),
    'decay_minutes' => 1,
];