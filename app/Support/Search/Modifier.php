<?php
/**
 * Modifier.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Support\Search;


use FireflyIII\Models\Transaction;
use Log;
use Steam;

class Modifier
{
    /**
     * @param Transaction $transaction
     * @param string      $amount
     * @param int         $expected
     *
     * @return bool
     */
    public static function amountCompare(Transaction $transaction, string $amount, int $expected): bool
    {
        $amount            = Steam::positive($amount);
        $transactionAmount = Steam::positive($transaction->transaction_amount);

        $compare = bccomp($amount, $transactionAmount);
        Log::debug(sprintf('%s vs %s is %d', $amount, $transactionAmount, $compare));

        return $compare === $expected;
    }

    /**
     * @param Transaction $transaction
     * @param string      $amount
     *
     * @return bool
     */
    public static function amountIs(Transaction $transaction, string $amount): bool
    {
        return self::amountCompare($transaction, $amount, 0);
    }

    /**
     * @param Transaction $transaction
     * @param string      $amount
     *
     * @return bool
     */
    public static function amountLess(Transaction $transaction, string $amount): bool
    {
        return self::amountCompare($transaction, $amount, 1);
    }

    /**
     * @param Transaction $transaction
     * @param string      $amount
     *
     * @return bool
     */
    public static function amountMore(Transaction $transaction, string $amount): bool
    {
        return self::amountCompare($transaction, $amount, -1);
    }

    /**
     * @param Transaction $transaction
     * @param string      $destination
     *
     * @return bool
     */
    public static function destination(Transaction $transaction, string $destination): bool
    {
        return self::stringCompare($transaction->opposing_account_name, $destination);
    }

    /**
     * @param Transaction $transaction
     * @param string      $source
     *
     * @return bool
     */
    public static function source(Transaction $transaction, string $source): bool
    {
        return self::stringCompare($transaction->account_name, $source);
    }

    /**
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     */
    public static function stringCompare(string $haystack, string $needle): bool
    {
        $res = !(strpos(strtolower($haystack), strtolower($needle)) === false);
        Log::debug(sprintf('"%s" is in "%s"? %s', $needle, $haystack, var_export($res, true)));

        return $res;

    }
}