<?php
/**
 * TransactionJournalTrait.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Models;


use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Transaction;
use FireflyIII\Support\CacheProperties;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Class TransactionJournalTrait
 *
 * @property int $id
 * @method Collection transactions()
 * @method bool isWithdrawal()
 *
 * @package FireflyIII\Support\Models
 */
trait TransactionJournalTrait
{
    /**
     * @return string
     * @throws FireflyException
     */
    public function amount(): string
    {
        $cache = new CacheProperties;
        $cache->addProperty($this->id);
        $cache->addProperty('transaction-journal');
        $cache->addProperty('amount');
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        // saves on queries:
        $amount = $this->transactions()->where('amount', '>', 0)->get()->sum('amount');

        if ($this->isWithdrawal()) {
            $amount = $amount * -1;
        }
        $amount = strval($amount);
        $cache->store($amount);

        return $amount;
    }

    /**
     * @return string
     */
    public function amountPositive(): string
    {
        $cache = new CacheProperties;
        $cache->addProperty($this->id);
        $cache->addProperty('transaction-journal');
        $cache->addProperty('amount-positive');
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        // saves on queries:
        $amount = $this->transactions()->where('amount', '>', 0)->get()->sum('amount');

        $amount = strval($amount);
        $cache->store($amount);

        return $amount;
    }

    /**
     * @return int
     */
    public function budgetId(): int
    {
        $budget = $this->budgets()->first();
        if (!is_null($budget)) {
            return $budget->id;
        }

        return 0;
    }

    /**
     * @return string
     */
    public function categoryAsString(): string
    {
        $category = $this->categories()->first();
        if (!is_null($category)) {
            return $category->name;
        }

        return '';
    }

    /**
     * @param string $dateField
     *
     * @return string
     */
    public function dateAsString(string $dateField = ''): string
    {
        if ($dateField === '') {
            return $this->date->format('Y-m-d');
        }
        if (!is_null($this->$dateField) && $this->$dateField instanceof Carbon) {
            // make field NULL
            $carbon           = clone $this->$dateField;
            $this->$dateField = null;
            $this->save();

            // create meta entry
            $this->setMeta($dateField, $carbon);

            // return that one instead.
            return $carbon->format('Y-m-d');
        }
        $metaField = $this->getMeta($dateField);
        if (!is_null($metaField)) {
            $carbon = new Carbon($metaField);

            return $carbon->format('Y-m-d');
        }

        return '';


    }

    /**
     * @return Collection
     */
    public function destinationAccountList(): Collection
    {
        $cache = new CacheProperties;
        $cache->addProperty($this->id);
        $cache->addProperty('transaction-journal');
        $cache->addProperty('destination-account-list');
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $transactions = $this->transactions()->where('amount', '>', 0)->orderBy('transactions.account_id')->with('account')->get();
        $list         = new Collection;
        /** @var Transaction $t */
        foreach ($transactions as $t) {
            $list->push($t->account);
        }
        $list = $list->unique('id');
        $cache->store($list);

        return $list;
    }

    /**
     * @return Collection
     */
    public function destinationTransactionList(): Collection
    {
        $cache = new CacheProperties;
        $cache->addProperty($this->id);
        $cache->addProperty('transaction-journal');
        $cache->addProperty('destination-transaction-list');
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $list = $this->transactions()->where('amount', '>', 0)->with('account')->get();
        $cache->store($list);

        return $list;
    }

    /**
     * @param Builder $query
     * @param string  $table
     *
     * @return bool
     */
    public function isJoined(Builder $query, string $table): bool
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
     * @return int
     */
    public function piggyBankId(): int
    {
        if ($this->piggyBankEvents()->count() > 0) {
            return $this->piggyBankEvents()->orderBy('date', 'DESC')->first()->piggy_bank_id;
        }

        return 0;
    }

    /**
     * @return Transaction
     */
    public function positiveTransaction(): Transaction
    {
        return $this->transactions()->where('amount', '>', 0)->first();
    }

    /**
     * @return Collection
     */
    public function sourceAccountList(): Collection
    {
        $cache = new CacheProperties;
        $cache->addProperty($this->id);
        $cache->addProperty('transaction-journal');
        $cache->addProperty('source-account-list');
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $transactions = $this->transactions()->where('amount', '<', 0)->orderBy('transactions.account_id')->with('account')->get();
        $list         = new Collection;
        /** @var Transaction $t */
        foreach ($transactions as $t) {
            $list->push($t->account);
        }
        $list = $list->unique('id');
        $cache->store($list);

        return $list;
    }

    /**
     * @return Collection
     */
    public function sourceTransactionList(): Collection
    {
        $cache = new CacheProperties;
        $cache->addProperty($this->id);
        $cache->addProperty('transaction-journal');
        $cache->addProperty('source-transaction-list');
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $list = $this->transactions()->where('amount', '<', 0)->with('account')->get();
        $cache->store($list);

        return $list;
    }

    /**
     * @return string
     */
    public function transactionTypeStr(): string
    {
        $cache = new CacheProperties;
        $cache->addProperty($this->id);
        $cache->addProperty('transaction-journal');
        $cache->addProperty('type-string');
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        $typeStr = $this->transaction_type_type ?? $this->transactionType->type;
        $cache->store($typeStr);

        return $typeStr;
    }
}
