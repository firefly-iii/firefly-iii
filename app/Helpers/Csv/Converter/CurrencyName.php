<?php

namespace FireflyIII\Helpers\Csv\Converter;

use FireflyIII\Models\TransactionCurrency;

/**
 * Class CurrencyName
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class CurrencyName extends BasicConverter implements ConverterInterface
{

    /**
     * @return mixed|static
     */
    public function convert()
    {
        if (isset($this->mapped[$this->index][$this->value])) {
            $currency = TransactionCurrency::find($this->mapped[$this->index][$this->value]);
        } else {
            $currency = TransactionCurrency::whereName($this->value)->first();
        }

        return $currency;
    }
}
