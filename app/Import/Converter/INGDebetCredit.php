<?php
/**
 * INGDebetCredit.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import\Converter;

use Log;

/**
 * Class INGDebetCredit
 *
 * @package FireflyIII\Import\Converter
 */
class INGDebetCredit extends BasicConverter implements ConverterInterface
{

    /**
     * @param $value
     *
     * @return int
     */
    public function convert($value)
    {
        Log::debug('Going to convert ing debet credit', ['value' => $value]);

        if ($value === 'Af') {
            Log::debug('Return -1');
            $this->setCertainty(100);

            return -1;
        }

        $this->setCertainty(100);
        Log::debug('Return 1');

        return 1;

    }
}
