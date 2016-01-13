<?php
/**
 * Domain.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Support;

use Config;

/**
 * Class Domain
 *
 * @package FireflyIII\Support
 */
class Domain
{
    /**
     * @return array
     */
    public static function getBindables()
    {
        return Config::get('firefly.bindables');

    }

    /**
     * @return array
     */
    public static function getRuleTriggers()
    {
        return Config::get('firefly.rule-triggers');
    }

    /**
     * @return array
     */
    public static function getRuleActions()
    {
        return Config::get('firefly.rule-actions');
    }
}