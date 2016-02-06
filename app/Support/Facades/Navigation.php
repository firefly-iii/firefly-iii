<?php
declare(strict_types = 1);

namespace FireflyIII\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Navigation
 *
 * @codeCoverageIgnore
 * @package FireflyIII\Support\Facades
 */
class Navigation extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'navigation';
    }

}
