<?php

namespace FireflyIII\Database\Scope;

/**
 * Class AccountScopeTrait
 *
 * @package FireflyIII\Database\Scope
 */
trait AccountScopeTrait
{
    /**
     * Boot the soft deleting trait for a model.
     *
     * @return void
     */
    public static function bootAccountScopeTrait()
    {
        static::addGlobalScope(new AccountScope);
    }

}