<?php
/**
 * Amount.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Converter;

use Log;

/**
 * Class RabobankDebetCredit
 *
 * @package FireflyIII\Import\Converter
 */
class Amount implements ConverterInterface
{

    /**
     * Some people, when confronted with a problem, think "I know, I'll use regular expressions." Now they have two problems.
     * - Jamie Zawinski
     *
     *
     * @param $value
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function convert($value): string
    {
        if(is_null($value)) {
            return '0';
        }
        $value = strval($value);
        Log::debug(sprintf('Start with amount "%s"', $value));
        $len             = strlen($value);
        $decimalPosition = $len - 3;
        $decimal         = null;

        if (($len > 2 && $value{$decimalPosition} === '.') || ($len > 2 && strpos($value, '.') > $decimalPosition)) {
            $decimal = '.';
            Log::debug(sprintf('Decimal character in "%s" seems to be a dot.', $value));
        }
        if ($len > 2 && $value{$decimalPosition} === ',') {
            $decimal = ',';
            Log::debug(sprintf('Decimal character in "%s" seems to be a comma.', $value));
        }

        // if decimal is dot, replace all comma's and spaces with nothing. then parse as float (round to 4 pos)
        if ($decimal === '.') {
            $search   = [',', ' '];
            $oldValue = $value;
            $value    = str_replace($search, '', $value);
            Log::debug(sprintf('Converted amount from "%s" to "%s".', $oldValue, $value));
        }
        if ($decimal === ',') {
            $search   = ['.', ' '];
            $oldValue = $value;
            $value    = str_replace($search, '', $value);
            $value    = str_replace(',', '.', $value);
            Log::debug(sprintf('Converted amount from "%s" to "%s".', $oldValue, $value));
        }
        if (is_null($decimal)) {
            // replace all:
            $search   = ['.', ' ', ','];
            $oldValue = $value;
            $value    = str_replace($search, '', $value);
            Log::debug(sprintf('No decimal character found. Converted amount from "%s" to "%s".', $oldValue, $value));
        }

        return strval(round(floatval($value), 12));

    }
}
