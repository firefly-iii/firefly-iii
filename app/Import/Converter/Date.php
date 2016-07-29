<?php
/**
 * Date.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import\Converter;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use InvalidArgumentException;
use Log;

/**
 * Class Date
 *
 * @package FireflyIII\Import\Converter
 */
class Date extends BasicConverter implements ConverterInterface
{

    /**
     * @param $value
     *
     * @return Carbon
     * @throws FireflyException
     */
    public function convert($value): Carbon
    {
        Log::debug('Going to convert ', ['value' => $value]);
        Log::debug('Format: ', ['format' => $this->config['date-format']]);
        try {
            $date = Carbon::createFromFormat($this->config['date-format'], $value);
        } catch (InvalidArgumentException $e) {
            Log::critical($e->getMessage());
            Log::critical('Cannot convert this string using the given format.', ['value' => $value, 'format' => $this->config['date-format']]);
            throw new FireflyException(sprintf('Cannot convert "%s" to a valid date using format "%s".', $value, $this->config['date-format']));
        }
        Log::debug('Converted date', ['converted' => $date->toAtomString()]);
        $this->setCertainty(100);
        return $date;
    }
}