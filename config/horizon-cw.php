<?php

return [

    'credentials' => [
        'key' => env('AWS_KEY'),
        'secret' => env('AWS_SECRET'),
    ],

    'region' => env('AWS_REGION', 'us-east-1'),

    'namespace' => 'laravel-horizon'

];
