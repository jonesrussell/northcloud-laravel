<?php

declare(strict_types=1);

namespace JonesRussell\NorthCloud\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void registerNavigation(array $items)
 * @method static array getRegisteredNavigation()
 */
class NorthCloud extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \JonesRussell\NorthCloud\NorthCloud::class;
    }
}
