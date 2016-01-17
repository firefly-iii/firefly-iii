<?php

namespace FireflyIII\Helpers\Csv\Converter;

use FireflyIII\Models\Account;

/**
 * Class AmountComma
 * 
 * Parses the input as the amount with a comma as decimal separator
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class AmountComma extends BasicConverter implements ConverterInterface
{

    /**
     * @return Account|null
     */
    public function convert()
    {
        $value = str_replace(",", ".", $this->value );
        
        if (is_numeric($value)) {
            return floatval($value);
        }

        return 0;
    }
}
