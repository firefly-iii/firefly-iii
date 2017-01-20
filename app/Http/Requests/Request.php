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
     * @return string
     */
    protected function getFieldOrEmptyString(string $field): string
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
