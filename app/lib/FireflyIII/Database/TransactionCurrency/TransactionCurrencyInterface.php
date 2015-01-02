<?php

namespace FireflyIII\Database\TransactionCurrency;


/**
 * Interface TransactionTypeInterface
 *
 * @package FireflyIII\Database
 */
interface TransactionCurrencyInterface
{
    /**
     * @param string $code
     *
     * @return \TransactionCurrency|null
     */
    public function findByCode($code);

} 
