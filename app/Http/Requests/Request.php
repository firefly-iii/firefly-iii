<?php
/**
 * Request.php
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class Request.
 */
class Request extends FormRequest
{
    /**
     * @param string $field
     *
     * @return bool
     */
    public function boolean(string $field): bool
    {
        return 1 === intval($this->input($field));
    }

    /**
     * @param string $field
     *
     * @return string
     */
    public function string(string $field): string
    {
        $string  = $this->get($field) ?? '';
        $search  = [
            "\u{0001}", // start of heading
            "\u{0002}", // start of text
            "\u{0003}", // end of text
            "\u{0004}", // end of transmission
            "\u{0005}", // enquiry
            "\u{0006}", // ACK
            "\u{0007}", // BEL
            "\u{0008}", // backspace
            "\u{000E}", // shift out
            "\u{000F}", // shift in
            "\u{0010}", // data link escape
            "\u{0011}", // DC1
            "\u{0012}", // DC2
            "\u{0013}", // DC3
            "\u{0014}", // DC4
            "\u{0015}", // NAK
            "\u{0016}", // SYN
            "\u{0017}", // ETB
            "\u{0018}", // CAN
            "\u{0019}", // EM
            "\u{001A}", // SUB
            "\u{001B}", // escape
            "\u{001C}", // file separator
            "\u{001D}", // group separator
            "\u{001E}", // record separator
            "\u{001F}", // unit separator
            "\u{007F}", // DEL
            "\u{00A0}", // non-breaking space
            "\u{1680}", // ogham space mark
            "\u{180E}", // mongolian vowel separator
            "\u{2000}", // en quad
            "\u{2001}", // em quad
            "\u{2002}", // en space
            "\u{2003}", // em space
            "\u{2004}", // three-per-em space
            "\u{2005}", // four-per-em space
            "\u{2006}", // six-per-em space
            "\u{2007}", // figure space
            "\u{2008}", // punctuation space
            "\u{2009}", // thin space
            "\u{200A}", // hair space
            "\u{200B}", // zero width space
            "\u{202F}", // narrow no-break space
            "\u{3000}", // ideographic space
            "\u{FEFF}", // zero width no -break space
        ];
        $replace = "\x20"; // plain old normal space
        $string  = str_replace($search, $replace, $string);

        return trim($string);
    }

    /**
     * @param string $field
     *
     * @return Carbon|null
     */
    protected function date(string $field)
    {
        return $this->get($field) ? new Carbon($this->get($field)) : null;
    }

    /**
     * @param string $field
     *
     * @return float
     */
    protected function float(string $field): float
    {
        return round($this->input($field), 12);
    }

    /**
     * @param string $field
     * @param string $type
     *
     * @return array
     */
    protected function getArray(string $field, string $type): array
    {
        $original = $this->get($field);
        $return   = [];
        foreach ($original as $index => $value) {
            $return[$index] = $this->$type($value);
        }

        return $return;
    }

    /**
     * @param string $field
     *
     * @return int
     */
    protected function integer(string $field): int
    {
        return intval($this->get($field));
    }
}
