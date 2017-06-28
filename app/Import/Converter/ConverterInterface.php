<?php
/**
 * ConverterInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Converter;

/**
 * Interface ConverterInterface
 *
 * @package FireflyIII\Import\Converter
 */
interface ConverterInterface
{
    /**
     * @param $value
     *
     */
    public function convert($value);
}
