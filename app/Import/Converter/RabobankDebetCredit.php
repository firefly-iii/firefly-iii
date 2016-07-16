<?php
/**
 * RabobankDebetCredit.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import\Converter;

use Log;

/**
 * Class RabobankDebetCredit
 *
 * @package FireflyIII\Import\Converter
 */
class RabobankDebetCredit extends BasicConverter implements ConverterInterface
{

    /**
     * @param $value
     *
     * @return int
     */
    public function convert($value): int
    {
        Log::debug('Going to convert ', ['value' => $value]);

        if ($value === 'D') {
            return -1;
        }

        return 1;
    }
}