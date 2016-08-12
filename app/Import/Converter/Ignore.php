<?php
/**
 * Ignore.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import\Converter;

/**
 * Class Ignore
 *
 * @package FireflyIII\Import\Converter
 */
class Ignore extends BasicConverter implements ConverterInterface
{

    /**
     * @param $value
     *
     * @return null
     */
    public function convert($value)
    {
        return null;

    }
}
