<?php
/**
 * Modifier.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
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

    /**
     * @param array       $modifier
     * @param Transaction $transaction
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @return bool
     *
     * @throws FireflyException
     */
    public static function apply(array $modifier, Transaction $transaction): bool
    {
        $res = true;
        switch ($modifier['type']) {
            case 'source':
                $name = Steam::tryDecrypt($transaction->account_name);
                $res  = self::stringCompare($name, $modifier['value']);
                Log::debug(sprintf('Source is %s? %s', $modifier['value'], var_export($res, true)));
                break;
            case 'destination':
                $name = Steam::tryDecrypt($transaction->opposing_account_name);
                $res  = self::stringCompare($name, $modifier['value']);
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
                $name = Steam::tryDecrypt($transaction->bill_name);
                $res  = self::stringCompare($name, $modifier['value']);
                Log::debug(sprintf('Bill is %s? %s', $modifier['value'], var_export($res, true)));
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
        $res = !(false === strpos(strtolower($haystack), strtolower($needle)));
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
        if (null !== $transaction->transaction_journal_budget_name) {
            $journalBudget = Steam::decrypt(intval($transaction->transaction_journal_budget_encrypted), $transaction->transaction_journal_budget_name);
        }
        $transactionBudget = '';
        if (null !== $transaction->transaction_budget_name) {
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
        if (null !== $transaction->transaction_journal_category_name) {
            $journalCategory = Steam::decrypt(intval($transaction->transaction_journal_category_encrypted), $transaction->transaction_journal_category_name);
        }
        $transactionCategory = '';
        if (null !== $transaction->transaction_category_name) {
            $journalCategory = Steam::decrypt(intval($transaction->transaction_category_encrypted), $transaction->transaction_category_name);
        }

        return self::stringCompare($journalCategory, $search) || self::stringCompare($transactionCategory, $search);
    }
}
