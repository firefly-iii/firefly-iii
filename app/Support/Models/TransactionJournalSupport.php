<?php
declare(strict_types = 1);
/**
 * TransactionJournalSupport.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Support\Models;


use Carbon\Carbon;
use DB;
use FireflyIII\Exceptions\FireflyException;
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
 * @mixin \Eloquent
 */
class TransactionJournalSupport extends Model
{
    /**
     * @param TransactionJournal $journal
     *
     * @return string
     * @throws FireflyException
     */
    public static function amount(TransactionJournal $journal): string
    {
        $cache = new CacheProperties;
        $cache->addProperty($journal->id);
        $cache->addProperty('transaction-journal');
        $cache->addProperty('amount');
        if ($cache->has()) {
            return $cache->get();
        }

        if ($journal->isWithdrawal() && !is_null($journal->source_amount)) {
            $cache->store($journal->source_amount);

            return $journal->source_amount;
        }
        if ($journal->isDeposit() && !is_null($journal->destination_amount)) {
            $cache->store($journal->destination_amount);

            return $journal->destination_amount;
        }

        $amount = $journal->transactions()->where('amount', '>', 0)->get()->sum('amount');
        if ($journal->isDeposit()) {
            $amount = $amount * -1;
        }
        $amount = strval($amount);
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
            return $cache->get();
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
     * @return int
     */
    public static function budgetId(TransactionJournal $journal): int
    {
        $budget = $journal->budgets()->first();
        if (!is_null($budget)) {
            return $budget->id;
        }

        return 0;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return string
     */
    public static function categoryAsString(TransactionJournal $journal): string
    {
        $category = $journal->categories()->first();
        if (!is_null($category)) {
            return $category->name;
        }

        return '';
    }

    /**
     * @param TransactionJournal $journal
     * @param string             $dateField
     *
     * @return string
     */
    public static function dateAsString(TransactionJournal $journal, string $dateField = ''): string
    {
        if ($dateField === '') {
            return $journal->date->format('Y-m-d');
        }
        if (!is_null($journal->$dateField) && $journal->$dateField instanceof Carbon) {
            return $journal->$dateField->format('Y-m-d');
        }

        return '';


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
            return $cache->get();
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
            return $cache->get();
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
     * @return int
     */
    public static function piggyBankId(TransactionJournal $journal): int
    {
        if ($journal->piggyBankEvents()->count() > 0) {
            return $journal->piggyBankEvents()->orderBy('date', 'DESC')->first()->piggy_bank_id;
        }

        return 0;
    }

    /**
     * @return array
     */
    public static function queryFields(): array
    {
        return [
            'transaction_journals.*',
            'transaction_types.type AS transaction_type_type', // the other field is called "transaction_type_id" so this is pretty consistent.
            'transaction_currencies.code AS transaction_currency_code',
            // all for destination:
            //'destination.amount AS destination_amount', // is always positive
            DB::raw('SUM(`destination`.`amount`) as `destination_amount`'),
            'destination_account.id AS destination_account_id',
            'destination_account.name AS destination_account_name',
            'destination_acct_type.type AS destination_account_type',
            // all for source:
            //'source.amount AS source_amount', // is always negative
            DB::raw('SUM(`source`.`amount`) as `source_amount`'),
            'source_account.id AS source_account_id',
            'source_account.name AS source_account_name',
            'source_acct_type.type AS source_account_type',
            DB::raw('COUNT(`destination`.`id`) + COUNT(`source`.`id`) as `count_transactions`'),
        ];
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
            return $cache->get();
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
            return $cache->get();
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
            return $cache->get();
        }

        $typeStr = $journal->transaction_type_type ?? $journal->transactionType->type;
        $cache->store($typeStr);

        return $typeStr;
    }


}
