<?php

return [
    'transaction' => [
        'seamless' => [
            'max_connections' => 100,
            'min_connections' => 1,
            'wait_timeout' => 3.0,
            'max_idle_time' => 60,
            'retries' => 1,
            'delay' => 10
        ],
    ],
];