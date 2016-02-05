<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

/**
 * Class Amount
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class Amount extends BasicConverter implements ConverterInterface
{

    /**
     * @return string|int
     */
    public function convert()
    {
        if (is_numeric($this->value)) {
            return $this->value;
        }

        return '0';
    }
}
