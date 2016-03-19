<?php
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
    public function convert()
    {
        $description = $this->data['description'] ?? '';

        return trim($description . ' ' . $this->value);
    }
}
