<?php
/**
 * Domain.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Support;

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
    public static function getBindables(): array
    {
        return config('firefly.bindables');

    }

    /**
     * @return array
     */
    public static function getRuleActions(): array
    {
        return config('firefly.rule-actions');
    }

    /**
     * @return array
     */
    public static function getRuleTriggers(): array
    {
        return config('firefly.rule-triggers');
    }
}
