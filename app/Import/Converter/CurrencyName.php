<?php
/**
 * CurrencyName.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import\Converter;

use FireflyIII\Exceptions\FireflyException;

/**
 * Class CurrencyName
 *
 * @package FireflyIII\Import\Converter
 */
class CurrencyName extends BasicConverter implements ConverterInterface
{

    /**
     * @param $value
     *
     * @throws FireflyException
     */
    public function convert($value)
    {
        throw new FireflyException('Importer with name CurrencyName has not yet been configured.');

    }
}