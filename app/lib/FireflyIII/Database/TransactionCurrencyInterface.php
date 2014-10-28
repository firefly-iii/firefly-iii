<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 24/10/14
 * Time: 10:28
 */

namespace FireflyIII\Database;


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