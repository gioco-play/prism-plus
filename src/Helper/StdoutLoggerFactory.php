<?php

declare(strict_types=1);
namespace GiocoPlus\PrismPlus\Helper;

use Psr\Container\ContainerInterface;

class StdoutLoggerFactory
{
    public function -public function __invoke(ContainerInterface $container)
    {
        return Log::get('system');
    }
}