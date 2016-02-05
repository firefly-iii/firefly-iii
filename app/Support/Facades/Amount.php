<?php
declare(strict_types = 1);

namespace FireflyIII\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Amount
 *
 * @codeCoverageIgnore
 * @package FireflyIII\Support\Facades
 */
class Amount extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'amount';
    }

}
