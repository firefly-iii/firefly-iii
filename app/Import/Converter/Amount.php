<?php
/**
 * Amount.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Import\Converter;

use Log;

/**
 * Class Amount.
 */
class Amount implements ConverterInterface
{
    /**
     * Some people, when confronted with a problem, think "I know, I'll use regular expressions." Now they have two problems.
     * - Jamie Zawinski.
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
        if (null === $value) {
            return '0';
        }
        Log::debug(sprintf('Start with amount "%s"', $value));
        $original        = $value;
        $value           = strval($value);
        $value           = $this->stripAmount($value);
        $len             = strlen($value);
        $decimalPosition = $len - 3;
        $altPosition     = $len - 2;
        $decimal         = null;

        if (($len > 2 && '.' === $value[$decimalPosition]) || ($len > 2 && strpos($value, '.') > $decimalPosition)) {
            $decimal = '.';
            Log::debug(sprintf('Decimal character in "%s" seems to be a dot.', $value));
        }
        if ($len > 2 && ',' === $value[$decimalPosition]) {
            $decimal = ',';
            Log::debug(sprintf('Decimal character in "%s" seems to be a comma.', $value));
        }
        // decimal character is null? find out if "0.1" or ".1" or "0,1" or ",1"
        if ($len > 1 && ('.' === $value[$altPosition] || ',' === $value[$altPosition])) {
            $decimal = $value[$altPosition];
            Log::debug(sprintf('Alternate search resulted in "%s" for decimal sign.', $decimal));
        }

        // decimal character still null? Search from the left for '.',',' or ' '.
        if (is_null($decimal)) {
            Log::debug('Decimal is still NULL, probably number with >2 decimals. Search for a dot.');
            $res = strrpos($value, '.');
            if (!(false === $res)) {
                // blandly assume this is the one.
                Log::debug(sprintf('Searched from the left for "." in amount "%s", assume this is the decimal sign.', $value));
                $decimal = '.';
            }
            unset($res);
        }

        // if decimal is dot, replace all comma's and spaces with nothing. then parse as float (round to 4 pos)
        if ('.' === $decimal) {
            $search = [',', ' '];
            $value  = str_replace($search, '', $value);
            Log::debug(sprintf('Converted amount from "%s" to "%s".', $original, $value));
        }
        if (',' === $decimal) {
            $search = ['.', ' '];
            $value  = str_replace($search, '', $value);
            $value  = str_replace(',', '.', $value);
            Log::debug(sprintf('Converted amount from "%s" to "%s".', $original, $value));
        }
        if (null === $decimal) {
            // replace all:
            $search = ['.', ' ', ','];
            $value  = str_replace($search, '', $value);
            Log::debug(sprintf('No decimal character found. Converted amount from "%s" to "%s".', $original, $value));
        }

        $number = strval(number_format(round(floatval($value), 12), 12, '.', ''));

        return $number;
    }

    /**
     * @param string $value
     *
     * @return string
     */
    private function stripAmount(string $value): string
    {
        $str = preg_replace('/[^\-\(\)\.\,0-9 ]/', '', $value);
        $len = strlen($str);
        if ($str{0} === '(' && $str{$len - 1} === ')') {
            $str = '-' . substr($str, 1, ($len - 2));
        }

        Log::debug(sprintf('Stripped "%s" away to "%s"', $value, $str));

        return $str;

    }
}
