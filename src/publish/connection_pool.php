<?php

return [
    'default' => [
        'pool' => [
            'min_active'          => (int)env('CONNPOOL_MIN_ACTIVE', 20),
            'max_active'          => (int)env('CONNPOOL_MAX_ACTIVE', 100),
            'max_wait_time'       => (int)env('CONNPOOL_MAX_WAIT', 5),
            'max_idle_time'       => (int)env('CONNPOOL_MAX_IDLE', 30),
            'idle_check_interval' => (int)env('CONNPOOL_IDLE_CHECK_INTERVAL', 15),
        ],
    ],
];