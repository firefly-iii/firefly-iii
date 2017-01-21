<?php
/**
 * Request.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class Request
 *
 * @package FireflyIII\Http\Requests
 */
class Request extends FormRequest
{
    /**
     * @param string $field
     *
     * @return bool
     */
    protected function boolean(string $field): bool
    {
        return intval($this->input($field)) === 1;
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

    /**
     * @param string $field
     *
     * @return string
     */
    protected function string(string $field): string
    {
        $string = $this->get($field) ?? '';

        $search  = [
            "\u{00A0}", // non-breaking space
            "\u{1680}", // OGHAM SPACE MARK
            "\u{180E}", // MONGOLIAN VOWEL SEPARATOR
            "\u{2000}", // EN QUAD
            "\u{2001}", // EM QUAD
            "\u{2002}", // EN SPACE
            "\u{2003}", // EM SPACE
            "\u{2004}", // THREE-PER-EM SPACE
            "\u{2005}", // FOUR-PER-EM SPACE
            "\u{2006}", // SIX-PER-EM SPACE
            "\u{2007}", // FIGURE SPACE
            "\u{2008}", // PUNCTUATION SPACE
            "\u{2009}", // THIN SPACE
            "\u{200A}", // HAIR SPACE
            "\u{200B}", // ZERO WIDTH SPACE
            "\u{202F}", // NARROW NO-BREAK SPACE
            "\u{205F}", // MEDIUM MATHEMATICAL SPACE
            "\u{3000}", // IDEOGRAPHIC SPACE
            "\u{FEFF}", // ZERO WIDTH NO -BREAK SPACE
        ];
        $replace = "\x20"; // plain old normal space
        $string  = str_replace($search, $replace, $string);

        return trim($string);
    }
}
