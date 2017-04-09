<?php
/**
 * Modifier.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Search;


use Carbon\Carbon;
use Exception;
use FireflyIII\Exceptions\FireflyException;
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

    public static function apply(array $modifier, Transaction $transaction): bool
    {
        switch ($modifier['type']) {
            default:
                throw new FireflyException(sprintf('Search modifier "%s" is not (yet) supported. Sorry!', $modifier['type']));
            case 'amount':
            case 'amount_is':
                $res = self::amountCompare($transaction, $modifier['value'], 0);
                Log::debug(sprintf('Amount is %s? %s', $modifier['value'], var_export($res, true)));
                break;
            case 'amount_min':
            case 'amount_less':
                $res = self::amountCompare($transaction, $modifier['value'], 1);
                Log::debug(sprintf('Amount less than %s? %s', $modifier['value'], var_export($res, true)));
                break;
            case 'amount_max':
            case 'amount_more':
                $res = self::amountCompare($transaction, $modifier['value'], -1);
                Log::debug(sprintf('Amount more than %s? %s', $modifier['value'], var_export($res, true)));
                break;
            case 'source':
                $res = self::stringCompare($transaction->account_name, $modifier['value']);
                Log::debug(sprintf('Source is %s? %s', $modifier['value'], var_export($res, true)));
                break;
            case 'destination':
                $res = self::stringCompare($transaction->opposing_account_name, $modifier['value']);
                Log::debug(sprintf('Destination is %s? %s', $modifier['value'], var_export($res, true)));
                break;
            case 'category':
                $res = self::category($transaction, $modifier['value']);
                Log::debug(sprintf('Category is %s? %s', $modifier['value'], var_export($res, true)));
                break;
            case 'budget':
                $res = self::budget($transaction, $modifier['value']);
                Log::debug(sprintf('Budget is %s? %s', $modifier['value'], var_export($res, true)));
                break;
            case 'bill':
                $res = self::stringCompare(strval($transaction->bill_name), $modifier['value']);
                Log::debug(sprintf('Bill is %s? %s', $modifier['value'], var_export($res, true)));
                break;
            case 'type':
                $res = self::stringCompare($transaction->transaction_type_type, $modifier['value']);
                Log::debug(sprintf('Transaction type is %s? %s', $modifier['value'], var_export($res, true)));
                break;
            case 'date':
            case 'on':
                $res = self::sameDate($transaction->date, $modifier['value']);
                Log::debug(sprintf('Date is %s? %s', $modifier['value'], var_export($res, true)));
                break;
            case 'date_before':
            case 'before':
                $res = self::dateBefore($transaction->date, $modifier['value']);
                Log::debug(sprintf('Date is %s? %s', $modifier['value'], var_export($res, true)));
                break;
            case 'date_after':
            case 'after':
                $res = self::dateAfter($transaction->date, $modifier['value']);
                Log::debug(sprintf('Date is %s? %s', $modifier['value'], var_export($res, true)));
                break;
        }

        return $res;
    }

    /**
     * @param Carbon $date
     * @param string $compare
     *
     * @return bool
     */
    public static function dateAfter(Carbon $date, string $compare): bool
    {
        try {
            $compareDate = new Carbon($compare);
        } catch (Exception $e) {
            return false;
        }

        return $date->greaterThanOrEqualTo($compareDate);
    }

    /**
     * @param Carbon $date
     * @param string $compare
     *
     * @return bool
     */
    public static function dateBefore(Carbon $date, string $compare): bool
    {
        try {
            $compareDate = new Carbon($compare);
        } catch (Exception $e) {
            return false;
        }

        return $date->lessThanOrEqualTo($compareDate);
    }

    /**
     * @param Carbon $date
     * @param string $compare
     *
     * @return bool
     */
    public static function sameDate(Carbon $date, string $compare): bool
    {
        try {
            $compareDate = new Carbon($compare);
        } catch (Exception $e) {
            return false;
        }

        return $compareDate->isSameDay($date);
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

    /**
     * @param Transaction $transaction
     * @param string      $search
     *
     * @return bool
     */
    private static function budget(Transaction $transaction, string $search): bool
    {
        $journalBudget = '';
        if (!is_null($transaction->transaction_journal_budget_name)) {
            $journalBudget = Steam::decrypt(intval($transaction->transaction_journal_budget_encrypted), $transaction->transaction_journal_budget_name);
        }
        $transactionBudget = '';
        if (!is_null($transaction->transaction_budget_name)) {
            $journalBudget = Steam::decrypt(intval($transaction->transaction_budget_encrypted), $transaction->transaction_budget_name);
        }

        return self::stringCompare($journalBudget, $search) || self::stringCompare($transactionBudget, $search);
    }

    /**
     * @param Transaction $transaction
     * @param string      $search
     *
     * @return bool
     */
    private static function category(Transaction $transaction, string $search): bool
    {
        $journalCategory = '';
        if (!is_null($transaction->transaction_journal_category_name)) {
            $journalCategory = Steam::decrypt(intval($transaction->transaction_journal_category_encrypted), $transaction->transaction_journal_category_name);
        }
        $transactionCategory = '';
        if (!is_null($transaction->transaction_category_name)) {
            $journalCategory = Steam::decrypt(intval($transaction->transaction_category_encrypted), $transaction->transaction_category_name);
        }

        return self::stringCompare($journalCategory, $search) || self::stringCompare($transactionCategory, $search);
    }
}