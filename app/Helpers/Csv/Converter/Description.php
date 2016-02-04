<?php

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
    public function convert()
    {
        return trim($this->data['description'] . ' ' . $this->value);
    }
}
