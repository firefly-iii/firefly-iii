<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

/**
 * Class Amount
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class Ignore extends BasicConverter implements ConverterInterface
{

    /**
     * @return null
     */
    public function convert()
    {
        return null;
    }
}
