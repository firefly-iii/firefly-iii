<?php
/**
 * Ignore.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

/**
 * Class Amount
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class Ignore extends BasicConverter implements ConverterInterface
{

    /**
     * @return null
     */
    public function convert()
    {
        return null;
    }
}
