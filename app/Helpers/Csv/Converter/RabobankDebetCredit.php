<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 05/07/15
 * Time: 06:12
 */

namespace FireflyIII\Helpers\Csv\Converter;


class RabobankDebetCredit extends BasicConverter implements ConverterInterface
{


    /**
     * @return mixed
     */
    public function convert()
    {
        if ($this->value == 'D') {
            return -1;
        }

        return 1;
    }
}