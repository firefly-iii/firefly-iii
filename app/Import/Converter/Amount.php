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
     */
    public function convert($value): string
    {
        $len             = strlen($value);
        $decimalPosition = $len - 3;
        $decimal         = null;

        if (($len > 2 && $value{$decimalPosition} === '.') || ($len > 2 && strpos($value, '.') > $decimalPosition)) {
            $decimal = '.';
        }
        if ($len > 2 && $value{$decimalPosition} === ',') {
            $decimal = ',';
        }

        // if decimal is dot, replace all comma's and spaces with nothing. then parse as float (round to 4 pos)
        if ($decimal === '.') {
            $search = [',', ' '];
            $value  = str_replace($search, '', $value);
        }
        if ($decimal === ',') {
            $search = ['.', ' '];
            $value  = str_replace($search, '', $value);
            $value  = str_replace(',', '.', $value);
        }
        if (is_null($decimal)) {
            // replace all:
            $search = ['.', ' ', ','];
            $value  = str_replace($search, '', $value);
        }

        return strval(round(floatval($value), 12));

    }
}
