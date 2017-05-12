<?php
/**
 * TagsSpace.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Converter;

use Illuminate\Support\Collection;
use Log;

/**
 * Class TagsSpace
 *
 * @package FireflyIII\Import\Converter
 */
class TagsSpace extends BasicConverter implements ConverterInterface
{

    /**
     * @param $value
     *
     * @return Collection
     */
    public function convert($value)
    {
        $value = trim($value);
        Log::debug('Going to convert using TagsSpace', ['value' => $value]);

        if (strlen($value) === 0) {
            $this->setCertainty(0);

            return new Collection;
        }
        $parts = array_unique(explode(' ', $value));
        $set   = TagSplit::createSetFromSplits($this->user, $this->mapping, $parts);
        $this->setCertainty(100);

        return $set;

    }
}
