<?php
/**
 * Description.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

/**
 * Class Description
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class Description extends BasicConverter implements ConverterInterface
{


    /**
     * @return string
     */
    public function convert(): string
    {
        $description = $this->data['description'] ?? '';

        return trim($description . ' ' . $this->value);
    }
}
