<?php
/**
 * Amount.php
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
class Amount extends BasicConverter implements ConverterInterface
{

    /**
     * @return string
     */
    public function convert(): string
    {
        if (is_numeric($this->value)) {
            return strval($this->value);
        }

        return '0';
    }
}
