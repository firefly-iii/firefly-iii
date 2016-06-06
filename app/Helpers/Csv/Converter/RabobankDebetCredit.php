<?php
/**
 * RabobankDebetCredit.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;


/**
 * Class RabobankDebetCredit
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class RabobankDebetCredit extends BasicConverter implements ConverterInterface
{


    /**
     * @return int
     */
    public function convert(): int
    {
        if ($this->value == 'D') {
            return -1;
        }

        return 1;
    }
}
