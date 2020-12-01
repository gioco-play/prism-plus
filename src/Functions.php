<?php

declare(strict_types=1);


if (!function_exists('micro_timestamp')) {
    /**
     * 時間戳
     *
     * @return int
     */
    function micro_timestamp(): int {
        return intval(round(microtime(true) * 1000));
    }
}