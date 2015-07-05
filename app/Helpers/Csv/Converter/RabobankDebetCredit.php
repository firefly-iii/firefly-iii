<?php

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