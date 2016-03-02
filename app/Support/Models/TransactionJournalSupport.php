<?php
/**
 * TransactionJournalSupport.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Support\Models;


use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Support\CacheProperties;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TransactionJournalSupport
 *
 * @package FireflyIII\Support\Models
 */
class TransactionJournalSupport extends Model
{
    /**
     * @param TransactionJournal $journal
     *
     * @return string
     */
    public static function amount(TransactionJournal $journal): string
    {
        $cache = new CacheProperties;
        $cache->addProperty($journal->id);
        $cache->addProperty('transaction-journal');
        $cache->addProperty('amount');
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        bcscale(2);
        $transaction = $journal->transactions->sortByDesc('amount')->first();
        $amount      = $transaction->amount;
        if ($journal->isWithdrawal()) {
            $amount = bcmul($amount, '-1');
        }
        $cache->store($amount);

        return $amount;


    }

    /**
     * @param TransactionJournal $journal
     *
     * @return string
     */
    public static function amountPositive(TransactionJournal $journal): string
    {
        $cache = new CacheProperties;
        $cache->addProperty($journal->id);
        $cache->addProperty('transaction-journal');
        $cache->addProperty('amount-positive');
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        $amount = '0';
        /** @var Transaction $t */
        foreach ($journal->transactions as $t) {
            if ($t->amount > 0) {
                $amount = $t->amount;
            }
        }
        $cache->store($amount);

        return $amount;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return Account
     */
    public static function destinationAccount(TransactionJournal $journal): Account
    {
        $cache = new CacheProperties;
        $cache->addProperty($journal->id);
        $cache->addProperty('transaction-journal');
        $cache->addProperty('destination-account');
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $transaction = $journal->transactions()->where('amount', '>', 0)->first();
        if (!is_null($transaction)) {
            $account = $transaction->account;
            $cache->store($account);
        } else {
            $account = new Account;
        }

        return $account;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return string
     */
    public static function destinationAccountTypeStr(TransactionJournal $journal): string
    {
        $cache = new CacheProperties;
        $cache->addProperty($journal->id);
        $cache->addProperty('transaction-journal');
        $cache->addProperty('destination-account-type-str');
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        $account = self::destinationAccount($journal);
        $type    = $account->accountType ? $account->accountType->type : '(unknown)';
        $cache->store($type);

        return $type;
    }

    /**
     * @param Builder $query
     * @param string  $table
     *
     * @return bool
     */
    public static function isJoined(Builder $query, string $table):bool
    {
        $joins = $query->getQuery()->joins;
        if (is_null($joins)) {
            return false;
        }
        foreach ($joins as $join) {
            if ($join->table === $table) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return Account
     */
    public static function sourceAccount(TransactionJournal $journal): Account
    {
        $cache = new CacheProperties;
        $cache->addProperty($journal->id);
        $cache->addProperty('transaction-journal');
        $cache->addProperty('source-account');
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $transaction = $journal->transactions()->where('amount', '<', 0)->first();
        if (!is_null($transaction)) {
            $account = $transaction->account;
            $cache->store($account);
        } else {
            $account = new Account;
        }

        return $account;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return string
     */
    public static function sourceAccountTypeStr(TransactionJournal $journal): string
    {
        $cache = new CacheProperties;
        $cache->addProperty($journal->id);
        $cache->addProperty('transaction-journal');
        $cache->addProperty('source-account-type-str');
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        $account = self::sourceAccount($journal);
        $type    = $account->accountType ? $account->accountType->type : '(unknown)';
        $cache->store($type);

        return $type;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return string
     */
    public static function transactionTypeStr(TransactionJournal $journal): string
    {
        $cache = new CacheProperties;
        $cache->addProperty($journal->id);
        $cache->addProperty('transaction-journal');
        $cache->addProperty('type-string');
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        $typeStr = $journal->transaction_type_type ?? $journal->transactionType->type;
        $cache->store($typeStr);

        return $typeStr;
    }


}