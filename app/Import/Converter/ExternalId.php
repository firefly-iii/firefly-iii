<?php
/**
 * ExternalId.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import\Converter;

/**
 * Class ExternalId
 *
 * @package FireflyIII\Import\Converter
 */
class ExternalId extends BasicConverter implements ConverterInterface
{

    /**
     * @param $value
     *
     * @return string
     */
    public function convert($value): string
    {
        // this should replace all control characters
        // but leave utf8 intact:
        $value = preg_replace('/[\x00-\x1F\x80-\x9F]/u', '', $value);
        $this->setCertainty(100);

        return strval(trim($value));

    }
}
