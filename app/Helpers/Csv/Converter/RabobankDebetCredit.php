<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;


/**
 * Class RabobankDebetCredit
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class RabobankDebetCredit extends BasicConverter implements ConverterInterface
{


    /**
     * @return int
     */
    public function convert()
    {
        if ($this->value == 'D') {
            return -1;
        }

        return 1;
    }
}
