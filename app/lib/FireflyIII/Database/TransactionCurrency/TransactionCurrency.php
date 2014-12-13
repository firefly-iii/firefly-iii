<?php

namespace FireflyIII\Database\TransactionCurrency;


/**
 * Class TransactionType
 *
 * @package FireflyIII\Database
 */
class TransactionCurrency implements TransactionCurrencyInterface
{

    /**
     * @param string $code
     *
     * @return \TransactionCurrency|null
     */
    public function findByCode($code)
    {
        return \TransactionCurrency::whereCode($code)->first();
    }
}