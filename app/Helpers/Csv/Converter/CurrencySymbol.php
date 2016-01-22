<?php

namespace FireflyIII\Helpers\Csv\Converter;

use FireflyIII\Models\TransactionCurrency;

/**
 * Class CurrencySymbol
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class CurrencySymbol extends BasicConverter implements ConverterInterface
{

    /**
     * @return TransactionCurrency
     */
    public function convert()
    {
        if (isset($this->mapped[$this->index][$this->value])) {
            $currency = TransactionCurrency::find($this->mapped[$this->index][$this->value]);
        } else {
            $currency = TransactionCurrency::whereSymbol($this->value)->first();
        }

        return $currency;
    }
}
