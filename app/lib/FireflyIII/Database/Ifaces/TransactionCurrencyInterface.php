<?php

namespace FireflyIII\Database\Ifaces;


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